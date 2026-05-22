<?php

namespace App\Services;

use App\Models\ArchitectBonusTask;
use App\Models\BonusUnitTier;
use App\Models\ProjectSchedule;
use App\Models\ProjectScheduleActivity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Links the architect-bonus system to project-schedules.
 *
 * Responsibilities:
 *   - On schedule approval: auto-create a pending bonus task for the assigned
 *     architect with scheduled_completion_date derived from the architect's
 *     activities.
 *   - On activity completion: keep the bonus task's status and derived metrics
 *     (actual_completion_date, client_revisions) in sync with what the schedule
 *     already records, until the bonus is scored by an admin.
 *
 * Both entry points are best-effort: failures are logged but never re-thrown, so
 * a bonus sync glitch can't break a schedule submission or activity completion.
 */
class BonusScheduleSyncService
{
    /**
     * Activities whose role matches this Spatie role are treated as "architect-owned"
     * for bonus tracking. Resolved once per request and cached.
     */
    protected ?int $architectRoleId = null;

    /**
     * Create a pending bonus task for the architect assigned to a freshly-approved schedule.
     * No-ops (and returns null) if: no architect assigned, no architect activities,
     * or a bonus task already exists for this schedule.
     */
    public function createFromSchedule(ProjectSchedule $schedule): ?ArchitectBonusTask
    {
        try {
            if (!$schedule->assigned_architect_id) {
                return null;
            }
            if ($schedule->bonusTask()->exists()) {
                return null;
            }

            $architectActivities = $this->architectActivities($schedule);
            if ($architectActivities->isEmpty()) {
                Log::info("BonusScheduleSync: schedule {$schedule->id} has no architect-role activities — skipping bonus creation");
                return null;
            }

            $scheduledCompletion = $architectActivities->max('end_date');
            $budget = (float) ($schedule->lead->estimated_value ?? 0);
            $maxUnits = BonusUnitTier::getMaxUnits($budget);

            $task = ArchitectBonusTask::create([
                'task_number'               => ArchitectBonusTask::generateTaskNumber(),
                'project_name'              => $this->resolveProjectName($schedule),
                'architect_id'              => $schedule->assigned_architect_id,
                'project_budget'            => $budget,
                'lead_id'                   => $schedule->lead_id,
                'project_schedule_id'       => $schedule->id,
                'auto_synced'               => true,
                'last_synced_at'            => now(),
                'start_date'                => $schedule->start_date,
                'scheduled_completion_date' => $scheduledCompletion,
                'max_units'                 => $maxUnits,
                'status'                    => 'pending',
                'created_by'                => $schedule->confirmed_by ?? $schedule->created_by,
                'notes'                     => 'Auto-created from project schedule on approval.',
            ]);

            Log::info("BonusScheduleSync: created bonus task {$task->task_number} for schedule {$schedule->id}");
            return $task;
        } catch (\Throwable $e) {
            Log::error("BonusScheduleSync::createFromSchedule failed for schedule {$schedule->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Sync the bonus task in response to an architect activity completing.
     * Transitions pending → in_progress on first architect activity, and fills
     * actual_completion_date + client_revisions when the last architect activity
     * completes.
     */
    public function syncFromActivity(ProjectScheduleActivity $activity): void
    {
        try {
            $schedule = $activity->schedule;
            if (!$schedule) {
                return;
            }
            $task = $schedule->bonusTask;
            if (!$task || !$task->auto_synced) {
                return;
            }
            if (in_array($task->status, ['scored', 'paid', 'no_bonus'], true)) {
                // Admin already finalised — stop auto-sync.
                $task->update(['auto_synced' => false]);
                return;
            }
            if ($activity->role_id !== $this->architectRoleId()) {
                // Non-architect activity (e.g. client review) — only refresh last_synced_at.
                $task->update(['last_synced_at' => now()]);
                return;
            }

            $updates = ['last_synced_at' => now()];

            // First architect activity to complete → flip task to in_progress.
            if ($task->status === 'pending') {
                $updates['status'] = 'in_progress';
                $updates['accepted_at'] = $task->accepted_at ?? now();
            }

            // If this was the last outstanding architect activity, close the actual_completion_date
            // and count client revisions so admin only needs to enter design_quality_score.
            if ($this->isLastArchitectActivityComplete($schedule)) {
                $updates['actual_completion_date'] = $activity->completed_at ?? now();
                $updates['client_revisions'] = $this->countClientRevisions($schedule);
            }

            $task->update($updates);

            Log::info("BonusScheduleSync: synced bonus task {$task->task_number} from activity {$activity->activity_code}");
        } catch (\Throwable $e) {
            Log::error("BonusScheduleSync::syncFromActivity failed for activity {$activity->id}: " . $e->getMessage());
        }
    }

    /**
     * Activities on this schedule assigned to the Architect Spatie role.
     */
    protected function architectActivities(ProjectSchedule $schedule): Collection
    {
        $roleId = $this->architectRoleId();
        if (!$roleId) {
            return new Collection();
        }
        return $schedule->activities->where('role_id', $roleId)->values();
    }

    /**
     * True when every architect-role activity on the schedule has status=completed.
     * Used to detect "the architect's work is fully done" without hardcoding B7.
     */
    protected function isLastArchitectActivityComplete(ProjectSchedule $schedule): bool
    {
        $architectActivities = $this->architectActivities($schedule);
        if ($architectActivities->isEmpty()) {
            return false;
        }
        return $architectActivities->every(fn ($a) => $a->status === 'completed');
    }

    /**
     * Count of completed "Client Review" activities on the schedule.
     * Match by name (case-insensitive substring) so we don't depend on activity_code
     * conventions like A2/A4/A6. Clamped to >=1 because the score form validation
     * requires min:1 (one round of review is the baseline) and a literal 0 would
     * bite admins later if they submit the pre-filled value as-is.
     */
    protected function countClientRevisions(ProjectSchedule $schedule): int
    {
        $count = $schedule->activities
            ->filter(fn ($a) => $a->status === 'completed'
                && stripos($a->name ?? '', 'client review') !== false)
            ->count();
        return max(1, $count);
    }

    /**
     * Look up the Architect role id once. Queries the `roles` table directly to
     * avoid Spatie's guard-scoped query — `project_schedule_activities.role_id`
     * stores raw ids without guard discrimination, so we must match the same way.
     * Returns null if the role doesn't exist (sync silently no-ops).
     */
    protected function architectRoleId(): ?int
    {
        if ($this->architectRoleId !== null) {
            return $this->architectRoleId;
        }
        $id = DB::table('roles')->where('name', 'Architect')->value('id');
        return $this->architectRoleId = $id ? (int) $id : null;
    }

    /**
     * Best-effort project name for the bonus task. Falls back gracefully so we
     * never crash on missing relations.
     */
    protected function resolveProjectName(ProjectSchedule $schedule): string
    {
        if ($schedule->lead && !empty($schedule->lead->name)) {
            return $schedule->lead->name;
        }
        if ($schedule->client) {
            $client = $schedule->client;
            $name = trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? ''));
            if ($name !== '') {
                return $name . ' — Schedule #' . $schedule->id;
            }
        }
        return 'Project Schedule #' . $schedule->id;
    }
}

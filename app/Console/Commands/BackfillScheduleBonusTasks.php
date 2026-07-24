<?php

namespace App\Console\Commands;

use App\Models\ProjectSchedule;
use App\Services\BonusScheduleSyncService;
use Illuminate\Console\Command;

/**
 * Backfills architect bonus tasks for project schedules that were approved
 * before the auto-create-on-approval feature shipped (2026-05-22).
 *
 * A bonus task is normally created at exactly one moment: when MD/CEO approve a
 * schedule, ProjectSchedule::onApprovalCompleted() calls
 * BonusScheduleSyncService::createFromSchedule(). Schedules confirmed before that
 * hook existed therefore have an assigned architect and architect activities but
 * no bonus task, and no other path ever creates one (the "backfill suggestions"
 * UI only re-links pre-existing orphan tasks, it never creates them).
 *
 * This command walks every confirmed / in-progress / completed schedule and runs
 * the same createFromSchedule() the approval hook would have. That method is
 * idempotent and self-guarding: it no-ops when a bonus already exists, when no
 * architect is assigned, or when the schedule has no architect-role activities.
 *
 * Dry-run by default. Pass --apply to actually create the tasks.
 */
class BackfillScheduleBonusTasks extends Command
{
    protected $signature = 'bonus:backfill-schedules
                            {--apply : Actually create bonus tasks (default is a dry run)}';

    protected $description = 'Create missing architect bonus tasks for already-approved project schedules';

    public function handle(BonusScheduleSyncService $sync): int
    {
        $apply = $this->option('apply');
        $this->{$apply ? 'warn' : 'info'}($apply
            ? 'APPLY MODE — bonus tasks will be created.'
            : 'DRY RUN — no changes will be saved (pass --apply to create).');

        $schedules = ProjectSchedule::whereIn('status', ['confirmed', 'in_progress', 'completed'])
            ->with(['activities', 'lead'])
            ->orderBy('id')
            ->get();

        $created = 0;
        $skippedExisting = 0;
        $skippedNoArchitect = 0;
        $skippedNoActivities = 0;
        $failed = 0;

        $architectRoleId = (int) (\Illuminate\Support\Facades\DB::table('roles')->where('name', 'Architect')->value('id') ?? 0);

        foreach ($schedules as $schedule) {
            // Replicate the service's eligibility checks so the dry run reports
            // accurately without persisting anything.
            if ($schedule->bonusTask()->exists()) {
                $skippedExisting++;
                continue;
            }
            if (!$schedule->assigned_architect_id) {
                $skippedNoArchitect++;
                $this->line("  sched#{$schedule->id}: SKIP — no architect assigned");
                continue;
            }
            $architectActivities = $architectRoleId
                ? $schedule->activities->where('role_id', $architectRoleId)->count()
                : 0;
            if ($architectActivities === 0) {
                $skippedNoActivities++;
                $this->line("  sched#{$schedule->id}: SKIP — no architect-role activities");
                continue;
            }

            if ($apply) {
                $task = $sync->createFromSchedule($schedule->fresh('activities'));
                if ($task) {
                    $created++;
                    $this->info("  sched#{$schedule->id}: created {$task->task_number} (architect {$schedule->assigned_architect_id})");
                } else {
                    $failed++;
                    $this->error("  sched#{$schedule->id}: createFromSchedule returned null (see logs)");
                }
            } else {
                $created++;
                $this->line("  sched#{$schedule->id}: WOULD CREATE bonus for architect {$schedule->assigned_architect_id} ({$architectActivities} architect activities)");
            }
        }

        $this->newLine();
        $verb = $apply ? 'Created' : 'Would create';
        $this->info("{$verb}: {$created} bonus task(s).");
        $this->line("Skipped — already has bonus: {$skippedExisting}, no architect: {$skippedNoArchitect}, no architect activities: {$skippedNoActivities}" . ($apply ? ", failed: {$failed}" : ''));
        if (!$apply && $created > 0) {
            $this->comment('Re-run with --apply to create the bonus tasks.');
        }

        return self::SUCCESS;
    }
}

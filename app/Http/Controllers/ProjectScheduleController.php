<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\ProjectSchedule;
use App\Models\ProjectScheduleActivity;
use App\Models\ProjectScheduleActivityAttachment;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ActivityReassignedNotification;
use App\Services\ProjectScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProjectScheduleController extends Controller
{
    /**
     * Display list of schedules
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user->hasAnyRole(['System Administrator', 'Managing Director', 'Sales and Marketing']);
        $canSeeAll = $isAdmin || $user->can('View All Schedule Activities');

        // Schedule visibility is per-person, not per-role: only show schedules where the user is the
        // assigned architect or has at least one activity explicitly assigned to them. The role_id
        // check is intentionally NOT applied here — every schedule contains generic role-tagged
        // activities (e.g. "Architect"), which would otherwise expose every schedule to every architect.
        $schedules = ProjectSchedule::with(['lead', 'assignedArchitect', 'client', 'approvalStatus'])
            ->when(!$canSeeAll, fn($q) => $q->where(function ($q) use ($user) {
                $q->where('assigned_architect_id', $user->id)
                  ->orWhereHas('activities', fn($aq) => $aq->where('assigned_to', $user->id));
            }))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->architect_id, fn($q, $id) => $q->where('assigned_architect_id', $id))
            ->when($request->search, function ($q, $term) {
                // Match lead name/number, client name, or architect name.
                $q->where(function ($qq) use ($term) {
                    $qq->whereHas('lead', fn($lq) => $lq
                            ->where('name', 'like', "%{$term}%")
                            ->orWhere('lead_number', 'like', "%{$term}%"))
                       ->orWhereHas('client', fn($cq) => $cq
                            ->where('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$term}%"]))
                       ->orWhereHas('assignedArchitect', fn($aq) => $aq->where('name', 'like', "%{$term}%"));
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('project-schedules.index', compact('schedules'));
    }

    /**
     * Show schedule details
     */
    public function show(ProjectSchedule $projectSchedule)
    {
        $user = auth()->user();
        $isAdmin = $user->hasAnyRole(['System Administrator', 'Managing Director', 'Chief Executive Officer', 'General Manager']);

        $projectSchedule->load(['lead.client', 'assignedArchitect', 'activities.assignedUser', 'activities.role', 'activities.attachments.uploader', 'assignments.user']);

        // Admins / overseers see everything; everyone else (including the assigned architect)
        // sees only the activities whose template role matches one of their roles.
        $canSeeAll = $isAdmin || $user->can('View All Schedule Activities');

        $activities = $projectSchedule->activities;

        if (!$canSeeAll) {
            $userRoleIds = $user->roles->pluck('id')->toArray();
            $activities = $activities->filter(fn ($a) => $a->role_id && in_array($a->role_id, $userRoleIds, true));
        }

        // Group activities by phase
        $activitiesByPhase = $activities->groupBy('phase');

        // Users available for assignment (with roles)
        $users = User::with('roles')->orderBy('name')->get();

        return view('project-schedules.show', compact('projectSchedule', 'activitiesByPhase', 'users', 'isAdmin', 'canSeeAll'));
    }

    /**
     * Show edit form (change start date)
     */
    public function edit(ProjectSchedule $projectSchedule)
    {
        if ($projectSchedule->isConfirmed()) {
            return redirect()
                ->route('project-schedules.show', $projectSchedule)
                ->with('error', 'Confirmed schedules cannot be edited.');
        }

        $projectSchedule->load(['lead.client', 'activities']);

        return view('project-schedules.edit', compact('projectSchedule'));
    }

    /**
     * Update schedule (recalculate dates from new start date)
     */
    public function update(Request $request, ProjectSchedule $projectSchedule)
    {
        if ($projectSchedule->isConfirmed()) {
            return redirect()
                ->route('project-schedules.show', $projectSchedule)
                ->with('error', 'Confirmed schedules cannot be edited.');
        }

        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        $newStartDate = Carbon::parse($request->start_date);

        // Recalculate all dates
        $success = ProjectScheduleService::recalculateSchedule($projectSchedule, $newStartDate);

        if ($success) {
            if ($request->notes) {
                $projectSchedule->update(['notes' => $request->notes]);
            }
            return redirect()
                ->route('project-schedules.show', $projectSchedule)
                ->with('success', 'Schedule dates recalculated successfully.');
        }

        return back()->with('error', 'Failed to recalculate schedule dates.');
    }

    /**
     * Delete an unconfirmed schedule
     */
    public function destroy(ProjectSchedule $projectSchedule)
    {
        if (!auth()->user()->can('Delete Project Schedule')) {
            return back()->with('error', 'You do not have permission to delete schedules.');
        }

        try {
            DB::beginTransaction();

            $projectSchedule->activities()->delete();
            ProjectAssignment::where('project_schedule_id', $projectSchedule->id)->delete();
            $projectSchedule->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete project schedule: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete schedule.');
        }

        return redirect()
            ->route('project-schedules.index')
            ->with('success', 'Schedule deleted successfully.');
    }

    /**
     * Submit the schedule for CEO/MD approval.
     * Replaces the old one-click "confirm" with a proper approval gate.
     */
    public function submit(ProjectSchedule $projectSchedule)
    {
        if ($projectSchedule->isConfirmed()) {
            return back()->with('error', 'Schedule is already confirmed.');
        }

        if ($projectSchedule->isPendingApproval()) {
            return back()->with('error', 'Schedule is already submitted and awaiting approval.');
        }

        if ($projectSchedule->activities()->count() === 0) {
            return back()->with('error', 'Cannot submit a schedule with no activities.');
        }

        $user = auth()->user();
        $isAdmin = $user->hasAnyRole(['System Administrator', 'Managing Director']);
        $isAssignedArchitect = $projectSchedule->assigned_architect_id === $user->id;

        if (!$isAdmin && !$isAssignedArchitect) {
            return back()->with('error', 'Only the assigned architect can submit this schedule for approval.');
        }

        // The Ringlesoft approval trait restricts submit() to the original creator_id.
        // Schedules are typically created by Sales/BDM but submitted by the assigned architect,
        // so align creator_id with the current submitter before delegating to the package.
        if ($projectSchedule->approvalStatus && $projectSchedule->approvalStatus->creator_id !== $user->id) {
            $projectSchedule->approvalStatus->update(['creator_id' => $user->id]);
            $projectSchedule->setRelation('approvalStatus', $projectSchedule->approvalStatus->fresh());
        }

        // Enter RingleSoft approval queue and mark as pending.
        // ApprovalNotificationListener (wired in EventServiceProvider) handles notifying
        // approvers for the current step and every subsequent step in the configured flow.
        $projectSchedule->submit();
        $projectSchedule->update(['status' => 'pending_confirmation']);

        return redirect()
            ->route('project-schedules.show', $projectSchedule)
            ->with('success', 'Schedule submitted for approval. Approvers will be notified.');
    }

    /**
     * Mark activity as started
     */
    public function startActivity(ProjectScheduleActivity $activity)
    {
        if (!$activity->schedule->isConfirmed()) {
            return back()->with('error', 'Schedule must be approved by the Managing Director before activities can begin.');
        }

        if ($activity->status !== 'pending') {
            return back()->with('error', 'Activity is already started or completed.');
        }

        // Predecessor not yet completed — allow with a warning so teams can work in parallel
        // or move forward when an earlier-stage activity (e.g. client review) is held up by another role.
        $outOfSequence = !$activity->canStart();
        if ($outOfSequence) {
            $predecessor = $activity->predecessor_code ? $activity->predecessor() : null;
            $predLabel   = $predecessor
                ? "{$predecessor->activity_code} ({$predecessor->name}) is " . str_replace('_', ' ', $predecessor->status)
                : "predecessor {$activity->predecessor_code} not found";
            Log::info("Out-of-sequence start: activity {$activity->activity_code} (#{$activity->id}) " .
                "started by user " . auth()->id() . " while {$predLabel}.");
        }

        $activity->markAsStarted(auth()->id());

        // Update schedule status to in_progress if not already
        if ($activity->schedule->status === 'confirmed') {
            $activity->schedule->update(['status' => 'in_progress']);
        }

        // Notify architect + assigned user (if different from starter)
        $schedule = $activity->schedule;
        $notifyUserIds = collect();
        if ($schedule->assigned_architect_id && $schedule->assigned_architect_id !== auth()->id()) {
            $notifyUserIds->push($schedule->assigned_architect_id);
        }
        if ($activity->assigned_to && $activity->assigned_to !== auth()->id() && !$notifyUserIds->contains($activity->assigned_to)) {
            $notifyUserIds->push($activity->assigned_to);
        }
        if ($notifyUserIds->isNotEmpty()) {
            $notifyUsers = User::whereIn('id', $notifyUserIds)->get();
            $this->sendScheduleNotification(
                $notifyUsers,
                'Activity Started',
                "Activity {$activity->activity_code}: {$activity->name} has been started by " . auth()->user()->name . ".",
                "/project-schedules/{$schedule->id}",
                $activity->id
            );
        }

        $message = $outOfSequence
            ? 'Activity marked as started (started out of sequence — predecessor was not yet completed).'
            : 'Activity marked as started.';

        return back()->with('success', $message);
    }

    /**
     * Mark activity as completed with notes and attachment
     */
    public function completeActivity(Request $request, ProjectScheduleActivity $activity)
    {
        if ($activity->status === 'completed') {
            return back()->with('error', 'Activity is already completed.');
        }

        $request->validate([
            'completion_notes' => 'nullable|string|max:1000',
            'attachments'   => 'nullable|array|max:10',
            'attachments.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip,dwg|max:51200',
        ]);

        // Update activity with completion data
        $activity->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => auth()->id(),
            'completion_notes' => $request->completion_notes,
        ]);

        // Store any uploaded attachments
        $this->storeActivityAttachments($activity, $request->file('attachments', []));

        // Check if all activities are completed
        $schedule = $activity->schedule;
        $pendingCount = $schedule->activities()->whereNotIn('status', ['completed', 'skipped'])->count();

        if ($pendingCount === 0) {
            $schedule->update(['status' => 'completed']);
        }

        // Notify architect + assigned user (if different from completer)
        $notifyUserIds = collect();
        if ($schedule->assigned_architect_id && $schedule->assigned_architect_id !== auth()->id()) {
            $notifyUserIds->push($schedule->assigned_architect_id);
        }
        if ($activity->assigned_to && $activity->assigned_to !== auth()->id() && !$notifyUserIds->contains($activity->assigned_to)) {
            $notifyUserIds->push($activity->assigned_to);
        }
        if ($notifyUserIds->isNotEmpty()) {
            $notifyUsers = User::whereIn('id', $notifyUserIds)->get();
            $this->sendScheduleNotification(
                $notifyUsers,
                'Activity Completed',
                "Activity {$activity->activity_code}: {$activity->name} has been completed by " . auth()->user()->name . ".",
                "/project-schedules/{$schedule->id}",
                $activity->id
            );
        }

        return back()->with('success', 'Activity marked as completed.');
    }

    /**
     * Add additional attachments to an activity (any status)
     */
    public function addActivityAttachments(Request $request, ProjectScheduleActivity $activity)
    {
        $request->validate([
            'attachments'   => 'required|array|min:1|max:10',
            'attachments.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip,dwg|max:51200',
        ]);

        $count = $this->storeActivityAttachments($activity, $request->file('attachments', []));

        return back()->with('success', "Uploaded {$count} attachment" . ($count === 1 ? '' : 's') . " for {$activity->activity_code}.");
    }

    /**
     * Remove a single attachment from an activity
     */
    public function removeActivityAttachment(ProjectScheduleActivityAttachment $attachment)
    {
        $activity = $attachment->activity;

        $isUploader = $attachment->uploaded_by === auth()->id();
        $isPrivileged = auth()->user()->hasAnyRole(['Managing Director', 'CEO', 'Chief Executive Officer', 'System Administrator']);

        if (!$isUploader && !$isPrivileged) {
            return back()->with('error', 'You can only remove attachments you uploaded.');
        }

        if ($attachment->path && Storage::disk('public')->exists($attachment->path)) {
            Storage::disk('public')->delete($attachment->path);
        }

        $name = $attachment->name;
        $attachment->delete();

        return back()->with('success', "Removed attachment '{$name}' from {$activity->activity_code}.");
    }

    /**
     * Store uploaded files as attachment rows for an activity.
     */
    protected function storeActivityAttachments(ProjectScheduleActivity $activity, array $files): int
    {
        $count = 0;
        foreach ($files as $file) {
            if (!$file) continue;
            $path = $file->store('activity-attachments/' . $activity->project_schedule_id, 'public');
            ProjectScheduleActivityAttachment::create([
                'activity_id' => $activity->id,
                'path'        => $path,
                'name'        => $file->getClientOriginalName(),
                'mime_type'   => $file->getClientMimeType(),
                'size_bytes'  => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
            $count++;
        }
        return $count;
    }

    /**
     * Assign a user to an activity
     */
    public function assignActivity(Request $request, ProjectScheduleActivity $activity)
    {
        if (!auth()->user()->can('Assign Project Activities')) {
            return back()->with('error', 'You do not have permission to assign activities.');
        }

        $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $activity->update([
            'assigned_to' => $request->assigned_to,
        ]);

        // Send notification to newly assigned user
        if ($request->assigned_to) {
            $assignedUser = User::find($request->assigned_to);
            $activity->load('schedule.lead');

            $notification = new ActivityReassignedNotification($activity, auth()->user());

            // Always save in-app notification (database channel)
            try {
                $assignedUser->notifyNow(
                    (clone $notification)->onlyDatabase()
                );
            } catch (\Exception $e) {
                \Log::warning("Failed to save activity assignment notification: " . $e->getMessage());
            }

            // Try sending email separately so it doesn't block database notification
            try {
                $assignedUser->notifyNow(
                    (clone $notification)->onlyMail()
                );
            } catch (\Exception $e) {
                \Log::warning("Failed to send activity assignment email to {$assignedUser->email}: " . $e->getMessage());
                return back()->with('success', "Activity {$activity->activity_code} assigned to {$assignedUser->name}. Notification sent.")
                             ->with('warning', 'Email could not be sent (invalid email address).');
            }

            return back()->with('success', "Activity {$activity->activity_code} assigned to {$assignedUser->name}. Email & notification sent.");
        }

        return back()->with('success', "Activity {$activity->activity_code} reset to default architect.");
    }

    /**
     * Bulk-assign multiple activities to one user
     */
    public function bulkAssignActivities(Request $request, ProjectSchedule $projectSchedule)
    {
        if (!auth()->user()->can('Assign Project Activities')) {
            return back()->with('error', 'You do not have permission to assign activities.');
        }

        $request->validate([
            'activity_ids'   => 'required|array|min:1',
            'activity_ids.*' => 'integer|exists:project_schedule_activities,id',
            'assigned_to'    => 'nullable|exists:users,id',
        ]);

        // Scope to this schedule only — prevents cross-schedule tampering
        $activities = ProjectScheduleActivity::whereIn('id', $request->activity_ids)
            ->where('project_schedule_id', $projectSchedule->id)
            ->get();

        if ($activities->isEmpty()) {
            return back()->with('error', 'No valid activities found for this schedule.');
        }

        $activities->each->update(['assigned_to' => $request->assigned_to]);

        if ($request->assigned_to) {
            $assignedUser = User::find($request->assigned_to);
            foreach ($activities as $activity) {
                $notification = new ActivityReassignedNotification($activity, auth()->user());
                try {
                    $assignedUser->notifyNow((clone $notification)->onlyDatabase());
                } catch (\Exception $e) {
                    \Log::warning("Bulk assign notification failed: " . $e->getMessage());
                }
            }
            $name = $assignedUser->name;
        } else {
            $name = $projectSchedule->assignedArchitect->name ?? 'Default Architect';
        }

        return back()->with('success', "Reassigned {$activities->count()} activities to {$name}.");
    }

    /**
     * Show schedule for a specific lead
     */
    public function showForLead(Lead $lead)
    {
        $projectSchedule = ProjectSchedule::where('lead_id', $lead->id)->first();

        if (!$projectSchedule) {
            return redirect()
                ->route('leads.show', $lead)
                ->with('error', 'No schedule found for this lead.');
        }

        return redirect()->route('project-schedules.show', $projectSchedule);
    }

    /**
     * Create schedule for a lead manually
     */
    public function createForLead(Request $request, Lead $lead)
    {
        $existingSchedule = ProjectSchedule::where('lead_id', $lead->id)->first();
        if ($existingSchedule) {
            return redirect()
                ->route('project-schedules.show', $existingSchedule)
                ->with('info', 'Schedule already exists for this lead.');
        }

        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $schedule = ProjectScheduleService::createScheduleFromTemplate($lead->id, $startDate, null, auth()->id());

        if ($schedule) {
            return redirect()
                ->route('project-schedules.show', $schedule)
                ->with('success', 'Schedule created successfully.');
        }

        return back()->with('error', 'Failed to create schedule.');
    }

    public function createForProject(Request $request, Project $project)
    {
        $existing = ProjectSchedule::where(function ($q) use ($project) {
            $q->whereHas('lead', fn($l) => $l->where('project_id', $project->id))
              ->orWhere('client_id', $project->client_id);
        })->first();

        if ($existing) {
            return redirect()
                ->route('project-schedules.show', $existing)
                ->with('info', 'A schedule already exists for this project.');
        }

        $request->validate([
            'start_date' => 'required|date',
        ]);

        $startDate   = Carbon::parse($request->start_date);
        $architectId = $request->assigned_architect_id ?: null;

        // Use lead if the project has one, otherwise create schedule directly
        $lead = $project->leads()->latest()->first();

        if ($lead) {
            $schedule = ProjectScheduleService::createScheduleFromTemplate(
                $lead->id, $startDate, $architectId, auth()->id()
            );
        } else {
            try {
                DB::beginTransaction();

                if (!$architectId) {
                    $architect   = ProjectAssignment::findArchitectWithLeastWorkload();
                    $architectId = $architect?->id;
                }

                $schedule = ProjectSchedule::create([
                    'lead_id'               => null,
                    'client_id'             => $project->client_id,
                    'start_date'            => $startDate,
                    'status'                => 'draft',
                    'assigned_architect_id' => $architectId,
                    'created_by'            => auth()->id(),
                ]);

                ProjectScheduleService::generateActivitiesFromTemplate($schedule, $startDate);

                $last = ProjectScheduleActivity::where('project_schedule_id', $schedule->id)
                    ->orderBy('end_date', 'desc')->first();
                if ($last) {
                    $schedule->update(['end_date' => $last->end_date]);
                }

                if ($architectId) {
                    $role = Role::where('name', 'Architect')->first();
                    if ($role) {
                        ProjectAssignment::create([
                            'lead_id'             => null,
                            'project_schedule_id' => $schedule->id,
                            'user_id'             => $architectId,
                            'role_id'             => $role->id,
                            'status'              => 'active',
                            'assigned_by'         => auth()->id(),
                            'assigned_at'         => now(),
                        ]);
                    }
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to create project schedule: ' . $e->getMessage());
                $schedule = null;
            }
        }

        if ($schedule) {
            return redirect()
                ->route('project-schedules.show', $schedule)
                ->with('success', 'Project schedule created successfully.');
        }

        return back()->with('error', 'Failed to create schedule. Please try again.');
    }

    /**
     * Update activity duration days
     */
    public function updateActivityDays(Request $request, ProjectScheduleActivity $activity)
    {
        $schedule = $activity->schedule;

        if ($schedule->isConfirmed()) {
            return back()->with('error', 'Cannot modify activities in a confirmed schedule.');
        }

        $request->validate([
            'duration_days' => 'required|integer|min:1|max:60',
        ]);

        // Update the duration
        $activity->update(['duration_days' => $request->duration_days]);

        // Recalculate all dates from the schedule start date
        $success = ProjectScheduleService::recalculateSchedule($schedule, $schedule->start_date);

        if ($success) {
            return back()->with('success', 'Activity duration updated and dates recalculated.');
        }

        return back()->with('error', 'Failed to recalculate schedule dates.');
    }

    /**
     * Remove an activity from the schedule
     */
    public function removeActivity(ProjectScheduleActivity $activity)
    {
        $schedule = $activity->schedule;

        if ($schedule->status !== 'draft') {
            return back()->with('error', 'Activities can only be removed while the schedule is still a draft.');
        }

        // Check if other activities depend on this one
        $dependents = $schedule->activities()
            ->where('predecessor_code', $activity->activity_code)
            ->get();

        // Update dependents to point to this activity's predecessor
        foreach ($dependents as $dependent) {
            $dependent->update(['predecessor_code' => $activity->predecessor_code]);
        }

        // Delete the activity
        $activityCode = $activity->activity_code;
        $activity->delete();

        // Recalculate all dates
        ProjectScheduleService::recalculateSchedule($schedule, $schedule->start_date);

        return back()->with('success', "Activity {$activityCode} removed and schedule recalculated.");
    }

    /**
     * Change the assigned architect for a schedule
     */
    public function changeArchitect(Request $request, ProjectSchedule $projectSchedule)
    {
        $request->validate([
            'assigned_architect_id' => 'required|exists:users,id',
        ]);

        $oldArchitect = $projectSchedule->assignedArchitect;
        $newArchitectId = $request->assigned_architect_id;
        $newArchitect = User::find($newArchitectId);

        // Update the architect
        $projectSchedule->update([
            'assigned_architect_id' => $newArchitectId,
        ]);

        // Notify the new architect
        if ($newArchitect && $newArchitectId !== auth()->id()) {
            $projectSchedule->load('lead');
            $leadNumber = $projectSchedule->lead->lead_number ?? $projectSchedule->lead->name ?? 'N/A';
            $this->sendScheduleNotification(
                $newArchitect,
                'Schedule Assigned',
                "You have been assigned as the architect for project schedule: {$leadNumber}.",
                "/project-schedules/{$projectSchedule->id}",
                $projectSchedule->id
            );
        }

        // Notify the old architect (if different from new and current user)
        if ($oldArchitect && $oldArchitect->id !== $newArchitectId && $oldArchitect->id !== auth()->id()) {
            $projectSchedule->load('lead');
            $leadNumber = $projectSchedule->lead->lead_number ?? $projectSchedule->lead->name ?? 'N/A';
            $this->sendScheduleNotification(
                $oldArchitect,
                'Schedule Reassigned',
                "Project schedule for {$leadNumber} has been reassigned to {$newArchitect->name}.",
                "/project-schedules/{$projectSchedule->id}",
                $projectSchedule->id
            );
        }

        return back()->with('success', "Architect changed to {$newArchitect->name} successfully.");
    }

    /**
     * Helper method to send notifications
     */
    private function sendScheduleNotification($users, string $title, string $body, string $link, int $referenceId)
    {
        $users = is_iterable($users) ? $users : [$users];

        foreach ($users as $user) {
            try {
                \App\Models\Notification::create([
                    'user_id' => $user->id,
                    'title' => $title,
                    'body' => $body,
                    'link' => $link,
                    'reference_id' => $referenceId,
                    'type' => 'schedule',
                    'read' => false,
                ]);
            } catch (\Exception $e) {
                \Log::warning("Failed to create notification for user {$user->id}: " . $e->getMessage());
            }
        }
    }
}

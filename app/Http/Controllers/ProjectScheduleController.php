<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\ProjectSchedule;
use App\Models\ProjectScheduleActivity;
use App\Services\ProjectScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProjectScheduleController extends Controller
{
    /**
     * Display list of schedules
     */
    public function index(Request $request)
    {
        $schedules = ProjectSchedule::with(['lead', 'assignedArchitect', 'client'])
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->architect_id, fn($q, $id) => $q->where('assigned_architect_id', $id))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('project-schedules.index', compact('schedules'));
    }

    /**
     * Show schedule details
     */
    public function show(ProjectSchedule $projectSchedule)
    {
        $projectSchedule->load(['lead.client', 'assignedArchitect', 'activities', 'assignments.user']);

        // Group activities by phase
        $activitiesByPhase = $projectSchedule->activities->groupBy('phase');

        return view('project-schedules.show', compact('projectSchedule', 'activitiesByPhase'));
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
     * Confirm the schedule
     */
    public function confirm(ProjectSchedule $projectSchedule)
    {
        if ($projectSchedule->isConfirmed()) {
            return back()->with('error', 'Schedule is already confirmed.');
        }

        $projectSchedule->confirm(auth()->id());
        $projectSchedule->update(['status' => 'confirmed']);

        return redirect()
            ->route('project-schedules.show', $projectSchedule)
            ->with('success', 'Schedule confirmed successfully. Activities are now visible on dashboard and calendar.');
    }

    /**
     * Mark activity as started
     */
    public function startActivity(ProjectScheduleActivity $activity)
    {
        if (!$activity->canStart()) {
            return back()->with('error', 'Cannot start this activity. Predecessor is not completed.');
        }

        if ($activity->status !== 'pending') {
            return back()->with('error', 'Activity is already started or completed.');
        }

        $activity->markAsStarted(auth()->id());

        // Update schedule status to in_progress if not already
        if ($activity->schedule->status === 'confirmed') {
            $activity->schedule->update(['status' => 'in_progress']);
        }

        return back()->with('success', 'Activity marked as started.');
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
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip,dwg|max:10240',
        ]);

        // Handle file upload
        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentPath = $file->store('activity-attachments/' . $activity->project_schedule_id, 'public');
        }

        // Update activity with completion data
        $activity->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => auth()->id(),
            'completion_notes' => $request->completion_notes,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        // Check if all activities are completed
        $schedule = $activity->schedule;
        $pendingCount = $schedule->activities()->whereNotIn('status', ['completed', 'skipped'])->count();

        if ($pendingCount === 0) {
            $schedule->update(['status' => 'completed']);
        }

        return back()->with('success', 'Activity marked as completed.');
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

        if ($schedule->isConfirmed()) {
            return back()->with('error', 'Cannot remove activities from a confirmed schedule.');
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
}

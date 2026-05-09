<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectStructuralDesign;
use App\Models\ProjectStructuralDesignStage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProjectStructuralDesignController extends Controller
{
    /**
     * Structural engineer dashboard — all active structural designs.
     */
    public function index(Request $request)
    {
        $query = ProjectStructuralDesign::with(['project.client', 'assignedEngineer', 'stages', 'approvalStatus']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Engineers see their own assignments; managers/admins see all
        if (!auth()->user()->hasAnyRole(['Managing Director', 'System Administrator', 'Admin'])) {
            $query->where('assigned_engineer_id', auth()->id());
        }

        $designs = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('pages.structural_design.index', compact('designs'));
    }

    /**
     * Show a single structural design with its stages (project-scoped URL).
     */
    public function show(Request $request, $id)
    {
        $design = ProjectStructuralDesign::with([
            'project.client',
            'assignedEngineer',
            'stages.completedByUser',
            'approvalStatus',
            'triggeringActivity',
        ])->findOrFail($id);

        $engineers = User::whereHas('roles', fn($q) =>
            $q->whereIn('name', ['Structural Engineer', 'Engineer'])
        )->get();

        return view('pages.structural_design.show', compact('design', 'engineers'));
    }

    /**
     * Manually create a structural design for a project
     * (used when the automatic trigger from B7 approval was missed).
     */
    public function store(Request $request)
    {
        $request->validate([
            'project_id'           => 'required|exists:projects,id',
            'assigned_engineer_id' => 'nullable|exists:users,id',
            'notes'                => 'nullable|string|max:1000',
        ]);

        if (ProjectStructuralDesign::where('project_id', $request->project_id)->exists()) {
            return back()->with('error', 'A structural design already exists for this project.');
        }

        $design = ProjectStructuralDesign::create([
            'project_id'           => $request->project_id,
            'assigned_engineer_id' => $request->assigned_engineer_id,
            'notes'                => $request->notes,
            'status'               => 'pending',
            'created_by'           => Auth::id(),
        ]);

        foreach (ProjectStructuralDesignStage::defaultStages() as $stage) {
            ProjectStructuralDesignStage::create(array_merge(
                $stage,
                ['structural_design_id' => $design->id, 'status' => 'pending']
            ));
        }

        return redirect()
            ->route('structural_design.show', $design)
            ->with('success', 'Structural design created successfully.');
    }

    /**
     * Update a single stage (mark in progress / complete, upload file).
     */
    public function updateStage(Request $request, $designId, $stageId)
    {
        $design = ProjectStructuralDesign::findOrFail($designId);
        $stage  = ProjectStructuralDesignStage::where('structural_design_id', $designId)
            ->findOrFail($stageId);

        $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
            'notes'  => 'nullable|string|max:1000',
            'file'   => 'nullable|file|max:20480|mimes:pdf,dwg,dxf,jpg,jpeg,png,zip',
        ]);

        if (!$design->scheduleApproved()) {
            return back()->with('error', 'Work schedule must be approved by management before stage work can begin.');
        }

        // Can only edit if not yet submitted for approval or if rejected
        if (in_array($stage->approval_status, ['submitted', 'approved'])) {
            return back()->with('error', 'This stage has already been submitted for approval and cannot be edited.');
        }

        $data = [
            'status' => $request->status,
            'notes'  => $request->notes,
        ];

        if ($request->status === 'completed') {
            $data['completed_at'] = now();
            $data['completed_by'] = Auth::id();
        }

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('structural_designs/' . $designId, 'public');
            $data['file_path'] = $path;
            $data['file_name'] = $request->file('file')->getClientOriginalName();
        }

        $stage->update($data);

        // Advance overall design status when first stage starts
        if ($request->status === 'in_progress' && $design->status === 'pending') {
            $design->update(['status' => 'in_progress']);
        }

        return back()->with('success', "Stage '{$stage->name}' updated.");
    }

    /**
     * Reassign the structural engineer.
     */
    public function reassignEngineer(Request $request, $id)
    {
        $request->validate([
            'assigned_engineer_id' => 'required|exists:users,id',
        ]);

        ProjectStructuralDesign::findOrFail($id)
            ->update(['assigned_engineer_id' => $request->assigned_engineer_id]);

        return back()->with('success', 'Engineer reassigned successfully.');
    }

    /**
     * Submit the structural design for CEO/MD approval.
     * All stages must be approved first.
     */
    public function submit(Request $request, $id)
    {
        $design = ProjectStructuralDesign::with('stages')->findOrFail($id);

        if (!$design->scheduleApproved()) {
            return back()->with('error', 'The work schedule must be approved by management before submitting the final design.');
        }

        $unapproved = $design->stages->where('approval_status', '!=', 'approved')->count();
        if ($unapproved > 0) {
            return back()->with('error', 'All stages must be individually approved by management before submitting the final design.');
        }

        if ($design->isSubmitted()) {
            return back()->with('error', 'This structural design has already been submitted.');
        }

        $design->submit(Auth::user());
        $design->update(['submitted_at' => now(), 'status' => 'submitted']);

        return back()->with('success', 'Structural design submitted for CEO/MD approval.');
    }

    // ── Work Schedule Flow ───────────────────────────────────────────────────

    /**
     * Engineer submits their work schedule for MD approval.
     */
    public function submitSchedule(Request $request, $id)
    {
        $design = ProjectStructuralDesign::findOrFail($id);

        $request->validate([
            'schedule_description'  => 'required|string|max:3000',
            'schedule_planned_start' => 'required|date',
            'schedule_planned_end'   => 'required|date|after_or_equal:schedule_planned_start',
        ]);

        if ($design->scheduleApproved()) {
            return back()->with('error', 'Work schedule is already approved.');
        }

        $design->update([
            'schedule_description'   => $request->schedule_description,
            'schedule_planned_start' => $request->schedule_planned_start,
            'schedule_planned_end'   => $request->schedule_planned_end,
            'schedule_status'        => 'submitted',
            'schedule_submitted_at'  => now(),
            'schedule_rejection_notes' => null,
        ]);

        // Notify MD/CEO
        $mdUsers = User::role(['Managing Director', 'CEO'])->get();
        foreach ($mdUsers as $md) {
            $md->notify(new \App\Notifications\SystemActionNotification(
                'Structural Design Work Schedule Awaiting Approval',
                "Engineer {$design->assignedEngineer?->name} submitted a work schedule for {$design->document_number}. Please review and approve.",
                "/structural-design/{$design->id}",
                null, $design->id
            ));
        }

        return back()->with('success', 'Work schedule submitted for management approval.');
    }

    /**
     * MD/CEO approves the work schedule → engineer can begin stages.
     */
    public function approveSchedule(Request $request, $id)
    {
        $design = ProjectStructuralDesign::findOrFail($id);

        if (!$design->schedulePending()) {
            return back()->with('error', 'No pending schedule to approve.');
        }

        $design->update([
            'schedule_status'      => 'approved',
            'schedule_approved_at' => now(),
            'schedule_approved_by' => Auth::id(),
        ]);

        // Notify the engineer
        if ($design->assigned_engineer_id) {
            $design->assignedEngineer?->notify(new \App\Notifications\SystemActionNotification(
                'Work Schedule Approved',
                "Your work schedule for {$design->document_number} has been approved. You may now begin the design stages.",
                "/structural-design/{$design->id}",
                null, $design->id
            ));
        }

        return back()->with('success', 'Work schedule approved. Engineer can now begin stages.');
    }

    /**
     * MD/CEO rejects the work schedule → engineer must revise and resubmit.
     */
    public function rejectSchedule(Request $request, $id)
    {
        $request->validate(['rejection_notes' => 'required|string|max:1000']);
        $design = ProjectStructuralDesign::findOrFail($id);

        $design->update([
            'schedule_status'          => 'rejected',
            'schedule_rejection_notes' => $request->rejection_notes,
        ]);

        $design->assignedEngineer?->notify(new \App\Notifications\SystemActionNotification(
            'Work Schedule Rejected',
            "Your work schedule for {$design->document_number} was rejected. Reason: {$request->rejection_notes}",
            "/structural-design/{$design->id}",
            null, $design->id
        ));

        return back()->with('error', 'Work schedule rejected. Engineer has been notified.');
    }

    // ── Per-Stage Approval Flow ──────────────────────────────────────────────

    /**
     * Engineer submits a completed stage for MD approval.
     */
    public function submitStage(Request $request, $designId, $stageId)
    {
        $design = ProjectStructuralDesign::findOrFail($designId);
        $stage  = ProjectStructuralDesignStage::where('structural_design_id', $designId)->findOrFail($stageId);

        if (!$design->scheduleApproved()) {
            return back()->with('error', 'Work schedule must be approved before submitting stages.');
        }

        if ($stage->status !== 'completed') {
            return back()->with('error', 'Stage must be marked as completed before submitting for approval.');
        }

        if (!$stage->file_path) {
            return back()->with('error', 'Please upload the stage document before submitting for approval.');
        }

        if ($stage->approval_status === 'submitted') {
            return back()->with('error', 'This stage is already awaiting approval.');
        }

        $stage->update([
            'approval_status' => 'submitted',
            'submitted_at'    => now(),
            'rejected_at'     => null,
            'rejection_notes' => null,
        ]);

        // Notify MD/CEO
        $mdUsers = User::role(['Managing Director', 'CEO'])->get();
        foreach ($mdUsers as $md) {
            $md->notify(new \App\Notifications\SystemActionNotification(
                'Stage Ready for Review',
                "Stage \"{$stage->name}\" of {$design->document_number} is ready for your review.",
                "/structural-design/{$design->id}",
                null, $design->id
            ));
        }

        return back()->with('success', "Stage \"{$stage->name}\" submitted for management approval.");
    }

    /**
     * MD/CEO approves a stage.
     */
    public function approveStage(Request $request, $designId, $stageId)
    {
        $design = ProjectStructuralDesign::findOrFail($designId);
        $stage  = ProjectStructuralDesignStage::where('structural_design_id', $designId)->findOrFail($stageId);

        $stage->update([
            'approval_status' => 'approved',
            'approved_at'     => now(),
            'approved_by'     => Auth::id(),
        ]);

        // Notify engineer
        $design->assignedEngineer?->notify(new \App\Notifications\SystemActionNotification(
            'Stage Approved',
            "Stage \"{$stage->name}\" of {$design->document_number} has been approved.",
            "/structural-design/{$design->id}",
            null, $design->id
        ));

        // If all stages are approved, update overall design status to in_progress
        $allApproved = $design->stages()->where('approval_status', '!=', 'approved')->doesntExist();
        if ($allApproved && $design->status === 'in_progress') {
            // All done — ready for final submission
        }

        return back()->with('success', "Stage \"{$stage->name}\" approved.");
    }

    /**
     * MD/CEO rejects a stage → engineer must revise.
     */
    public function rejectStage(Request $request, $designId, $stageId)
    {
        $request->validate(['rejection_notes' => 'required|string|max:1000']);
        $design = ProjectStructuralDesign::findOrFail($designId);
        $stage  = ProjectStructuralDesignStage::where('structural_design_id', $designId)->findOrFail($stageId);

        $stage->update([
            'approval_status' => 'rejected',
            'rejected_at'     => now(),
            'rejection_notes' => $request->rejection_notes,
        ]);

        // Revert stage to in_progress so engineer can fix it
        if ($stage->status === 'completed') {
            $stage->update(['status' => 'in_progress']);
        }

        $design->assignedEngineer?->notify(new \App\Notifications\SystemActionNotification(
            'Stage Rejected — Revision Required',
            "Stage \"{$stage->name}\" of {$design->document_number} was rejected. Reason: {$request->rejection_notes}",
            "/structural-design/{$design->id}",
            null, $design->id
        ));

        return back()->with('error', "Stage \"{$stage->name}\" rejected. Engineer has been notified.");
    }
}

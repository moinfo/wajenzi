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
     * All stages must be completed first.
     */
    public function submit(Request $request, $id)
    {
        $design = ProjectStructuralDesign::with('stages')->findOrFail($id);

        if (!$design->allStagesCompleted()) {
            return back()->with('error', 'All stages must be completed before submitting for approval.');
        }

        if ($design->isSubmitted()) {
            return back()->with('error', 'This structural design has already been submitted.');
        }

        $design->submit(Auth::user());
        $design->update(['submitted_at' => now(), 'status' => 'submitted']);

        return back()->with('success', 'Structural design submitted for CEO/MD approval.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectBoqPlan;
use App\Models\ProjectStructuralDesign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectBoqPlanController extends Controller
{
    /**
     * List all BOQ plans (QS sees their own; managers see all).
     */
    public function index(Request $request)
    {
        $query = ProjectBoqPlan::with(['project.client', 'creator', 'approvalStatus'])
            ->orderBy('created_at', 'desc');

        if (!auth()->user()->hasAnyRole(['Managing Director', 'CEO', 'System Administrator'])) {
            $query->where('created_by', auth()->id());
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $plans = $query->paginate(20);
        $projects = Project::where('status', 'structural_approved')->orderBy('project_name')->get();

        return view('pages.boq_plans.index', compact('plans', 'projects'));
    }

    /**
     * Show a single BOQ plan.
     */
    public function show($id)
    {
        $plan = ProjectBoqPlan::with(['project.client', 'creator', 'approvalStatus'])->findOrFail($id);

        return view('pages.boq_plans.show', compact('plan'));
    }

    /**
     * Create a new BOQ Preparation Plan for a project.
     */
    public function store(Request $request)
    {
        $request->validate([
            'project_id'        => 'required|exists:projects,id',
            'planned_start'     => 'required|date',
            'planned_end'       => 'required|date|after_or_equal:planned_start',
            'scope_description' => 'nullable|string|max:3000',
        ]);

        // Must have an approved structural design first
        if (!ProjectStructuralDesign::isApprovedForProject($request->project_id)) {
            return back()->with('error', 'A BOQ plan can only be created after the structural design is approved.');
        }

        // Prevent duplicates
        if (ProjectBoqPlan::where('project_id', $request->project_id)
                ->whereNotIn('status', ['rejected'])
                ->exists()) {
            return back()->with('error', 'A BOQ preparation plan already exists for this project.');
        }

        $plan = ProjectBoqPlan::create([
            'project_id'        => $request->project_id,
            'planned_start'     => $request->planned_start,
            'planned_end'       => $request->planned_end,
            'scope_description' => $request->scope_description,
            'status'            => 'draft',
            'created_by'        => Auth::id(),
        ]);

        return redirect()->route('project-boq-plans.show', $plan)
            ->with('success', 'BOQ preparation plan created. Submit it for CEO/MD approval when ready.');
    }

    /**
     * Submit the plan for CEO/MD approval.
     */
    public function submit($id)
    {
        $plan = ProjectBoqPlan::findOrFail($id);

        if ($plan->status !== 'draft' && $plan->status !== 'rejected') {
            return back()->with('error', 'This plan has already been submitted or approved.');
        }

        $plan->submit(Auth::user());
        $plan->update(['status' => 'submitted', 'submitted_at' => now()]);

        // Notify MD/CEO
        $mdUsers = \App\Models\User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Managing Director', 'CEO']))->get();
        foreach ($mdUsers as $md) {
            $md->notify(new \App\Notifications\SystemActionNotification(
                'BOQ Plan Awaiting Approval',
                "A BOQ preparation plan for {$plan->project->project_name} has been submitted for your approval.",
                "/project-boq-plans/{$plan->id}",
                null,
                $plan->id
            ));
        }

        return redirect()->route('project-boq-plans.show', $plan)
            ->with('success', 'BOQ preparation plan submitted for CEO/MD approval.');
    }
}

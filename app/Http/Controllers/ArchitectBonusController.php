<?php

namespace App\Http\Controllers;

use App\Models\ArchitectBonusTask;
use App\Models\BonusUnitTier;
use App\Models\BonusWeightConfig;
use App\Models\Lead;
use App\Models\User;
use App\Services\BonusCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ArchitectBonusController extends Controller
{
    /**
     * Check if current user is admin.
     */
    private function isAdmin(): bool
    {
        return Auth::user()->hasAnyRole(['System Administrator', 'Managing Director']);
    }

    /**
     * Get architects (users with Architect role or similar).
     */
    private function getArchitects()
    {
        return User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['Architect', 'Admin', 'Project Manager']);
        })->orWhere('designation', 'like', '%architect%')->orderBy('name')->get();
    }

    /**
     * List bonus tasks - admin sees all, architect sees own.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $this->isAdmin();

        $query = ArchitectBonusTask::with(['architect', 'lead', 'creator']);

        if (!$isAdmin) {
            $query->where('architect_id', $user->id);
        }

        if ($request->architect_id) {
            $query->where('architect_id', $request->architect_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('project_name', 'like', '%' . $request->search . '%')
                  ->orWhere('task_number', 'like', '%' . $request->search . '%');
            });
        }

        $tasks = $query->orderBy('created_at', 'desc')->paginate(20);

        // Summary stats
        $summaryQuery = ArchitectBonusTask::query();
        if (!$isAdmin) {
            $summaryQuery->where('architect_id', $user->id);
        }
        $totalBonusEarned = (clone $summaryQuery)->whereIn('status', ['scored', 'paid'])->sum('bonus_amount');
        $totalTasksCompleted = (clone $summaryQuery)->whereIn('status', ['scored', 'paid'])->count();
        $pendingTasks = (clone $summaryQuery)->whereIn('status', ['pending', 'in_progress'])->count();

        $architects = $isAdmin ? $this->getArchitects() : collect();

        return view('pages.bonus.index', compact(
            'tasks', 'isAdmin', 'architects', 'totalBonusEarned', 'totalTasksCompleted', 'pendingTasks'
        ));
    }

    /**
     * Show create form (admin only).
     */
    public function create()
    {
        if (!$this->isAdmin()) {
            abort(403);
        }

        $architects = $this->getArchitects();
        $projects = \App\Models\Project::orderBy('project_name')->get();
        $leads = Lead::orderBy('lead_date', 'desc')->get();
        $tiers = BonusUnitTier::orderBy('min_amount')->get();

        return view('pages.bonus.create', compact('architects', 'projects', 'leads', 'tiers'));
    }

    /**
     * Store a new bonus task (admin only).
     */
    public function store(Request $request)
    {
        if (!$this->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'project_name' => 'required_without:project_id|nullable|string|max:255',
            'architect_id' => 'required|exists:users,id',
            'project_budget' => 'required|numeric|min:0',
            'lead_id' => 'nullable|exists:leads,id',
            'start_date' => 'required|date',
            'scheduled_completion_date' => 'required|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        // If project selected, use its name and budget
        $projectName = $request->project_name;
        $projectBudget = $request->project_budget;
        if ($request->project_id) {
            $project = \App\Models\Project::findOrFail($request->project_id);
            $projectName = $project->project_name;
            if ($project->contract_value) {
                $projectBudget = $project->contract_value;
            }
        }

        $maxUnits = BonusUnitTier::getMaxUnits($projectBudget);

        if ($maxUnits === 0) {
            return back()->withInput()->with('error', 'No bonus tier found for the given project budget. Budget may exceed tier range.');
        }

        ArchitectBonusTask::create([
            'task_number' => ArchitectBonusTask::generateTaskNumber(),
            'project_name' => $projectName,
            'architect_id' => $request->architect_id,
            'project_budget' => $projectBudget,
            'lead_id' => $request->lead_id,
            'start_date' => $request->start_date,
            'scheduled_completion_date' => $request->scheduled_completion_date,
            'max_units' => $maxUnits,
            'notes' => $request->notes,
            'status' => 'pending',
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('architect-bonus.index')->with('success', 'Bonus task created successfully.');
    }

    /**
     * Show task detail.
     */
    public function show($id)
    {
        $task = ArchitectBonusTask::with(['architect', 'lead', 'creator', 'scorer'])->findOrFail($id);
        $user = Auth::user();
        $isAdmin = $this->isAdmin();

        // Architects can only see their own tasks
        if (!$isAdmin && $task->architect_id !== $user->id) {
            abort(403);
        }

        $weights = BonusWeightConfig::getWeights();

        return view('pages.bonus.show', compact('task', 'isAdmin', 'weights'));
    }

    /**
     * Show scoring form (admin only).
     */
    public function score($id)
    {
        if (!$this->isAdmin()) {
            abort(403);
        }

        $task = ArchitectBonusTask::with(['architect'])->findOrFail($id);

        if ($task->status === 'paid') {
            return back()->with('error', 'This task has already been paid.');
        }

        $weights = BonusWeightConfig::getWeights();

        return view('pages.bonus.score', compact('task', 'weights'));
    }

    /**
     * Process scoring (admin only).
     */
    public function updateScore(Request $request, $id)
    {
        if (!$this->isAdmin()) {
            abort(403);
        }

        $task = ArchitectBonusTask::findOrFail($id);

        if ($task->status === 'paid') {
            return back()->with('error', 'This task has already been paid.');
        }

        $request->validate([
            'actual_completion_date' => 'required|date|after_or_equal:' . $task->start_date->format('Y-m-d'),
            'design_quality_score' => 'required|numeric|min:0.4|max:1.0',
            'client_revisions' => 'required|integer|min:1|max:20',
        ]);

        $task->update([
            'actual_completion_date' => $request->actual_completion_date,
            'design_quality_score' => $request->design_quality_score,
            'client_revisions' => $request->client_revisions,
            'scored_by' => Auth::id(),
            'scored_at' => now(),
        ]);

        // Calculate bonus
        $task = BonusCalculationService::calculate($task);

        $message = $task->status === 'no_bonus'
            ? 'Task scored. No bonus awarded due to excessive delay.'
            : 'Task scored successfully. Bonus: TZS ' . number_format($task->bonus_amount);

        return redirect()->route('architect-bonus.show', $task->id)->with('success', $message);
    }

    /**
     * Mark task as paid (admin only).
     */
    public function markPaid($id)
    {
        if (!$this->isAdmin()) {
            abort(403);
        }

        $task = ArchitectBonusTask::findOrFail($id);

        if ($task->status !== 'scored') {
            return back()->with('error', 'Only scored tasks can be marked as paid.');
        }

        $task->update(['status' => 'paid']);

        return back()->with('success', 'Bonus marked as paid.');
    }

    /**
     * Architect accepts their assigned task — starts the bonus clock.
     */
    public function accept($id)
    {
        $user = Auth::user();
        $task = ArchitectBonusTask::findOrFail($id);

        if ($task->architect_id !== $user->id) {
            abort(403);
        }

        if ($task->status !== 'pending') {
            return back()->with('error', 'This task cannot be accepted.');
        }

        $now = now();
        $task->update([
            'accepted_at' => $now,
            'start_date'  => $now->toDateString(),
            'status'      => 'in_progress',
        ]);

        return back()->with('success', 'Task accepted! The bonus clock has started from today.');
    }

    /**
     * Admin force-starts a pending task (bypasses architect acceptance).
     */
    public function start($id)
    {
        if (!$this->isAdmin()) {
            abort(403);
        }

        $task = ArchitectBonusTask::findOrFail($id);

        if ($task->status !== 'pending') {
            return back()->with('error', 'Only pending tasks can be started.');
        }

        $now = now();
        $task->update([
            'accepted_at' => $task->accepted_at ?? $now,
            'start_date'  => $task->accepted_at ? $task->start_date : $now->toDateString(),
            'status'      => 'in_progress',
        ]);

        return back()->with('success', 'Task force-started by admin.');
    }

    /**
     * Weight configuration page (admin only).
     */
    public function weights()
    {
        if (!$this->isAdmin()) {
            abort(403);
        }

        $weights = BonusWeightConfig::all();
        $tiers = BonusUnitTier::orderBy('min_amount')->get();

        return view('pages.bonus.weights', compact('weights', 'tiers'));
    }

    /**
     * Update weights (admin only).
     */
    public function updateWeights(Request $request)
    {
        if (!$this->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'weights' => 'required|array',
            'weights.schedule' => 'required|numeric|min:0|max:1',
            'weights.quality' => 'required|numeric|min:0|max:1',
            'weights.client' => 'required|numeric|min:0|max:1',
        ]);

        $total = array_sum($request->weights);
        if (abs($total - 1.0) > 0.01) {
            return back()->with('error', 'Weights must sum to 1.0 (100%). Current total: ' . round($total * 100) . '%');
        }

        foreach ($request->weights as $factor => $weight) {
            BonusWeightConfig::where('factor', $factor)->update(['weight' => $weight]);
        }

        return back()->with('success', 'Bonus weights updated successfully.');
    }

    /**
     * Update a tier (admin only).
     */
    public function updateTier(Request $request, $id)
    {
        if (!$this->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'required|numeric|gt:min_amount',
            'max_units' => 'required|integer|min:1',
        ]);

        $tier = BonusUnitTier::findOrFail($id);
        $tier->update($request->only('min_amount', 'max_amount', 'max_units'));

        return back()->with('success', 'Tier updated.');
    }

    /**
     * Update a task's project budget and recompute its max_units from the tier
     * table (admin only). Only allowed before scoring — once scored/paid/no_bonus
     * the bonus is locked and changing the budget would invalidate it.
     */
    public function updateBudget(Request $request, $id)
    {
        if (!$this->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'project_budget' => 'required|numeric|min:0',
        ]);

        $task = ArchitectBonusTask::findOrFail($id);

        if (in_array($task->status, ['scored', 'paid', 'no_bonus'], true)) {
            return back()->with('error', "Cannot change the budget of a {$task->status} task — it has already been finalised.");
        }

        $maxUnits = BonusUnitTier::getMaxUnits((float) $request->project_budget);

        $task->update([
            'project_budget' => $request->project_budget,
            'max_units'      => $maxUnits,
        ]);

        return back()->with('success', "Budget for {$task->task_number} updated to TZS " . number_format($request->project_budget) . " (max units: {$maxUnits}).");
    }

    /**
     * Monthly report (admin only).
     */
    public function report(Request $request)
    {
        if (!$this->isAdmin()) {
            abort(403);
        }

        $month = $request->month ?? now()->format('Y-m');
        $year = substr($month, 0, 4);
        $mon = substr($month, 5, 2);

        $tasks = ArchitectBonusTask::with(['architect'])
            ->whereIn('status', ['scored', 'paid', 'no_bonus'])
            ->whereYear('scored_at', $year)
            ->whereMonth('scored_at', $mon)
            ->orderBy('architect_id')
            ->get();

        $architectSummary = $tasks->groupBy('architect_id')->map(function ($group) {
            return [
                'architect' => $group->first()->architect,
                'tasks_count' => $group->count(),
                'total_units' => $group->sum('final_units'),
                'total_bonus' => $group->sum('bonus_amount'),
                'avg_performance' => round($group->avg('performance_score'), 3),
            ];
        });

        return view('pages.bonus.report', compact('tasks', 'architectSummary', 'month'));
    }

    /**
     * Suggest project_schedule matches for bonus tasks that are not yet linked.
     *
     * For each unlinked bonus task, finds candidate schedules with the same architect
     * and ranks them by name similarity (Levenshtein-based). Admin picks the best
     * match (or none) and submits via linkSchedule().
     */
    public function backfillSuggestions(Request $request)
    {
        if (!$this->isAdmin()) {
            abort(403);
        }

        $unlinked = ArchitectBonusTask::whereNull('project_schedule_id')
            ->with('architect')
            ->orderBy('created_at', 'desc')
            ->get();

        $suggestions = $unlinked->map(function ($task) {
            $candidates = \App\Models\ProjectSchedule::where('assigned_architect_id', $task->architect_id)
                ->whereDoesntHave('bonusTask') // schedule isn't already taken
                ->with(['lead', 'client'])
                ->get()
                ->map(function ($s) use ($task) {
                    $scheduleName = $s->lead->name ?? trim(($s->client->first_name ?? '') . ' ' . ($s->client->last_name ?? '')) ?: ('Schedule #' . $s->id);
                    return (object) [
                        'id'         => $s->id,
                        'name'       => $scheduleName,
                        'start_date' => optional($s->start_date)->format('d M Y'),
                        'status'     => $s->status,
                        'similarity' => $this->nameSimilarity($task->project_name, $scheduleName),
                    ];
                })
                ->sortByDesc('similarity')
                ->values();

            return (object) [
                'task'       => $task,
                'candidates' => $candidates,
            ];
        });

        return view('pages.bonus.backfill_suggestions', [
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Link an unlinked bonus task to a chosen project_schedule. Marks it auto_synced
     * so future activity completions will continue to update it.
     */
    public function linkSchedule(Request $request, $id)
    {
        if (!$this->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'project_schedule_id' => 'required|exists:project_schedules,id',
        ]);

        $task = ArchitectBonusTask::findOrFail($id);
        if ($task->project_schedule_id) {
            return back()->with('error', 'This bonus task is already linked to a schedule.');
        }

        // Reject if another bonus task already claims the schedule.
        $taken = ArchitectBonusTask::where('project_schedule_id', $request->project_schedule_id)->exists();
        if ($taken) {
            return back()->with('error', 'That schedule is already linked to another bonus task.');
        }

        $task->update([
            'project_schedule_id' => $request->project_schedule_id,
            'auto_synced'         => true,
            'last_synced_at'      => now(),
        ]);

        return back()->with('success', "Bonus {$task->task_number} linked to schedule #{$request->project_schedule_id}.");
    }

    /**
     * Crude similarity 0..1 between two project names. Uses similar_text() so we
     * stay tolerant of word order, casing, and minor punctuation differences.
     */
    protected function nameSimilarity(string $a, string $b): float
    {
        similar_text(strtolower(trim($a)), strtolower(trim($b)), $percent);
        return round($percent / 100, 3);
    }

    /**
     * Delete a bonus task (admin only, pending tasks only).
     */
    public function destroy($id)
    {
        if (!$this->isAdmin()) {
            abort(403);
        }

        $task = ArchitectBonusTask::findOrFail($id);

        if ($task->status !== 'pending') {
            return back()->with('error', 'Only pending tasks can be deleted.');
        }

        $task->delete();

        return redirect()->route('architect-bonus.index')->with('success', 'Bonus task deleted.');
    }

    /**
     * AJAX: Get max units for a budget amount.
     */
    public function getMaxUnits(Request $request)
    {
        $amount = $request->input('amount', 0);
        $maxUnits = BonusUnitTier::getMaxUnits($amount);

        return response()->json(['max_units' => $maxUnits, 'unit_value' => BonusCalculationService::UNIT_VALUE]);
    }
}

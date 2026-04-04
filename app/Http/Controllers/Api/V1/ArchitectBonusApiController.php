<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ArchitectBonusTask;
use App\Models\BonusUnitTier;
use App\Models\BonusWeightConfig;
use App\Models\Project;
use App\Models\User;
use App\Models\Lead;
use App\Services\BonusCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ArchitectBonusApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
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

            $items = collect($tasks->items())->map(fn($task) => $this->formatTask($task));

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $items,
                    'meta' => [
                        'current_page' => $tasks->currentPage(),
                        'last_page' => $tasks->lastPage(),
                        'per_page' => $tasks->perPage(),
                        'total' => $tasks->total(),
                    ],
                    'summary' => [
                        'total_bonus_earned' => (float) $totalBonusEarned,
                        'total_tasks_completed' => $totalTasksCompleted,
                        'pending_tasks' => $pendingTasks,
                    ],
                    'filters' => [
                        'architect_id' => $request->architect_id,
                        'status' => $request->status,
                        'search' => $request->search,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ArchitectBonus index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch bonus tasks: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function referenceData(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $isAdmin = $this->isAdmin();

            $architects = $isAdmin ? $this->getArchitects() : collect();
            $projects = \App\Models\Project::orderBy('project_name')->get();
            $leads = Lead::orderBy('lead_date', 'desc')->get();
            $tiers = BonusUnitTier::orderBy('min_amount')->get();
            $weights = BonusWeightConfig::all();

            return response()->json([
                'success' => true,
                'data' => [
                    'architects' => $architects->map(fn($architect) => [
                        'id' => $architect->id,
                        'name' => $architect->name,
                        'email' => $architect->email,
                    ]),
                    'projects' => $projects->map(fn($project) => [
                        'id' => $project->id,
                        'project_name' => $project->project_name,
                        'contract_value' => (float) ($project->contract_value ?? 0),
                    ]),
                    'leads' => $leads->map(fn($lead) => [
                        'id' => $lead->id,
                        'lead_name' => $lead->lead_name ?? $lead->name,
                        'lead_number' => $lead->lead_number,
                        'lead_date' => $lead->lead_date?->format('Y-m-d'),
                    ]),
                    'tiers' => $tiers->map(fn($tier) => [
                        'id' => $tier->id,
                        'name' => $tier->name,
                        'min_amount' => (float) $tier->min_amount,
                        'max_amount' => (float) $tier->max_amount,
                        'max_units' => (int) $tier->max_units,
                    ]),
                    'weights' => $weights->map(fn($weight) => [
                        'id' => $weight->id,
                        'factor' => $weight->factor,
                        'weight' => (float) $weight->weight,
                        'description' => $weight->description,
                    ]),
                    'statuses' => [
                        ['value' => 'pending', 'label' => 'Pending'],
                        ['value' => 'in_progress', 'label' => 'In Progress'],
                        ['value' => 'completed', 'label' => 'Completed'],
                        ['value' => 'scored', 'label' => 'Scored'],
                        ['value' => 'no_bonus', 'label' => 'No Bonus'],
                        ['value' => 'paid', 'label' => 'Paid'],
                    ],
                    'is_admin' => $isAdmin,
                    'summary' => [
                        'total_bonus_earned' => (float) ArchitectBonusTask::when(
                            !$isAdmin,
                            fn($query) => $query->where('architect_id', $user->id)
                        )->whereIn('status', ['scored', 'paid'])->sum('bonus_amount'),
                        'total_tasks_completed' => ArchitectBonusTask::when(
                            !$isAdmin,
                            fn($query) => $query->where('architect_id', $user->id)
                        )->whereIn('status', ['scored', 'paid'])->count(),
                        'pending_tasks' => ArchitectBonusTask::when(
                            !$isAdmin,
                            fn($query) => $query->where('architect_id', $user->id)
                        )->whereIn('status', ['pending', 'in_progress'])->count(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ArchitectBonus referenceData error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reference data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $task = ArchitectBonusTask::with(['architect', 'lead', 'creator'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatTask($task),
            ]);
        } catch (\Throwable $e) {
            Log::error('ArchitectBonus show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch task: ' . $e->getMessage(),
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            if (!$this->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only administrators can create bonus tasks',
                ], 403);
            }

            $validated = $request->validate([
                'project_id' => 'nullable|exists:projects,id',
                'project_name' => 'required_without:project_id|nullable|string|max:255',
                'architect_id' => 'required|exists:users,id',
                'project_budget' => 'required|numeric|min:0',
                'lead_id' => 'nullable|exists:leads,id',
                'start_date' => 'required|date',
                'scheduled_completion_date' => 'required|date|after:start_date',
                'notes' => 'nullable|string',
            ]);

            [$projectName, $projectBudget, $maxUnits] = $this->resolveTaskPayload($validated);

            if ($maxUnits === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No bonus tier found for the given project budget. Budget may exceed tier range.',
                ], 422);
            }

            $task = ArchitectBonusTask::create([
                'task_number' => ArchitectBonusTask::generateTaskNumber(),
                'project_name' => $projectName,
                'architect_id' => $validated['architect_id'],
                'project_budget' => $projectBudget,
                'lead_id' => $validated['lead_id'] ?? null,
                'start_date' => $validated['start_date'],
                'scheduled_completion_date' => $validated['scheduled_completion_date'],
                'max_units' => $maxUnits,
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            $task->load(['architect', 'lead', 'creator']);

            return response()->json([
                'success' => true,
                'data' => $this->formatTask($task),
                'message' => 'Bonus task created successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ArchitectBonus store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create task: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            if (!$this->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only administrators can update bonus tasks',
                ], 403);
            }

            $task = ArchitectBonusTask::with(['architect', 'lead', 'creator'])->findOrFail($id);

            if (in_array($task->status, ['scored', 'paid', 'no_bonus'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Scored, no-bonus, or paid tasks cannot be edited',
                ], 422);
            }

            $validated = $request->validate([
                'project_id' => 'nullable|exists:projects,id',
                'project_name' => 'required_without:project_id|nullable|string|max:255',
                'architect_id' => 'required|exists:users,id',
                'project_budget' => 'required|numeric|min:0',
                'lead_id' => 'nullable|exists:leads,id',
                'start_date' => 'required|date',
                'scheduled_completion_date' => 'required|date|after:start_date',
                'notes' => 'nullable|string',
            ]);

            [$projectName, $projectBudget, $maxUnits] = $this->resolveTaskPayload($validated);

            if ($maxUnits === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No bonus tier found for the given project budget. Budget may exceed tier range.',
                ], 422);
            }

            $task->update([
                'project_name' => $projectName,
                'architect_id' => $validated['architect_id'],
                'project_budget' => $projectBudget,
                'lead_id' => $validated['lead_id'] ?? null,
                'start_date' => $validated['start_date'],
                'scheduled_completion_date' => $validated['scheduled_completion_date'],
                'max_units' => $maxUnits,
                'notes' => $validated['notes'] ?? null,
            ]);

            $task->refresh()->load(['architect', 'lead', 'creator']);

            return response()->json([
                'success' => true,
                'data' => $this->formatTask($task),
                'message' => 'Bonus task updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ArchitectBonus update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            if (!$this->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only administrators can delete bonus tasks',
                ], 403);
            }

            $task = ArchitectBonusTask::findOrFail($id);

            if (in_array($task->status, ['scored', 'paid', 'no_bonus'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Scored, no-bonus, or paid tasks cannot be deleted',
                ], 422);
            }

            $task->delete();

            return response()->json([
                'success' => true,
                'message' => 'Bonus task deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ArchitectBonus destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete task: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function start(int $id): JsonResponse
    {
        try {
            if (!$this->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only administrators can start bonus tasks',
                ], 403);
            }

            $task = ArchitectBonusTask::with(['architect', 'lead', 'creator'])->findOrFail($id);

            if ($task->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending tasks can be started.',
                ], 422);
            }

            $task->update(['status' => 'in_progress']);

            return response()->json([
                'success' => true,
                'message' => 'Task marked as in progress.',
                'data' => $this->formatTask($task->fresh(['architect', 'lead', 'creator'])),
            ]);
        } catch (\Throwable $e) {
            Log::error('ArchitectBonus start error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to start task: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function score(Request $request, int $id): JsonResponse
    {
        try {
            if (!$this->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only administrators can score bonus tasks',
                ], 403);
            }

            $task = ArchitectBonusTask::with(['architect', 'lead', 'creator', 'scorer'])->findOrFail($id);

            if ($task->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'This task has already been paid.',
                ], 422);
            }

            $validated = $request->validate([
                'actual_completion_date' => 'required|date|after_or_equal:' . $task->start_date->format('Y-m-d'),
                'design_quality_score' => 'required|numeric|min:0.4|max:1.0',
                'client_revisions' => 'required|integer|min:1|max:20',
            ]);

            $task->update([
                'actual_completion_date' => $validated['actual_completion_date'],
                'design_quality_score' => $validated['design_quality_score'],
                'client_revisions' => $validated['client_revisions'],
                'scored_by' => Auth::id(),
                'scored_at' => now(),
            ]);

            $task = BonusCalculationService::calculate($task);
            $task->load(['architect', 'lead', 'creator', 'scorer']);

            return response()->json([
                'success' => true,
                'message' => $task->status === 'no_bonus'
                    ? 'Task scored. No bonus awarded due to excessive delay.'
                    : 'Task scored successfully.',
                'data' => $this->formatTask($task),
            ]);
        } catch (\Throwable $e) {
            Log::error('ArchitectBonus score error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to score task: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function markPaid(int $id): JsonResponse
    {
        try {
            if (!$this->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only administrators can mark bonuses as paid',
                ], 403);
            }

            $task = ArchitectBonusTask::with(['architect', 'lead', 'creator', 'scorer'])->findOrFail($id);

            if ($task->status !== 'scored') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only scored tasks can be marked as paid.',
                ], 422);
            }

            $task->update(['status' => 'paid']);

            return response()->json([
                'success' => true,
                'message' => 'Bonus marked as paid.',
                'data' => $this->formatTask($task->fresh(['architect', 'lead', 'creator', 'scorer'])),
            ]);
        } catch (\Throwable $e) {
            Log::error('ArchitectBonus markPaid error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark task as paid: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function isAdmin(): bool
    {
        return Auth::user()->hasAnyRole(['System Administrator', 'Managing Director']);
    }

    private function getArchitects()
    {
        return User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['Architect', 'Admin', 'Project Manager']);
        })->orWhere('designation', 'like', '%architect%')->orderBy('name')->get();
    }

    private function formatTask($task): array
    {
        return [
            'id' => $task->id,
            'task_number' => $task->task_number,
            'project_name' => $task->project_name,
            'project_budget' => (float) ($task->project_budget ?? 0),
            'start_date' => $task->start_date?->format('Y-m-d'),
            'scheduled_completion_date' => $task->scheduled_completion_date?->format('Y-m-d'),
            'actual_completion_date' => $task->actual_completion_date?->format('Y-m-d'),
            'max_units' => (int) ($task->max_units ?? 0),
            'scheduled_days' => $task->start_date && $task->scheduled_completion_date ? $task->scheduled_days : null,
            'actual_days' => $task->actual_completion_date ? $task->actual_days : null,
            'status' => $task->status,
            'status_badge_class' => $this->getStatusBadgeClass($task->status),
            'bonus_amount' => (float) ($task->bonus_amount ?? 0),
            'final_units' => $task->final_units !== null ? (int) $task->final_units : null,
            'design_quality_score' => $task->design_quality_score !== null ? (float) $task->design_quality_score : null,
            'client_revisions' => $task->client_revisions !== null ? (int) $task->client_revisions : null,
            'schedule_performance' => $task->schedule_performance !== null ? (float) $task->schedule_performance : null,
            'client_approval_efficiency' => $task->client_approval_efficiency !== null ? (float) $task->client_approval_efficiency : null,
            'performance_score' => $task->performance_score !== null ? (float) $task->performance_score : null,
            'notes' => $task->notes,
            'is_excessive_delay' => $task->actual_completion_date ? $task->isExcessiveDelay() : false,
            'can_start' => $this->isAdmin() && $task->status === 'pending',
            'can_score' => $this->isAdmin() && in_array($task->status, ['in_progress', 'completed'], true),
            'can_mark_paid' => $this->isAdmin() && $task->status === 'scored',
            'can_edit' => $this->isAdmin() && !in_array($task->status, ['scored', 'paid', 'no_bonus'], true),
            'can_delete' => $this->isAdmin() && !in_array($task->status, ['scored', 'paid', 'no_bonus'], true),
            'created_at' => $task->created_at?->format('Y-m-d H:i:s'),
            'architect' => [
                'id' => $task->architect?->id,
                'name' => $task->architect?->name,
                'email' => $task->architect?->email,
            ],
            'lead' => [
                'id' => $task->lead?->id,
                'lead_name' => $task->lead?->lead_name,
            ],
            'creator' => [
                'id' => $task->creator?->id,
                'name' => $task->creator?->name,
            ],
            'scorer' => [
                'id' => $task->scorer?->id,
                'name' => $task->scorer?->name,
            ],
        ];
    }

    private function resolveTaskPayload(array $validated): array
    {
        $projectName = $validated['project_name'] ?? null;
        $projectBudget = (float) $validated['project_budget'];

        if (!empty($validated['project_id'])) {
            $project = Project::findOrFail($validated['project_id']);
            $projectName = $project->project_name;
            if ($project->contract_value) {
                $projectBudget = (float) $project->contract_value;
            }
        }

        $maxUnits = BonusUnitTier::getMaxUnits($projectBudget);

        return [$projectName, $projectBudget, $maxUnits];
    }

    public function report(Request $request): JsonResponse
    {
        try {
            if (!$this->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only administrators can access the report',
                ], 403);
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
                    'architect' => [
                        'id' => $group->first()->architect?->id,
                        'name' => $group->first()->architect?->name,
                    ],
                    'tasks_count' => $group->count(),
                    'total_units' => (int) $group->sum('final_units'),
                    'total_bonus' => (float) $group->sum('bonus_amount'),
                    'avg_performance' => round($group->avg('performance_score') ?? 0, 3),
                ];
            })->values();

            $formattedTasks = $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'task_number' => $task->task_number,
                    'project_name' => $task->project_name,
                    'architect' => [
                        'id' => $task->architect?->id,
                        'name' => $task->architect?->name,
                    ],
                    'schedule_performance' => $task->schedule_performance,
                    'design_quality_score' => $task->design_quality_score,
                    'client_approval_efficiency' => $task->client_approval_efficiency,
                    'performance_score' => $task->performance_score,
                    'final_units' => $task->final_units,
                    'bonus_amount' => (float) ($task->bonus_amount ?? 0),
                    'status' => $task->status,
                    'status_label' => ucwords(str_replace('_', ' ', $task->status)),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'month' => $month,
                    'architect_summary' => $architectSummary,
                    'tasks' => $formattedTasks,
                    'grand_total_bonus' => (float) $tasks->sum('bonus_amount'),
                    'grand_total_units' => (int) $tasks->sum('final_units'),
                    'total_tasks' => $tasks->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ArchitectBonus report error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch report: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function weights(): JsonResponse
    {
        try {
            if (!$this->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only administrators can access weights configuration',
                ], 403);
            }

            $weights = BonusWeightConfig::all();
            $tiers = BonusUnitTier::orderBy('min_amount')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'weights' => $weights->map(fn($w) => [
                        'id' => $w->id,
                        'factor' => $w->factor,
                        'weight' => (float) $w->weight,
                        'description' => $w->description,
                    ]),
                    'tiers' => $tiers->map(fn($t) => [
                        'id' => $t->id,
                        'name' => $t->name,
                        'min_amount' => (float) $t->min_amount,
                        'max_amount' => (float) $t->max_amount,
                        'max_units' => (int) $t->max_units,
                        'max_bonus_amount' => (float) ($t->max_units * BonusCalculationService::UNIT_VALUE),
                    ]),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ArchitectBonus weights error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch weights: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateWeights(Request $request): JsonResponse
    {
        try {
            if (!$this->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only administrators can update weights',
                ], 403);
            }

            $validated = $request->validate([
                'weights' => 'required|array',
                'weights.schedule' => 'required|numeric|min:0|max:1',
                'weights.quality' => 'required|numeric|min:0|max:1',
                'weights.client' => 'required|numeric|min:0|max:1',
            ]);

            $total = array_sum($validated['weights']);
            if (abs($total - 1.0) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'Weights must sum to 1.0 (100%). Current total: ' . round($total * 100) . '%',
                ], 422);
            }

            foreach ($validated['weights'] as $factor => $weight) {
                BonusWeightConfig::where('factor', $factor)->update(['weight' => $weight]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Bonus weights updated successfully',
                'data' => $validated['weights'],
            ]);
        } catch (\Throwable $e) {
            Log::error('ArchitectBonus updateWeights error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update weights: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateTier(Request $request, int $id): JsonResponse
    {
        try {
            if (!$this->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only administrators can update tiers',
                ], 403);
            }

            $validated = $request->validate([
                'min_amount' => 'required|numeric|min:0',
                'max_amount' => 'required|numeric|gt:min_amount',
                'max_units' => 'required|integer|min:1',
            ]);

            $tier = BonusUnitTier::findOrFail($id);
            $tier->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Tier updated successfully',
                'data' => [
                    'id' => $tier->id,
                    'name' => $tier->name,
                    'min_amount' => (float) $tier->min_amount,
                    'max_amount' => (float) $tier->max_amount,
                    'max_units' => (int) $tier->max_units,
                    'max_bonus_amount' => (float) ($tier->max_units * BonusCalculationService::UNIT_VALUE),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ArchitectBonus updateTier error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tier: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function getStatusBadgeClass(string $status): string
    {
        return match($status) {
            'pending' => 'secondary',
            'in_progress' => 'primary',
            'completed' => 'info',
            'scored' => 'warning',
            'no_bonus' => 'danger',
            'paid' => 'success',
            default => 'secondary'
        };
    }
}

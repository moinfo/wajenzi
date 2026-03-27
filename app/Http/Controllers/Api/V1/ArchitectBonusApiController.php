<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ArchitectBonusTask;
use App\Models\BonusUnitTier;
use App\Models\BonusWeightConfig;
use App\Models\User;
use App\Models\Lead;
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
                    ]),
                    'leads' => $leads->map(fn($lead) => [
                        'id' => $lead->id,
                        'lead_name' => $lead->lead_name,
                        'lead_date' => $lead->lead_date?->format('Y-m-d'),
                    ]),
                    'tiers' => $tiers->map(fn($tier) => [
                        'id' => $tier->id,
                        'name' => $tier->name,
                        'min_amount' => (float) $tier->min_amount,
                        'max_amount' => (float) $tier->max_amount,
                        'bonus_percentage' => (float) $tier->bonus_percentage,
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
                'architect_id' => 'required|exists:users,id',
                'lead_id' => 'required|exists:leads,id',
                'task_number' => 'required|string|max:255',
                'project_name' => 'required|string|max:255',
                'task_description' => 'required|string',
                'bonus_weight' => 'required|integer|min:1|max:10',
                'estimated_hours' => 'required|numeric|min:0',
                'due_date' => 'required|date',
                'status' => 'sometimes|in:pending,in_progress,completed,scored,paid',
            ]);

            $task = ArchitectBonusTask::create($validated);

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
            $task = ArchitectBonusTask::findOrFail($id);

            $validated = $request->validate([
                'task_number' => 'sometimes|required|string|max:255',
                'project_name' => 'sometimes|required|string|max:255',
                'task_description' => 'sometimes|required|string',
                'bonus_weight' => 'sometimes|required|integer|min:1|max:10',
                'estimated_hours' => 'sometimes|required|numeric|min:0',
                'due_date' => 'sometimes|required|date',
                'status' => 'sometimes|in:pending,in_progress,completed,scored,paid',
            ]);

            $task->update($validated);

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
            $task = ArchitectBonusTask::findOrFail($id);
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
            'task_description' => $task->task_description,
            'bonus_weight' => $task->bonus_weight,
            'estimated_hours' => (float) $task->estimated_hours,
            'due_date' => $task->due_date?->format('Y-m-d'),
            'status' => $task->status,
            'status_badge_class' => $this->getStatusBadgeClass($task->status),
            'bonus_amount' => (float) ($task->bonus_amount ?? 0),
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
        ];
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
                        'bonus_percentage' => (float) $t->bonus_percentage,
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

    private function getStatusBadgeClass(string $status): string
    {
        return match($status) {
            'pending' => 'secondary',
            'in_progress' => 'primary',
            'completed' => 'info',
            'scored' => 'warning',
            'paid' => 'success',
            default => 'secondary'
        };
    }
}

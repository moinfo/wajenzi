<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LaborContract;
use App\Models\LaborWorkLog;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LaborWorkLogApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date', date('Y-m-01'));
            $endDate = $request->input('end_date', date('Y-m-d'));
            $contractId = $request->input('contract_id');
            $projectId = $request->input('project_id');
            $perPage = $request->input('per_page', 20);

            $query = LaborWorkLog::with(['contract.project', 'contract.artisan', 'logger'])
                ->whereBetween('log_date', [$startDate, $endDate])
                ->orderBy('log_date', 'desc');

            if ($contractId) {
                $query->where('labor_contract_id', $contractId);
            }
            if ($projectId) {
                $query->whereHas('contract', fn($q) => $q->where('project_id', $projectId));
            }

            $logs = $query->paginate($perPage);

            $items = collect($logs->items())->map(fn($l) => $this->formatWorkLog($l));

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $items,
                    'meta' => [
                        'current_page' => $logs->currentPage(),
                        'last_page' => $logs->lastPage(),
                        'per_page' => $logs->perPage(),
                        'total' => $logs->total(),
                    ],
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'contract_id' => $contractId,
                        'project_id' => $projectId,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborWorkLog index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch work logs: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function referenceData(Request $request): JsonResponse
    {
        try {
            $projectId = $request->input('project_id');

            $projects = Project::orderBy('project_name')
                ->get(['id', 'project_name', 'document_number']);

            $contractsQuery = LaborContract::with(['project', 'artisan'])
                ->whereIn('status', ['active', 'on_hold'])
                ->orderBy('contract_number');

            if ($projectId) {
                $contractsQuery->where('project_id', $projectId);
            }

            $contracts = $contractsQuery->get()->map(fn($c) => [
                'id' => $c->id,
                'contract_number' => $c->contract_number,
                'project_name' => $c->project?->project_name,
                'artisan_name' => $c->artisan?->name,
            ]);

            $weatherOptions = [
                ['value' => 'sunny', 'label' => 'Sunny'],
                ['value' => 'cloudy', 'label' => 'Cloudy'],
                ['value' => 'rainy', 'label' => 'Rainy'],
                ['value' => 'stormy', 'label' => 'Stormy'],
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'projects' => $projects,
                    'contracts' => $contracts,
                    'weather_options' => $weatherOptions,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborWorkLog reference data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reference data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $contract = LaborContract::findOrFail($request->labor_contract_id);

            if (!$contract->isActive() && !$contract->isOnHold()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Work logs can only be added to active or on-hold contracts',
                ], 400);
            }

            $validated = $request->validate([
                'labor_contract_id' => 'required|exists:labor_contracts,id',
                'log_date' => 'required|date',
                'work_done' => 'required|string|min:10',
                'workers_present' => 'required|integer|min:1',
                'hours_worked' => 'nullable|numeric|min:0',
                'progress_percentage' => 'nullable|numeric|min:0|max:100',
                'challenges' => 'nullable|string',
                'materials_used' => 'nullable|array',
                'materials_used.*.name' => 'required_with:materials_used|string',
                'materials_used.*.quantity' => 'nullable|numeric',
                'weather_conditions' => 'nullable|string|in:sunny,cloudy,rainy,stormy',
                'notes' => 'nullable|string',
                'photos' => 'nullable|array',
            ]);

            $workLog = LaborWorkLog::create([
                'labor_contract_id' => $validated['labor_contract_id'],
                'log_date' => $validated['log_date'],
                'work_done' => $validated['work_done'],
                'workers_present' => $validated['workers_present'],
                'hours_worked' => $validated['hours_worked'] ?? null,
                'progress_percentage' => $validated['progress_percentage'] ?? null,
                'challenges' => $validated['challenges'] ?? null,
                'materials_used' => $validated['materials_used'] ?? null,
                'weather_conditions' => $validated['weather_conditions'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'logged_by' => $request->user()->id,
            ]);

            $workLog->load(['contract.project', 'contract.artisan', 'logger']);

            return response()->json([
                'success' => true,
                'message' => 'Work log created successfully.',
                'data' => $this->formatWorkLog($workLog),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('LaborWorkLog store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create work log: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $log = LaborWorkLog::with(['contract.project', 'contract.artisan', 'logger'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatWorkLog($log, true),
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborWorkLog show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch work log: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $log = LaborWorkLog::findOrFail($id);

            if ($log->log_date->diffInDays(now()) > 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Work logs older than 3 days cannot be edited',
                ], 400);
            }

            $validated = $request->validate([
                'log_date' => 'sometimes|required|date',
                'work_done' => 'sometimes|required|string|min:10',
                'workers_present' => 'sometimes|required|integer|min:1',
                'hours_worked' => 'nullable|numeric|min:0',
                'progress_percentage' => 'nullable|numeric|min:0|max:100',
                'challenges' => 'nullable|string',
                'materials_used' => 'nullable|array',
                'materials_used.*.name' => 'required_with:materials_used|string',
                'materials_used.*.quantity' => 'nullable|numeric',
                'weather_conditions' => 'nullable|string|in:sunny,cloudy,rainy,stormy',
                'notes' => 'nullable|string',
            ]);

            $log->update($validated);
            $log->load(['contract.project', 'contract.artisan', 'logger']);

            return response()->json([
                'success' => true,
                'message' => 'Work log updated successfully.',
                'data' => $this->formatWorkLog($log),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('LaborWorkLog update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update work log: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $log = LaborWorkLog::findOrFail($id);

            if ($log->log_date->diffInDays(now()) > 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Work logs older than 3 days cannot be deleted',
                ], 400);
            }

            if ($log->photos) {
                foreach ($log->photos as $photo) {
                    $path = str_replace('/storage/', '', $photo);
                    Storage::disk('public')->delete($path);
                }
            }

            $log->delete();

            return response()->json([
                'success' => true,
                'message' => 'Work log deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborWorkLog destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete work log: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function contractLogs(int $contractId): JsonResponse
    {
        try {
            $contract = LaborContract::with(['project', 'artisan'])->findOrFail($contractId);
            
            $logs = LaborWorkLog::with(['logger'])
                ->where('labor_contract_id', $contractId)
                ->orderBy('log_date', 'desc')
                ->paginate(20);

            $items = collect($logs->items())->map(fn($l) => $this->formatWorkLog($l));

            return response()->json([
                'success' => true,
                'data' => [
                    'contract' => [
                        'id' => $contract->id,
                        'contract_number' => $contract->contract_number,
                        'project_name' => $contract->project?->project_name,
                        'artisan_name' => $contract->artisan?->name,
                        'status' => $contract->status,
                    ],
                    'data' => $items,
                    'meta' => [
                        'current_page' => $logs->currentPage(),
                        'last_page' => $logs->lastPage(),
                        'per_page' => $logs->perPage(),
                        'total' => $logs->total(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborWorkLog contractLogs error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch contract logs: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function dashboard(): JsonResponse
    {
        try {
            $today = date('Y-m-d');
            $weekAgo = date('Y-m-d', strtotime('-7 days'));
            $monthStart = date('Y-m-01');

            $stats = [
                'total_logs' => LaborWorkLog::count(),
                'logs_this_week' => LaborWorkLog::whereBetween('log_date', [$weekAgo, $today])->count(),
                'logs_this_month' => LaborWorkLog::whereBetween('log_date', [$monthStart, $today])->count(),
                'logs_today' => LaborWorkLog::whereDate('log_date', $today)->count(),
            ];

            $totalWorkersToday = LaborWorkLog::whereDate('log_date', $today)->sum('workers_present');
            $totalHoursToday = LaborWorkLog::whereDate('log_date', $today)->sum('hours_worked');

            $recentLogs = LaborWorkLog::with(['contract.project', 'contract.artisan', 'logger'])
                ->orderBy('log_date', 'desc')
                ->limit(10)
                ->get()
                ->map(fn($l) => $this->formatWorkLog($l));

            $todayLogs = LaborWorkLog::with(['contract.project', 'contract.artisan', 'logger'])
                ->whereDate('log_date', $today)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($l) => $this->formatWorkLog($l));

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'today_totals' => [
                        'workers_present' => $totalWorkersToday,
                        'hours_worked' => round($totalHoursToday, 2),
                    ],
                    'recent_logs' => $recentLogs,
                    'today_logs' => $todayLogs,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborWorkLog dashboard error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function formatWorkLog($log, bool $detailed = false)
    {
        $canEdit = $log->log_date->diffInDays(now()) <= 3;
        $canDelete = $log->log_date->diffInDays(now()) <= 3;

        $data = [
            'id' => $log->id,
            'labor_contract_id' => $log->labor_contract_id,
            'log_date' => $log->log_date?->format('Y-m-d'),
            'work_done' => $log->work_done,
            'workers_present' => $log->workers_present,
            'hours_worked' => $log->hours_worked ? round((float) $log->hours_worked, 2) : null,
            'progress_percentage' => $log->progress_percentage ? round((float) $log->progress_percentage, 2) : null,
            'challenges' => $log->challenges,
            'materials_used' => $log->materials_used,
            'materials_count' => $log->materials_count,
            'weather_conditions' => $log->weather_conditions,
            'weather_badge_class' => $log->weather_badge_class,
            'notes' => $log->notes,
            'photo_count' => $log->photo_count,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
            'created_at' => $log->created_at?->toISOString(),
        ];

        if ($log->relationLoaded('contract') && $log->contract) {
            $data['contract'] = [
                'id' => $log->contract->id,
                'contract_number' => $log->contract->contract_number,
                'status' => $log->contract->status,
            ];
            if ($log->contract->relationLoaded('project') && $log->contract->project) {
                $data['contract']['project_name'] = $log->contract->project->project_name;
            }
            if ($log->contract->relationLoaded('artisan') && $log->contract->artisan) {
                $data['contract']['artisan_name'] = $log->contract->artisan->name;
            }
        }

        if ($log->relationLoaded('logger') && $log->logger) {
            $data['logger'] = [
                'id' => $log->logger->id,
                'name' => $log->logger->name,
            ];
        }

        if ($detailed) {
            $data['photos'] = $log->photos ?? [];
        }

        return $data;
    }
}

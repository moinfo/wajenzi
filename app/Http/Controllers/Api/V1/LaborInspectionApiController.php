<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LaborContract;
use App\Models\LaborInspection;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LaborInspectionApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $projectId = $request->input('project_id');
            $contractId = $request->input('contract_id');
            $status = $request->input('status');
            $inspectionType = $request->input('inspection_type');
            $perPage = $request->input('per_page', 20);

            $query = LaborInspection::with(['contract.project', 'contract.artisan', 'inspector', 'paymentPhase'])
                ->orderBy('inspection_date', 'desc');

            $this->applyInspectionDateFilter($query, $startDate, $endDate);

            if ($projectId) {
                $query->whereHas('contract', fn($q) => $q->where('project_id', $projectId));
            }
            if ($contractId) {
                $query->where('labor_contract_id', $contractId);
            }
            if ($status) {
                $query->where('status', $status);
            }
            if ($inspectionType) {
                $query->where('inspection_type', $inspectionType);
            }

            $inspections = $query->paginate($perPage);

            $items = collect($inspections->items())->map(fn($i) => $this->formatInspection($i));

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $items,
                    'meta' => [
                        'current_page' => $inspections->currentPage(),
                        'last_page' => $inspections->lastPage(),
                        'per_page' => $inspections->perPage(),
                        'total' => $inspections->total(),
                    ],
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'project_id' => $projectId,
                        'contract_id' => $contractId,
                        'status' => $status,
                        'inspection_type' => $inspectionType,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborInspection index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch inspections: ' . $e->getMessage(),
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
                ->whereIn('status', ['active', 'completed'])
                ->orderBy('contract_number');

            if ($projectId) {
                $contractsQuery->where('project_id', $projectId);
            }

            $contracts = $contractsQuery->get()->map(fn($c) => [
                'id' => $c->id,
                'contract_number' => $c->contract_number,
                'project_name' => $c->project?->project_name,
                'artisan_name' => $c->artisan?->name,
                'latest_progress' => $c->latest_progress,
            ]);

            // Get contracts pending inspection
            $contractsPendingInspection = LaborContract::with(['project', 'artisan'])
                ->where('status', 'active')
                ->whereHas('paymentPhases', fn($q) => $q->where('status', 'pending'))
                ->orderBy('contract_number')
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'contract_number' => $c->contract_number,
                    'project_name' => $c->project?->project_name,
                    'artisan_name' => $c->artisan?->name,
                    'latest_progress' => $c->latest_progress,
                ]);

            $inspectionTypes = [
                ['value' => 'routine', 'label' => 'Routine'],
                ['value' => 'progress', 'label' => 'Progress'],
                ['value' => 'milestone', 'label' => 'Milestone'],
                ['value' => 'final', 'label' => 'Final'],
                ['value' => 'quality', 'label' => 'Quality'],
                ['value' => 'safety', 'label' => 'Safety'],
            ];

            $statuses = [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'verified', 'label' => 'Verified'],
                ['value' => 'approved', 'label' => 'Approved'],
                ['value' => 'rejected', 'label' => 'Rejected'],
            ];

            $qualityLevels = [
                ['value' => 'excellent', 'label' => 'Excellent'],
                ['value' => 'good', 'label' => 'Good'],
                ['value' => 'acceptable', 'label' => 'Acceptable'],
                ['value' => 'poor', 'label' => 'Poor'],
                ['value' => 'unacceptable', 'label' => 'Unacceptable'],
            ];

            $results = [
                ['value' => 'pass', 'label' => 'Pass'],
                ['value' => 'fail', 'label' => 'Fail'],
                ['value' => 'conditional', 'label' => 'Conditional'],
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'projects' => $projects,
                    'contracts' => $contracts,
                    'contracts_pending_inspection' => $contractsPendingInspection,
                    'inspection_types' => $inspectionTypes,
                    'statuses' => $statuses,
                    'quality_levels' => $qualityLevels,
                    'results' => $results,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborInspection reference data error: ' . $e->getMessage());
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

            if (!$contract->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inspections can only be created for active contracts',
                ], 400);
            }

            $validated = $request->validate([
                'labor_contract_id' => 'required|exists:labor_contracts,id',
                'inspection_date' => 'required|date',
                'inspection_type' => 'required|string|in:routine,progress,milestone,final,quality,safety',
                'completion_percentage' => 'required|numeric|min:0|max:100',
                'work_quality' => 'required|string|in:excellent,good,acceptable,poor,unacceptable',
                'scope_compliance' => 'required|boolean',
                'defects_found' => 'nullable|integer|min:0',
                'rectification_required' => 'required|boolean',
                'rectification_notes' => 'nullable|string',
                'payment_phase_id' => 'nullable|exists:labor_payment_phases,id',
                'notes' => 'nullable|string',
                'photos' => 'nullable|array',
            ]);

            // Generate inspection number
            $inspectionNumber = 'INS-' . date('Y') . '-' . str_pad(LaborInspection::count() + 1, 4, '0', STR_PAD_LEFT);

            $inspection = LaborInspection::create([
                'labor_contract_id' => $validated['labor_contract_id'],
                'inspection_date' => $validated['inspection_date'],
                'inspection_type' => $validated['inspection_type'],
                'completion_percentage' => $validated['completion_percentage'],
                'work_quality' => $validated['work_quality'],
                'scope_compliance' => $validated['scope_compliance'],
                'defects_found' => $validated['defects_found'] ?? 0,
                'rectification_required' => $validated['rectification_required'],
                'rectification_notes' => $validated['rectification_notes'] ?? null,
                'payment_phase_id' => $validated['payment_phase_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'inspector_id' => $request->user()->id,
                'status' => 'draft',
            ]);

            $inspection->load(['contract.project', 'contract.artisan', 'inspector', 'paymentPhase']);

            return response()->json([
                'success' => true,
                'message' => 'Inspection created successfully.',
                'data' => $this->formatInspection($inspection, true),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('LaborInspection store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create inspection: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $inspection = LaborInspection::with(['contract.project', 'contract.artisan', 'inspector', 'paymentPhase'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatInspection($inspection, true),
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborInspection show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch inspection: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $inspection = LaborInspection::findOrFail($id);

            if ($inspection->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft inspections can be edited',
                ], 400);
            }

            $validated = $request->validate([
                'inspection_date' => 'sometimes|required|date',
                'inspection_type' => 'sometimes|required|string|in:routine,progress,milestone,final,quality,safety',
                'completion_percentage' => 'sometimes|required|numeric|min:0|max:100',
                'work_quality' => 'sometimes|required|string|in:excellent,good,acceptable,poor,unacceptable',
                'scope_compliance' => 'sometimes|required|boolean',
                'defects_found' => 'nullable|integer|min:0',
                'rectification_required' => 'sometimes|required|boolean',
                'rectification_notes' => 'nullable|string',
                'payment_phase_id' => 'nullable|exists:labor_payment_phases,id',
                'notes' => 'nullable|string',
            ]);

            $inspection->update($validated);
            $inspection->load(['contract.project', 'contract.artisan', 'inspector', 'paymentPhase']);

            return response()->json([
                'success' => true,
                'message' => 'Inspection updated successfully.',
                'data' => $this->formatInspection($inspection, true),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('LaborInspection update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update inspection: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $inspection = LaborInspection::findOrFail($id);

            if ($inspection->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft inspections can be deleted',
                ], 400);
            }

            // Delete associated photos
            if ($inspection->photos) {
                foreach ($inspection->photos as $photo) {
                    $path = str_replace('/storage/', '', $photo);
                    Storage::disk('public')->delete($path);
                }
            }

            $inspection->delete();

            return response()->json([
                'success' => true,
                'message' => 'Inspection deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborInspection destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete inspection: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function submit(int $id): JsonResponse
    {
        try {
            $inspection = LaborInspection::findOrFail($id);

            if ($inspection->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft inspections can be submitted',
                ], 400);
            }

            $inspection->update(['status' => 'pending']);

            return response()->json([
                'success' => true,
                'message' => 'Inspection submitted for approval.',
                'data' => $this->formatInspection($inspection),
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborInspection submit error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit inspection: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function contractInspections(int $contractId): JsonResponse
    {
        try {
            $contract = LaborContract::with(['project', 'artisan'])->findOrFail($contractId);
            
            $inspections = LaborInspection::with(['inspector', 'paymentPhase'])
                ->where('labor_contract_id', $contractId)
                ->orderBy('inspection_date', 'desc')
                ->paginate(20);

            $items = collect($inspections->items())->map(fn($i) => $this->formatInspection($i));

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
                        'current_page' => $inspections->currentPage(),
                        'last_page' => $inspections->lastPage(),
                        'per_page' => $inspections->perPage(),
                        'total' => $inspections->total(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborInspection contractInspections error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch contract inspections: ' . $e->getMessage(),
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
                'total_inspections' => LaborInspection::count(),
                'inspections_this_week' => LaborInspection::whereBetween('inspection_date', [$weekAgo, $today])->count(),
                'inspections_this_month' => LaborInspection::whereBetween('inspection_date', [$monthStart, $today])->count(),
                'inspections_today' => LaborInspection::whereDate('inspection_date', $today)->count(),
            ];

            $statusBreakdown = LaborInspection::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $resultBreakdown = LaborInspection::selectRaw('result, COUNT(*) as count')
                ->groupBy('result')
                ->pluck('count', 'result')
                ->toArray();

            $recentInspections = LaborInspection::with(['contract.project', 'contract.artisan', 'inspector'])
                ->orderBy('inspection_date', 'desc')
                ->limit(10)
                ->get()
                ->map(fn($i) => $this->formatInspection($i));

            $pendingContracts = LaborContract::with(['project', 'artisan'])
                ->where('status', 'active')
                ->whereHas('paymentPhases', fn($q) => $q->where('status', 'pending'))
                ->orderBy('contract_number')
                ->limit(10)
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'contract_number' => $c->contract_number,
                    'project_name' => $c->project?->project_name,
                    'artisan_name' => $c->artisan?->name,
                    'latest_progress' => $c->latest_progress,
                ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'status_breakdown' => $statusBreakdown,
                    'result_breakdown' => $resultBreakdown,
                    'recent_inspections' => $recentInspections,
                    'pending_contracts' => $pendingContracts,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborInspection dashboard error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function formatInspection($inspection, bool $detailed = false)
    {
        $canEdit = $inspection->status === 'draft';
        $canDelete = $inspection->status === 'draft';
        $canSubmit = $inspection->status === 'draft';

        $data = [
            'id' => $inspection->id,
            'inspection_number' => $inspection->inspection_number,
            'labor_contract_id' => $inspection->labor_contract_id,
            'inspection_date' => $inspection->inspection_date?->format('Y-m-d'),
            'inspection_type' => $inspection->inspection_type,
            'completion_percentage' => $inspection->completion_percentage ? round((float) $inspection->completion_percentage, 1) : null,
            'work_quality' => $inspection->work_quality,
            'scope_compliance' => $inspection->scope_compliance,
            'defects_found' => $inspection->defects_found,
            'rectification_required' => $inspection->rectification_required,
            'rectification_notes' => $inspection->rectification_notes,
            'result' => $inspection->result,
            'notes' => $inspection->notes,
            'status' => $inspection->status,
            'photo_count' => $inspection->photo_count,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
            'can_submit' => $canSubmit,
            'created_at' => $inspection->created_at?->toISOString(),
            'type_badge_class' => $inspection->type_badge_class,
            'quality_badge_class' => $inspection->quality_badge_class,
            'result_badge_class' => $inspection->result_badge_class,
            'status_badge_class' => $inspection->status_badge_class,
        ];

        if ($inspection->relationLoaded('contract') && $inspection->contract) {
            $data['contract'] = [
                'id' => $inspection->contract->id,
                'contract_number' => $inspection->contract->contract_number,
                'status' => $inspection->contract->status,
            ];
            if ($inspection->contract->relationLoaded('project') && $inspection->contract->project) {
                $data['contract']['project_name'] = $inspection->contract->project->project_name;
            }
            if ($inspection->contract->relationLoaded('artisan') && $inspection->contract->artisan) {
                $data['contract']['artisan_name'] = $inspection->contract->artisan->name;
            }
        }

        if ($inspection->relationLoaded('inspector') && $inspection->inspector) {
            $data['inspector'] = [
                'id' => $inspection->inspector->id,
                'name' => $inspection->inspector->name,
            ];
        }

        if ($inspection->relationLoaded('paymentPhase') && $inspection->paymentPhase) {
            $data['payment_phase'] = [
                'id' => $inspection->paymentPhase->id,
                'phase_number' => $inspection->paymentPhase->phase_number,
                'phase_name' => $inspection->paymentPhase->phase_name,
            ];
        }

        if ($detailed) {
            $data['photos'] = $inspection->photos ?? [];
            $data['defects'] = $inspection->defects ?? [];
        }

        return $data;
    }

    private function applyInspectionDateFilter($query, ?string $startDate, ?string $endDate): void
    {
        if ($startDate && $endDate) {
            $query->whereBetween('inspection_date', [$startDate, $endDate]);
            return;
        }

        if ($startDate) {
            $query->whereDate('inspection_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('inspection_date', '<=', $endDate);
        }
    }
}

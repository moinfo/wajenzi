<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LaborRequest;
use App\Models\Project;
use App\Models\ProjectBoq;
use App\Models\ProjectBoqSection;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LaborRequestApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date', date('Y-m-01'));
            $endDate = $request->input('end_date', date('Y-m-d'));
            $projectId = $request->input('project_id');
            $status = $request->input('status');
            $perPage = $request->input('per_page', 20);

            $query = LaborRequest::with(['project', 'artisan', 'requester', 'constructionPhase', 'contract'])
                ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                ->orderBy('created_at', 'desc');

            if ($projectId) {
                $query->where('project_id', $projectId);
            }
            if ($status) {
                $query->where('status', $status);
            }

            $requests = $query->paginate($perPage);

            $items = collect($requests->items())->map(fn($r) => $this->formatLaborRequest($r));

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $items,
                    'meta' => [
                        'current_page' => $requests->currentPage(),
                        'last_page' => $requests->lastPage(),
                        'per_page' => $requests->perPage(),
                        'total' => $requests->total(),
                    ],
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'project_id' => $projectId,
                        'status' => $status,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborRequest index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch labor requests: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function referenceData(Request $request): JsonResponse
    {
        try {
            $projectId = $request->input('project_id');

            $projects = Project::orderBy('project_name')
                ->get(['id', 'project_name', 'document_number']);

            $artisans = Supplier::where('is_artisan', 1)
                ->orWhere('supplier_type', 'artisan')
                ->orderBy('name')
                ->get(['id', 'name', 'trade_skill', 'daily_rate', 'phone']);

            $constructionPhases = collect();
            if ($projectId) {
                $boqIds = ProjectBoq::where('project_id', $projectId)->pluck('id');
                $constructionPhases = ProjectBoqSection::whereIn('boq_id', $boqIds)
                    ->whereNull('parent_id')
                    ->orderBy('name')
                    ->get(['id', 'name', 'boq_id']);
            }

            $statuses = [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'approved', 'label' => 'Approved'],
                ['value' => 'rejected', 'label' => 'Rejected'],
                ['value' => 'contracted', 'label' => 'Contracted'],
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'projects' => $projects,
                    'artisans' => $artisans,
                    'construction_phases' => $constructionPhases,
                    'statuses' => $statuses,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborRequest reference data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reference data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'project_id' => 'required|exists:projects,id',
                'artisan_id' => 'nullable|exists:suppliers,id',
                'construction_phase_id' => 'nullable|exists:project_boq_sections,id',
                'work_description' => 'required|string|min:10',
                'work_location' => 'nullable|string|max:255',
                'estimated_duration_days' => 'nullable|integer|min:1',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'artisan_assessment' => 'nullable|string',
                'materials_included' => 'nullable|boolean',
                'materials_list' => 'nullable|array',
                'materials_list.*.name' => 'required_with:materials_list|string',
                'materials_list.*.quantity' => 'nullable|numeric',
                'proposed_amount' => 'required|numeric|min:0',
                'negotiated_amount' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|in:TZS,USD,EUR',
                'payment_terms' => 'nullable|string',
            ]);

            $laborRequest = LaborRequest::create([
                'project_id' => $validated['project_id'],
                'artisan_id' => $validated['artisan_id'] ?? null,
                'construction_phase_id' => $validated['construction_phase_id'] ?? null,
                'work_description' => $validated['work_description'],
                'work_location' => $validated['work_location'] ?? null,
                'estimated_duration_days' => $validated['estimated_duration_days'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'artisan_assessment' => $validated['artisan_assessment'] ?? null,
                'materials_list' => $validated['materials_list'] ?? null,
                'materials_included' => $validated['materials_included'] ?? false,
                'proposed_amount' => $validated['proposed_amount'],
                'negotiated_amount' => $validated['negotiated_amount'] ?? null,
                'currency' => $validated['currency'] ?? 'TZS',
                'payment_terms' => $validated['payment_terms'] ?? null,
                'status' => 'draft',
                'requested_by' => $request->user()->id,
            ]);

            $laborRequest->load(['project', 'artisan', 'requester', 'constructionPhase']);

            return response()->json([
                'success' => true,
                'message' => 'Labor request created successfully.',
                'data' => $this->formatLaborRequest($laborRequest, true),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('LaborRequest store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create labor request: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $laborRequest = LaborRequest::with([
                'project',
                'artisan',
                'requester',
                'approver',
                'constructionPhase',
                'contract.paymentPhases'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatLaborRequest($laborRequest, true),
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborRequest show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch labor request: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $laborRequest = LaborRequest::findOrFail($id);

            if (!$laborRequest->isDraft()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft requests can be edited',
                ], 400);
            }

            $validated = $request->validate([
                'project_id' => 'sometimes|required|exists:projects,id',
                'artisan_id' => 'nullable|exists:suppliers,id',
                'construction_phase_id' => 'nullable|exists:project_boq_sections,id',
                'work_description' => 'sometimes|required|string|min:10',
                'work_location' => 'nullable|string|max:255',
                'estimated_duration_days' => 'nullable|integer|min:1',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'artisan_assessment' => 'nullable|string',
                'materials_included' => 'nullable|boolean',
                'materials_list' => 'nullable|array',
                'materials_list.*.name' => 'required_with:materials_list|string',
                'materials_list.*.quantity' => 'nullable|numeric',
                'proposed_amount' => 'sometimes|required|numeric|min:0',
                'negotiated_amount' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|in:TZS,USD,EUR',
                'payment_terms' => 'nullable|string',
            ]);

            $laborRequest->update($validated);
            $laborRequest->load(['project', 'artisan', 'requester', 'constructionPhase']);

            return response()->json([
                'success' => true,
                'message' => 'Labor request updated successfully.',
                'data' => $this->formatLaborRequest($laborRequest, true),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('LaborRequest update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update labor request: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $laborRequest = LaborRequest::findOrFail($id);

            if (!$laborRequest->isDraft()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft requests can be deleted',
                ], 400);
            }

            $laborRequest->delete();

            return response()->json([
                'success' => true,
                'message' => 'Labor request deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborRequest destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete labor request: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function submit(int $id): JsonResponse
    {
        try {
            $laborRequest = LaborRequest::with('artisan')->findOrFail($id);

            if (!$laborRequest->isDraft()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft requests can be submitted for approval',
                ], 400);
            }

            if (!$laborRequest->artisan_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please assign an artisan before submitting for approval',
                ], 400);
            }

            $laborRequest->status = 'pending';
            $laborRequest->save();
            $laborRequest->submit();

            $laborRequest->load(['project', 'artisan', 'requester', 'constructionPhase']);

            return response()->json([
                'success' => true,
                'message' => 'Labor request submitted for approval.',
                'data' => $this->formatLaborRequest($laborRequest),
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborRequest submit error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit labor request: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'comment' => 'nullable|string|max:500',
                'approved_amount' => 'nullable|numeric|min:0',
            ]);

            $laborRequest = LaborRequest::findOrFail($id);

            if (!$laborRequest->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending requests can be approved',
                ], 400);
            }

            $updateData = [
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ];

            if (isset($validated['approved_amount'])) {
                $updateData['approved_amount'] = $validated['approved_amount'];
            }

            $laborRequest->update($updateData);
            $laborRequest->submitApproval();

            $laborRequest->load(['project', 'artisan', 'requester', 'approver', 'constructionPhase']);

            return response()->json([
                'success' => true,
                'message' => 'Labor request approved.',
                'data' => $this->formatLaborRequest($laborRequest),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('LaborRequest approve error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve labor request: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'comment' => 'required|string|max:500',
                'rejection_reason' => 'nullable|string|max:500',
            ]);

            $laborRequest = LaborRequest::findOrFail($id);

            if (!$laborRequest->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending requests can be rejected',
                ], 400);
            }

            $laborRequest->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'] ?? $validated['comment'],
            ]);

            $laborRequest->load(['project', 'artisan', 'requester', 'constructionPhase']);

            return response()->json([
                'success' => true,
                'message' => 'Labor request rejected.',
                'data' => $this->formatLaborRequest($laborRequest),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('LaborRequest reject error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject labor request: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateNegotiation(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'negotiated_amount' => 'required|numeric|min:0',
                'artisan_assessment' => 'nullable|string',
            ]);

            $laborRequest = LaborRequest::findOrFail($id);

            if ($laborRequest->isApproved() || $laborRequest->isContracted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update negotiation for approved/contracted requests',
                ], 400);
            }

            $laborRequest->update([
                'negotiated_amount' => $validated['negotiated_amount'],
                'artisan_assessment' => $validated['artisan_assessment'] ?? $laborRequest->artisan_assessment,
            ]);

            $laborRequest->load(['project', 'artisan', 'requester', 'constructionPhase']);

            return response()->json([
                'success' => true,
                'message' => 'Negotiation details updated.',
                'data' => $this->formatLaborRequest($laborRequest),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('LaborRequest updateNegotiation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update negotiation: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function recordAssessment(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'artisan_assessment' => 'required|string',
            ]);

            $laborRequest = LaborRequest::findOrFail($id);
            $laborRequest->update([
                'artisan_assessment' => $validated['artisan_assessment'],
            ]);

            $laborRequest->load(['project', 'artisan', 'requester', 'constructionPhase']);

            return response()->json([
                'success' => true,
                'message' => 'Artisan assessment recorded.',
                'data' => $this->formatLaborRequest($laborRequest),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('LaborRequest recordAssessment error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to record assessment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getConstructionPhases(int $projectId): JsonResponse
    {
        try {
            $boqIds = ProjectBoq::where('project_id', $projectId)->pluck('id');
            $phases = ProjectBoqSection::whereIn('boq_id', $boqIds)
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get(['id', 'name', 'boq_id']);

            return response()->json([
                'success' => true,
                'data' => $phases,
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborRequest getConstructionPhases error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch construction phases: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function dashboard(): JsonResponse
    {
        try {
            $stats = [
                'total' => LaborRequest::count(),
                'draft' => LaborRequest::where('status', 'draft')->count(),
                'pending' => LaborRequest::where('status', 'pending')->count(),
                'approved' => LaborRequest::where('status', 'approved')->count(),
                'rejected' => LaborRequest::where('status', 'rejected')->count(),
                'contracted' => LaborRequest::where('status', 'contracted')->count(),
            ];

            $recentRequests = LaborRequest::with(['project', 'artisan'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(fn($r) => $this->formatLaborRequest($r));

            $pendingForApproval = LaborRequest::with(['project', 'artisan', 'requester'])
                ->where('status', 'pending')
                ->orderBy('created_at', 'asc')
                ->limit(10)
                ->get()
                ->map(fn($r) => $this->formatLaborRequest($r));

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'recent_requests' => $recentRequests,
                    'pending_for_approval' => $pendingForApproval,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborRequest dashboard error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function formatLaborRequest($request, bool $detailed = false)
    {
        $data = [
            'id' => $request->id,
            'request_number' => $request->request_number,
            'status' => $request->status,
            'status_badge_class' => $request->status_badge_class ?? 'secondary',
            'work_description' => $request->work_description,
            'work_location' => $request->work_location,
            'estimated_duration_days' => $request->estimated_duration_days,
            'start_date' => $request->start_date?->format('Y-m-d'),
            'end_date' => $request->end_date?->format('Y-m-d'),
            'materials_included' => $request->materials_included,
            'materials_list' => $request->materials_list,
            'proposed_amount' => round((float) $request->proposed_amount, 2),
            'negotiated_amount' => $request->negotiated_amount ? round((float) $request->negotiated_amount, 2) : null,
            'approved_amount' => $request->approved_amount ? round((float) $request->approved_amount, 2) : null,
            'final_amount' => round($request->final_amount, 2),
            'currency' => $request->currency ?? 'TZS',
            'payment_terms' => $request->payment_terms,
            'artisan_assessment' => $request->artisan_assessment,
            'rejection_reason' => $request->rejection_reason,
            'can_edit' => $request->isDraft(),
            'can_submit' => $request->isDraft() && $request->artisan_id,
            'can_approve' => $request->isPending(),
            'can_create_contract' => $request->canCreateContract(),
            'has_contract' => $request->contract && $request->contract->exists,
            'created_at' => $request->created_at?->toISOString(),
            'updated_at' => $request->updated_at?->toISOString(),
        ];

        if ($request->relationLoaded('project') && $request->project) {
            $data['project'] = [
                'id' => $request->project->id,
                'project_name' => $request->project->project_name,
                'document_number' => $request->project->document_number,
            ];
        }

        if ($request->relationLoaded('artisan') && $request->artisan) {
            $data['artisan'] = [
                'id' => $request->artisan->id,
                'name' => $request->artisan->name,
                'trade_skill' => $request->artisan->trade_skill,
                'daily_rate' => $request->artisan->daily_rate,
                'phone' => $request->artisan->phone,
            ];
        }

        if ($request->relationLoaded('requester') && $request->requester) {
            $data['requester'] = [
                'id' => $request->requester->id,
                'name' => $request->requester->name,
            ];
        }

        if ($request->relationLoaded('approver') && $request->approver) {
            $data['approver'] = [
                'id' => $request->approver->id,
                'name' => $request->approver->name,
                'approved_at' => $request->approved_at?->toISOString(),
            ];
        }

        if ($request->relationLoaded('constructionPhase') && $request->constructionPhase) {
            $data['construction_phase'] = [
                'id' => $request->constructionPhase->id,
                'name' => $request->constructionPhase->name,
            ];
        }

        if ($request->relationLoaded('contract') && $request->contract) {
            $data['contract'] = [
                'id' => $request->contract->id,
                'contract_number' => $request->contract->contract_number,
                'status' => $request->contract->status,
            ];
        }

        return $data;
    }
}

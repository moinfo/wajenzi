<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectBoq;
use App\Models\ProjectBoqItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BoqController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $currentUser = auth()->user();
            $query = ProjectBoq::with(['project', 'approvalStatus']);

            if ($request->project_id) {
                $query->where('project_id', $request->project_id);
            }

            if ($request->type) {
                $query->where('type', $request->type);
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            $boqs = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 20);

            $items = collect($boqs->items())->map(fn($boq) => [
                'id' => $boq->id,
                'project_id' => $boq->project_id,
                'project_name' => $boq->project?->project_name,
                'version' => $boq->version,
                'type' => $boq->type,
                'total_amount' => $boq->total_amount,
                'status' => $boq->approvalStatus?->status ?? 'Pending',
                'items_count' => $boq->items()->count(),
                'is_submitted' => $boq->isSubmitted(),
                'can_be_submitted' => $currentUser ? $boq->canBeSubmittedBy($currentUser) : false,
                'can_be_approved' => $currentUser ? ($boq->canBeApprovedBy($currentUser) && !$boq->isSubmitted()) : false,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $items,
                    'meta' => [
                        'current_page' => $boqs->currentPage(),
                        'last_page' => $boqs->lastPage(),
                        'per_page' => $boqs->perPage(),
                        'total' => $boqs->total(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Boq index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch BOQs: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'project_id' => 'required|exists:projects,id',
                'version' => 'required|integer|min:1',
                'type' => 'required|in:client,internal',
                'status' => 'nullable|string',
            ]);

            $validated['total_amount'] = 0;
            $validated['created_by'] = $request->user()->id;

            $boq = ProjectBoq::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'BOQ created successfully.',
                'data' => [
                    'id' => $boq->id,
                    'project_id' => $boq->project_id,
                    'version' => $boq->version,
                    'type' => $boq->type,
                    'total_amount' => $boq->total_amount,
                    'status' => 'Draft',
                    'items_count' => 0,
                ],
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Boq store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create BOQ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $boq = ProjectBoq::with(['project', 'approvalStatus', 'items'])->findOrFail($id);

            $currentUser = auth()->user();
            $isSubmitted = $boq->isSubmitted();
            $isRejected = $boq->isRejected();
            $isReturned = $boq->isReturned();
            $canApprove = $currentUser ? $boq->canBeApprovedBy($currentUser) : false;

            $items = $boq->items->map(fn($item) => [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'description' => $item->description,
                'specification' => $item->specification,
                'unit' => $item->unit,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
                'item_type' => $item->item_type,
                'procurement_status' => $item->procurement_status,
            ]);

            $approvalSteps = [];
            if ($isSubmitted && $boq->approvalStatus) {
                $modelApprovalSteps = collect($boq->approvalStatus->steps ?? [])->map(function ($step) {
                    $stepData = \RingleSoft\LaravelProcessApproval\Models\ProcessApprovalFlowStep::with('role')->find($step['id']);
                    $approval = ($step['process_approval_id'] !== null) 
                        ? \RingleSoft\LaravelProcessApproval\Models\ProcessApproval::find($step['process_approval_id']) 
                        : null;
                    return [
                        'step' => $stepData,
                        'approval' => $approval,
                    ];
                });
                foreach ($modelApprovalSteps as $idx => $step) {
                    $approvalSteps[] = [
                        'step' => $step['step']?->role?->name ?? 'Step ' . ($idx + 1),
                        'status' => $step['approval']?->approval_action ?? 'pending',
                        'user_name' => $step['approval']?->user?->name ?? null,
                        'comment' => $step['approval']?->comment ?? null,
                        'created_at' => $step['approval']?->created_at?->format('Y-m-d H:i:s') ?? null,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $boq->id,
                    'project_id' => $boq->project_id,
                    'project_name' => $boq->project?->project_name,
                    'version' => $boq->version,
                    'type' => $boq->type,
                    'status' => $boq->approvalStatus?->status ?? 'Pending',
                    'total_amount' => $boq->total_amount,
                    'items_count' => $boq->items()->count(),
                    'items' => $items,
                    'is_submitted' => $isSubmitted,
                    'is_rejected' => $isRejected,
                    'is_returned' => $isReturned,
                    'can_be_approved' => $canApprove && !$isRejected,
                    'can_be_rejected' => $canApprove && !$isRejected,
                    'can_be_returned' => $canApprove && !$isRejected,
                    'can_be_submitted' => $currentUser ? $boq->canBeSubmittedBy($currentUser) : false,
                    'approval_steps' => $approvalSteps,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Boq show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch BOQ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $boq = ProjectBoq::findOrFail($id);

            $validated = $request->validate([
                'project_id' => 'sometimes|exists:projects,id',
                'version' => 'sometimes|integer|min:1',
                'type' => 'sometimes|in:client,internal',
            ]);

            $boq->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'BOQ updated successfully.',
                'data' => [
                    'id' => $boq->id,
                    'project_id' => $boq->project_id,
                    'project_name' => $boq->project?->project_name,
                    'version' => $boq->version,
                    'type' => $boq->type,
                    'total_amount' => $boq->total_amount,
                    'status' => $boq->approvalStatus?->status ?? 'Draft',
                    'items_count' => $boq->items()->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Boq update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update BOQ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $boq = ProjectBoq::findOrFail($id);

            $forceDelete = $request->boolean('force', false);
            $itemsCount = $boq->items()->count();

            if ($itemsCount > 0 && !$forceDelete) {
                $boq->items()->delete();
            }

            $boq->delete();

            return response()->json([
                'success' => true,
                'message' => "BOQ deleted successfully. ($itemsCount items also deleted)",
            ]);
        } catch (\Throwable $e) {
            Log::error('Boq destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete BOQ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function projects(): JsonResponse
    {
        try {
            $projects = Project::orderBy('project_name')
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'project_name' => $p->project_name,
                ]);

            return response()->json([
                'success' => true,
                'data' => $projects,
            ]);
        } catch (\Throwable $e) {
            Log::error('Boq projects error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch projects: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function nextVersion(Request $request): JsonResponse
    {
        try {
            $projectId = $request->project_id;
            $maxVersion = ProjectBoq::where('project_id', $projectId)->max('version') ?? 0;

            return response()->json([
                'success' => true,
                'version' => $maxVersion + 1,
            ]);
        } catch (\Throwable $e) {
            Log::error('Boq nextVersion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get next version: ' . $e->getMessage(),
                'version' => 1,
            ], 500);
        }
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        try {
            $boq = ProjectBoq::findOrFail($id);
            
            if (!$boq->canBeSubmittedBy($request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot submit this BOQ for approval.',
                ], 403);
            }

            $boq->submitForApproval();

            return response()->json([
                'success' => true,
                'message' => 'BOQ submitted for approval successfully.',
                'data' => [
                    'status' => $boq->approvalStatus?->status ?? 'Submitted',
                    'is_submitted' => true,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Boq submit error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit BOQ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $boq = ProjectBoq::findOrFail($id);
            
            if (!$boq->canBeApprovedBy($request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot approve this BOQ.',
                ], 403);
            }

            $boq->approve($request->input('comment'), auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'BOQ approved successfully.',
                'data' => [
                    'status' => $boq->approvalStatus?->status ?? 'Approved',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Boq approve error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve BOQ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $boq = ProjectBoq::findOrFail($id);
            
            if (!$boq->canBeApprovedBy($request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot reject this BOQ.',
                ], 403);
            }

            $request->validate(['comment' => 'required|string']);

            $boq->reject($request->input('comment'), auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'BOQ rejected.',
                'data' => [
                    'status' => $boq->approvalStatus?->status ?? 'Rejected',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Boq reject error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject BOQ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function returnBoq(Request $request, int $id): JsonResponse
    {
        try {
            $boq = ProjectBoq::findOrFail($id);
            
            if (!$boq->canBeApprovedBy($request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot return this BOQ.',
                ], 403);
            }

            $request->validate(['comment' => 'required|string']);

            $boq->return($request->input('comment'), auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'BOQ returned for revision.',
                'data' => [
                    'status' => $boq->approvalStatus?->status ?? 'Returned',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Boq return error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to return BOQ: ' . $e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectBoqItem;
use App\Models\ProjectMaterialRequest;
use App\Models\ProjectMaterialRequestItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RingleSoft\LaravelProcessApproval\Models\ProcessApprovalFlowStep;

/**
 * Mobile API for project material requests.
 * Mirrors web {@see \App\Http\Controllers\ProjectMaterialRequestController}:
 * list, detail, bulk create (BOQ-linked or free-text items with duplicate-pending
 * and quantity-vs-BOQ-available guards), approve-quantity editing, delete
 * (pending only) and RingleSoft submit/approve/reject.
 */
class MaterialRequestController extends Controller
{
    private const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    /**
     * GET /api/v1/material-requests
     */
    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->requirePermission($request, 'Material Requests')) {
            return $denied;
        }

        try {
            $query = ProjectMaterialRequest::with([
                    'project:id,project_name',
                    'requester:id,name',
                    'approver:id,name',
                    'items.boqItem:id,item_code,description,unit',
                    'approvalStatus',
                ])
                ->orderByDesc('created_at')
                ->orderByDesc('id');

            if ($request->filled('project_id')) {
                $query->where('project_id', $request->project_id);
            }

            if ($request->filled('status')) {
                $this->applyStatusFilter($query, (string) $request->status);
            }

            if ($request->filled('my_requests')) {
                $query->where('requester_id', $request->user()->id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('request_number', 'like', "%{$search}%")
                      ->orWhere('purpose', 'like', "%{$search}%")
                      ->orWhereHas('project', function ($projectQuery) use ($search) {
                          $projectQuery->where('project_name', 'like', "%{$search}%");
                      });
                });
            }

            $perPage = min(max((int) ($request->per_page ?? 20), 1), 100);
            $requests = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $requests->getCollection()
                        ->map(fn (ProjectMaterialRequest $mr) => $this->formatRequest($mr))
                        ->values(),
                    'meta' => [
                        'current_page' => $requests->currentPage(),
                        'last_page' => $requests->lastPage(),
                        'per_page' => $requests->perPage(),
                        'total' => $requests->total(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('MaterialRequest index error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch material requests: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/material-requests/reference-data
     * Projects + (when ?project_id given) the project's material BOQ items with
     * available quantity (= quantity - quantity_requested) and a pending-request
     * flag, mirroring the web create form / BOQ-page bulk request.
     */
    public function referenceData(Request $request): JsonResponse
    {
        if ($denied = $this->requirePermission($request, 'Material Requests')) {
            return $denied;
        }

        try {
            $projectId = $request->query('project_id');

            $boqItems = collect();
            if ($projectId) {
                // BOQ item ids already on a non-finalised request for this project
                // (web duplicate-pending guard set).
                $pendingIds = ProjectMaterialRequestItem::whereNotNull('boq_item_id')
                    ->whereHas('materialRequest', function ($q) use ($projectId) {
                        $q->where('project_id', $projectId)
                          ->whereRaw("UPPER(status) NOT IN ('APPROVED','COMPLETED')");
                    })
                    ->pluck('boq_item_id')
                    ->unique()
                    ->flip();

                $boqItems = ProjectBoqItem::byProject($projectId)
                    ->materials()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get(['id', 'boq_id', 'item_code', 'description', 'unit', 'specification', 'quantity', 'quantity_requested'])
                    ->map(fn (ProjectBoqItem $i) => [
                        'id' => $i->id,
                        'item_code' => $i->item_code,
                        'description' => $i->description,
                        'unit' => $i->unit,
                        'specification' => $i->specification,
                        'quantity' => (float) $i->quantity,
                        'quantity_requested' => (float) $i->quantity_requested,
                        'available_quantity' => (float) max(0, ((float) $i->quantity) - ((float) $i->quantity_requested)),
                        'has_pending_request' => $pendingIds->has($i->id),
                    ])
                    ->values();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'projects' => Project::orderBy('project_name')
                        ->get(['id', 'project_name', 'document_number'])
                        ->map(fn ($p) => [
                            'id' => $p->id,
                            'name' => $p->project_name,
                            'document_number' => $p->document_number,
                        ]),
                    'priorities' => self::PRIORITIES,
                    'boq_items' => $boqItems,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('MaterialRequest referenceData error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reference data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/material-requests/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->requirePermission($request, 'Material Requests')) {
            return $denied;
        }

        try {
            $materialRequest = ProjectMaterialRequest::with([
                'project:id,project_name',
                'requester:id,name',
                'approver:id,name',
                'items.boqItem',
                'approvalStatus',
                'approvals.user:id,name',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatRequest($materialRequest, full: true),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Material request not found.',
            ], 404);
        } catch (\Throwable $e) {
            Log::error('MaterialRequest show error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch material request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/material-requests/bulk
     * Web-parity bulk create ({@see ProjectMaterialRequestController::storeBulk}):
     * parent 'pending' + line items (BOQ-linked or free-text), duplicate-pending
     * guard and quantity-vs-BOQ-available guard. The model's created hook writes
     * the RingleSoft approval status as Submitted, exactly like the web flow.
     */
    public function storeBulk(Request $request): JsonResponse
    {
        if ($denied = $this->requirePermission($request, 'Add Material Request')) {
            return $denied;
        }

        try {
            $validated = $request->validate([
                'project_id' => 'required|exists:projects,id',
                'items' => 'required|array|min:1',
                'items.*.boq_item_id' => 'nullable|exists:project_boq_items,id',
                'items.*.description' => 'nullable|string|max:255',
                'items.*.quantity_requested' => 'required|numeric|min:0.01',
                'items.*.unit' => 'required|string|max:20',
                'required_date' => 'required|date',
                'priority' => 'required|in:low,medium,high,urgent',
                'purpose' => 'nullable|string|max:500',
            ]);

            return $this->createRequest($request, $validated);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('MaterialRequest storeBulk error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create material request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/material-requests
     * Legacy mobile payload (items[].material_name). Normalised into the bulk
     * shape so both entry points share the same guards and web-parity status.
     */
    public function store(Request $request): JsonResponse
    {
        if ($denied = $this->requirePermission($request, 'Add Material Request')) {
            return $denied;
        }

        try {
            $validated = $request->validate([
                'project_id' => 'required|exists:projects,id',
                'purpose' => 'nullable|string|max:500',
                'items' => 'required|array|min:1',
                'items.*.boq_item_id' => 'nullable|exists:project_boq_items,id',
                'items.*.material_name' => 'nullable|string|max:255',
                'items.*.description' => 'nullable|string|max:255',
                'items.*.quantity' => 'nullable|numeric|min:0.01',
                'items.*.quantity_requested' => 'nullable|numeric|min:0.01',
                'items.*.unit' => 'nullable|string|max:20',
                'required_date' => 'nullable|date',
                'priority' => 'nullable|in:low,medium,high,urgent',
            ]);

            $normalized = [
                'project_id' => $validated['project_id'],
                'purpose' => $validated['purpose'] ?? null,
                'required_date' => $validated['required_date'] ?? now()->addDays(7)->toDateString(),
                'priority' => $validated['priority'] ?? 'medium',
                'items' => [],
            ];

            foreach ($validated['items'] as $i => $item) {
                $quantity = $item['quantity_requested'] ?? $item['quantity'] ?? null;
                if ($quantity === null) {
                    return $this->guardError("Item #" . ($i + 1) . ": quantity is required.", "items.{$i}.quantity_requested");
                }
                $normalized['items'][] = [
                    'boq_item_id' => $item['boq_item_id'] ?? null,
                    'description' => $item['description'] ?? $item['material_name'] ?? null,
                    'quantity_requested' => $quantity,
                    'unit' => $item['unit'] ?? 'unit',
                ];
            }

            return $this->createRequest($request, $normalized);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('MaterialRequest store error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create material request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/v1/material-requests/{id}
     * Wholesale edit while not yet approved.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->requirePermission($request, 'Edit Material Request')) {
            return $denied;
        }

        try {
            $materialRequest = ProjectMaterialRequest::with('items')->findOrFail($id);

            if ($materialRequest->isApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit an approved request.',
                ], 422);
            }

            $validated = $request->validate([
                'purpose' => 'sometimes|nullable|string|max:500',
                'required_date' => 'sometimes|date',
                'priority' => 'sometimes|in:low,medium,high,urgent',
                'items' => 'sometimes|array|min:1',
                'items.*.boq_item_id' => 'nullable|exists:project_boq_items,id',
                'items.*.material_name' => 'nullable|string|max:255',
                'items.*.description' => 'nullable|string|max:255',
                'items.*.quantity_requested' => 'nullable|numeric|min:0.01',
                'items.*.quantity' => 'nullable|numeric|min:0.01',
                'items.*.unit' => 'nullable|string|max:20',
            ]);

            $parentData = collect($validated)->only(['purpose', 'required_date', 'priority'])->all();

            $items = null;
            if (array_key_exists('items', $validated)) {
                $items = [];
                foreach ($validated['items'] as $i => $item) {
                    $quantity = $item['quantity_requested'] ?? $item['quantity'] ?? null;
                    if ($quantity === null) {
                        return $this->guardError("Item #" . ($i + 1) . ": quantity is required.", "items.{$i}.quantity_requested");
                    }
                    $items[] = [
                        'boq_item_id' => $item['boq_item_id'] ?? null,
                        'description' => $item['description'] ?? $item['material_name'] ?? null,
                        'quantity_requested' => $quantity,
                        'unit' => $item['unit'] ?? 'unit',
                    ];
                }

                if ($error = $this->validateItemBusinessRules($materialRequest->project_id, $items, $materialRequest->id)) {
                    return $error;
                }
            }

            DB::transaction(function () use ($materialRequest, $parentData, $items) {
                if (!empty($parentData)) {
                    $materialRequest->update($parentData);
                }
                if ($items !== null) {
                    $materialRequest->items()->delete();
                    foreach ($items as $index => $item) {
                        $boqItem = $item['boq_item_id'] ? ProjectBoqItem::find($item['boq_item_id']) : null;
                        ProjectMaterialRequestItem::create([
                            'material_request_id' => $materialRequest->id,
                            'boq_item_id' => $boqItem?->id,
                            'quantity_requested' => $item['quantity_requested'],
                            'unit' => $item['unit'],
                            'description' => $boqItem?->description ?? $item['description'],
                            'specification' => $boqItem?->specification,
                            'sort_order' => $index,
                        ]);
                    }
                }
            });

            $materialRequest = $this->loadFull($materialRequest->id);

            return response()->json([
                'success' => true,
                'message' => 'Material request updated successfully.',
                'data' => $this->formatRequest($materialRequest, full: true),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Material request not found.'], 404);
        } catch (\Throwable $e) {
            Log::error('MaterialRequest update error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update material request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/material-requests/{id}/quantities
     * Approve-quantity editing (web updateQuantities). Blocked when APPROVED.
     */
    public function updateQuantities(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->requirePermission($request, 'Edit Material Request')) {
            return $denied;
        }

        try {
            $materialRequest = ProjectMaterialRequest::with('items')->findOrFail($id);

            if ($materialRequest->isApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit quantities on an approved request.',
                ], 422);
            }

            $validated = $request->validate([
                'quantities' => 'required|array',
                'quantities.*' => 'required|numeric|min:0.01',
            ]);

            foreach ($validated['quantities'] as $itemId => $quantity) {
                $item = $materialRequest->items->firstWhere('id', (int) $itemId);
                if ($item) {
                    $item->update(['quantity_approved' => $quantity]);
                }
            }

            $materialRequest = $this->loadFull($id);

            return response()->json([
                'success' => true,
                'message' => 'Approved quantities updated.',
                'data' => $this->formatRequest($materialRequest, full: true),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Material request not found.'], 404);
        } catch (\Throwable $e) {
            Log::error('MaterialRequest updateQuantities error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update quantities: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/material-requests/{id}
     * Web parity: only while status is still pending.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->requirePermission($request, 'Delete Material Request')) {
            return $denied;
        }

        try {
            $materialRequest = ProjectMaterialRequest::findOrFail($id);

            if (!$materialRequest->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending requests can be deleted.',
                ], 422);
            }

            DB::transaction(function () use ($materialRequest) {
                $materialRequest->items()->delete();
                $materialRequest->delete(); // deleted hook purges approval rows
            });

            return response()->json([
                'success' => true,
                'message' => 'Material request deleted successfully.',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Material request not found.'], 404);
        } catch (\Throwable $e) {
            Log::error('MaterialRequest destroy error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete material request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/material-requests/{id}/submit
     * RingleSoft submit — package-restricted to the approval creator.
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        try {
            $materialRequest = ProjectMaterialRequest::with('approvalStatus')->findOrFail($id);

            if (!$materialRequest->approvalStatus) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request has no approval workflow attached.',
                ], 422);
            }

            if ($materialRequest->isSubmitted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request has already been submitted for approval.',
                ], 422);
            }

            if (!$materialRequest->canBeSubmittedBy($request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only the creator can submit this request for approval.',
                ], 403);
            }

            $materialRequest->submit($request->user());

            $materialRequest = $this->loadFull($id);

            return response()->json([
                'success' => true,
                'message' => 'Material request submitted for approval.',
                'data' => $this->formatRequest($materialRequest, full: true),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Material request not found.'], 404);
        } catch (\Throwable $e) {
            Log::error('MaterialRequest submit error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit material request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/material-requests/{id}/approve
     * Approves the next pending RingleSoft step. Completing the flow runs
     * onApprovalCompleted (status APPROVED + BOQ quantity_requested rollups).
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $materialRequest = ProjectMaterialRequest::with(['approvalStatus', 'items.boqItem'])->findOrFail($id);

            if (!$materialRequest->canBeApprovedBy($request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to approve this material request at the current step.',
                ], 403);
            }

            $ok = $materialRequest->approve($request->input('comment'), $request->user());

            if ($ok === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to approve this material request at the current step.',
                ], 403);
            }

            $materialRequest = $this->loadFull($id);

            return response()->json([
                'success' => true,
                'message' => 'Material request approved.',
                'data' => $this->formatRequest($materialRequest, full: true),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Material request not found.'], 404);
        } catch (\Throwable $e) {
            Log::error('MaterialRequest approve error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve material request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/material-requests/{id}/reject
     * RingleSoft reject. The reason is persisted as the approval comment
     * (surfaced back as rejection_reason / approval_flow steps). Web parity:
     * the parent status column stays 'pending'.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate(['reason' => 'required|string|max:500']);

            $materialRequest = ProjectMaterialRequest::with('approvalStatus')->findOrFail($id);

            if (!$materialRequest->canBeApprovedBy($request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to reject this material request at the current step.',
                ], 403);
            }

            $ok = $materialRequest->reject($request->reason, $request->user());

            if ($ok === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to reject this material request at the current step.',
                ], 403);
            }

            $materialRequest = $this->loadFull($id);

            return response()->json([
                'success' => true,
                'message' => 'Material request rejected.',
                'data' => $this->formatRequest($materialRequest, full: true),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Material request not found.'], 404);
        } catch (\Throwable $e) {
            Log::error('MaterialRequest reject error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject material request: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Internals
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Shared creation path for store()/storeBulk(). Runs the web business
     * guards and creates the parent + items inside a transaction. Models are
     * created via Eloquent so the manual-id and approval hooks fire.
     */
    private function createRequest(Request $request, array $data): JsonResponse
    {
        if ($error = $this->validateItemBusinessRules($data['project_id'], $data['items'])) {
            return $error;
        }

        $materialRequest = DB::transaction(function () use ($request, $data) {
            $materialRequest = ProjectMaterialRequest::create([
                'project_id' => $data['project_id'],
                'requester_id' => $request->user()->id,
                'status' => 'pending',
                'required_date' => $data['required_date'],
                'purpose' => $data['purpose'] ?? null,
                'priority' => $data['priority'],
            ]);

            foreach ($data['items'] as $index => $item) {
                $boqItem = !empty($item['boq_item_id']) ? ProjectBoqItem::find($item['boq_item_id']) : null;

                ProjectMaterialRequestItem::create([
                    'material_request_id' => $materialRequest->id,
                    'boq_item_id' => $boqItem?->id,
                    'quantity_requested' => $item['quantity_requested'],
                    'unit' => $item['unit'],
                    'description' => $boqItem?->description ?? ($item['description'] ?? null),
                    'specification' => $boqItem?->specification,
                    'sort_order' => $index,
                ]);
            }

            return $materialRequest;
        });

        $materialRequest = $this->loadFull($materialRequest->id);
        $count = count($data['items']);

        return response()->json([
            'success' => true,
            'message' => "Material request submitted with {$count} item(s).",
            'data' => $this->formatRequest($materialRequest, full: true),
        ], 201);
    }

    /**
     * Web storeBulk business guards:
     * 1. each item needs a BOQ item or a free-text description;
     * 2. BOQ items must belong to the request's project;
     * 3. duplicate-pending guard (BOQ item already on a non-finalised request);
     * 4. quantity must not exceed BOQ available (quantity - quantity_requested).
     *
     * @param array<int, array{boq_item_id: mixed, description: mixed, quantity_requested: mixed, unit: mixed}> $items
     */
    private function validateItemBusinessRules(int $projectId, array $items, ?int $ignoreRequestId = null): ?JsonResponse
    {
        $pendingIds = ProjectMaterialRequestItem::whereNotNull('boq_item_id')
            ->whereHas('materialRequest', function ($q) use ($projectId, $ignoreRequestId) {
                $q->where('project_id', $projectId)
                  ->whereRaw("UPPER(status) NOT IN ('APPROVED','COMPLETED')");
                if ($ignoreRequestId) {
                    $q->where('id', '!=', $ignoreRequestId);
                }
            })
            ->pluck('boq_item_id')
            ->unique()
            ->flip();

        foreach ($items as $i => $item) {
            $n = $i + 1;
            $boqItemId = $item['boq_item_id'] ?? null;
            $description = trim((string) ($item['description'] ?? ''));

            if (!$boqItemId && $description === '') {
                return $this->guardError("Item #{$n}: provide a BOQ item or enter a description.", "items.{$i}.description");
            }

            if ($boqItemId) {
                $boqItem = ProjectBoqItem::with('boq:id,project_id')->find($boqItemId);

                if (!$boqItem || (int) $boqItem->boq?->project_id !== (int) $projectId) {
                    return $this->guardError("Item #{$n}: the selected BOQ item does not belong to this project.", "items.{$i}.boq_item_id");
                }

                if ($pendingIds->has($boqItem->id)) {
                    return $this->guardError("{$boqItem->item_code} already has a pending request.", "items.{$i}.boq_item_id");
                }

                $available = max(0, ((float) $boqItem->quantity) - ((float) $boqItem->quantity_requested));
                if ((float) $item['quantity_requested'] > $available) {
                    return $this->guardError("Quantity for {$boqItem->item_code} exceeds available ({$available}).", "items.{$i}.quantity_requested");
                }
            }
        }

        return null;
    }

    private function guardError(string $message, string $field): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => [$field => [$message]],
        ], 422);
    }

    private function loadFull(int $id): ProjectMaterialRequest
    {
        return ProjectMaterialRequest::with([
            'project:id,project_name',
            'requester:id,name',
            'approver:id,name',
            'items.boqItem',
            'approvalStatus',
            'approvals.user:id,name',
        ])->findOrFail($id);
    }

    /**
     * Require a Spatie permission (names shared verbatim with web).
     */
    private function requirePermission(Request $request, string $permission): ?JsonResponse
    {
        if ($request->user()?->can($permission)) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to perform this action.',
        ], 403);
    }

    /**
     * Status filter accepts pending|approved|rejected (case-insensitive).
     * 'rejected' lives on the RingleSoft approvalStatus (the parent status
     * column never becomes rejected on web).
     */
    private function applyStatusFilter($query, string $status): void
    {
        switch (strtolower($status)) {
            case 'approved':
                $query->whereRaw("UPPER(status) = 'APPROVED'");
                break;
            case 'rejected':
                $query->whereHas('approvalStatus', fn ($q) => $q->whereIn('status', ['Rejected', 'Discarded']));
                break;
            case 'pending':
                $query->whereRaw("UPPER(status) != 'APPROVED'")
                    ->where(function ($q) {
                        $q->whereDoesntHave('approvalStatus')
                          ->orWhereHas('approvalStatus', fn ($sq) => $sq->whereNotIn('status', ['Rejected', 'Discarded']));
                    });
                break;
            default:
                $query->whereRaw('UPPER(status) = ?', [strtoupper($status)]);
        }
    }

    private function formatRequest(ProjectMaterialRequest $mr, bool $full = false): array
    {
        $items = $mr->relationLoaded('items') ? $mr->items : collect();

        $summary = $items
            ->map(fn ($i) => $i->boqItem?->item_code ?? $i->description)
            ->filter()
            ->implode(', ');
        if (strlen($summary) > 60) {
            $summary = substr($summary, 0, 57) . '...';
        }

        $data = [
            'id' => $mr->id,
            'request_number' => $mr->request_number,
            'project_id' => $mr->project_id,
            'project_name' => $mr->project?->project_name,
            'requester_id' => $mr->requester_id,
            'requester_name' => $mr->requester?->name,
            'approved_by' => $mr->approved_by,
            'approver_name' => $mr->approver?->name,
            'status' => $mr->status,
            'approval_status' => $mr->approvalStatus?->status ?? 'Pending',
            'priority' => $mr->priority ?? 'medium',
            'required_date' => optional($mr->required_date)->format('Y-m-d'),
            'requested_date' => optional($mr->requested_date)->toIso8601String(),
            'approved_date' => optional($mr->approved_date)->toIso8601String(),
            'purpose' => $mr->purpose,
            'items_count' => $items->count(),
            'items_summary' => $summary,
            'created_at' => optional($mr->created_at)->toIso8601String(),
        ];

        if (!$full) {
            return $data;
        }

        $data['items'] = $items->map(function (ProjectMaterialRequestItem $item) {
            $boqItem = $item->boqItem;
            return [
                'id' => $item->id,
                'boq_item_id' => $item->boq_item_id,
                'description' => $item->description,
                'specification' => $item->specification,
                'quantity_requested' => (float) $item->quantity_requested,
                'quantity_approved' => $item->quantity_approved !== null ? (float) $item->quantity_approved : null,
                'unit' => $item->unit,
                'sort_order' => (int) $item->sort_order,
                'boq_item' => $boqItem ? [
                    'id' => $boqItem->id,
                    'item_code' => $boqItem->item_code,
                    'description' => $boqItem->description,
                    'unit' => $boqItem->unit,
                    'quantity' => (float) $boqItem->quantity,
                    'quantity_requested' => (float) $boqItem->quantity_requested,
                    'quantity_received' => (float) $boqItem->quantity_received,
                    'quantity_remaining' => (float) $boqItem->quantity_remaining,
                ] : null,
            ];
        })->values();

        $data['approval_flow'] = $this->buildApprovalFlow($mr);
        $data['rejection_reason'] = $mr->relationLoaded('approvals')
            ? $mr->approvals->where('approval_action', 'Rejected')->sortByDesc('id')->first()?->comment
            : null;
        $data['can_edit_quantities'] = !$mr->isApproved();
        $data['can_delete'] = $mr->isPending();
        $data['approval_page_url'] = url("/project_material_request/{$mr->id}/0");

        return $data;
    }

    /**
     * RingleSoft flow readout — same shape as PurchaseApiController.
     */
    private function buildApprovalFlow(ProjectMaterialRequest $mr): array
    {
        $steps = collect($mr->approvalStatus?->steps ?? [])->map(function ($step) use ($mr) {
            $flowStep = ProcessApprovalFlowStep::with('role')->find($step['id'] ?? null);
            $approval = null;
            if (!empty($step['process_approval_id']) && $mr->relationLoaded('approvals')) {
                $approval = $mr->approvals->firstWhere('id', $step['process_approval_id']);
            }

            return [
                'step_id' => $step['id'] ?? null,
                'role_name' => $flowStep?->role?->name ?? ('Step ' . ($step['id'] ?? '')),
                'action' => $step['process_approval_action'] ?? 'Pending',
                'approver_name' => $approval?->user?->name ?? $approval?->approver_name ?? null,
                'date' => $approval?->created_at?->format('d F, Y') ?? null,
                'comment' => $approval?->comment ?? null,
            ];
        })->values();

        $nextStep = $mr->nextApprovalStep();
        $isCompleted = $mr->isApprovalCompleted();
        $user = auth()->user();

        return [
            'status_label' => $isCompleted
                ? 'Approval completed!'
                : ($mr->isSubmitted() ? 'In Progress' : 'Not Submitted'),
            'next_role_name' => $nextStep?->role?->name,
            'is_submitted' => $mr->isSubmitted(),
            'is_completed' => $isCompleted,
            'can_be_submitted' => $user ? (bool) $mr->canBeSubmittedBy($user) : false,
            'can_be_approved' => $user ? (bool) $mr->canBeApprovedBy($user) : false,
            'steps' => $steps,
        ];
    }
}

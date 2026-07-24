<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MaterialInspection;
use App\Models\ProjectBoqItem;
use App\Models\SupplierReceiving;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RingleSoft\LaravelProcessApproval\Models\ProcessApprovalFlowStep;

/**
 * Mobile API for material inspections (quality checks on supplier receivings).
 * Mirrors web {@see \App\Http\Controllers\MaterialInspectionController}:
 * one inspection per receiving, fixed 6-key criteria checklist, auto-submit
 * into the RingleSoft 2-step flow (Site Supervisor VERIFY -> Managing
 * Director APPROVE); onApprovalCompleted runs updateStock() on the model
 * (inventory increment + MM-#### movement + BOQ quantity_received rollup).
 */
class MaterialInspectionController extends Controller
{
    /** Fixed criteria checklist keys (same as web create_inspection form). */
    private const CRITERIA_CHECKLIST = [
        'packaging_intact' => 'Packaging is intact and undamaged',
        'quantity_correct' => 'Quantity matches delivery note',
        'specification_match' => 'Specifications match order requirements',
        'no_visible_defects' => 'No visible defects or damage',
        'proper_labeling' => 'Proper labeling and documentation',
        'storage_suitable' => 'Materials are suitable for storage',
    ];

    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->can('Material Inspections')) {
            return $this->permissionDenied();
        }

        try {
            $query = MaterialInspection::with(['supplierReceiving.supplier', 'project', 'inspector', 'approvalStatus'])
                ->orderBy('created_at', 'desc');

            if ($request->project_id) {
                $query->where('project_id', $request->project_id);
            }

            if ($request->status) {
                // Statuses are case-inconsistent on the web side ('pending' vs 'APPROVED').
                $query->whereRaw('UPPER(status) = ?', [strtoupper($request->status)]);
            }

            if ($request->start_date) {
                $query->whereDate('inspection_date', '>=', $request->start_date);
            }

            if ($request->end_date) {
                $query->whereDate('inspection_date', '<=', $request->end_date);
            }

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('inspection_number', 'like', "%{$search}%")
                      ->orWhereHas('supplierReceiving', function ($srq) use ($search) {
                          $srq->where('receiving_number', 'like', "%{$search}%");
                      })
                      ->orWhereHas('project', function ($pq) use ($search) {
                          $pq->where('name', 'like', "%{$search}%")
                            ->orWhere('project_name', 'like', "%{$search}%");
                      });
                });
            }

            $inspections = $query->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => collect($inspections->items())
                        ->map(fn($i) => $this->formatInspection($i))
                        ->values(),
                    'pending_receivings' => SupplierReceiving::with(['supplier', 'purchase.project'])
                        ->pendingInspection()
                        ->orderByDesc('date')
                        ->limit(20)
                        ->get()
                        ->map(fn($receiving) => $this->formatPendingReceiving($receiving))
                        ->values(),
                    'meta' => [
                        'current_page' => $inspections->currentPage(),
                        'last_page' => $inspections->lastPage(),
                        'per_page' => $inspections->perPage(),
                        'total' => $inspections->total(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('MaterialInspection index error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch material inspections: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->can('Material Inspections')) {
            return $this->permissionDenied();
        }

        try {
            $inspection = MaterialInspection::with([
                'supplierReceiving.supplier',
                'supplierReceiving.purchase',
                'project',
                'boqItem',
                'inspector',
                'verifier',
                'approvalStatus',
                'approvals.user',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatInspection($inspection, true),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Material inspection not found.',
            ], 404);
        } catch (\Throwable $e) {
            Log::error('MaterialInspection show error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch material inspection: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/procurement/inspections/receivings/{receivingId}/create-data
     * Everything the mobile create-inspection form needs: receiving summary,
     * BOQ item choices (from the PO items, falling back to the project's BOQ)
     * and the fixed criteria checklist. Mirrors web create($receivingId).
     */
    public function createData(Request $request, int $receivingId): JsonResponse
    {
        if (!$request->user()->can('Material Inspections')) {
            return $this->permissionDenied();
        }

        try {
            $receiving = SupplierReceiving::with([
                'supplier',
                'purchase.project',
                'purchase.purchaseItems.boqItem',
            ])->findOrFail($receivingId);

            // One inspection per receiving (web redirects to the existing one).
            $existing = $receiving->inspections()->first();

            $boqItems = collect();
            if ($receiving->purchase) {
                $boqItems = $receiving->purchase->purchaseItems
                    ->pluck('boqItem')
                    ->filter()
                    ->unique('id')
                    ->values();
            }
            if ($boqItems->isEmpty() && $receiving->project_id) {
                $boqItems = ProjectBoqItem::byProject($receiving->project_id)->get();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'receiving' => [
                        'id' => $receiving->id,
                        'receiving_number' => $receiving->receiving_number,
                        'delivery_note_number' => $receiving->delivery_note_number,
                        'date' => $receiving->date?->format('Y-m-d'),
                        'condition' => $receiving->condition,
                        'status' => $receiving->status,
                        'quantity_ordered' => (float) ($receiving->quantity_ordered ?? 0),
                        'quantity_delivered' => (float) ($receiving->quantity_delivered ?? 0),
                        'project_id' => $receiving->project_id ?? $receiving->purchase?->project_id,
                        'project_name' => $receiving->project?->project_name
                            ?? $receiving->purchase?->project?->project_name,
                        'supplier_name' => $receiving->supplier?->name,
                        'purchase_number' => $receiving->purchase?->document_number
                            ?? ($receiving->purchase_id ? 'PO-' . $receiving->purchase_id : null),
                    ],
                    'existing_inspection_id' => $existing?->id,
                    'boq_items' => $boqItems->map(fn($item) => [
                        'id' => $item->id,
                        'item_code' => $item->item_code,
                        'description' => $item->description,
                        'unit' => $item->unit,
                    ])->values(),
                    'criteria_checklist' => self::CRITERIA_CHECKLIST,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier receiving not found.',
            ], 404);
        } catch (\Throwable $e) {
            Log::error('MaterialInspection createData error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load inspection form data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/procurement/inspections
     * Creates an inspection from a receiving and auto-submits it for approval
     * (mirrors web store: status 'pending', receiving -> 'received',
     * RingleSoft submit). quantity_rejected and overall_result are recomputed
     * by the model's saving hook.
     */
    public function store(Request $request): JsonResponse
    {
        if (!$request->user()->can('Add Material Inspection')) {
            return $this->permissionDenied();
        }

        try {
            $validated = $request->validate([
                'supplier_receiving_id' => 'required|exists:supplier_receivings,id',
                'project_id' => 'required|exists:projects,id',
                'boq_item_id' => 'nullable|exists:project_boq_items,id',
                'quantity_delivered' => 'required|numeric|min:0',
                'quantity_accepted' => 'required|numeric|min:0|lte:quantity_delivered',
                'overall_condition' => 'required|in:excellent,good,acceptable,poor,rejected',
                'rejection_reason' => 'nullable|string',
                'inspection_notes' => 'nullable|string',
                'criteria_checklist' => 'nullable|array',
            ]);

            $receiving = SupplierReceiving::findOrFail($validated['supplier_receiving_id']);

            // One inspection per receiving.
            $existing = $receiving->inspections()->first();
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => "An inspection already exists for this delivery ({$existing->inspection_number}).",
                    'data' => ['existing_inspection_id' => $existing->id],
                ], 422);
            }

            // Collect the fixed checklist keys into a bool map. Unknown keys
            // are dropped; missing keys default to false.
            $rawChecklist = $validated['criteria_checklist'] ?? [];
            $criteriaChecklist = [];
            foreach (array_keys(self::CRITERIA_CHECKLIST) as $key) {
                $value = $rawChecklist[$key] ?? $request->input("criteria_{$key}", false);
                $criteriaChecklist[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }

            $inspection = DB::transaction(function () use ($validated, $criteriaChecklist, $receiving, $request) {
                $inspection = MaterialInspection::create([
                    'supplier_receiving_id' => $validated['supplier_receiving_id'],
                    'project_id' => $validated['project_id'],
                    'boq_item_id' => $validated['boq_item_id'] ?? null,
                    'inspection_date' => now(),
                    'quantity_delivered' => $validated['quantity_delivered'],
                    'quantity_accepted' => $validated['quantity_accepted'],
                    'quantity_rejected' => $validated['quantity_delivered'] - $validated['quantity_accepted'],
                    'overall_condition' => $validated['overall_condition'],
                    'rejection_reason' => $validated['rejection_reason'] ?? null,
                    'inspection_notes' => $validated['inspection_notes'] ?? null,
                    'criteria_checklist' => $criteriaChecklist,
                    'inspector_id' => $request->user()->id,
                    'status' => 'pending',
                ]);

                // Update receiving status (mirrors web).
                $receiving->status = 'received';
                $receiving->save();

                // Auto-submit into the approval flow (Supervisor VERIFY -> MD APPROVE).
                $inspection->submit($request->user());

                return $inspection;
            });

            $inspection->refresh()->load([
                'supplierReceiving.supplier',
                'supplierReceiving.purchase',
                'project',
                'boqItem',
                'inspector',
                'verifier',
                'approvalStatus',
                'approvals.user',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Material inspection created: ' . $inspection->inspection_number,
                'data' => $this->formatInspection($inspection, true),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('MaterialInspection store error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create material inspection: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/procurement/inspections/{id}/approve
     * Advances the RingleSoft flow one step (Site Supervisor VERIFY, then
     * Managing Director APPROVE — the package enforces step order/roles).
     * Completing the flow runs onApprovalCompleted: status APPROVED,
     * receiving -> 'inspected', updateStock().
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $inspection = MaterialInspection::with('approvalStatus')->findOrFail($id);

            if (!$inspection->canBeApprovedBy($request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to approve this inspection at the current step.',
                ], 403);
            }

            $ok = $inspection->approve($request->input('comment'), $request->user());

            if ($ok === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to approve this inspection at the current step.',
                ], 403);
            }

            $inspection->refresh()->load([
                'supplierReceiving.supplier',
                'supplierReceiving.purchase',
                'project',
                'boqItem',
                'inspector',
                'verifier',
                'approvalStatus',
                'approvals.user',
            ]);

            return response()->json([
                'success' => true,
                'message' => $inspection->isApproved()
                    ? 'Inspection approved. Stock has been updated.'
                    : 'Inspection verified. Awaiting final approval.',
                'data' => $this->formatInspection($inspection, true),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Material inspection not found.',
            ], 404);
        } catch (\Throwable $e) {
            Log::error('MaterialInspection approve error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve material inspection: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/procurement/inspections/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate(['reason' => 'required|string|max:500']);

            $inspection = MaterialInspection::with('approvalStatus')->findOrFail($id);

            if (!$inspection->canBeApprovedBy($request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to reject this inspection at the current step.',
                ], 403);
            }

            $ok = $inspection->reject($request->reason, $request->user());

            if ($ok === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to reject this inspection at the current step.',
                ], 403);
            }

            $inspection->refresh()->load([
                'supplierReceiving.supplier',
                'supplierReceiving.purchase',
                'project',
                'boqItem',
                'inspector',
                'verifier',
                'approvalStatus',
                'approvals.user',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inspection rejected.',
                'data' => $this->formatInspection($inspection, true),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Material inspection not found.',
            ], 404);
        } catch (\Throwable $e) {
            Log::error('MaterialInspection reject error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject material inspection: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/procurement/inspections/{id}/update-stock
     * Manual re-run for approved inspections that missed the automatic stock
     * update (mirrors web updateStock: must be approved and not yet updated).
     */
    public function updateStock(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->can('Material Inspections')) {
            return $this->permissionDenied();
        }

        try {
            $inspection = MaterialInspection::findOrFail($id);

            if (!$inspection->isApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved inspections can update stock.',
                ], 422);
            }

            if ($inspection->stock_updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock has already been updated for this inspection.',
                ], 422);
            }

            $inspection->updateStock();

            $inspection->refresh()->load([
                'supplierReceiving.supplier',
                'supplierReceiving.purchase',
                'project',
                'boqItem',
                'inspector',
                'verifier',
                'approvalStatus',
                'approvals.user',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stock updated successfully.',
                'data' => $this->formatInspection($inspection, true),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Material inspection not found.',
            ], 404);
        } catch (\Throwable $e) {
            Log::error('MaterialInspection updateStock error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function permissionDenied(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to perform this action.',
        ], 403);
    }

    private function formatPendingReceiving(SupplierReceiving $receiving): array
    {
        return [
            'id' => $receiving->id,
            'receiving_number' => $receiving->receiving_number,
            'supplier_name' => $receiving->supplier?->name,
            'delivery_date' => $receiving->date?->format('Y-m-d'),
            'quantity_delivered' => $receiving->quantity_delivered,
            'condition' => $receiving->condition,
            'purchase_number' => $receiving->purchase?->document_number ?? ('PO-' . $receiving->purchase_id),
            'project_name' => $receiving->purchase?->project?->project_name
                ?? $receiving->purchase?->project?->name,
        ];
    }

    private function formatInspection($inspection, bool $full = false): array
    {
        $data = [
            'id' => $inspection->id,
            'inspection_number' => $inspection->inspection_number,
            'supplier_receiving_id' => $inspection->supplier_receiving_id,
            'supplier_receiving' => $inspection->supplierReceiving ? [
                'id' => $inspection->supplierReceiving->id,
                'supplier_name' => $inspection->supplierReceiving->supplier?->name ?? null,
                'receiving_number' => $inspection->supplierReceiving->receiving_number,
                'delivery_note_number' => $inspection->supplierReceiving->delivery_note_number,
                'status' => $inspection->supplierReceiving->status,
                'purchase_number' => $inspection->supplierReceiving->purchase?->document_number
                    ?? ($inspection->supplierReceiving->purchase_id
                        ? 'PO-' . $inspection->supplierReceiving->purchase_id
                        : null),
            ] : null,
            'project_id' => $inspection->project_id,
            'project_name' => $inspection->project?->project_name ?? $inspection->project?->name,
            'boq_item' => $inspection->boqItem ? [
                'id' => $inspection->boqItem->id,
                'description' => $inspection->boqItem->description,
                'item_code' => $inspection->boqItem->item_code,
                'unit' => $inspection->boqItem->unit,
            ] : null,
            'inspector_id' => $inspection->inspector_id,
            'inspector_name' => $inspection->inspector?->name ?? null,
            'inspection_date' => $inspection->inspection_date?->format('Y-m-d'),
            'quantity_delivered' => $inspection->quantity_delivered,
            'quantity_accepted' => $inspection->quantity_accepted,
            'quantity_rejected' => $inspection->quantity_rejected,
            'acceptance_rate' => $inspection->acceptance_rate,
            'status' => $inspection->status,
            'approval_status' => $inspection->approvalStatus?->status,
            'overall_condition' => $inspection->overall_condition,
            'overall_result' => $inspection->overall_result,
            'rejection_reason' => $inspection->rejection_reason,
            'notes' => $inspection->inspection_notes,
            'stock_updated' => (bool) $inspection->stock_updated,
            'created_at' => $inspection->created_at?->toISOString(),
        ];

        if ($full) {
            $data['verifier_name'] = $inspection->verifier?->name;
            $data['criteria_checklist'] = $inspection->criteria_checklist;
            $data['criteria_labels'] = self::CRITERIA_CHECKLIST;
            $data['stock_updated_at'] = $inspection->stock_updated_at?->toISOString();
            $data['can_update_stock'] = $inspection->isApproved()
                && !$inspection->stock_updated
                && (float) $inspection->quantity_accepted > 0;
            $data['approval_flow'] = $this->buildApprovalFlow($inspection);
        }

        return $data;
    }

    /**
     * Approval-flow readout (same shape as PurchaseApiController) so the
     * mobile app can gate verify/approve/reject buttons on server flags
     * instead of hardcoding step roles.
     */
    private function buildApprovalFlow(MaterialInspection $inspection): array
    {
        $steps = collect($inspection->approvalStatus?->steps ?? [])->map(function ($step) use ($inspection) {
            $flowStep = ProcessApprovalFlowStep::with('role')->find($step['id'] ?? null);
            $approval = null;
            if (!empty($step['process_approval_id']) && $inspection->relationLoaded('approvals')) {
                $approval = $inspection->approvals->firstWhere('id', $step['process_approval_id']);
            }

            return [
                'step_id' => $step['id'] ?? null,
                'role_name' => $flowStep?->role?->name ?? ('Step ' . ($step['id'] ?? '')),
                'step_action' => $flowStep?->action,
                'action' => $step['process_approval_action'] ?? 'Pending',
                'approver_name' => $approval?->user?->name ?? $approval?->approver_name ?? null,
                'date' => $approval?->created_at?->format('d F, Y') ?? null,
                'comment' => $approval?->comment ?? null,
            ];
        })->values();

        $nextStep = $inspection->nextApprovalStep();
        $isCompleted = $inspection->isApprovalCompleted();
        $user = auth()->user();

        return [
            'status_label' => $isCompleted
                ? 'Approval completed!'
                : ($inspection->isSubmitted() ? 'In Progress' : 'Not Submitted'),
            'next_role_name' => $nextStep?->role?->name,
            'next_action' => $nextStep?->action,
            'is_submitted' => $inspection->isSubmitted(),
            'is_completed' => $isCompleted,
            'can_be_submitted' => $user ? (bool) $inspection->canBeSubmittedBy($user) : false,
            'can_be_approved' => $user ? (bool) $inspection->canBeApprovedBy($user) : false,
            'steps' => $steps,
        ];
    }
}

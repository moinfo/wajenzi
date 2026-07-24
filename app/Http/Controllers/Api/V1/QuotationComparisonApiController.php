<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\QuotationComparison;
use App\Models\ProjectMaterialRequest;
use App\Models\SupplierQuotation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Mobile API for quotation comparisons (create / approve / reject / generate PO).
 *
 * Read endpoints (index / show) are served by {@see ProcurementController}.
 * Approval actions defer to the RingleSoft trait and mirror the web behaviour;
 * they are NOT gated on the seeded "Approve Quotation Comparison" permission —
 * a 403 is returned when the package refuses the current user/step.
 */
class QuotationComparisonApiController extends Controller
{
    private const MINIMUM_QUOTATIONS = 3;

    /**
     * GET /procurement/quotation-comparisons/create-eligibility/{materialRequestId}
     *
     * Returns whether a comparison can be created for the request, plus the
     * non-rejected quotations and a per-item price matrix for the create UI.
     */
    public function createEligibility(Request $request, int $materialRequestId): JsonResponse
    {
        if (!$request->user()->can('Quotation Comparisons')) {
            return $this->permissionDenied();
        }

        try {
            $materialRequest = ProjectMaterialRequest::with(['project', 'items.boqItem'])
                ->findOrFail($materialRequestId);

            $quotations = SupplierQuotation::with(['supplier', 'items'])
                ->where('material_request_id', $materialRequestId)
                ->where('status', '!=', 'rejected')
                ->orderBy('total_amount')
                ->orderBy('created_at')
                ->get();

            $activeComparison = QuotationComparison::where('material_request_id', $materialRequestId)
                ->whereRaw("UPPER(status) IN ('PENDING','APPROVED')")
                ->first();

            $count = $quotations->count();
            $canCreate = true;
            $blockReason = null;

            if ($activeComparison) {
                $canCreate = false;
                $blockReason = 'An active comparison already exists for this request.';
            } elseif ($count < self::MINIMUM_QUOTATIONS) {
                $canCreate = false;
                $blockReason = 'At least '.self::MINIMUM_QUOTATIONS.' quotations are required (found '.$count.').';
            }

            // items[i] = one material-request line item
            $items = $materialRequest->items->map(fn ($mrItem) => [
                'material_request_item_id' => $mrItem->id,
                'boq_item_id' => $mrItem->boq_item_id,
                'description' => $mrItem->description ?? $mrItem->boqItem?->description ?? 'Material',
                'unit' => $mrItem->unit ?? $mrItem->boqItem?->unit,
                'quantity' => (float) ($mrItem->quantity_approved ?? $mrItem->quantity_requested),
            ])->values();

            // itemPriceMatrix[material_request_item_id][quotation_id] = {unit_price, total_price, quantity, unit}
            $matrix = [];
            $quotationList = $quotations->map(function ($quotation) use (&$matrix) {
                foreach ($quotation->items as $qItem) {
                    if ($qItem->material_request_item_id === null) {
                        continue;
                    }
                    $matrix[(string) $qItem->material_request_item_id][(string) $quotation->id] = [
                        'unit_price' => (float) $qItem->unit_price,
                        'total_price' => (float) $qItem->total_price,
                        'quantity' => (float) $qItem->quantity,
                        'unit' => $qItem->unit,
                    ];
                }

                return [
                    'id' => $quotation->id,
                    'quotation_number' => $quotation->quotation_number,
                    'supplier_id' => $quotation->supplier_id,
                    'supplier_name' => $quotation->supplier?->name,
                    'status' => $quotation->status,
                    'quotation_date' => $quotation->quotation_date?->toDateString(),
                    'valid_until' => $quotation->valid_until?->toDateString(),
                    'delivery_time_days' => $quotation->delivery_time_days,
                    'payment_terms' => $quotation->payment_terms,
                    'is_expired' => $quotation->isExpired(),
                    'total_amount' => (float) $quotation->total_amount,
                    'vat_amount' => (float) $quotation->vat_amount,
                    'grand_total' => (float) $quotation->grand_total,
                ];
            })->values();

            $grandTotals = $quotationList->pluck('grand_total');

            return response()->json([
                'success' => true,
                'data' => [
                    'can_create' => $canCreate,
                    'block_reason' => $blockReason,
                    'minimum_required' => self::MINIMUM_QUOTATIONS,
                    'quotation_count' => $count,
                    'material_request' => [
                        'id' => $materialRequest->id,
                        'request_number' => $materialRequest->request_number,
                        'project_name' => $materialRequest->project?->project_name
                            ?? $materialRequest->project?->name,
                        'status' => $materialRequest->status,
                    ],
                    'items' => $items,
                    'quotations' => $quotationList,
                    'item_price_matrix' => (object) $matrix,
                    'analysis' => [
                        'lowest' => $grandTotals->min() ?? 0,
                        'highest' => $grandTotals->max() ?? 0,
                        'average' => $grandTotals->isEmpty() ? 0 : round($grandTotals->avg(), 2),
                        'variance' => ($grandTotals->max() ?? 0) - ($grandTotals->min() ?? 0),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('QuotationComparison eligibility error: '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load comparison eligibility: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /procurement/quotation-comparisons
     */
    public function store(Request $request): JsonResponse
    {
        if (!$request->user()->can('Add Quotation Comparison')) {
            return $this->permissionDenied();
        }

        $validated = $request->validate([
            'material_request_id' => 'required|exists:project_material_requests,id',
            'selected_quotation_id' => 'required|exists:supplier_quotations,id',
            'recommendation_reason' => 'required|string|min:10',
        ]);

        try {
            $quotation = SupplierQuotation::where('id', $validated['selected_quotation_id'])
                ->where('material_request_id', $validated['material_request_id'])
                ->first();

            if (!$quotation) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected quotation does not belong to this material request.',
                ], 422);
            }

            $existing = QuotationComparison::where('material_request_id', $validated['material_request_id'])
                ->whereRaw("UPPER(status) IN ('PENDING','APPROVED')")
                ->exists();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'An active comparison already exists for this material request.',
                ], 422);
            }

            $nonRejectedCount = SupplierQuotation::where('material_request_id', $validated['material_request_id'])
                ->where('status', '!=', 'rejected')
                ->count();

            if ($nonRejectedCount < self::MINIMUM_QUOTATIONS) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least '.self::MINIMUM_QUOTATIONS.' quotations are required to create a comparison.',
                ], 422);
            }

            // Eloquent create → model boot auto-creates the RingleSoft approval
            // status in SUBMITTED state (single Managing Director APPROVE step).
            $comparison = QuotationComparison::create([
                'material_request_id' => $validated['material_request_id'],
                'comparison_date' => now(),
                'recommended_supplier_id' => $quotation->supplier_id,
                'selected_quotation_id' => $quotation->id,
                'recommendation_reason' => $validated['recommendation_reason'],
                'prepared_by' => $request->user()->id,
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Quotation comparison created and submitted for approval.',
                'data' => [
                    'id' => $comparison->id,
                    'comparison_number' => $comparison->comparison_number,
                    'status' => $comparison->status,
                ],
            ], 201);
        } catch (\Throwable $e) {
            Log::error('QuotationComparison store error: '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create quotation comparison: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /procurement/quotation-comparisons/{id}/approve
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $comparison = QuotationComparison::findOrFail($id);

            $ok = $comparison->approve($request->input('comment'));

            if ($ok === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to approve this comparison at the current step.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'Quotation comparison approved.',
                'data' => [
                    'id' => $comparison->id,
                    'status' => $comparison->fresh()?->status,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('QuotationComparison approve error: '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve quotation comparison: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /procurement/quotation-comparisons/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate(['reason' => 'required|string|max:500']);

            $comparison = QuotationComparison::findOrFail($id);
            $ok = $comparison->reject($request->reason);

            if ($ok === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to reject this comparison at the current step.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'Quotation comparison rejected.',
                'data' => [
                    'id' => $comparison->id,
                    'status' => $comparison->fresh()?->status,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('QuotationComparison reject error: '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject quotation comparison: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /procurement/quotation-comparisons/{id}/create-purchase
     */
    public function createPurchase(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->can('Quotation Comparisons')) {
            return $this->permissionDenied();
        }

        try {
            $comparison = QuotationComparison::findOrFail($id);

            if (!$comparison->isApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved comparisons can generate purchase orders.',
                ], 422);
            }

            if ($comparison->purchases()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'A purchase order already exists for this comparison.',
                ], 422);
            }

            $purchase = Purchase::createFromComparison($comparison, $request->user()->id);

            if (!$purchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate the purchase order.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Purchase order created.',
                'data' => [
                    'purchase_id' => $purchase->id,
                    'document_number' => $purchase->document_number ?? ('PO-'.$purchase->id),
                ],
            ], 201);
        } catch (\Throwable $e) {
            Log::error('QuotationComparison createPurchase error: '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase order: '.$e->getMessage(),
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
}

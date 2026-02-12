<?php

namespace App\Http\Controllers;

use App\Models\ProjectMaterialRequest;
use App\Models\Purchase;
use App\Models\QuotationComparison;
use App\Models\SupplierQuotation;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuotationComparisonController extends Controller
{
    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * Display listing of comparisons
     */
    public function index(Request $request)
    {
        if ($this->handleCrud($request, 'QuotationComparison')) {
            return back();
        }

        $start_date = $request->input('start_date') ?? date('Y-m-01');
        $end_date = $request->input('end_date') ?? date('Y-m-d');

        $comparisons = QuotationComparison::with([
                'materialRequest.project',
                'selectedQuotation.supplier',
                'preparedBy'
            ])
            ->whereBetween('comparison_date', [$start_date, $end_date])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.procurement.quotation_comparisons')->with([
            'comparisons' => $comparisons,
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
    }

    /**
     * Show form to create new comparison
     */
    public function create($materialRequestId)
    {
        $materialRequest = ProjectMaterialRequest::with(['project', 'items.boqItem', 'requester'])
            ->findOrFail($materialRequestId);

        // Check if already has pending/approved comparison
        $existingComparison = $materialRequest->comparisons()
            ->whereIn('status', ['pending', 'approved', 'APPROVED'])
            ->first();

        if ($existingComparison) {
            return redirect()->route('quotation_comparison', [
                'id' => $existingComparison->id,
                'document_type_id' => 0
            ])->with('info', 'A comparison already exists for this request');
        }

        $quotations = SupplierQuotation::with(['supplier', 'items'])
            ->where('material_request_id', $materialRequestId)
            ->where('status', '!=', 'rejected')
            ->orderBy('grand_total', 'asc')
            ->get();

        if ($quotations->count() < 3) {
            return back()->with('error', 'At least 3 quotations are required. Currently have: ' . $quotations->count());
        }

        $analysis = [
            'lowest' => $quotations->first(),
            'highest' => $quotations->last(),
            'average' => $quotations->avg('grand_total'),
            'variance' => $quotations->last()->grand_total - $quotations->first()->grand_total,
        ];

        // Build per-item comparison matrix: mrItemId => [quotation_id => unit_price]
        $itemPriceMatrix = [];
        foreach ($quotations as $q) {
            foreach ($q->items as $qItem) {
                $itemPriceMatrix[$qItem->material_request_item_id][$q->id] = $qItem;
            }
        }

        return view('pages.procurement.create_comparison')->with([
            'materialRequest' => $materialRequest,
            'quotations' => $quotations,
            'analysis' => $analysis,
            'itemPriceMatrix' => $itemPriceMatrix,
        ]);
    }

    /**
     * Store new comparison
     */
    public function store(Request $request)
    {
        $request->validate([
            'material_request_id' => 'required|exists:project_material_requests,id',
            'selected_quotation_id' => 'required|exists:supplier_quotations,id',
            'recommendation_reason' => 'required|string|min:10'
        ]);

        $materialRequest = ProjectMaterialRequest::findOrFail($request->material_request_id);

        // Verify quotation belongs to this request
        $quotation = SupplierQuotation::where('id', $request->selected_quotation_id)
            ->where('material_request_id', $request->material_request_id)
            ->firstOrFail();

        try {
            DB::beginTransaction();

            $comparison = QuotationComparison::create([
                'material_request_id' => $request->material_request_id,
                'comparison_date' => now(),
                'selected_quotation_id' => $request->selected_quotation_id,
                'recommended_supplier_id' => $quotation->supplier_id,
                'recommendation_reason' => $request->recommendation_reason,
                'prepared_by' => auth()->id(),
                'status' => 'pending'
            ]);
            // Approval status auto-created with SUBMITTED status via model boot

            DB::commit();

            return redirect()->route('quotation_comparisons')
                ->with('success', 'Quotation comparison created and submitted for approval');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create comparison: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show comparison details with approval workflow
     */
    public function comparison($id, $document_type_id)
    {
        $this->approvalService->markNotificationAsRead($id, $document_type_id, 'quotation_comparison');

        $comparison = QuotationComparison::with([
            'materialRequest.project',
            'materialRequest.items.boqItem',
            'selectedQuotation.supplier',
            'preparedBy',
            'approvedBy'
        ])->findOrFail($id);

        $quotations = SupplierQuotation::with(['supplier', 'items.boqItem'])
            ->where('material_request_id', $comparison->material_request_id)
            ->orderBy('grand_total', 'asc')
            ->get();

        $details = [
            'Comparison Number' => $comparison->comparison_number,
            'Material Request' => $comparison->materialRequest->request_number,
            'Project' => $comparison->materialRequest->project->name ?? 'N/A',
            'Comparison Date' => $comparison->comparison_date?->format('Y-m-d'),
            'Selected Supplier' => $comparison->selectedQuotation?->supplier?->name,
            'Selected Amount' => number_format($comparison->selectedQuotation?->grand_total ?? 0, 2),
            'Quotations Compared' => $quotations->count(),
            'Prepared By' => $comparison->preparedBy?->name
        ];

        return view('approvals._approve_page')->with([
            'approval_data' => $comparison,
            'document_id' => $id,
            'approval_document_type_id' => $document_type_id,
            'page_name' => 'Quotation Comparison',
            'approval_data_name' => $comparison->comparison_number,
            'details' => $details,
            'model' => 'QuotationComparison',
            'route' => 'quotation_comparison',
            'quotations' => $quotations,
            'recommendation_reason' => $comparison->recommendation_reason
        ]);
    }

    /**
     * Create purchase order from approved comparison
     */
    public function createPurchase($id)
    {
        $comparison = QuotationComparison::with(['materialRequest', 'selectedQuotation'])
            ->findOrFail($id);

        if (!$comparison->isApproved()) {
            return back()->with('error', 'Only approved comparisons can generate purchase orders');
        }

        if ($comparison->purchases()->exists()) {
            return back()->with('error', 'A purchase order already exists for this comparison');
        }

        try {
            $purchase = Purchase::createFromComparison($comparison);

            if ($purchase) {
                return redirect()->route('purchase_orders')
                    ->with('success', 'Purchase order created: ' . ($purchase->document_number ?? 'PO-' . $purchase->id));
            }

            return back()->with('error', 'Failed to create purchase order');

        } catch (\Exception $e) {
            return back()->with('error', 'Error creating purchase: ' . $e->getMessage());
        }
    }

    /**
     * Approval actions
     */
    public function submit(QuotationComparison $comparison)
    {
        if (!$comparison->canBeSubmitted()) {
            return back()->with('error', 'Comparison cannot be submitted. Ensure at least 3 quotations and a selection is made.');
        }

        $comparison->status = 'pending';
        $comparison->save();
        $comparison->submit();

        return back()->with('success', 'Comparison submitted for approval');
    }

    public function approve(QuotationComparison $comparison)
    {
        $comparison->approve();
        return back()->with('success', 'Comparison approved');
    }

    public function reject(QuotationComparison $comparison, Request $request)
    {
        $comparison->reject($request->reason);
        return back()->with('success', 'Comparison rejected');
    }
}

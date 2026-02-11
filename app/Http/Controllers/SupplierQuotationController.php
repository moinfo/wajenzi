<?php

namespace App\Http\Controllers;

use App\Models\ProjectMaterialRequest;
use App\Models\Supplier;
use App\Models\SupplierQuotation;
use Illuminate\Http\Request;

class SupplierQuotationController extends Controller
{
    /**
     * Display listing of quotations
     */
    public function index(Request $request)
    {
        if ($this->handleCrud($request, 'SupplierQuotation')) {
            return back();
        }

        $start_date = $request->input('start_date') ?? date('Y-m-01');
        $end_date = $request->input('end_date') ?? date('Y-m-d');

        $quotations = SupplierQuotation::with(['materialRequest.project', 'supplier', 'createdBy'])
            ->whereBetween('quotation_date', [$start_date, $end_date])
            ->orderBy('created_at', 'desc')
            ->get();

        $suppliers = Supplier::orderBy('name')->get();
        $materialRequests = ProjectMaterialRequest::whereRaw('UPPER(status) = ?', ['APPROVED'])
            ->with('project')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.procurement.supplier_quotations')->with([
            'quotations' => $quotations,
            'suppliers' => $suppliers,
            'materialRequests' => $materialRequests,
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
    }

    /**
     * Display quotations for a specific material request
     */
    public function byRequest(Request $request, $id)
    {
        if ($this->handleCrud($request, 'SupplierQuotation')) {
            return back();
        }

        $materialRequest = ProjectMaterialRequest::with(['project', 'items.boqItem', 'requester'])
            ->findOrFail($id);

        $quotations = SupplierQuotation::with(['supplier', 'createdBy'])
            ->where('material_request_id', $id)
            ->orderBy('grand_total', 'asc')
            ->get();

        $suppliers = Supplier::orderBy('name')->get();

        // Check if comparison can be created
        $canCreateComparison = $quotations->count() >= 3 &&
                               !$materialRequest->comparisons()->whereIn('status', ['pending', 'approved', 'APPROVED'])->exists();

        return view('pages.procurement.request_quotations')->with([
            'materialRequest' => $materialRequest,
            'quotations' => $quotations,
            'suppliers' => $suppliers,
            'canCreateComparison' => $canCreateComparison,
            'quotationCount' => $quotations->count(),
            'minimumRequired' => 3
        ]);
    }

    /**
     * Get quotation details for AJAX
     */
    public function show($id)
    {
        $quotation = SupplierQuotation::with(['materialRequest.project', 'supplier', 'createdBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'quotation' => $quotation
        ]);
    }

    /**
     * Compare quotations for a material request
     */
    public function compare($materialRequestId)
    {
        $materialRequest = ProjectMaterialRequest::with(['project', 'items.boqItem'])
            ->findOrFail($materialRequestId);

        $quotations = SupplierQuotation::with('supplier')
            ->where('material_request_id', $materialRequestId)
            ->where('status', '!=', 'rejected')
            ->orderBy('grand_total', 'asc')
            ->get();

        if ($quotations->count() < 3) {
            return back()->with('error', 'At least 3 quotations are required for comparison');
        }

        $analysis = [
            'lowest' => $quotations->first(),
            'highest' => $quotations->last(),
            'average' => $quotations->avg('grand_total'),
            'variance' => $quotations->last()->grand_total - $quotations->first()->grand_total,
            'count' => $quotations->count()
        ];

        return view('pages.procurement.quotation_compare')->with([
            'materialRequest' => $materialRequest,
            'quotations' => $quotations,
            'analysis' => $analysis
        ]);
    }

    /**
     * Get suppliers for a request (excluding those with existing quotations)
     */
    public function availableSuppliers($materialRequestId)
    {
        $existingSupplierIds = SupplierQuotation::where('material_request_id', $materialRequestId)
            ->pluck('supplier_id');

        $suppliers = Supplier::whereNotIn('id', $existingSupplierIds)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'suppliers' => $suppliers
        ]);
    }
}

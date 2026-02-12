<?php

namespace App\Http\Controllers;

use App\Models\ProjectMaterialRequest;
use App\Models\Supplier;
use App\Models\SupplierQuotation;
use App\Models\SupplierQuotationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $quotations = SupplierQuotation::with(['supplier', 'createdBy', 'items'])
            ->where('material_request_id', $id)
            ->orderBy('grand_total', 'asc')
            ->get();

        $suppliers = Supplier::orderBy('name')->get();

        // Check if comparison can be created
        $approvedComparison = $materialRequest->comparisons()
            ->whereRaw('UPPER(status) = ?', ['APPROVED'])
            ->first();

        $canCreateComparison = !$approvedComparison &&
                               $quotations->count() >= 3 &&
                               !$materialRequest->comparisons()->where('status', 'pending')->exists();

        return view('pages.procurement.request_quotations')->with([
            'materialRequest' => $materialRequest,
            'quotations' => $quotations,
            'suppliers' => $suppliers,
            'canCreateComparison' => $canCreateComparison,
            'approvedComparison' => $approvedComparison,
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
     * Store a new quotation with per-item pricing
     */
    public function store(Request $request)
    {
        $request->validate([
            'material_request_id' => 'required|exists:project_material_requests,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'quotation_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.material_request_item_id' => 'required',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $totalAmount = 0;
            $totalQuantity = 0;

            // Calculate totals from items
            foreach ($request->items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $totalAmount += $lineTotal;
                $totalQuantity += $item['quantity'];
            }

            $vatAmount = $request->input('vat_amount', 0) ?: 0;

            // Handle file upload
            $filePath = null;
            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('quotations', 'public');
                $filePath = '/storage/' . $filePath;
            }

            $quotation = SupplierQuotation::create([
                'material_request_id' => $request->material_request_id,
                'supplier_id' => $request->supplier_id,
                'quotation_date' => $request->quotation_date,
                'valid_until' => $request->valid_until,
                'delivery_time_days' => $request->delivery_time_days,
                'payment_terms' => $request->payment_terms,
                'quantity' => $totalQuantity,
                'unit_price' => $totalQuantity > 0 ? $totalAmount / $totalQuantity : 0,
                'total_amount' => $totalAmount,
                'vat_amount' => $vatAmount,
                'file' => $filePath,
                'notes' => $request->notes,
                'status' => 'received',
            ]);

            foreach ($request->items as $i => $itemData) {
                SupplierQuotationItem::create([
                    'supplier_quotation_id' => $quotation->id,
                    'material_request_item_id' => $itemData['material_request_item_id'],
                    'boq_item_id' => $itemData['boq_item_id'] ?? null,
                    'description' => $itemData['description'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'] ?? null,
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $itemData['quantity'] * $itemData['unit_price'],
                    'sort_order' => $i,
                ]);
            }
        });

        return back()->with('success', 'Quotation added successfully.');
    }

    /**
     * Update an existing quotation with per-item pricing
     */
    public function update(Request $request, $id)
    {
        $quotation = SupplierQuotation::findOrFail($id);

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'quotation_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.material_request_item_id' => 'required',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $quotation) {
            $totalAmount = 0;
            $totalQuantity = 0;

            foreach ($request->items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $totalAmount += $lineTotal;
                $totalQuantity += $item['quantity'];
            }

            $vatAmount = $request->input('vat_amount', 0) ?: 0;

            // Handle file upload
            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('quotations', 'public');
                $quotation->file = '/storage/' . $filePath;
            }

            $quotation->update([
                'supplier_id' => $request->supplier_id,
                'quotation_date' => $request->quotation_date,
                'valid_until' => $request->valid_until,
                'delivery_time_days' => $request->delivery_time_days,
                'payment_terms' => $request->payment_terms,
                'quantity' => $totalQuantity,
                'unit_price' => $totalQuantity > 0 ? $totalAmount / $totalQuantity : 0,
                'total_amount' => $totalAmount,
                'vat_amount' => $vatAmount,
                'notes' => $request->notes,
            ]);

            // Replace items
            $quotation->items()->delete();
            foreach ($request->items as $i => $itemData) {
                SupplierQuotationItem::create([
                    'supplier_quotation_id' => $quotation->id,
                    'material_request_item_id' => $itemData['material_request_item_id'],
                    'boq_item_id' => $itemData['boq_item_id'] ?? null,
                    'description' => $itemData['description'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'] ?? null,
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $itemData['quantity'] * $itemData['unit_price'],
                    'sort_order' => $i,
                ]);
            }
        });

        return back()->with('success', 'Quotation updated successfully.');
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

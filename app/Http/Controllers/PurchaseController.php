<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierReceiving;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        if($this->handleCrud($request, 'Purchase')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $suppliers = Supplier::all();
        $purchases = Purchase::with(['supplier', 'item'])
            ->whereNotNull('item_id')
            ->where('date','>=',$start_date)->where('date','<=',$end_date)->get();
        $data = [
            'suppliers' => $suppliers,
            'purchases' => $purchases
        ];
        return view('pages.purchases.purchases_index')->with($data);
    }

    public function purchaseOrders(Request $request)
    {
        if ($this->handleCrud($request, 'Purchase')) {
            return back();
        }

        $start_date = $request->input('start_date') ?? date('Y-m-01');
        $end_date = $request->input('end_date') ?? date('Y-m-d');

        $purchaseOrders = Purchase::with([
                'supplier', 'project', 'materialRequest', 'quotationComparison',
                'purchaseItems.boqItem', 'user'
            ])
            ->whereNotNull('material_request_id')
            ->whereBetween('date', [$start_date, $end_date])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.procurement.purchase_orders')->with([
            'purchaseOrders' => $purchaseOrders,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ]);
    }

    public function purchaseOrderDetail($id, $document_type_id)
    {
        $this->approvalService->markNotificationAsRead($id, $document_type_id, 'purchase');

        $purchase = Purchase::with([
            'supplier', 'project', 'materialRequest.items.boqItem',
            'quotationComparison.selectedQuotation', 'purchaseItems.boqItem', 'user'
        ])->findOrFail($id);

        $details = [
            'Document Number' => $purchase->document_number ?? 'PO-' . $purchase->id,
            'Project' => $purchase->project?->name ?? 'N/A',
            'Supplier' => $purchase->supplier?->name ?? 'N/A',
            'Material Request' => $purchase->materialRequest?->request_number ?? 'N/A',
            'Comparison' => $purchase->quotationComparison?->comparison_number ?? 'N/A',
            'Date' => $purchase->date,
            'Items' => $purchase->purchaseItems->count() . ' item(s)',
            'Subtotal' => number_format($purchase->amount_vat_exc, 2),
            'VAT (18%)' => number_format($purchase->vat_amount, 2),
            'Total Amount' => number_format($purchase->total_amount, 2),
            'Payment Terms' => $purchase->payment_terms ?? 'N/A',
            'Expected Delivery' => $purchase->expected_delivery_date ?? 'N/A',
            'Created By' => $purchase->user?->name ?? 'N/A',
        ];

        return view('approvals._approve_page')->with([
            'approval_data' => $purchase,
            'document_id' => $id,
            'approval_document_type_id' => $document_type_id,
            'page_name' => 'Purchase Order',
            'approval_data_name' => $purchase->supplier?->name ?? ('PO-' . $purchase->id),
            'details' => $details,
            'model' => 'Purchase',
            'route' => 'purchase_order',
            'purchaseItems' => $purchase->purchaseItems,
        ]);
    }

    public function purchase($id,$document_type_id){
        // Mark notification as read
        $this->approvalService->markNotificationAsRead($id, $document_type_id,'purchase');

        $approval_data = \App\Models\Purchase::where('id',$id)->get()->first();
        $document_id = $id;

        $details = [
            'Supplier' => $approval_data->supplier?->name ?? '-',
            'Supplier VRN' => $approval_data->supplier?->vrn ?? '-',
            'Tax Invoice' => $approval_data->tax_invoice,
            'Invoice Date' => $approval_data->invoice_date,
            'Goods' => $approval_data->item?->name ?? ($approval_data->document_number ?? '-'),
            'Total Amount' => number_format($approval_data->total_amount),
            'Amount VAT EXC' => number_format($approval_data->amount_vat_exc),
            'Date' => $approval_data->date,
            'VAT Amount' => number_format($approval_data->vat_amount),
            'Uploaded File' => $approval_data->file
        ];

        // Load purchase items for procurement-linked purchases
        $purchaseItems = $approval_data->purchaseItems()->with('boqItem')->get();

        $data = [
            'approval_data' => $approval_data,
            'document_id' => $document_id,
            'approval_document_type_id' => $document_type_id,
            'page_name' => 'Purchases',
            'approval_data_name' => $approval_data->supplier?->name ?? $approval_data->document_number ?? 'Purchase #'.$id,
            'details' => $details,
            'model' => 'Purchase',
            'route' => 'purchase',
            'purchaseItems' => $purchaseItems,
        ];
        return view('approvals._approve_page')->with($data);
    }

//    public function purchase($id,$document_type_id){
//        $purchase = \App\Models\Purchase::where('id',$id)->get()->first();
//        $approvalStages = Approval::getApprovalStages($id,$document_type_id);
//        $nextApproval = Approval::getNextApproval($id,$document_type_id);
//        $approvalCompleted = Approval::isApprovalCompleted($id,$document_type_id);
//        $rejected = Approval::isRejected($id,$document_type_id);
//        $document_id = $id;
//        $data = [
//            'purchase' => $purchase,
//            'approvalStages' => $approvalStages,
//            'nextApproval' => $nextApproval,
//            'approvalCompleted' => $approvalCompleted,
//            'rejected' => $rejected,
//            'document_id' => $document_id,
//        ];
//        return view('pages.purchases.purchase')->with($data);
//    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function show(Purchase $purchase)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function edit(Purchase $purchase)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Purchase $purchase)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function destroy(Purchase $purchase)
    {
        //
    }

    // Add approval-specific methods
    public function submit(Purchase $purchase)
    {
        $purchase->submit();
        $this->notify('Purchase submitted for approval', 'Success', 'success');
        return redirect()->back();
    }

    public function approve(Purchase $purchase)
    {
        $purchase->approve();
        $this->notify('Purchase approved', 'Success', 'success');
        return redirect()->back();
    }

    public function reject(Purchase $purchase, Request $request)
    {
        $purchase->reject($request->reason);
        $this->notify('Purchase rejected', 'Success', 'success');
        return redirect()->back();
    }

    public function pendingDeliveries(Request $request)
    {
        $purchaseOrders = Purchase::with(['supplier', 'project', 'materialRequest', 'purchaseItems'])
            ->whereNotNull('material_request_id')
            ->whereRaw('UPPER(status) = ?', ['APPROVED'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(fn($po) => $po->purchaseItems->contains(fn($i) => !$i->isFullyReceived()));

        return view('pages.procurement.record_deliveries')->with([
            'purchaseOrders' => $purchaseOrders,
        ]);
    }

    public function recordDelivery($id)
    {
        $purchase = Purchase::with(['supplier', 'project', 'purchaseItems.boqItem'])
            ->findOrFail($id);

        if (strtoupper($purchase->status) !== 'APPROVED') {
            $this->notify('Only approved purchase orders can receive deliveries.', 'Error', 'error');
            return redirect()->route('purchase_orders');
        }

        $hasPendingItems = $purchase->purchaseItems->contains(fn($item) => !$item->isFullyReceived());
        if (!$hasPendingItems) {
            $this->notify('All items in this purchase order have been fully received.', 'Info', 'info');
            return redirect()->route('purchase_orders');
        }

        return view('pages.procurement.record_delivery')->with([
            'purchase' => $purchase,
        ]);
    }

    public function storeDelivery(Request $request, $id)
    {
        $purchase = Purchase::with(['supplier', 'project', 'purchaseItems'])
            ->findOrFail($id);

        if (strtoupper($purchase->status) !== 'APPROVED') {
            $this->notify('Only approved purchase orders can receive deliveries.', 'Error', 'error');
            return redirect()->route('purchase_orders');
        }

        $request->validate([
            'delivery_note_number' => 'required|string|max:100',
            'date' => 'required|date',
            'condition' => 'required|in:good,damaged,partial_damage',
            'items' => 'required|array',
            'items.*.quantity' => 'required|numeric|min:0',
        ]);

        $items = $request->input('items', []);
        $totalDelivered = 0;
        $totalOrdered = 0;

        foreach ($purchase->purchaseItems as $pItem) {
            $qty = (float) ($items[$pItem->id]['quantity'] ?? 0);
            $totalDelivered += $qty;
            $totalOrdered += $pItem->quantity;
        }

        if ($totalDelivered <= 0) {
            $this->notify('At least one item must have a delivery quantity greater than zero.', 'Error', 'error');
            return back()->withInput();
        }

        DB::transaction(function () use ($purchase, $request, $items, $totalDelivered, $totalOrdered) {
            $receiving = SupplierReceiving::create([
                'purchase_id' => $purchase->id,
                'project_id' => $purchase->project_id,
                'supplier_id' => $purchase->supplier_id,
                'received_by' => auth()->id(),
                'delivery_note_number' => $request->delivery_note_number,
                'date' => $request->date,
                'condition' => $request->condition,
                'description' => $request->description,
                'quantity_ordered' => $totalOrdered,
                'quantity_delivered' => $totalDelivered,
                'status' => 'pending',
            ]);

            if ($request->hasFile('file')) {
                $path = $request->file('file')->store('delivery_notes', 'public');
                $receiving->file = $path;
                $receiving->save();
            }

            foreach ($purchase->purchaseItems as $pItem) {
                $qty = (float) ($items[$pItem->id]['quantity'] ?? 0);
                if ($qty > 0) {
                    $pItem->recordReceiving($qty);
                }
            }
        });

        $this->notify('Delivery recorded successfully. It will appear in Material Inspections for review.', 'Success', 'success');
        return redirect()->route('purchase_orders');
    }

    public function receivings(Request $request)
    {
        $start_date = $request->input('start_date') ?? date('Y-m-01');
        $end_date = $request->input('end_date') ?? date('Y-m-d');

        $receivings = SupplierReceiving::with(['purchase', 'supplier', 'project', 'receivedBy'])
            ->whereNotNull('purchase_id')
            ->whereHas('purchase', fn($q) => $q->whereNotNull('material_request_id'))
            ->whereBetween('date', [$start_date, $end_date])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.procurement.supplier_receivings')->with([
            'receivings' => $receivings,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ]);
    }

    public function receivingDetail($id)
    {
        $receiving = SupplierReceiving::with([
            'purchase.purchaseItems.boqItem', 'supplier', 'project', 'receivedBy', 'inspections'
        ])->findOrFail($id);

        return view('pages.procurement.supplier_receiving_detail')->with([
            'receiving' => $receiving,
        ]);
    }
}

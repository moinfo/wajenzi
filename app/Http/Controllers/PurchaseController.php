<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\ApprovalService;
use Illuminate\Http\Request;

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
        $purchases = Purchase::where('date','>=',$start_date)->where('date','<=',$end_date)->get();
        $data = [
            'suppliers' => $suppliers,
            'purchases' => $purchases
        ];
        return view('pages.purchases.purchases_index')->with($data);
    }

    public function purchase($id,$document_type_id){
        // Mark notification as read
        $this->approvalService->markNotificationAsRead($id, $document_type_id,'purchase');

        $approval_data = \App\Models\Purchase::where('id',$id)->get()->first();
        $document_id = $id;

        $details = [
            'Supplier VRN' => $approval_data->supplier->vrn,
            'Tax Invoice' => $approval_data->tax_invoice,
            'Invoice Date' => $approval_data->invoice_date,
            'Goods' => $approval_data->item->name,
            'Total Amount' => number_format($approval_data->total_amount),
            'Amount VAT EXC' => number_format($approval_data->amount_vat_exc),
            'Date' => $approval_data->date,
            'VAT Amount' => number_format($approval_data->vat_amount),
            'Uploaded File' => $approval_data->file
        ];

        $data = [
            'approval_data' => $approval_data,
            'document_id' => $document_id,
            'approval_document_type_id' => $document_type_id, //improve $approval_document_type_id
            'page_name' => 'Purchases',
            'approval_data_name' => $approval_data->supplier->name,
            'details' => $details,
            'model' => 'Purchase',
            'route' => 'purchase',

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
        return redirect()->back()->with('success', 'Purchase submitted for approval');
    }

    public function approve(Purchase $purchase)
    {
        $purchase->approve();
        return redirect()->back()->with('success', 'Purchase approved');
    }

    public function reject(Purchase $purchase, Request $request)
    {
        $purchase->reject($request->reason);
        return redirect()->back()->with('success', 'Purchase rejected');
    }
}

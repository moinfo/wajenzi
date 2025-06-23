<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Supervisor;
use App\Models\VatPayment;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VatPaymentController extends Controller
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
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'VatPayment')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $vat_payments =  VatPayment::where('date','>=',$start_date)->where('date','<=',$end_date)->get();


        $data = [
            'vat_payments' => $vat_payments
        ];
        return view('pages.vat_payment.vat_payment_index')->with($data);
    }

    public function vat_payment($id,$document_type_id){
        // Mark notification as read
        $this->approvalService->markNotificationAsRead($id, $document_type_id,'vat_payment');

        $approval_data = \App\Models\VatPayment::where('id',$id)->get()->first();
        $document_id = $id;

        $details = [
            'Description' => $approval_data->description,
            'Total Amount' => number_format($approval_data->amount),
            'Date' => $approval_data->date,
            'Uploaded File' => $approval_data->file
        ];

        $data = [
            'approval_data' => $approval_data,
            'document_id' => $document_id,
            'approval_document_type_id' => $document_type_id, //improve $approval_document_type_id
            'page_name' => 'Vat Payment',
            'approval_data_name' => $approval_data->bank_name,
            'details' => $details,
            'model' => 'VatPayment',
            'route' => 'vat_payment',

        ];
        return view('approvals._approve_page')->with($data);
    }

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
     * @param  \App\Models\VatPayment  $vatPayment
     * @return \Illuminate\Http\Response
     */
    public function show(VatPayment $vatPayment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\VatPayment  $vatPayment
     * @return \Illuminate\Http\Response
     */
    public function edit(VatPayment $vatPayment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\VatPayment  $vatPayment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, VatPayment $vatPayment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\VatPayment  $vatPayment
     * @return \Illuminate\Http\Response
     */
    public function destroy(VatPayment $vatPayment)
    {
        //
    }
}

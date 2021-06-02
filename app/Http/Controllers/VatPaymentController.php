<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Supervisor;
use App\Models\VatPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VatPaymentController extends Controller
{
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
        $vat_payment = \App\Models\VatPayment::where('id',$id)->get()->first();
        $approvalStages = Approval::getApprovalStages($id,$document_type_id);
        $nextApproval = Approval::getNextApproval($id,$document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id,$document_type_id);
        $rejected = Approval::isRejected($id,$document_type_id);
        $document_id = $id;
        $data = [
            'vat_payment' => $vat_payment,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
            'document_id' => $document_id,
        ];
        return view('pages.vat_payment.vat_payment')->with($data);
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

<?php

namespace App\Http\Controllers;

use App\Models\StatutoryPayment;
use Illuminate\Http\Request;

class StatutoryPaymentController extends Controller
{


    public function statutory_payments(Request $request){
        if($this->handleCrud($request, 'StatutoryPayment')) {
            return back();
        }
        $data = [
            'statutory_payments' => StatutoryPayment::all()
        ];
        return view('pages.settings.settings_statutory_payments')->with($data);
    }


    public function statutory_payment($id,$document_type_id){
        // Mark notification as read
        $this->approvalService->markNotificationAsRead($id, $document_type_id,'statutory_payment');

        $approval_data = \App\Models\StatutoryPayment::where('id',$id)->get()->first();
        $document_id = $id;

        $details = [
            'Sub Category' => $approval_data->subCategory->name,
            'Control Number' => $approval_data->control_number,
            'Description' => $approval_data->description,
            'Total Amount' => number_format($approval_data->amount),
            'Issue Date' => $approval_data->issue_date,
            'Due Date' => $approval_data->due_date,
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
            'model' => 'StatutoryPayment',
            'route' => 'statutory_payment',

        ];
        return view('approvals._approve_page')->with($data);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
     * @param  \App\Models\StatutoryPayment  $statutoryPayment
     * @return \Illuminate\Http\Response
     */
    public function show(StatutoryPayment $statutoryPayment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\StatutoryPayment  $statutoryPayment
     * @return \Illuminate\Http\Response
     */
    public function edit(StatutoryPayment $statutoryPayment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\StatutoryPayment  $statutoryPayment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, StatutoryPayment $statutoryPayment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\StatutoryPayment  $statutoryPayment
     * @return \Illuminate\Http\Response
     */
    public function destroy(StatutoryPayment $statutoryPayment)
    {
        //
    }
}

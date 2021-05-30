<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\BankDeposit;
use Illuminate\Http\Request;

class BankDepositController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'BankDeposit')) {
            return back();
        }

        $data = [];
        return view('pages.bank_deposit.bank_deposit_index')->with($data);
    }

    public function bank_deposit($id,$document_type_id){
        $bank_deposit = \App\Models\BankDeposit::where('id',$id)->get()->first();
        $approvalStages = Approval::getApprovalStages($id,$document_type_id);
        $nextApproval = Approval::getNextApproval($id,$document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id,$document_type_id);
        $rejected = Approval::isRejected($id,$document_type_id);
        $document_id = $id;
        $data = [
            'bank_deposit' => $bank_deposit,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
            'document_id' => $document_id,
        ];
        return view('pages.bank_deposit.bank_deposit')->with($data);
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
     * @param  \App\Models\BankDeposit  $bankDeposit
     * @return \Illuminate\Http\Response
     */
    public function show(BankDeposit $bankDeposit)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BankDeposit  $bankDeposit
     * @return \Illuminate\Http\Response
     */
    public function edit(BankDeposit $bankDeposit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BankDeposit  $bankDeposit
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BankDeposit $bankDeposit)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BankDeposit  $bankDeposit
     * @return \Illuminate\Http\Response
     */
    public function destroy(BankDeposit $bankDeposit)
    {
        //
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\BankWithdraw;
use Illuminate\Http\Request;

class BankWithdrawController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'BankWithdraw')) {
            return back();
        }

        $data = [];
        return view('pages.bank_withdraw.bank_withdraw_index')->with($data);
    }

    public function bank_withdraw($id,$document_type_id){
        $bank_withdraw = \App\Models\Expense::where('id',$id)->get()->first();
        $approvalStages = Approval::getApprovalStages($id,$document_type_id);
        $nextApproval = Approval::getNextApproval($id,$document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id,$document_type_id);
        $rejected = Approval::isRejected($id,$document_type_id);
        $document_id = $id;
        $data = [
            'bank_withdraw' => $bank_withdraw,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
            'document_id' => $document_id,
        ];
        return view('pages.bank_withdraw.bank_withdraw')->with($data);
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
     * @param  \App\Models\BankWithdraw  $bankWithdraw
     * @return \Illuminate\Http\Response
     */
    public function show(BankWithdraw $bankWithdraw)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BankWithdraw  $bankWithdraw
     * @return \Illuminate\Http\Response
     */
    public function edit(BankWithdraw $bankWithdraw)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BankWithdraw  $bankWithdraw
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BankWithdraw $bankWithdraw)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BankWithdraw  $bankWithdraw
     * @return \Illuminate\Http\Response
     */
    public function destroy(BankWithdraw $bankWithdraw)
    {
        //
    }
}

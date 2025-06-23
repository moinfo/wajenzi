<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\SystemCash;
use Illuminate\Http\Request;

class SystemCashController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'SystemCash')) {
            return back();
        }

        $data = [];
        return view('pages.system_cash.system_cash_index')->with($data);
    }

    public function system_cash($id,$document_type_id){
        $system_cash = \App\Models\SystemCash::where('id',$id)->get()->first();
        $approvalStages = Approval::getApprovalStages($id,$document_type_id);
        $nextApproval = Approval::getNextApproval($id,$document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id,$document_type_id);
        $rejected = Approval::isRejected($id,$document_type_id);
        $document_id = $id;
        $data = [
            'system_cash' => $system_cash,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
            'document_id' => $document_id,
        ];
        return view('pages.system_cash.system_cash')->with($data);
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
     * @param  \App\Models\SystemCash  $systemCash
     * @return \Illuminate\Http\Response
     */
    public function show(SystemCash $systemCash)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SystemCash  $systemCash
     * @return \Illuminate\Http\Response
     */
    public function edit(SystemCash $systemCash)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SystemCash  $systemCash
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SystemCash $systemCash)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SystemCash  $systemCash
     * @return \Illuminate\Http\Response
     */
    public function destroy(SystemCash $systemCash)
    {
        //
    }
}

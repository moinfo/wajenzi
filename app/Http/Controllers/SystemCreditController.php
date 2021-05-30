<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\SystemCredit;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemCreditController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'SystemCredit')) {
            return back();
        }

        $data = [];
        return view('pages.system_credit.system_credit_index')->with($data);
    }

    public function system_credit($id,$document_type_id){
        $system_credit = \App\Models\SystemCredit::where('id',$id)->get()->first();
        $approvalStages = Approval::getApprovalStages($id,$document_type_id);
        $nextApproval = Approval::getNextApproval($id,$document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id,$document_type_id);
        $rejected = Approval::isRejected($id,$document_type_id);
        $document_id = $id;
        $data = [
            'system_credit' => $system_credit,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
            'document_id' => $document_id,
        ];
        return view('pages.system_credit.system_credit')->with($data);
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
     * @param  \App\Models\SystemCredit  $systemCredit
     * @return \Illuminate\Http\Response
     */
    public function show(SystemCredit $systemCredit)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SystemCredit  $systemCredit
     * @return \Illuminate\Http\Response
     */
    public function edit(SystemCredit $systemCredit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SystemCredit  $systemCredit
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SystemCredit $systemCredit)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SystemCredit  $systemCredit
     * @return \Illuminate\Http\Response
     */
    public function destroy(SystemCredit $systemCredit)
    {
        //
    }
}

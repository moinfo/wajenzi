<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\SystemInventory;
use Illuminate\Http\Request;

class SystemInventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'SystemInventory')) {
            return back();
        }

        $data = [];
        return view('pages.system_inventory.system_inventory_index')->with($data);
    }

    public function system_inventory($id,$document_type_id){
        $system_inventory = \App\Models\SystemInventory::where('id',$id)->get()->first();
        $approvalStages = Approval::getApprovalStages($id,$document_type_id);
        $nextApproval = Approval::getNextApproval($id,$document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id,$document_type_id);
        $rejected = Approval::isRejected($id,$document_type_id);
        $document_id = $id;
        $data = [
            'system_inventory' => $system_inventory,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
            'document_id' => $document_id,
        ];
        return view('pages.system_inventory.system_inventory')->with($data);
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
     * @param  \App\Models\SystemInventory  $systemInventory
     * @return \Illuminate\Http\Response
     */
    public function show(SystemInventory $systemInventory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SystemInventory  $systemInventory
     * @return \Illuminate\Http\Response
     */
    public function edit(SystemInventory $systemInventory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SystemInventory  $systemInventory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SystemInventory $systemInventory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SystemInventory  $systemInventory
     * @return \Illuminate\Http\Response
     */
    public function destroy(SystemInventory $systemInventory)
    {
        //
    }
}

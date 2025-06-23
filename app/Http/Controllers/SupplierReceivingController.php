<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Supplier;
use App\Models\SupplierReceiving;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierReceivingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'SupplierReceiving')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $supplier_id = $request->input('supplier_id');
        if($supplier_id == 0){
            $supplier_receivings = DB::table('supplier_receivings')
                ->join('suppliers', 'suppliers.id', '=', 'supplier_receivings.supplier_id')
                ->select('supplier_receivings.*','suppliers.name as supplier_name')
                ->where('date','>=',$start_date)
                ->where('date','<=',$end_date)
                ->get();
        }else{
            $supplier_receivings = DB::table('supplier_receivings')
                ->join('suppliers', 'suppliers.id', '=', 'supplier_receivings.supplier_id')
                ->select('supplier_receivings.*','suppliers.name as supplier_name')
                ->where('date','>=',$start_date)
                ->where('date','<=',$end_date)
                ->where('supplier_id','=',$supplier_id)
                ->get();
        }
        $suppliers = Supplier::all();

        $data = [
            'suppliers' => $suppliers,
            'supplier_receivings' => $supplier_receivings
        ];
        return view('pages.supplier_receiving.supplier_receiving_index')->with($data);
    }

    public function supplier_receiving($id,$document_type_id){
        $supplier_receiving = \App\Models\SupplierReceiving::where('id',$id)->get()->first();
        $approvalStages = Approval::getApprovalStages($id,$document_type_id);
        $nextApproval = Approval::getNextApproval($id,$document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id,$document_type_id);
        $rejected = Approval::isRejected($id,$document_type_id);
        $document_id = $id;
        $data = [
            'supplier_receiving' => $supplier_receiving,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
            'document_id' => $document_id,
        ];
        return view('pages.supplier_receiving.supplier_receiving')->with($data);
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
     * @param  \App\Models\SupplierReceiving  $supplierReceiving
     * @return \Illuminate\Http\Response
     */
    public function show(SupplierReceiving $supplierReceiving)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SupplierReceiving  $supplierReceiving
     * @return \Illuminate\Http\Response
     */
    public function edit(SupplierReceiving $supplierReceiving)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SupplierReceiving  $supplierReceiving
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SupplierReceiving $supplierReceiving)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SupplierReceiving  $supplierReceiving
     * @return \Illuminate\Http\Response
     */
    public function destroy(SupplierReceiving $supplierReceiving)
    {
    }

    public function search(Request $request){
        if($this->handleCrud($request, 'SupplierReceiving')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $supplier_id = $request->input('supplier_id');
        if($supplier_id == 0){
            $supplier_receivings = DB::table('supplier_receivings')
                ->join('suppliers', 'suppliers.id', '=', 'supplier_receivings.supplier_id')
                ->select('supplier_receivings.*','suppliers.name as supplier_name')
                ->where('date','>=',$start_date)
                ->where('date','<=',$end_date)
                ->get();
        }else{
            $supplier_receivings = DB::table('supplier_receivings')
                ->join('suppliers', 'suppliers.id', '=', 'supplier_receivings.supplier_id')
                ->select('supplier_receivings.*','suppliers.name as supplier_name')
                ->where('date','>=',$start_date)
                ->where('date','<=',$end_date)
                ->where('supplier_id','=',$supplier_id)
                ->get();
        }

        $suppliers = Supplier::all();
        return view('pages.supplier_receiving.supplier_receiving_index',compact('supplier_receivings','suppliers'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Supervisor;
use App\Models\Supplier;
use App\Models\TransactionMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionMovementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'TransactionMovement')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $supplier_id = $request->input('supplier_id');
        if($supplier_id == 0){
            $transaction_movements = DB::table('transaction_movements')
                ->join('suppliers', 'suppliers.id', '=', 'transaction_movements.supplier_id')
                ->select('transaction_movements.*','suppliers.name as supplier_name')
                ->where('date','>=',$start_date)
                ->where('date','<=',$end_date)
                ->get();
        }else{
            $transaction_movements = DB::table('transaction_movements')
                ->join('suppliers', 'suppliers.id', '=', 'transaction_movements.supplier_id')
                ->select('transaction_movements.*','suppliers.name as supplier_name')
                ->where('date','>=',$start_date)
                ->where('date','<=',$end_date)
                ->where('supplier_id','=',$supplier_id)
                ->get();
        }
        $suppliers = Supplier::all();

        $data = [
            'suppliers' => $suppliers,
            'transaction_movements' => $transaction_movements
        ];
        return view('pages.transaction_movement.transaction_movement_index')->with($data);
    }

    public function transaction_movement($id,$document_type_id){
        $transaction_movement = \App\Models\TransactionMovement::where('id',$id)->get()->first();
        $approvalStages = Approval::getApprovalStages($id,$document_type_id);
        $nextApproval = Approval::getNextApproval($id,$document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id,$document_type_id);
        $rejected = Approval::isRejected($id,$document_type_id);
        $document_id = $id;
        $data = [
            'transaction_movement' => $transaction_movement,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
            'document_id' => $document_id,
        ];
        return view('pages.transaction_movement.transaction_movement')->with($data);
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
     * @param  \App\Models\TransactionMovement  $transactionMovement
     * @return \Illuminate\Http\Response
     */
    public function show(TransactionMovement $transactionMovement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\TransactionMovement  $transactionMovement
     * @return \Illuminate\Http\Response
     */
    public function edit(TransactionMovement $transactionMovement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TransactionMovement  $transactionMovement
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TransactionMovement $transactionMovement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TransactionMovement  $transactionMovement
     * @return \Illuminate\Http\Response
     */
    public function destroy(TransactionMovement $transactionMovement)
    {
        //
    }

    public function search(Request $request){
        if($this->handleCrud($request, 'TransactionMovement')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $supplier_id = $request->input('supplier_id');
        if($supplier_id == 0){
            $transaction_movements = DB::table('transaction_movements')
                ->join('suppliers', 'suppliers.id', '=', 'transaction_movements.supplier_id')
                ->select('transaction_movements.*','suppliers.name as supplier_name')
                ->where('date','>=',$start_date)
                ->where('date','<=',$end_date)
                ->get();
        }else{
            $transaction_movements = DB::table('transaction_movements')
                ->join('suppliers', 'suppliers.id', '=', 'transaction_movements.supplier_id')
                ->select('transaction_movements.*','suppliers.name as supplier_name')
                ->where('date','>=',$start_date)
                ->where('date','<=',$end_date)
                ->where('supplier_id','=',$supplier_id)
                ->get();
        }

        $suppliers = Supplier::all();
        return view('pages.transaction_movement.transaction_movement_index',compact('transaction_movements','suppliers'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Classes\Utility;
use App\Models\AssetProperty;
use App\Models\BankReconciliation;
use App\Models\Efd;
use App\Models\Supplier;
use App\Models\System;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class BankReconciliationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'BankReconciliation')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $payment_type = $request->input('payment_type') ?? 'OFFICE';
        $suppliers = Supplier::all();
        $systems = System::where('id','!=',5)->get();
        $supplier_with_deposits = BankReconciliation::where('date','>=',$start_date)->where('date','<=',$end_date)->where('payment_type','SALES')->select('suppliers.name','bank_reconciliations.supplier_id')
            ->join('suppliers','suppliers.id','=','bank_reconciliations.supplier_id')->groupBy('supplier_id')->get();
        $efds = Efd::all();
        $reports = Efd::allWithTransactions($start_date, $end_date);
        $reports_2 = Efd::allWithTransactionsWithOfficePaymentType($start_date, $end_date, $payment_type);
        $reports_3 = Efd::allWithTransactionsWithOfficePaymentType($start_date, $end_date, 'SALES');
        $maxTransactions = 0;
        foreach ($reports as $index => $item) {
            if($item->transactions()->count() > $maxTransactions){
                $maxTransactions = $item->transactions()->count();
            }
        }

        $bank_reconciliation_payment_types = [
            ['name'=>'SALES'],
            ['name'=>'OFFICE']
        ];
        $data = [
            'bank_reconciliation_payment_types' => $bank_reconciliation_payment_types,
            'supplier_with_deposits' => $supplier_with_deposits,
            'suppliers' => $suppliers,
            'systems' => $systems,
            'efds' => $efds,
            'efdTransactions' => $reports,
            'efdTransactions_2' => $reports_2,
            'efdTransactions_3' => $reports_3,
            'maxTransactions' => $maxTransactions
        ];
        return view('pages.bank_reconciliation.bank_reconciliation_index')->with($data);
    }

    public function transferReports(Request $request){
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $efd_id = $request->input('efd_if') ?? null;
        $supplier_id = $request->input('supplier_id') ?? null;

        $bank_reconciliations = \App\Models\BankReconciliation::getOnlyTransfered($start_date,$end_date,$efd_id,$supplier_id);
        $data = [
           'bank_reconciliations' => $bank_reconciliations
        ];
        return view('pages.bank_reconciliation.transfer_reports')->with($data);
    }

    public function transfer_by_only_supplier_reports(Request $request){
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $efd_id = $request->input('efd_if') ?? null;
        $supplier_id = $request->input('supplier_id') ?? null;

        $bank_reconciliations = \App\Models\BankReconciliation::getOnlyTransferedBySupplier($start_date,$end_date,$efd_id,$supplier_id);
        $data = [
           'bank_reconciliations' => $bank_reconciliations
        ];
        return view('pages.bank_reconciliation.transfer_by_only_supplier_reports')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function transfer(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $from_to_id = $from.$to;
        $efd_id = $request->input('efd_id');
        $date = $request->input('date');
        $debit = Utility::strip_commas($request->input('debit'));
        $description = $request->input('description');
        $reference = $request->input('reference');
        $payment_type = $request->input('payment_type');
      DB::table('bank_reconciliations')->insert([
           ['supplier_id' => $from, 'efd_id' => $efd_id, 'from_to_id' => $from_to_id, 'to_id' => $to, 'date' => $date, 'reference' => $reference, 'description' => $description, 'debit' => $debit*-1, 'payment_type' => $payment_type],
           ['supplier_id' => $to, 'to_id' => $from, 'from_to_id' => $to.$from, 'efd_id' => $efd_id, 'date' => $date, 'reference' => "$reference 1",'description' => $description, 'debit' => $debit, 'payment_type' => $payment_type]
       ]);


       return Redirect::back();
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
     * @param  \App\Models\BankReconciliation  $bankReconciliation
     * @return \Illuminate\Http\Response
     */
    public function show(BankReconciliation $bankReconciliation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BankReconciliation  $bankReconciliation
     * @return \Illuminate\Http\Response
     */
    public function edit(BankReconciliation $bankReconciliation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BankReconciliation  $bankReconciliation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BankReconciliation $bankReconciliation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BankReconciliation  $bankReconciliation
     * @return \Illuminate\Http\Response
     */
    public function destroy(BankReconciliation $bankReconciliation)
    {
        //
    }

    public function getTransferredBalance(Request $request){
        $efd_id = $request->input('efd_id');
        $date = $request->input('date');
        $supplier_from = $request->input('supplier_from');
        $balance = BankReconciliation::select(DB::raw("SUM(debit) AS total"))->where('date',$date)->where('efd_id',$efd_id)->where('supplier_id',$supplier_from)->get()->first()['total'] ?? 0;
            $balance_arr[] = array("cash_available" => $balance);
        echo json_encode($balance_arr);

    }
}

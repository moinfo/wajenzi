<?php

namespace App\Http\Controllers;

use App\Models\BankReconciliation;
use App\Models\Efd;
use App\Models\Supplier;
use Illuminate\Http\Request;

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
        $supplier_with_deposits = BankReconciliation::where('date','>=',$start_date)->where('date','<=',$end_date)->select('suppliers.name','bank_reconciliations.supplier_id')
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
            'efds' => $efds,
            'efdTransactions' => $reports,
            'efdTransactions_2' => $reports_2,
            'efdTransactions_3' => $reports_3,
            'maxTransactions' => $maxTransactions
        ];
        return view('pages.bank_reconciliation.bank_reconciliation_index')->with($data);
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
}

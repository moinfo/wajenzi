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
        $suppliers = Supplier::all();
        $efds = Efd::all();

        $data = [
            'suppliers' => $suppliers,
            'efds' => $efds,
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

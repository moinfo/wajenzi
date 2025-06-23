<?php

namespace App\Http\Controllers;

use App\Models\AdjustmentExpense;
use Illuminate\Http\Request;

class AdjustmentExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'AdjustmentExpense')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-01');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $expenses = AdjustmentExpense::getAdjustable($start_date,$end_date);
        $data = [
            'adjustable_expenses' => $expenses
        ];
        return view('pages.purchases.expense_adjustable')->with($data);
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
     * @param  \App\Models\AdjustmentExpense  $adjustmentExpense
     * @return \Illuminate\Http\Response
     */
    public function show(AdjustmentExpense $adjustmentExpense)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\AdjustmentExpense  $adjustmentExpense
     * @return \Illuminate\Http\Response
     */
    public function edit(AdjustmentExpense $adjustmentExpense)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AdjustmentExpense  $adjustmentExpense
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AdjustmentExpense $adjustmentExpense)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AdjustmentExpense  $adjustmentExpense
     * @return \Illuminate\Http\Response
     */
    public function destroy(AdjustmentExpense $adjustmentExpense)
    {
        //
    }
}

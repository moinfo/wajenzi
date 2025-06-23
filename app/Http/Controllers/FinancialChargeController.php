<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\FinancialCharge;
use App\Models\FinancialChargeCategory;
use App\Models\SubCategory;
use Illuminate\Http\Request;

class FinancialChargeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'FinancialCharge')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $financial_charges = FinancialCharge::where('date','>=',$start_date)->where('date','<=',$end_date)->get();

        $data = [
            'financial_charges' => $financial_charges
        ];
        return view('pages.financial_charges.financial_charges_index')->with($data);
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
     * @param  \App\Models\FinancialCharge  $financialCharge
     * @return \Illuminate\Http\Response
     */
    public function show(FinancialCharge $financialCharge)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\FinancialCharge  $financialCharge
     * @return \Illuminate\Http\Response
     */
    public function edit(FinancialCharge $financialCharge)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\FinancialCharge  $financialCharge
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, FinancialCharge $financialCharge)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\FinancialCharge  $financialCharge
     * @return \Illuminate\Http\Response
     */
    public function destroy(FinancialCharge $financialCharge)
    {
        //
    }

    public function getCharges(Request $request){
        $financial_charge_category_id = $request->input('financial_charge_category_id');
        $financial_charges = FinancialChargeCategory::where('id',$financial_charge_category_id)->get();

        $charge_arr = [];
        foreach ($financial_charges as $index => $financial_charge) {

            $charge_arr[] = array("charge" => $financial_charge->charge);
        }
        echo json_encode($charge_arr);

    }
}

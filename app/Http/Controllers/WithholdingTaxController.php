<?php

namespace App\Http\Controllers;

use App\Models\WithholdingTax;
use Illuminate\Http\Request;

class WithholdingTaxController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'WithholdingTax')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $withholding_taxes =  WithholdingTax::where('date','>=',$start_date)->where('date','<=',$end_date)->get();


        $data = [
            'withholding_taxes' => $withholding_taxes
        ];
        return view('pages.withholding_tax.withholding_tax_index')->with($data);
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
     * @param  \App\Models\WithholdingTax  $withholdingTax
     * @return \Illuminate\Http\Response
     */
    public function show(WithholdingTax $withholdingTax)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\WithholdingTax  $withholdingTax
     * @return \Illuminate\Http\Response
     */
    public function edit(WithholdingTax $withholdingTax)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\WithholdingTax  $withholdingTax
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, WithholdingTax $withholdingTax)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\WithholdingTax  $withholdingTax
     * @return \Illuminate\Http\Response
     */
    public function destroy(WithholdingTax $withholdingTax)
    {
        //
    }
}

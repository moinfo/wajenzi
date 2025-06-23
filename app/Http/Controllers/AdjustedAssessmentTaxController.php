<?php

namespace App\Http\Controllers;

use App\Models\AdjustedAssessmentTax;
use Illuminate\Http\Request;

class AdjustedAssessmentTaxController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'AdjustedAssessmentTax')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $adjusted_assessment_taxes =  AdjustedAssessmentTax::where('date','>=',$start_date)->where('date','<=',$end_date)->get();


        $data = [
            'adjusted_assessment_taxes' => $adjusted_assessment_taxes
        ];
        return view('pages.adjusted_assessment_tax.adjusted_assessment_tax_index')->with($data);
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
     * @param  \App\Models\AdjustedAssessmentTax  $adjustedAssessmentTax
     * @return \Illuminate\Http\Response
     */
    public function show(AdjustedAssessmentTax $adjustedAssessmentTax)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\AdjustedAssessmentTax  $adjustedAssessmentTax
     * @return \Illuminate\Http\Response
     */
    public function edit(AdjustedAssessmentTax $adjustedAssessmentTax)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AdjustedAssessmentTax  $adjustedAssessmentTax
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AdjustedAssessmentTax $adjustedAssessmentTax)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AdjustedAssessmentTax  $adjustedAssessmentTax
     * @return \Illuminate\Http\Response
     */
    public function destroy(AdjustedAssessmentTax $adjustedAssessmentTax)
    {
        //
    }
}

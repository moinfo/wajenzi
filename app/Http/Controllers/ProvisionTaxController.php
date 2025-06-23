<?php

namespace App\Http\Controllers;

use App\Models\ProvisionTax;
use App\Models\VatPayment;
use Illuminate\Http\Request;

class ProvisionTaxController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'ProvisionTax')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $provision_taxes =  ProvisionTax::where('date','>=',$start_date)->where('date','<=',$end_date)->get();


        $data = [
            'provision_taxes' => $provision_taxes
        ];
        return view('pages.provision_tax.provision_tax_index')->with($data);
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
     * @param  \App\Models\ProvisionTax  $provisionTax
     * @return \Illuminate\Http\Response
     */
    public function show(ProvisionTax $provisionTax)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ProvisionTax  $provisionTax
     * @return \Illuminate\Http\Response
     */
    public function edit(ProvisionTax $provisionTax)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProvisionTax  $provisionTax
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ProvisionTax $provisionTax)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ProvisionTax  $provisionTax
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProvisionTax $provisionTax)
    {
        //
    }
}

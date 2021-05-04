<?php

namespace App\Http\Controllers;

use App\Models\Supervisor;
use App\Models\VatPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VatPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'VatPayment')) {
            return back();
        }
//        $collections = Collection::whereDate('date', DB::raw('CURDATE()'))->get();
        $collections =  VatPayment::all();


        $data = [
            'collections' => $collections
        ];
        return view('pages.vat_payment.vat_payment_index')->with($data);
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
     * @param  \App\Models\VatPayment  $vatPayment
     * @return \Illuminate\Http\Response
     */
    public function show(VatPayment $vatPayment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\VatPayment  $vatPayment
     * @return \Illuminate\Http\Response
     */
    public function edit(VatPayment $vatPayment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\VatPayment  $vatPayment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, VatPayment $vatPayment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\VatPayment  $vatPayment
     * @return \Illuminate\Http\Response
     */
    public function destroy(VatPayment $vatPayment)
    {
        //
    }
}

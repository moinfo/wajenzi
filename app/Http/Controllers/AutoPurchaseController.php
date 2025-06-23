<?php

namespace App\Http\Controllers;

use App\Models\AutoPurchase;
use App\Models\Purchase;
use App\Models\Receipt;
use App\Models\Supplier;
use Illuminate\Http\Request;

class AutoPurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'Receipt')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-06-01');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $purchases = Receipt::whereBetween('receipt_date',[$start_date,$end_date])->orderBy('receipt_date','DESC')->get();
        $data = [
            'purchases' => $purchases
        ];
        return view('pages.auto_purchases.auto_purchases_index')->with($data);
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
     * @param  \App\Models\AutoPurchase  $autoPurchase
     * @return \Illuminate\Http\Response
     */
    public function show(AutoPurchase $autoPurchase)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\AutoPurchase  $autoPurchase
     * @return \Illuminate\Http\Response
     */
    public function edit(AutoPurchase $autoPurchase)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AutoPurchase  $autoPurchase
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AutoPurchase $autoPurchase)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AutoPurchase  $autoPurchase
     * @return \Illuminate\Http\Response
     */
    public function destroy(AutoPurchase $autoPurchase)
    {
        //
    }
}

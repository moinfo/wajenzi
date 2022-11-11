<?php

namespace App\Http\Controllers;

use App\Models\ReceiptItem;
use Illuminate\Http\Request;

class ReceiptItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        $receipt = new ReceiptItem;
        $receipt->receipt_id = $request->receipt_id;
        $receipt->description = $request->description;
        $receipt->qty = $request->qty;
        $receipt->amount = $request->amount;
        $result = $receipt->save();
        if ($result){
            return ['results' => 'add receipt Item successful'];
        }else{
            return ['results' => 'add receipt Item failed'];
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ReceiptItem  $receiptItem
     * @return \Illuminate\Http\Response
     */
    public function show(ReceiptItem $receiptItem)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ReceiptItem  $receiptItem
     * @return \Illuminate\Http\Response
     */
    public function edit(ReceiptItem $receiptItem)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ReceiptItem  $receiptItem
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ReceiptItem $receiptItem)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ReceiptItem  $receiptItem
     * @return \Illuminate\Http\Response
     */
    public function destroy(ReceiptItem $receiptItem)
    {
        //
    }
}

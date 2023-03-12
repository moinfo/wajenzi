<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Models\ReceiptItem;
use Illuminate\Http\Request;

class ReceiptController extends Controller
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
//        Receipt::isExist($request->receipt_verification_code);
        $receipt = new Receipt;
        $receipt->company_name = $request->company_name;
        $receipt->p_o_box = $request->p_o_box;
        $receipt->mobile = $request->mobile;
        $receipt->tin = $request->tin;
        $receipt->vrn = $request->vrn;
        $receipt->serial_no = $request->serial_no;
        $receipt->uin = $request->uin;
        $receipt->tax_office = $request->tax_office;
        $receipt->customer_name = $request->customer_name;
        $receipt->customer_id_type = $request->customer_id_type;
        $receipt->customer_id = $request->customer_id;
        $receipt->customer_mobile = $request->customer_mobile;
        $receipt->receipt_number = $request->receipt_number;
        $receipt->receipt_z_number = $request->receipt_z_number;
        $receipt->receipt_date = $request->receipt_date;
        $receipt->receipt_time = $request->receipt_time;
        $receipt->receipt_verification_code = $request->receipt_verification_code;
        $receipt->receipt_total_excl_of_tax = $request->receipt_total_excl_of_tax;
        $receipt->receipt_total_tax = $request->receipt_total_tax;
        $receipt->receipt_total_incl_of_tax = $request->receipt_total_incl_of_tax;
        $receipt->receipt_total_discount = $request->receipt_total_discount;
        $receipt->receipt_rea = $request->receipt_rea;
        $receipt->receipt_property_tax = $request->receipt_property_tax;
        $receipt->receipt_ewura = $request->receipt_ewura;
        $receipt->date = $request->receipt_date;
        $result = $receipt->save();
        $receipt_id = Receipt::latest('id')->first()->id;
        $items = $request->items;
        if (count($items) > 0) {
            foreach($items as $item) {
                $data = [
                    'receipt_id' => $receipt_id,
                    'description' => $item['item_description'],
                    'qty' => $item['item_qty'],
                    'amount' => $item['item_amount']
                ];
                ReceiptItem::insert($data);
            }
        }
        if ($result){
            return ['results' => 'add receipt successful'];
        }else{
            return ['results' => 'add receipt failed'];
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Receipt  $receipt
     * @return \Illuminate\Http\Response
     */
    public function show(Receipt $receipt)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Receipt  $receipt
     * @return \Illuminate\Http\Response
     */
    public function edit(Receipt $receipt)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Receipt  $receipt
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Receipt $receipt)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Receipt  $receipt
     * @return \Illuminate\Http\Response
     */
    public function destroy(Receipt $receipt)
    {
        //
    }
}

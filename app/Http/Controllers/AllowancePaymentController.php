<?php

namespace App\Http\Controllers;

use App\Models\AllowancePayment;
use App\Models\RentRoomAssignment;
use Illuminate\Http\Request;

class AllowancePaymentController extends Controller
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\AllowancePayment  $allowancePayment
     * @return \Illuminate\Http\Response
     */
    public function show(AllowancePayment $allowancePayment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\AllowancePayment  $allowancePayment
     * @return \Illuminate\Http\Response
     */
    public function edit(AllowancePayment $allowancePayment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AllowancePayment  $allowancePayment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AllowancePayment $allowancePayment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AllowancePayment  $allowancePayment
     * @return \Illuminate\Http\Response
     */
    public function destroy(AllowancePayment $allowancePayment)
    {
        //
    }

    public function getAllowanceCost(Request $request){
        $month = $request->input('month');
        $allowance_cost = \App\Models\Staff::getAllStaffAllowance($month);

//        foreach ($allowance_cost as $index => $item) {
//            $id = $item->id;
//            $price = $item->rentRoom->price;
//
//        }
        $price_arr[] = array("amount" => $allowance_cost);
        echo json_encode($price_arr);
    }
}

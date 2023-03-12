<?php

namespace App\Http\Controllers;

use App\Models\Api;
use App\Models\Attendance;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $data = [];
        return response()->json($data);
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

        $validation = $request->validate([  // TODO  Implement validation logic
            "data" => ['array'],
        ]);
        $data = $request->post('data');
        $newData = Attendance::recordFromDevice($data);


        return response()->json($newData);
    }

    public function receipts($id = null)
    {
        if($id){
            $receipts = DB::table('receipts')->where('id',$id)->get()->toArray();
            $receipt_items = DB::table('receipt_items')->where('receipt_id',$id)->get()->toArray();

            foreach($receipts as &$receipt)
            {
                $receipt->receipt_items = array_filter($receipt_items, function($receipt_item) use ($receipt) {
                    return $receipt_item->receipt_id === $receipt->id;
                });
            }

            return $receipts;
        }else{
            return Receipt::with(['items'])->latest();
        }

    }

    public function receipt_items($id = null)
    {
        return $id?ReceiptItem::where('receipt_id',$id)->get():ReceiptItem::all();
    }

    public function employees($id = null)
    {
        return $id?User::find($id):User::all();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Api  $api
     * @return \Illuminate\Http\Response
     */
    public function show(Api $api)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Api  $api
     * @return \Illuminate\Http\Response
     */
    public function edit(Api $api)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Api  $api
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Api $api)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Api  $api
     * @return \Illuminate\Http\Response
     */
    public function destroy(Api $api)
    {
        //
    }
}

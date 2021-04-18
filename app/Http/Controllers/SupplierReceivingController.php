<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierReceiving;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierReceivingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'SupplierReceiving')) {
            return back();
        }
        $supplier_receivings = SupplierReceiving::whereDate('date', DB::raw('CURDATE()'))->get();
        $suppliers = Supplier::all();

        $data = [
            'suppliers' => $suppliers,
            'supplier_receivings' => $supplier_receivings
        ];
        return view('pages.supplier_receiving.supplier_receiving_index')->with($data);
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
     * @param  \App\Models\SupplierReceiving  $supplierReceiving
     * @return \Illuminate\Http\Response
     */
    public function show(SupplierReceiving $supplierReceiving)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SupplierReceiving  $supplierReceiving
     * @return \Illuminate\Http\Response
     */
    public function edit(SupplierReceiving $supplierReceiving)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SupplierReceiving  $supplierReceiving
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SupplierReceiving $supplierReceiving)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SupplierReceiving  $supplierReceiving
     * @return \Illuminate\Http\Response
     */
    public function destroy(SupplierReceiving $supplierReceiving)
    {
    }

    public function search(Request $request){
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $supplier_id = $request->input('supplier_id');
        if($supplier_id == 0){
            $supplier_receivings = DB::table('supplier_receivings')
                ->join('suppliers', 'suppliers.id', '=', 'supplier_receivings.supplier_id')
                ->select('supplier_receivings.*','suppliers.name as supplier_name')
                ->where('date','>=',$start_date)
                ->where('date','<=',$end_date)
                ->get();
        }else{
            $supplier_receivings = DB::table('supplier_receivings')
                ->join('suppliers', 'suppliers.id', '=', 'supplier_receivings.supplier_id')
                ->select('supplier_receivings.*','suppliers.name as supplier_name')
                ->where('date','>=',$start_date)
                ->where('date','<=',$end_date)
                ->where('supplier_id','=',$supplier_id)
                ->get();
        }

        $suppliers = Supplier::all();
        return view('pages.supplier_receiving.supplier_receiving_index',compact('supplier_receivings','suppliers'));
    }
}

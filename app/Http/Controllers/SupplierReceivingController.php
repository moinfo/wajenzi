<?php

namespace App\Http\Controllers;

use App\Models\SupplierReceiving;
use Illuminate\Http\Request;

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
        $supplier_receivings = SupplierReceiving::all();

        $data = [
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
        //
    }
}

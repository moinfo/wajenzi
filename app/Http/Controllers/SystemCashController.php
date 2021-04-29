<?php

namespace App\Http\Controllers;

use App\Models\SystemCash;
use Illuminate\Http\Request;

class SystemCashController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'SystemCash')) {
            return back();
        }

        $data = [];
        return view('pages.system_cash.system_cash_index')->with($data);
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
     * @param  \App\Models\SystemCash  $systemCash
     * @return \Illuminate\Http\Response
     */
    public function show(SystemCash $systemCash)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SystemCash  $systemCash
     * @return \Illuminate\Http\Response
     */
    public function edit(SystemCash $systemCash)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SystemCash  $systemCash
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SystemCash $systemCash)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SystemCash  $systemCash
     * @return \Illuminate\Http\Response
     */
    public function destroy(SystemCash $systemCash)
    {
        //
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\SystemCapital;
use Illuminate\Http\Request;

class SystemCapitalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'SystemCapital')) {
            return back();
        }

        $data = [];
        return view('pages.system_capital.system_capital_index')->with($data);
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
     * @param  \App\Models\SystemCapital  $systemCapital
     * @return \Illuminate\Http\Response
     */
    public function show(SystemCapital $systemCapital)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SystemCapital  $systemCapital
     * @return \Illuminate\Http\Response
     */
    public function edit(SystemCapital $systemCapital)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SystemCapital  $systemCapital
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SystemCapital $systemCapital)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SystemCapital  $systemCapital
     * @return \Illuminate\Http\Response
     */
    public function destroy(SystemCapital $systemCapital)
    {
        //
    }
}

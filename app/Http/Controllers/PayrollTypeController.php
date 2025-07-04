<?php

namespace App\Http\Controllers;

use App\Models\Capital;
use App\Models\PayrollType;
use Illuminate\Http\Request;

class PayrollTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'PayrollType')) {
            return back();
        }
        $data = [
            'payroll_types' => PayrollType::all()
        ];
        return view('pages.payroll.payroll_types')->with($data);
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
     * @param  \App\Models\PayrollType  $payrollType
     * @return \Illuminate\Http\Response
     */
    public function show(PayrollType $payrollType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PayrollType  $payrollType
     * @return \Illuminate\Http\Response
     */
    public function edit(PayrollType $payrollType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PayrollType  $payrollType
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PayrollType $payrollType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PayrollType  $payrollType
     * @return \Illuminate\Http\Response
     */
    public function destroy(PayrollType $payrollType)
    {
        //
    }
}

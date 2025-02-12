<?php

namespace App\Http\Controllers;

use App\Models\Adjustment;
use App\Models\Staff;
use App\Models\StaffBankDetail;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = [
            'staffs' => Staff::getList()
        ];
        return view('pages.staff.staff_index')->with($data);
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
     * @param  \App\Models\Staff  $staff
     * @return \Illuminate\Http\Response
     */
    public function show(Staff $staff)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Staff  $staff
     * @return \Illuminate\Http\Response
     */
    public function edit(Staff $staff)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Staff  $staff
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Staff $staff)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Staff  $staff
     * @return \Illuminate\Http\Response
     */
    public function destroy(Staff $staff)
    {
        //
    }

    public function staff_bank_details(Request $request)
    {
        if($this->handleCrud($request, 'StaffBankDetail')) {
            return back();
        }
        $data = [
            'staff_bank_details' => StaffBankDetail::all()
        ];
        return view('pages.staff.staff_bank_details')->with($data);
    }

    public function adjustment(Request $request)
    {
        if($this->handleCrud($request, 'Adjustment')) {
            return back();
        }
        $data = [
            'adjustments' => Adjustment::all()
        ];
        return view('pages.staff.adjustment')->with($data);
    }

}

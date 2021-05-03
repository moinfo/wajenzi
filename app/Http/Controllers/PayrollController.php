<?php

namespace App\Http\Controllers;

use App\Models\FinancialCharge;
use App\Models\Payroll;
use App\Models\Staff;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'Payroll')) {
            return back();
        }
        $staffs = Staff::getList();
        $payroll = Payroll::all();

        $data = [
            'payroll' => $payroll,
             'staffs' => $staffs
        ];
        return view('pages.payroll.payroll_index')->with($data);
    }
}

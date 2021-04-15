<?php

namespace App\Http\Controllers;

use App\Classes\Utility;
use App\Models\User;
use Illuminate\Http\Request;

class TimesheetController extends Controller
{
    public function index(Request $request) {
        $data = [
            'staff_data' => User::all(),
            'months' => Utility::getMonthNames()
        ];
        return view('pages.timesheet.timesheet_index')->with($data);
    }
}

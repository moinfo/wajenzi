<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request) {
        $data = [];
        return view('pages.leave.leave_index')->with($data);
    }
}

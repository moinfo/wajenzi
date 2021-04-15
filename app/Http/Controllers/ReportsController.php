<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index(Request $request) {
        $data = [];
        return view('pages.reports.reports_index')->with($data);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index(Request $request) {
        $data = [];
        return view('pages.loan.loan_index')->with($data);
    }
}

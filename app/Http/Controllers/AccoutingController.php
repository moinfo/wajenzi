<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccoutingController extends Controller
{
    public function index(Request $request) {
        $data = [];
        return view('pages.accounting.accounting_index')->with($data);
    }
}

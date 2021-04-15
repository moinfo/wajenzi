<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SystemController extends Controller
{
    public function index(Request $request) {
        $data = [];
        return view('pages.system.system_index')->with($data);
    }
}

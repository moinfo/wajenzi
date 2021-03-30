<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    //
    public function index(Request $request) {
        $data = [];
        $this->notify('This is a sample notification', 'Hello dev', 'success');
        return view('pages.dashboard')->with($data);
    }
}

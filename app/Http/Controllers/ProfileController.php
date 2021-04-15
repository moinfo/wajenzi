<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request) {
        $data = [];
        return view('pages.user.user_profile')->with($data);
    }
}

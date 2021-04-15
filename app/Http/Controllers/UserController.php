<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request) {
        $data = [];
        return view('pages.user.user_index')->with($data);
    }
    public function profile(Request $request) {
        $data = [];
        return view('pages.user.user_profile')->with($data);
    }
    public function settings(Request $request) {
        $data = [];
        return view('pages.user.user_settings')->with($data);
    }
    public function inbox(Request $request) {
        $data = [];
        return view('pages.user.user_inbox')->with($data);
    }
    public function notifications(Request $request) {
        $data = [];
        return view('pages.user.user_notifications')->with($data);
    }
}

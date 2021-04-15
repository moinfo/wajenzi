<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RecruitmentController extends Controller
{
    public function index(Request $request) {
        $data = [];
        $this->handleCrud($request, 'Recruitment', $request->input('id'));
        return view('pages.recruitment.recruitment_index')->with($data);
    }
}

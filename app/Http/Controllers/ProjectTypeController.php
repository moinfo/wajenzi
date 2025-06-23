<?php

namespace App\Http\Controllers;

use App\Models\ProjectType;
use Illuminate\Http\Request;

class ProjectTypeController extends Controller
{
    public function index(Request $request) {
        //handle crud operations
        if($this->handleCrud($request, 'ProjectType')) {
            return back();
        }

        $projectTypes = ProjectType::withCount('projects')->get();

        $data = [
            'projectTypes' => $projectTypes
        ];
        return view('pages.projects.project_types')->with($data);
    }
}

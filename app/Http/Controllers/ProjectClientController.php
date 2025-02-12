<?php

namespace App\Http\Controllers;

use App\Models\ProjectClient;
use Illuminate\Http\Request;


class ProjectClientController extends Controller
{
    public function index(Request $request) {
        //handle crud operations
        if($this->handleCrud($request, 'ProjectClient')) {
            return back();
        }

        $clients = \App\Models\ProjectClient::withCount(['projects', 'documents'])->get();

        $data = [
            'clients' => $clients
        ];
        return view('pages.projects.project_clients')->with($data);
    }

    public function client($id){
        $client = \App\Models\ProjectClient::with(['projects', 'documents'])->where('id', $id)->first();

        $data = [
            'client' => $client
        ];
        return view('pages.projects.client')->with($data);
    }
}

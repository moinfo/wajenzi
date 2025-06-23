<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\ProjectClient;
use App\Services\ApprovalService;
use Illuminate\Http\Request;


class ProjectClientController extends Controller
{

    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

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

    public function project_clients($id,$document_type_id){
        // Mark notification as read
        $this->approvalService->markNotificationAsRead($id, $document_type_id,'project_clients');

        $approval_data = \App\Models\ProjectClient::where('id',$id)->get()->first();

        $document_id = $id;

        $details = [
            'First Name' => $approval_data->first_name,
            'Last Name' => $approval_data->last_name,
            'Email' => $approval_data->email,
            'Phone Number' => $approval_data->phone_number,
            'Date Created' => $approval_data->created_at,
            'Uploaded File' => $approval_data->file
        ];

        $data = [
            'document_id' => $document_id,
            'approval_document_type_id' => $document_type_id, //improve $approval_document_type_id
            'page_name' => 'Project Client',
            'approval_data' => $approval_data,
            'approval_data_name' => $approval_data->first_name.' '.$approval_data->last_name,
            'details' => $details,
            'model' => 'ProjectClient',
            'route' => 'project_clients',

        ];
        return view('approvals._approve_page')->with($data);
    }



}

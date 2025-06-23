<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Approval;
use App\Models\ProjectType;
use App\Services\ApprovalService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{

    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }
    public function index(Request $request) {
        //handle crud operations
        if($this->handleCrud($request, 'Project')) {
            return back();
        }

        $projects = Project::all();
        $projectTypes = ProjectType::all();

        $data = [
            'projects' => $projects,
            'projectTypes' => $projectTypes
        ];
        return view('pages.projects.projects')->with($data);
    }

    public function projects($id,$document_type_id){
        // Mark notification as read
        $this->approvalService->markNotificationAsRead($id, $document_type_id,'projects');
        $approval_data = \App\Models\Project::where('id',$id)->get()->first();
        $document_id = $id;
        $details = [
            'Project Name' => $approval_data->project_name ?? null,
            'Client Name' => $approval_data->client->first_name.' '.$approval_data->client->last_name,
            'Project Type' => $approval_data->projectType->name,
            'Phone Number' => $approval_data->client->phone_number,
            'Start Date' => $approval_data->start_date,
            'Expected End Date' => $approval_data->expected_end_date
        ];

        $data = [
            'approval_data' => $approval_data,
            'document_id' => $document_id,
            'approval_document_type_id' => $document_type_id, //improve $approval_document_type_id
            'page_name' => 'Project',
            'approval_data_name' => $approval_data->name,
            'details' => $details,
            'model' => 'Project',
            'route' => 'projects',
        ];
        return view('approvals._approve_page')->with($data);
    }

    // Submit a fund request for approval
    public function submit(Project $project)
    {
        $project->submit();
        return redirect()->back()->with('success', 'Request submitted for approval');
    }

    // Approve a fund request
    public function approve(Project $project)
    {
        $project->approve();
        return redirect()->back()->with('success', 'Request approved');
    }

    // Reject a fund request
    public function reject(Project $project, Request $request)
    {
        $project->reject($request->reason);
        return redirect()->back()->with('success', 'Request rejected');
    }

    // List fund requests with different statuses
//    public function index()
//    {
//        $approvedRequests = Project::approved()->get();
//        $rejectedRequests = Project::rejected()->get();
//        $pendingRequests = Project::submitted()->get();
//
//        return view('fund-requests.index', compact(
//            'approvedRequests',
//            'rejectedRequests',
//            'pendingRequests'
//        ));
//    }
}

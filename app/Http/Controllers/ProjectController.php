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

    //for approvals process
    public function project($id, $document_type_id){
        $project = \App\Models\Project::where('id', $id)->first();
        $approvalStages = Approval::getApprovalStages($id, $document_type_id);
        $nextApproval = Approval::getNextApproval($id, $document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id, $document_type_id);
        $rejected = Approval::isRejected($id, $document_type_id);
        $document_id = $id;

        $data = [
            'project' => $project,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
            'document_id' => $document_id,
        ];
        return view('pages.projects.project')->with($data);
    }

    public function projects($id,$document_type_id){
        // Mark notification as read
        $this->approvalService->markNotificationAsRead($id, $document_type_id,'projects');

        // Get timeline data
        $timeline = $this->approvalService->getApprovalTimeline($document_type_id, $id);

        $approval_data = \App\Models\Project::where('id',$id)->get()->first();

        $approvalStages = Approval::getApprovalStages($id,$document_type_id);
        $nextApproval = Approval::getNextApproval($id,$document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id,$document_type_id);
        $rejected = Approval::isRejected($id,$document_type_id);
        $document_id = $id;

        $details = [
            'Project Name' => $approval_data->project_name ?? null,
            'Client Name' => $approval_data->client->first_name.' '.$approval_data->client->last_name,
            'Project Type' => $approval_data->projectType->name,
            'Phone Number' => $approval_data->client->phone_number,
//            'Total Amount' => number_format($approval_data->total_amount),
            'Start Date' => $approval_data->start_date,
            'Expected End Date' => $approval_data->expected_end_date
        ];

        $data = [
            'timeline' => $timeline,
            'approval_data' => $approval_data,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
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
}

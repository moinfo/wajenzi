<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Approval;
use App\Models\ProjectType;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
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
}

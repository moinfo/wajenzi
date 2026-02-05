<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Project;
use App\Models\ProjectMaterial;
use App\Models\ProjectMaterialInventory;
use App\Models\ProjectMaterialRequest;
use Illuminate\Http\Request;

class ProjectMaterialRequestController extends Controller
{
    public function index(Request $request) {
        //handle crud operations
        if($this->handleCrud($request, 'ProjectMaterialRequest')) {
            return back();
        }

        $requests = ProjectMaterialRequest::with(['project', 'boqItem', 'requester'])->get();
        $projects = Project::all();

        $data = [
            'requests' => $requests,
            'projects' => $projects,
        ];
        return view('pages.projects.project_material_requests')->with($data);
    }

    public function request($id, $document_type_id){
        $request = ProjectMaterialRequest::with(['project', 'boqItem', 'requester', 'approver', 'quotations'])->where('id', $id)->first();
        $approvalStages = Approval::getApprovalStages($id, $document_type_id);
        $nextApproval = Approval::getNextApproval($id, $document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id, $document_type_id);
        $rejected = Approval::isRejected($id, $document_type_id);
        $document_id = $id;

        $data = [
            'request' => $request,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
            'document_id' => $document_id,
        ];
        return view('pages.projects.material_request')->with($data);
    }

    // Process approved request
    public function processRequest($id) {
        $request = ProjectMaterialRequest::findOrFail($id);

        if($request->status !== 'approved') {
            return back()->with('error', 'Only approved requests can be processed');
        }

        // Update inventory
        $inventory = ProjectMaterialInventory::firstOrCreate(
            [
                'project_id' => $request->project_id,
                'material_id' => $request->material_id
            ],
            ['quantity' => 0]
        );

        $inventory->increment('quantity', $request->quantity);
        $request->update(['status' => 'completed']);

        return back()->with('success', 'Material request processed successfully');
    }
}

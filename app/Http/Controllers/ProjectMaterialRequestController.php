<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMaterial;
use App\Models\ProjectMaterialInventory;
use App\Models\ProjectMaterialRequest;
use App\Services\ApprovalService;
use Illuminate\Http\Request;

class ProjectMaterialRequestController extends Controller
{
    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    public function index(Request $request) {
        //handle crud operations
        if($this->handleCrud($request, 'ProjectMaterialRequest')) {
            return back();
        }

        $requests = ProjectMaterialRequest::with(['project', 'boqItem', 'requester', 'approvalStatus'])->get();
        $projects = Project::all();

        $data = [
            'requests' => $requests,
            'projects' => $projects,
        ];
        return view('pages.projects.project_material_requests')->with($data);
    }

    public function request($id, $document_type_id){
        $this->approvalService->markNotificationAsRead($id, $document_type_id, 'project_material_request');

        $materialRequest = ProjectMaterialRequest::with(['project', 'boqItem', 'requester', 'approver', 'quotations'])->where('id', $id)->first();

        $details = [
            'Request Number' => $materialRequest->request_number,
            'Project' => $materialRequest->project->name ?? 'N/A',
            'BOQ Item' => ($materialRequest->boqItem->item_code ?? '') . ' - ' . ($materialRequest->boqItem->description ?? 'N/A'),
            'Quantity Requested' => number_format($materialRequest->quantity_requested, 2) . ' ' . $materialRequest->unit,
            'Priority' => ucfirst($materialRequest->priority ?? 'medium'),
            'Required Date' => $materialRequest->required_date ? \Carbon\Carbon::parse($materialRequest->required_date)->format('d M Y') : 'N/A',
            'Requested By' => $materialRequest->requester->name ?? 'N/A',
        ];

        $data = [
            'approval_data' => $materialRequest,
            'request' => $materialRequest,
            'document_id' => $id,
            'approval_document_type_id' => $document_type_id,
            'page_name' => 'Material Request',
            'approval_data_name' => $materialRequest->request_number,
            'details' => $details,
            'model' => 'ProjectMaterialRequest',
            'route' => 'project_material_request',
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

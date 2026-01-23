<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Approval;
use App\Models\ProjectType;
use App\Models\ServiceType;
use App\Models\User;
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

        $query = Project::with(['client', 'projectType', 'serviceType', 'salesperson', 'projectManager']);

        // Apply filters
        if ($request->filled('project_type_id')) {
            $query->where('project_type_id', $request->project_type_id);
        }

        if ($request->filled('service_type_id')) {
            $query->where('service_type_id', $request->service_type_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('start_date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('salesperson_id')) {
            $query->where('salesperson_id', $request->salesperson_id);
        }

        if ($request->filled('project_manager_id')) {
            $query->where('project_manager_id', $request->project_manager_id);
        }

        $projects = $query->orderBy('created_at', 'desc')->get();
        $projectTypes = ProjectType::all();
        $serviceTypes = ServiceType::all();

        // Get users for filters (salespersons and project managers)
        $salespersons = User::whereHas('roles', function($q) {
            $q->where('name', 'like', '%Sales%');
        })->get();

        $projectManagers = User::whereHas('roles', function($q) {
            $q->where('name', 'like', '%Manager%')
              ->orWhere('name', 'like', '%Architect%');
        })->get();

        // Summary statistics
        $stats = [
            'total' => $projects->count(),
            'active' => $projects->whereIn('status', ['pending', 'in_progress', 'APPROVED'])->count(),
            'completed' => $projects->where('status', 'COMPLETED')->count(),
            'delayed' => $projects->filter(fn($p) => $p->isDelayed())->count(),
            'total_value' => $projects->sum('contract_value'),
        ];

        $data = [
            'projects' => $projects,
            'projectTypes' => $projectTypes,
            'serviceTypes' => $serviceTypes,
            'salespersons' => $salespersons,
            'projectManagers' => $projectManagers,
            'stats' => $stats,
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

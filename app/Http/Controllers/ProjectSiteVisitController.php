<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Project;
use App\Models\ProjectDailyReport;
use App\Models\ProjectSiteVisit;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Http\Request;

class ProjectSiteVisitController extends Controller
{

    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    public function index(Request $request) {
        //handle crud operations
        if($this->handleCrud($request, 'ProjectSiteVisit')) {
            return back();
        }

        $visits = ProjectSiteVisit::with(['project', 'inspector'])
            ->when($request->start_date, function($query) use ($request) {
                return $query->whereDate('visit_date', '>=', $request->start_date);
            })
            ->when($request->end_date, function($query) use ($request) {
                return $query->whereDate('visit_date', '<=', $request->end_date);
            })
            ->when($request->project_id, function($query) use ($request) {
                return $query->where('project_id', $request->project_id);
            })
            ->when($request->status, function($query) use ($request) {
                return $query->where('status', $request->status);
            })
            ->get();

        $projects = Project::all();
//        $inspectors = User::whereHas('roles', function($query) {
//            $query->where('name', 'inspector');
//        })->get();

        $data = [
            'visits' => $visits,
            'projects' => $projects,
//            'inspectors' => $inspectors
        ];
        return view('pages.projects.project_site_visits')->with($data);
    }

    public function project_site_visits($id,$document_type_id){
        // Mark notification as read
        $this->approvalService->markNotificationAsRead($id, $document_type_id,'project_site_visits');

        $approval_data = \App\Models\ProjectSiteVisit::where('id',$id)->get()->first();
        $document_id = $id;

        $details = [
            'Project Name' => $approval_data->project->project_name ?? null,
            'Client Name' => $approval_data->project->client->first_name.' '.$approval_data->project->client->last_name,
            'Project Type' => $approval_data->project->projectType->name,
            'Phone Number' => $approval_data->project->client->phone_number,
//            'Total Amount' => number_format($approval_data->total_amount),
            'Visit Date' => $approval_data->visit_date,
            'Site Location' => $approval_data->location,
            'Description' => $approval_data->description,
//            'Expected End Date' => $approval_data->expected_end_date
        ];

        $data = [
            'approval_data' => $approval_data,
            'document_id' => $document_id,
            'approval_document_type_id' => $document_type_id, //improve $approval_document_type_id
            'page_name' => 'Project Site Visit',
            'approval_data_name' => $approval_data->project->project_name ?? null,
            'details' => $details,
            'model' => 'ProjectSiteVisit',
            'route' => 'project_site_visits',

        ];
        return view('approvals._approve_page')->with($data);
    }


    public function visit($id, $document_type_id) {
        $visit = ProjectSiteVisit::where('id', $id)->first();
        $approvalStages = Approval::getApprovalStages($id, $document_type_id);
        $nextApproval = Approval::getNextApproval($id, $document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id, $document_type_id);
        $rejected = Approval::isRejected($id, $document_type_id);
        $document_id = $id;

        $data = [
            'visit' => $visit,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
            'document_id' => $document_id,
        ];
        return view('pages.projects.project_site_visit')->with($data);
    }

    public function schedule(Request $request) {
        $projectId = $request->project_id;
        $inspectorId = $request->inspector_id;
        $visitDate = $request->visit_date;

        // Check for conflicts
        $existingVisit = ProjectSiteVisit::where('inspector_id', $inspectorId)
            ->whereDate('visit_date', $visitDate)
            ->first();

        if($existingVisit) {
            return response()->json([
                'success' => false,
                'message' => 'Inspector already has a visit scheduled for this date'
            ], 400);
        }

        $visit = ProjectSiteVisit::create([
            'project_id' => $projectId,
            'inspector_id' => $inspectorId,
            'visit_date' => $visitDate,
            'status' => 'scheduled'
        ]);

        // Send notifications
        $visit->project->manager->notify(new SiteVisitScheduled($visit));
        $visit->inspector->notify(new SiteVisitAssigned($visit));

        return response()->json([
            'success' => true,
            'visit' => $visit
        ]);
    }

    public function reschedule(Request $request, $id) {
        $visit = ProjectSiteVisit::findOrFail($id);

        if(!in_array($visit->status, ['scheduled', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only scheduled or cancelled visits can be rescheduled'
            ], 400);
        }

        $visit->update([
            'visit_date' => $request->new_date,
            'status' => 'scheduled'
        ]);

        // Send notifications
        $visit->project->manager->notify(new SiteVisitRescheduled($visit));
        $visit->inspector->notify(new SiteVisitRescheduled($visit));

        return response()->json([
            'success' => true,
            'visit' => $visit
        ]);
    }

    public function complete(Request $request, $id) {
        $visit = ProjectSiteVisit::findOrFail($id);

        $visit->update([
            'findings' => $request->findings,
            'recommendations' => $request->recommendations,
            'status' => 'completed'
        ]);

        // Create a daily report automatically
        ProjectDailyReport::create([
            'project_id' => $visit->project_id,
            'supervisor_id' => $visit->inspector_id,
            'report_date' => $visit->visit_date,
            'work_completed' => $visit->findings,
            'issues_faced' => $request->issues ?? null,
            'weather_conditions' => $request->weather_conditions,
            'labor_hours' => $request->labor_hours ?? 0
        ]);

        return response()->json([
            'success' => true,
            'visit' => $visit
        ]);
    }
}

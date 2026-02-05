<?php

namespace App\Http\Controllers;

use App\Classes\Utility;
use App\Models\Approval;
use App\Models\LaborRequest;
use App\Models\Project;
use App\Models\ProjectConstructionPhase;
use App\Models\Supplier;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaborRequestController extends Controller
{
    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * Display listing of labor requests
     */
    public function index(Request $request)
    {
        if ($this->handleCrud($request, 'LaborRequest')) {
            return back();
        }

        $startDate = $request->input('start_date') ?? date('Y-m-01');
        $endDate = $request->input('end_date') ?? date('Y-m-d');
        $projectId = $request->input('project_id');
        $status = $request->input('status');

        $query = LaborRequest::with(['project', 'artisan', 'requester', 'constructionPhase', 'contract'])
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->orderBy('created_at', 'desc');

        if ($projectId) {
            $query->where('project_id', $projectId);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $requests = $query->get();
        $projects = Project::orderBy('project_name')->get();
        $artisans = Supplier::artisans()->orderBy('name')->get();

        return view('labor.requests.index')->with([
            'requests' => $requests,
            'projects' => $projects,
            'artisans' => $artisans,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'selected_project' => $projectId,
            'selected_status' => $status
        ]);
    }

    /**
     * Show form to create new labor request
     */
    public function create(Request $request)
    {
        $projects = Project::orderBy('project_name')->get();
        $artisans = Supplier::artisans()->orderBy('name')->get();
        $constructionPhases = [];

        if ($request->has('project_id')) {
            $constructionPhases = ProjectConstructionPhase::where('project_id', $request->project_id)->get();
        }

        return view('labor.requests.create')->with([
            'projects' => $projects,
            'artisans' => $artisans,
            'constructionPhases' => $constructionPhases,
            'selectedProject' => $request->project_id
        ]);
    }

    /**
     * Store new labor request
     */
    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'work_description' => 'required|string|min:10',
            'proposed_amount' => 'required|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();

            $materialsListRaw = $request->input('materials_list');
            $materialsList = null;
            if ($materialsListRaw) {
                if (is_string($materialsListRaw)) {
                    $materialsList = json_decode($materialsListRaw, true);
                } else {
                    $materialsList = $materialsListRaw;
                }
            }

            $laborRequest = LaborRequest::create([
                'project_id' => $request->project_id,
                'construction_phase_id' => $request->construction_phase_id,
                'artisan_id' => $request->artisan_id,
                'work_description' => $request->work_description,
                'work_location' => $request->work_location,
                'estimated_duration_days' => $request->estimated_duration_days,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'artisan_assessment' => $request->artisan_assessment,
                'materials_list' => $materialsList,
                'materials_included' => $request->boolean('materials_included'),
                'proposed_amount' => Utility::strip_commas($request->proposed_amount),
                'negotiated_amount' => $request->negotiated_amount ? Utility::strip_commas($request->negotiated_amount) : null,
                'currency' => $request->currency ?? 'TZS',
                'payment_terms' => $request->payment_terms,
                'status' => 'draft'
            ]);

            DB::commit();

            $this->notify('Labor request created: ' . $laborRequest->request_number, 'Success', 'success');
            return redirect()->route('labor.requests.index');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create labor request: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show labor request details
     */
    public function show($id)
    {
        $laborRequest = LaborRequest::with([
            'project',
            'artisan',
            'requester',
            'approver',
            'constructionPhase',
            'contract.paymentPhases'
        ])->findOrFail($id);

        return view('labor.requests.show')->with([
            'request' => $laborRequest
        ]);
    }

    /**
     * Show form to edit labor request
     */
    public function edit($id)
    {
        $laborRequest = LaborRequest::findOrFail($id);

        if (!$laborRequest->isDraft()) {
            return back()->with('error', 'Only draft requests can be edited');
        }

        $projects = Project::orderBy('project_name')->get();
        $artisans = Supplier::artisans()->orderBy('name')->get();
        $constructionPhases = ProjectConstructionPhase::where('project_id', $laborRequest->project_id)->get();

        return view('labor.requests.edit')->with([
            'request' => $laborRequest,
            'projects' => $projects,
            'artisans' => $artisans,
            'constructionPhases' => $constructionPhases
        ]);
    }

    /**
     * Update labor request
     */
    public function update(Request $request, $id)
    {
        $laborRequest = LaborRequest::findOrFail($id);

        if (!$laborRequest->isDraft()) {
            return back()->with('error', 'Only draft requests can be edited');
        }

        $request->validate([
            'work_description' => 'required|string|min:10',
            'proposed_amount' => 'required|numeric|min:0'
        ]);

        try {
            $materialsListRaw = $request->input('materials_list');
            $materialsList = null;
            if ($materialsListRaw) {
                if (is_string($materialsListRaw)) {
                    $materialsList = json_decode($materialsListRaw, true);
                } else {
                    $materialsList = $materialsListRaw;
                }
            }

            $laborRequest->update([
                'construction_phase_id' => $request->construction_phase_id,
                'artisan_id' => $request->artisan_id,
                'work_description' => $request->work_description,
                'work_location' => $request->work_location,
                'estimated_duration_days' => $request->estimated_duration_days,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'artisan_assessment' => $request->artisan_assessment,
                'materials_list' => $materialsList,
                'materials_included' => $request->boolean('materials_included'),
                'proposed_amount' => Utility::strip_commas($request->proposed_amount),
                'negotiated_amount' => $request->negotiated_amount ? Utility::strip_commas($request->negotiated_amount) : null,
                'payment_terms' => $request->payment_terms
            ]);

            $this->notify('Labor request updated', 'Success', 'success');
            return redirect()->route('labor.requests.index');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update labor request: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Submit labor request for approval
     */
    public function submitForApproval($id)
    {
        $laborRequest = LaborRequest::findOrFail($id);

        if (!$laborRequest->isDraft()) {
            return back()->with('error', 'Only draft requests can be submitted for approval');
        }

        if (!$laborRequest->artisan_id) {
            return back()->with('error', 'Please assign an artisan before submitting for approval');
        }

        try {
            $laborRequest->status = 'pending';
            $laborRequest->save();
            $laborRequest->submit();

            $this->notify('Labor request submitted for approval', 'Success', 'success');
            return back();

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to submit for approval: ' . $e->getMessage());
        }
    }

    /**
     * Show labor request approval page
     */
    public function approval($id, $documentTypeId)
    {
        $this->approvalService->markNotificationAsRead($id, $documentTypeId, 'labor/requests');

        $laborRequest = LaborRequest::with([
            'project',
            'artisan',
            'requester',
            'constructionPhase'
        ])->findOrFail($id);

        $details = [
            'Request Number' => $laborRequest->request_number,
            'Project' => $laborRequest->project?->project_name,
            'Artisan' => $laborRequest->artisan?->name,
            'Trade Skill' => $laborRequest->artisan?->trade_skill,
            'Work Location' => $laborRequest->work_location,
            'Construction Phase' => $laborRequest->constructionPhase?->name,
            'Duration (Days)' => $laborRequest->estimated_duration_days,
            'Start Date' => $laborRequest->start_date?->format('Y-m-d'),
            'End Date' => $laborRequest->end_date?->format('Y-m-d'),
            'Materials Included' => $laborRequest->materials_included ? 'Yes' : 'No',
            'Proposed Amount' => number_format($laborRequest->proposed_amount, 2) . ' ' . $laborRequest->currency,
            'Negotiated Amount' => $laborRequest->negotiated_amount ? number_format($laborRequest->negotiated_amount, 2) . ' ' . $laborRequest->currency : 'N/A',
            'Requested By' => $laborRequest->requester?->name,
            'Date Requested' => $laborRequest->created_at?->format('Y-m-d')
        ];

        return view('approvals._approve_page')->with([
            'approval_data' => $laborRequest,
            'document_id' => $id,
            'approval_document_type_id' => $documentTypeId,
            'page_name' => 'Labor Request',
            'approval_data_name' => $laborRequest->request_number,
            'details' => $details,
            'model' => 'LaborRequest',
            'route' => 'labor/requests',
            'work_description' => $laborRequest->work_description,
            'artisan_assessment' => $laborRequest->artisan_assessment,
            'materials_list' => $laborRequest->materials_list,
            'payment_terms' => $laborRequest->payment_terms
        ]);
    }

    /**
     * Update negotiation details
     */
    public function updateNegotiation(Request $request, $id)
    {
        $laborRequest = LaborRequest::findOrFail($id);

        if ($laborRequest->isApproved() || $laborRequest->isContracted()) {
            return back()->with('error', 'Cannot update negotiation for approved/contracted requests');
        }

        $request->validate([
            'negotiated_amount' => 'required|numeric|min:0'
        ]);

        $laborRequest->update([
            'negotiated_amount' => Utility::strip_commas($request->negotiated_amount),
            'artisan_assessment' => $request->artisan_assessment
        ]);

        $this->notify('Negotiation details updated', 'Success', 'success');
        return back();
    }

    /**
     * Record artisan assessment
     */
    public function recordAssessment(Request $request, $id)
    {
        $laborRequest = LaborRequest::findOrFail($id);

        $request->validate([
            'artisan_assessment' => 'required|string'
        ]);

        $laborRequest->update([
            'artisan_assessment' => $request->artisan_assessment
        ]);

        $this->notify('Artisan assessment recorded', 'Success', 'success');
        return back();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\LaborContract;
use App\Models\LaborInspection;
use App\Models\LaborPaymentPhase;
use App\Models\Project;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LaborInspectionController extends Controller
{
    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * Display listing of inspections
     */
    public function index(Request $request)
    {
        if ($this->handleCrud($request, 'LaborInspection')) {
            return back();
        }

        $startDate = $request->input('start_date') ?? date('Y-m-01');
        $endDate = $request->input('end_date') ?? date('Y-m-d');
        $projectId = $request->input('project_id');
        $status = $request->input('status');
        $inspectionType = $request->input('inspection_type');

        $query = LaborInspection::with(['contract.project', 'contract.artisan', 'inspector', 'paymentPhase'])
            ->whereBetween('inspection_date', [$startDate, $endDate])
            ->orderBy('inspection_date', 'desc');

        if ($projectId) {
            $query->whereHas('contract', fn($q) => $q->where('project_id', $projectId));
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($inspectionType) {
            $query->where('inspection_type', $inspectionType);
        }

        $inspections = $query->get();

        // Get contracts pending inspection
        $contractsPendingInspection = LaborContract::with(['project', 'artisan'])
            ->where('status', 'active')
            ->whereHas('paymentPhases', fn($q) => $q->where('status', 'pending'))
            ->orderBy('contract_number')
            ->get();

        $projects = Project::orderBy('project_name')->get();

        return view('labor.inspections.index')->with([
            'inspections' => $inspections,
            'contractsPendingInspection' => $contractsPendingInspection,
            'projects' => $projects,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'selected_project' => $projectId,
            'selected_status' => $status,
            'selected_type' => $inspectionType
        ]);
    }

    /**
     * Show form to create inspection for a contract
     */
    public function create($contractId)
    {
        $contract = LaborContract::with([
            'project',
            'artisan',
            'paymentPhases' => fn($q) => $q->whereIn('status', ['pending', 'due'])->orderBy('phase_number'),
            'workLogs' => fn($q) => $q->orderBy('log_date', 'desc')->limit(5)
        ])->findOrFail($contractId);

        if (!$contract->isActive()) {
            return back()->with('error', 'Inspections can only be created for active contracts');
        }

        // Get the latest progress from work logs
        $latestProgress = $contract->workLogs()->max('progress_percentage') ?? 0;

        return view('labor.inspections.create')->with([
            'contract' => $contract,
            'latestProgress' => $latestProgress
        ]);
    }

    /**
     * Store new inspection
     */
    public function store(Request $request)
    {
        $request->validate([
            'labor_contract_id' => 'required|exists:labor_contracts,id',
            'inspection_type' => 'required|in:progress,milestone,final',
            'work_quality' => 'required|in:excellent,good,acceptable,poor,unacceptable',
            'completion_percentage' => 'required|numeric|min:0|max:100'
        ]);

        $contract = LaborContract::findOrFail($request->labor_contract_id);

        if (!$contract->isActive()) {
            return back()->with('error', 'Inspections can only be created for active contracts');
        }

        try {
            DB::beginTransaction();

            // Handle photo uploads
            $photos = [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $fileName = time() . '_' . $photo->getClientOriginalName();
                    $filePath = $photo->storeAs('uploads/labor_inspections', $fileName, 'public');
                    $photos[] = '/storage/' . $filePath;
                }
            }

            $inspection = LaborInspection::create([
                'labor_contract_id' => $request->labor_contract_id,
                'payment_phase_id' => $request->payment_phase_id,
                'inspection_date' => now(),
                'inspection_type' => $request->inspection_type,
                'work_quality' => $request->work_quality,
                'completion_percentage' => $request->completion_percentage,
                'scope_compliance' => $request->boolean('scope_compliance', true),
                'defects_found' => $request->defects_found,
                'rectification_required' => $request->boolean('rectification_required'),
                'rectification_notes' => $request->rectification_notes,
                'photos' => $photos ?: null,
                'result' => $request->result ?? 'pass',
                'notes' => $request->notes,
                'status' => 'pending'
            ]);

            // Auto-submit for approval
            $inspection->submit();

            DB::commit();

            $this->notify('Inspection created: ' . $inspection->inspection_number, 'Success', 'success');
            return redirect()->route('labor.inspections.index');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create inspection: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show inspection details
     */
    public function show($id)
    {
        $inspection = LaborInspection::with([
            'contract.project',
            'contract.artisan',
            'paymentPhase',
            'inspector',
            'verifier'
        ])->findOrFail($id);

        return view('labor.inspections.show')->with([
            'inspection' => $inspection
        ]);
    }

    /**
     * Show inspection approval page
     */
    public function approval($id, $documentTypeId)
    {
        $this->approvalService->markNotificationAsRead($id, $documentTypeId, 'labor/inspections');

        $inspection = LaborInspection::with([
            'contract.project',
            'contract.artisan',
            'paymentPhase',
            'inspector'
        ])->findOrFail($id);

        $details = [
            'Inspection Number' => $inspection->inspection_number,
            'Inspection Date' => $inspection->inspection_date?->format('Y-m-d'),
            'Contract' => $inspection->contract?->contract_number,
            'Project' => $inspection->contract?->project?->project_name,
            'Artisan' => $inspection->contract?->artisan?->name,
            'Inspection Type' => ucfirst($inspection->inspection_type),
            'Payment Phase' => $inspection->paymentPhase?->phase_name ?? 'N/A',
            'Work Quality' => ucfirst($inspection->work_quality),
            'Completion %' => number_format($inspection->completion_percentage, 1) . '%',
            'Scope Compliance' => $inspection->scope_compliance ? 'Yes' : 'No',
            'Rectification Required' => $inspection->rectification_required ? 'Yes' : 'No',
            'Result' => ucfirst($inspection->result),
            'Inspector' => $inspection->inspector?->name
        ];

        return view('approvals._approve_page')->with([
            'approval_data' => $inspection,
            'document_id' => $id,
            'approval_document_type_id' => $documentTypeId,
            'page_name' => 'Labor Inspection',
            'approval_data_name' => $inspection->inspection_number,
            'details' => $details,
            'model' => 'LaborInspection',
            'route' => 'labor/inspections',
            'defects_found' => $inspection->defects_found,
            'rectification_notes' => $inspection->rectification_notes,
            'notes' => $inspection->notes,
            'photos' => $inspection->photos
        ]);
    }

    /**
     * Show form to edit inspection (only draft)
     */
    public function edit($id)
    {
        $inspection = LaborInspection::with(['contract', 'paymentPhase'])->findOrFail($id);

        if (!$inspection->isDraft()) {
            return back()->with('error', 'Only draft inspections can be edited');
        }

        return view('labor.inspections.edit')->with([
            'inspection' => $inspection
        ]);
    }

    /**
     * Update inspection
     */
    public function update(Request $request, $id)
    {
        $inspection = LaborInspection::findOrFail($id);

        if (!$inspection->isDraft()) {
            return back()->with('error', 'Only draft inspections can be edited');
        }

        $request->validate([
            'work_quality' => 'required|in:excellent,good,acceptable,poor,unacceptable',
            'completion_percentage' => 'required|numeric|min:0|max:100'
        ]);

        try {
            // Handle new photo uploads
            $photos = $inspection->photos ?? [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $fileName = time() . '_' . $photo->getClientOriginalName();
                    $filePath = $photo->storeAs('uploads/labor_inspections', $fileName, 'public');
                    $photos[] = '/storage/' . $filePath;
                }
            }

            $inspection->update([
                'work_quality' => $request->work_quality,
                'completion_percentage' => $request->completion_percentage,
                'scope_compliance' => $request->boolean('scope_compliance', true),
                'defects_found' => $request->defects_found,
                'rectification_required' => $request->boolean('rectification_required'),
                'rectification_notes' => $request->rectification_notes,
                'photos' => $photos ?: null,
                'result' => $request->result ?? 'pass',
                'notes' => $request->notes
            ]);

            $this->notify('Inspection updated', 'Success', 'success');
            return redirect()->route('labor.inspections.show', $inspection->id);

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update inspection: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Submit inspection for approval
     */
    public function submit($id)
    {
        $inspection = LaborInspection::findOrFail($id);

        if (!$inspection->isDraft()) {
            return back()->with('error', 'Only draft inspections can be submitted');
        }

        try {
            $inspection->status = 'pending';
            $inspection->save();
            $inspection->submit();

            $this->notify('Inspection submitted for approval', 'Success', 'success');
            return back();

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to submit inspection: ' . $e->getMessage());
        }
    }

    /**
     * Approval actions
     */
    public function approve(LaborInspection $inspection)
    {
        $inspection->approve();
        return back()->with('success', 'Inspection approved');
    }

    public function reject(LaborInspection $inspection, Request $request)
    {
        $inspection->reject($request->reason);
        return back()->with('success', 'Inspection rejected');
    }
}

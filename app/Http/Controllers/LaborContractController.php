<?php

namespace App\Http\Controllers;

use App\Classes\Utility;
use App\Models\LaborContract;
use App\Models\LaborRequest;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LaborContractController extends Controller
{
    /**
     * Display listing of labor contracts
     */
    public function index(Request $request)
    {
        if ($this->handleCrud($request, 'LaborContract')) {
            return back();
        }

        $projectId = $request->input('project_id');
        $status = $request->input('status');

        $query = LaborContract::with(['project', 'artisan', 'laborRequest', 'supervisor', 'paymentPhases'])
            ->orderBy('created_at', 'desc');

        if ($projectId) {
            $query->where('project_id', $projectId);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $contracts = $query->get();
        $projects = Project::orderBy('project_name')->get();

        // Get approved requests available for contract creation
        $availableRequests = LaborRequest::availableForContract()->with(['project', 'artisan'])->get();

        return view('labor.contracts.index')->with([
            'contracts' => $contracts,
            'projects' => $projects,
            'availableRequests' => $availableRequests,
            'selected_project' => $projectId,
            'selected_status' => $status
        ]);
    }

    /**
     * Show form to create contract from approved request
     */
    public function create($requestId)
    {
        $laborRequest = LaborRequest::with(['project', 'artisan', 'constructionPhase'])
            ->findOrFail($requestId);

        if (!$laborRequest->canCreateContract()) {
            return back()->with('error', 'This request is not eligible for contract creation');
        }

        $supervisors = User::role('Site Supervisor')->orderBy('name')->get();

        // Default payment phases structure
        $defaultPhases = [
            ['phase_number' => 1, 'phase_name' => 'Mobilization', 'percentage' => 20, 'milestone_description' => 'Contract signed and work commenced'],
            ['phase_number' => 2, 'phase_name' => 'Progress', 'percentage' => 30, 'milestone_description' => '50% work completed'],
            ['phase_number' => 3, 'phase_name' => 'Substantial', 'percentage' => 30, 'milestone_description' => '90% work completed'],
            ['phase_number' => 4, 'phase_name' => 'Final', 'percentage' => 20, 'milestone_description' => 'Final inspection approved'],
        ];

        return view('labor.contracts.create')->with([
            'request' => $laborRequest,
            'supervisors' => $supervisors,
            'defaultPhases' => $defaultPhases
        ]);
    }

    /**
     * Store new contract with payment phases
     */
    public function store(Request $request)
    {
        $request->validate([
            'labor_request_id' => 'required|exists:labor_requests,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'scope_of_work' => 'required|string',
            'total_amount' => 'required|numeric|min:0'
        ]);

        $laborRequest = LaborRequest::findOrFail($request->labor_request_id);

        if (!$laborRequest->canCreateContract()) {
            return back()->with('error', 'This request is not eligible for contract creation');
        }

        try {
            DB::beginTransaction();

            $contract = LaborContract::create([
                'labor_request_id' => $request->labor_request_id,
                'project_id' => $laborRequest->project_id,
                'artisan_id' => $laborRequest->artisan_id,
                'supervisor_id' => $request->supervisor_id,
                'contract_date' => now(),
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'scope_of_work' => $request->scope_of_work,
                'terms_conditions' => $request->terms_conditions,
                'total_amount' => Utility::strip_commas($request->total_amount),
                'currency' => $laborRequest->currency,
                'status' => 'draft'
            ]);

            // Create payment phases
            if ($request->has('phases')) {
                foreach ($request->phases as $phaseData) {
                    $contract->paymentPhases()->create([
                        'phase_number' => $phaseData['phase_number'],
                        'phase_name' => $phaseData['phase_name'],
                        'percentage' => $phaseData['percentage'],
                        'amount' => ($phaseData['percentage'] / 100) * $contract->total_amount,
                        'milestone_description' => $phaseData['milestone_description'] ?? null,
                        'status' => 'pending'
                    ]);
                }
            } else {
                // Use default phases
                $contract->createDefaultPaymentPhases();
            }

            // Update labor request status
            $laborRequest->update(['status' => 'contracted']);

            DB::commit();

            $this->notify('Labor contract created: ' . $contract->contract_number, 'Success', 'success');
            return redirect()->route('labor.contracts.show', $contract->id);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create contract: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show contract details
     */
    public function show($id)
    {
        $contract = LaborContract::with([
            'project',
            'artisan',
            'laborRequest',
            'supervisor',
            'paymentPhases',
            'workLogs' => fn($q) => $q->orderBy('log_date', 'desc')->limit(10),
            'inspections' => fn($q) => $q->orderBy('inspection_date', 'desc')
        ])->findOrFail($id);

        return view('labor.contracts.show')->with([
            'contract' => $contract
        ]);
    }

    /**
     * Show form to edit contract
     */
    public function edit($id)
    {
        $contract = LaborContract::with(['paymentPhases'])->findOrFail($id);

        if (!$contract->isDraft()) {
            return back()->with('error', 'Only draft contracts can be edited');
        }

        $supervisors = User::role('Site Supervisor')->orderBy('name')->get();

        return view('labor.contracts.edit')->with([
            'contract' => $contract,
            'supervisors' => $supervisors
        ]);
    }

    /**
     * Update contract
     */
    public function update(Request $request, $id)
    {
        $contract = LaborContract::findOrFail($id);

        if (!$contract->isDraft()) {
            return back()->with('error', 'Only draft contracts can be edited');
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'scope_of_work' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            $contract->update([
                'supervisor_id' => $request->supervisor_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'scope_of_work' => $request->scope_of_work,
                'terms_conditions' => $request->terms_conditions
            ]);

            // Update payment phases if provided
            if ($request->has('phases')) {
                $contract->paymentPhases()->delete();
                foreach ($request->phases as $phaseData) {
                    $contract->paymentPhases()->create([
                        'phase_number' => $phaseData['phase_number'],
                        'phase_name' => $phaseData['phase_name'],
                        'percentage' => $phaseData['percentage'],
                        'amount' => ($phaseData['percentage'] / 100) * $contract->total_amount,
                        'milestone_description' => $phaseData['milestone_description'] ?? null,
                        'status' => 'pending'
                    ]);
                }
            }

            DB::commit();

            $this->notify('Contract updated', 'Success', 'success');
            return redirect()->route('labor.contracts.show', $contract->id);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update contract: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Sign contract (capture signatures and activate)
     */
    public function sign(Request $request, $id)
    {
        $contract = LaborContract::with(['artisan', 'supervisor'])->findOrFail($id);

        if (!$contract->isDraft()) {
            return back()->with('error', 'Contract is already signed/active');
        }

        try {
            // Use supervisor's profile signature if available
            if ($contract->supervisor && $contract->supervisor->profile) {
                $contract->supervisor_signature = $contract->supervisor->profile;
            }

            // For artisan signature, check if they have a stored signature
            if ($contract->artisan && $contract->artisan->profile) {
                $contract->artisan_signature = $contract->artisan->profile;
            }

            // Handle uploaded contract file
            if ($request->hasFile('contract_file')) {
                $file = $request->file('contract_file');
                $fileName = time() . '_contract_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('uploads/labor_contracts', $fileName, 'public');
                $contract->contract_file = '/storage/' . $filePath;
            }

            // Activate contract
            $contract->status = 'active';

            // Mark first payment phase as due (mobilization)
            $firstPhase = $contract->paymentPhases()->where('phase_number', 1)->first();
            if ($firstPhase) {
                $firstPhase->markAsDue();
            }

            $contract->save();

            $this->notify('Contract signed and activated', 'Success', 'success');
            return redirect()->route('labor.contracts.show', $contract->id);

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to sign contract: ' . $e->getMessage());
        }
    }

    /**
     * Generate contract PDF
     */
    public function generatePDF($id)
    {
        $contract = LaborContract::with([
            'project',
            'artisan',
            'laborRequest',
            'supervisor',
            'paymentPhases'
        ])->findOrFail($id);

        $pdf = Pdf::loadView('labor.contracts.pdf', ['contract' => $contract]);

        return $pdf->download('Labor_Contract_' . $contract->contract_number . '.pdf');
    }

    /**
     * Terminate contract early
     */
    public function terminate(Request $request, $id)
    {
        $contract = LaborContract::findOrFail($id);

        if ($contract->isCompleted() || $contract->isTerminated()) {
            return back()->with('error', 'Contract is already completed or terminated');
        }

        $request->validate([
            'termination_reason' => 'required|string|min:10'
        ]);

        try {
            $contract->update([
                'status' => 'terminated',
                'actual_end_date' => now(),
                'notes' => $contract->notes . "\n\nTermination Reason: " . $request->termination_reason
            ]);

            // Put all pending payment phases on hold
            $contract->paymentPhases()
                ->whereIn('status', ['pending', 'due', 'approved'])
                ->update(['status' => 'held']);

            $this->notify('Contract terminated', 'Warning', 'warning');
            return redirect()->route('labor.contracts.index');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to terminate contract: ' . $e->getMessage());
        }
    }

    /**
     * Put contract on hold
     */
    public function putOnHold(Request $request, $id)
    {
        $contract = LaborContract::findOrFail($id);

        if (!$contract->isActive()) {
            return back()->with('error', 'Only active contracts can be put on hold');
        }

        $contract->update([
            'status' => 'on_hold',
            'notes' => $contract->notes . "\n\nPut on hold: " . ($request->reason ?? 'No reason specified')
        ]);

        $this->notify('Contract put on hold', 'Info', 'info');
        return back();
    }

    /**
     * Resume contract from hold
     */
    public function resume($id)
    {
        $contract = LaborContract::findOrFail($id);

        if (!$contract->isOnHold()) {
            return back()->with('error', 'Contract is not on hold');
        }

        $contract->update([
            'status' => 'active',
            'notes' => $contract->notes . "\n\nResumed on: " . now()->format('Y-m-d H:i')
        ]);

        $this->notify('Contract resumed', 'Success', 'success');
        return back();
    }
}

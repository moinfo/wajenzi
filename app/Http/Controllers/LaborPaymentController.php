<?php

namespace App\Http\Controllers;

use App\Classes\Utility;
use App\Models\LaborContract;
use App\Models\LaborPaymentPhase;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaborPaymentController extends Controller
{
    /**
     * Display listing of payment phases
     */
    public function index(Request $request)
    {
        $projectId = $request->input('project_id');
        $status = $request->input('status');
        $contractId = $request->input('contract_id');

        $query = LaborPaymentPhase::with([
            'contract.project',
            'contract.artisan',
            'paidByUser'
        ])
            ->orderByRaw("FIELD(status, 'due', 'approved', 'pending', 'held', 'paid')")
            ->orderBy('created_at', 'desc');

        if ($projectId) {
            $query->whereHas('contract', fn($q) => $q->where('project_id', $projectId));
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($contractId) {
            $query->where('labor_contract_id', $contractId);
        }

        $phases = $query->get();

        // Summary stats
        $stats = [
            'pending_count' => LaborPaymentPhase::where('status', 'pending')->count(),
            'due_count' => LaborPaymentPhase::where('status', 'due')->count(),
            'due_amount' => LaborPaymentPhase::where('status', 'due')->sum('amount'),
            'approved_count' => LaborPaymentPhase::where('status', 'approved')->count(),
            'approved_amount' => LaborPaymentPhase::where('status', 'approved')->sum('amount'),
            'paid_count' => LaborPaymentPhase::where('status', 'paid')->count(),
            'paid_amount' => LaborPaymentPhase::where('status', 'paid')->sum('amount'),
            'held_count' => LaborPaymentPhase::where('status', 'held')->count(),
            'held_amount' => LaborPaymentPhase::where('status', 'held')->sum('amount'),
        ];

        $projects = Project::orderBy('project_name')->get();
        $contracts = LaborContract::with('artisan')
            ->whereIn('status', ['active', 'completed'])
            ->orderBy('contract_number')
            ->get();

        return view('labor.payments.index')->with([
            'phases' => $phases,
            'stats' => $stats,
            'projects' => $projects,
            'contracts' => $contracts,
            'selected_project' => $projectId,
            'selected_status' => $status,
            'selected_contract' => $contractId
        ]);
    }

    /**
     * Show payment phases for a specific contract
     */
    public function contractPayments($contractId)
    {
        $contract = LaborContract::with([
            'project',
            'artisan',
            'paymentPhases.paidByUser',
            'paymentPhases.inspections'
        ])->findOrFail($contractId);

        return view('labor.payments.contract')->with([
            'contract' => $contract
        ]);
    }

    /**
     * Show details for a single payment phase
     */
    public function show($phaseId)
    {
        $phase = LaborPaymentPhase::with([
            'contract.project',
            'contract.artisan',
            'paidByUser',
            'inspections'
        ])->findOrFail($phaseId);

        return view('labor.payments.show')->with([
            'phase' => $phase
        ]);
    }

    /**
     * Approve a payment phase (MD action)
     */
    public function approve($phaseId)
    {
        $phase = LaborPaymentPhase::findOrFail($phaseId);

        if (!$phase->canBeApproved()) {
            return back()->with('error', 'This payment phase cannot be approved. Status must be "due".');
        }

        try {
            $phase->approve();

            $this->notify('Payment phase approved for processing', 'Success', 'success');
            return back();

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to approve payment: ' . $e->getMessage());
        }
    }

    /**
     * Show payment processing form (Finance action)
     */
    public function processForm($phaseId)
    {
        $phase = LaborPaymentPhase::with([
            'contract.project',
            'contract.artisan'
        ])->findOrFail($phaseId);

        if (!$phase->canBePaid()) {
            return back()->with('error', 'This payment phase cannot be processed. Status must be "approved".');
        }

        return view('labor.payments.process')->with([
            'phase' => $phase
        ]);
    }

    /**
     * Process payment for a phase (Finance action)
     */
    public function process(Request $request, $phaseId)
    {
        $phase = LaborPaymentPhase::findOrFail($phaseId);

        if (!$phase->canBePaid()) {
            return back()->with('error', 'This payment phase cannot be processed. Status must be "approved".');
        }

        $request->validate([
            'payment_reference' => 'required|string|max:100'
        ]);

        try {
            DB::beginTransaction();

            $phase->processPayment(
                $request->payment_reference,
                $request->notes
            );

            DB::commit();

            $this->notify('Payment processed successfully', 'Success', 'success');
            return redirect()->route('labor.payments.index');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to process payment: ' . $e->getMessage());
        }
    }

    /**
     * Put payment on hold
     */
    public function hold(Request $request, $phaseId)
    {
        $phase = LaborPaymentPhase::findOrFail($phaseId);

        if ($phase->isPaid()) {
            return back()->with('error', 'Paid phases cannot be put on hold');
        }

        $request->validate([
            'hold_reason' => 'required|string|min:10'
        ]);

        try {
            $phase->putOnHold($request->hold_reason);

            $this->notify('Payment put on hold', 'Warning', 'warning');
            return back();

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to put payment on hold: ' . $e->getMessage());
        }
    }

    /**
     * Release payment from hold
     */
    public function release($phaseId)
    {
        $phase = LaborPaymentPhase::findOrFail($phaseId);

        if (!$phase->isHeld()) {
            return back()->with('error', 'Only held payments can be released');
        }

        try {
            $phase->update([
                'status' => 'due',
                'notes' => $phase->notes . "\n\nReleased from hold on: " . now()->format('Y-m-d H:i')
            ]);

            $this->notify('Payment released from hold', 'Success', 'success');
            return back();

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to release payment: ' . $e->getMessage());
        }
    }

    /**
     * Bulk approve multiple phases
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'phase_ids' => 'required|array',
            'phase_ids.*' => 'exists:labor_payment_phases,id'
        ]);

        $approved = 0;
        $failed = 0;

        foreach ($request->phase_ids as $phaseId) {
            $phase = LaborPaymentPhase::find($phaseId);
            if ($phase && $phase->canBeApproved()) {
                $phase->approve();
                $approved++;
            } else {
                $failed++;
            }
        }

        if ($approved > 0) {
            $this->notify("$approved payment(s) approved", 'Success', 'success');
        }
        if ($failed > 0) {
            $this->notify("$failed payment(s) could not be approved", 'Warning', 'warning');
        }

        return back();
    }

    /**
     * Payment summary report
     */
    public function report(Request $request)
    {
        $startDate = $request->input('start_date') ?? date('Y-01-01');
        $endDate = $request->input('end_date') ?? date('Y-m-d');
        $projectId = $request->input('project_id');

        $query = LaborPaymentPhase::with(['contract.project', 'contract.artisan'])
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$startDate, $endDate . ' 23:59:59']);

        if ($projectId) {
            $query->whereHas('contract', fn($q) => $q->where('project_id', $projectId));
        }

        $payments = $query->orderBy('paid_at', 'desc')->get();

        // Group by project
        $byProject = $payments->groupBy(fn($p) => $p->contract->project_id)
            ->map(fn($group) => [
                'project' => $group->first()->contract->project,
                'count' => $group->count(),
                'total' => $group->sum('amount')
            ]);

        // Group by artisan
        $byArtisan = $payments->groupBy(fn($p) => $p->contract->artisan_id)
            ->map(fn($group) => [
                'artisan' => $group->first()->contract->artisan,
                'count' => $group->count(),
                'total' => $group->sum('amount')
            ]);

        $projects = Project::orderBy('project_name')->get();

        return view('labor.payments.report')->with([
            'payments' => $payments,
            'byProject' => $byProject,
            'byArtisan' => $byArtisan,
            'projects' => $projects,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'selected_project' => $projectId,
            'total_amount' => $payments->sum('amount')
        ]);
    }
}

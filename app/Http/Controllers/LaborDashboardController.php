<?php

namespace App\Http\Controllers;

use App\Models\LaborContract;
use App\Models\LaborInspection;
use App\Models\LaborPaymentPhase;
use App\Models\LaborRequest;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

class LaborDashboardController extends Controller
{
    public function index(Request $request)
    {
        $projectId = $request->input('project_id');

        // Active contracts stats
        $contractsQuery = LaborContract::query();
        if ($projectId) {
            $contractsQuery->where('project_id', $projectId);
        }

        $activeContracts = (clone $contractsQuery)->where('status', 'active')->count();
        $activeContractValue = (clone $contractsQuery)->where('status', 'active')->sum('total_amount');
        $completedContracts = (clone $contractsQuery)->where('status', 'completed')->count();

        // Pending approvals
        $pendingRequests = LaborRequest::where('status', 'pending')->count();
        $pendingInspections = LaborInspection::whereIn('status', ['pending', 'verified'])->count();

        // Payment stats
        $pendingPaymentPhases = LaborPaymentPhase::whereIn('status', ['due', 'approved'])->count();
        $pendingPaymentAmount = LaborPaymentPhase::whereIn('status', ['due', 'approved'])->sum('amount');
        $paidAmount = LaborPaymentPhase::where('status', 'paid')
            ->when($projectId, fn($q) => $q->whereHas('contract', fn($c) => $c->where('project_id', $projectId)))
            ->sum('amount');

        // Recent activity
        $recentRequests = LaborRequest::with(['project', 'artisan', 'requester'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentContracts = LaborContract::with(['project', 'artisan'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentInspections = LaborInspection::with(['contract.project', 'inspector'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Contracts nearing end date (within 7 days)
        $contractsNearingEnd = LaborContract::with(['project', 'artisan'])
            ->where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays(7)])
            ->orderBy('end_date')
            ->get();

        // Overdue contracts
        $overdueContracts = LaborContract::with(['project', 'artisan'])
            ->where('status', 'active')
            ->where('end_date', '<', now())
            ->orderBy('end_date')
            ->get();

        $projects = Project::orderBy('project_name')->get();

        return view('labor.dashboard')->with([
            'activeContracts' => $activeContracts,
            'activeContractValue' => $activeContractValue,
            'completedContracts' => $completedContracts,
            'pendingRequests' => $pendingRequests,
            'pendingInspections' => $pendingInspections,
            'pendingPaymentPhases' => $pendingPaymentPhases,
            'pendingPaymentAmount' => $pendingPaymentAmount,
            'paidAmount' => $paidAmount,
            'recentRequests' => $recentRequests,
            'recentContracts' => $recentContracts,
            'recentInspections' => $recentInspections,
            'contractsNearingEnd' => $contractsNearingEnd,
            'overdueContracts' => $overdueContracts,
            'projects' => $projects,
            'selectedProject' => $projectId
        ]);
    }

    public function trainingGuide()
    {
        $pdf = PDF::loadView('labor.training-guide-pdf');
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('Labor_Procurement_Training_Guide.pdf');
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LaborContract;
use App\Models\LaborInspection;
use App\Models\LaborPaymentPhase;
use App\Models\LaborRequest;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PDF;

class LaborDashboardApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $projectId = $request->integer('project_id');

        $contractsQuery = LaborContract::query();
        if ($projectId) {
            $contractsQuery->where('project_id', $projectId);
        }

        $activeContracts = (clone $contractsQuery)->where('status', 'active')->count();
        $activeContractValue = (clone $contractsQuery)->where('status', 'active')->sum('total_amount');
        $completedContracts = (clone $contractsQuery)->where('status', 'completed')->count();

        // NOTE: To match web LaborDashboardController@index semantics, the
        // pending/inspection/payment stats and the recent/nearing/overdue lists
        // below are NOT project-filtered. Only the contract stats (above) and
        // paid_amount (below) are filtered by the selected project on web.
        $pendingRequests = LaborRequest::query()
            ->where('status', 'pending')
            ->count();

        $pendingInspections = LaborInspection::query()
            ->whereIn('status', ['pending', 'verified'])
            ->count();

        $pendingPaymentPhases = LaborPaymentPhase::query()
            ->whereIn('status', ['due', 'approved'])
            ->count();

        $pendingPaymentAmount = LaborPaymentPhase::query()
            ->whereIn('status', ['due', 'approved'])
            ->sum('amount');

        $paidAmount = LaborPaymentPhase::query()
            ->when(
                $projectId,
                fn ($q) => $q->whereHas('contract', fn ($c) => $c->where('project_id', $projectId))
            )
            ->where('status', 'paid')
            ->sum('amount');

        $recentRequests = LaborRequest::with(['project', 'artisan', 'requester'])
            ->latest()
            ->limit(5)
            ->get();

        $recentContracts = LaborContract::with(['project', 'artisan'])
            ->latest()
            ->limit(5)
            ->get();

        $recentInspections = LaborInspection::with(['contract.project', 'contract.artisan', 'inspector'])
            ->latest()
            ->limit(5)
            ->get();

        $contractsNearingEnd = LaborContract::with(['project', 'artisan'])
            ->where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays(7)])
            ->orderBy('end_date')
            ->get();

        $overdueContracts = LaborContract::with(['project', 'artisan'])
            ->where('status', 'active')
            ->where('end_date', '<', now())
            ->orderBy('end_date')
            ->get();

        $projects = Project::orderBy('project_name')
            ->get(['id', 'project_name', 'document_number']);

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'active_contracts' => $activeContracts,
                    'active_contract_value' => (float) $activeContractValue,
                    'completed_contracts' => $completedContracts,
                    'pending_requests' => $pendingRequests,
                    'pending_inspections' => $pendingInspections,
                    'pending_payment_phases' => $pendingPaymentPhases,
                    'pending_payment_amount' => (float) $pendingPaymentAmount,
                    'paid_amount' => (float) $paidAmount,
                ],
                'actions_required' => [
                    'pending_requests' => $pendingRequests,
                    'pending_inspections' => $pendingInspections,
                    'payments_due' => $pendingPaymentPhases,
                ],
                'recent_requests' => $recentRequests->map(fn (LaborRequest $item) => [
                    'id' => $item->id,
                    'request_number' => $item->request_number,
                    'status' => $item->status,
                    'status_badge_class' => $item->status_badge_class,
                    'project_name' => $item->project?->project_name ?? $item->project?->name,
                    'artisan_name' => $item->artisan?->name,
                    'requester_name' => $item->requester?->name,
                    'work_description' => $item->work_description,
                    'created_at' => $item->created_at?->toISOString(),
                ])->values(),
                'recent_contracts' => $recentContracts->map(fn (LaborContract $item) => [
                    'id' => $item->id,
                    'contract_number' => $item->contract_number,
                    'status' => $item->status,
                    'status_badge_class' => $item->status_badge_class,
                    'project_name' => $item->project?->project_name ?? $item->project?->name,
                    'artisan_name' => $item->artisan?->name,
                    'payment_progress' => (float) $item->payment_progress,
                    'total_amount' => (float) $item->total_amount,
                    'amount_paid' => (float) $item->amount_paid,
                    'end_date' => $item->end_date?->toDateString(),
                    'created_at' => $item->created_at?->toISOString(),
                ])->values(),
                'recent_inspections' => $recentInspections->map(fn (LaborInspection $item) => [
                    'id' => $item->id,
                    'inspection_number' => $item->inspection_number,
                    'contract_number' => $item->contract?->contract_number,
                    'artisan_name' => $item->contract?->artisan?->name,
                    'project_name' => $item->contract?->project?->project_name ?? $item->contract?->project?->name,
                    'inspection_type' => $item->inspection_type,
                    'type_badge_class' => $item->type_badge_class,
                    'completion_percentage' => (float) $item->completion_percentage,
                    'result' => $item->result,
                    'result_badge_class' => $item->result_badge_class,
                    'status' => $item->status,
                    'status_badge_class' => $item->status_badge_class,
                    'inspection_date' => $item->inspection_date?->toDateString(),
                ])->values(),
                'contracts_nearing_end' => $contractsNearingEnd->map(fn (LaborContract $item) => [
                    'id' => $item->id,
                    'contract_number' => $item->contract_number,
                    'artisan_name' => $item->artisan?->name,
                    'project_name' => $item->project?->project_name ?? $item->project?->name,
                    'days_remaining' => $item->days_remaining,
                    'end_date' => $item->end_date?->toDateString(),
                ])->values(),
                'overdue_contracts' => $overdueContracts->map(fn (LaborContract $item) => [
                    'id' => $item->id,
                    'contract_number' => $item->contract_number,
                    'artisan_name' => $item->artisan?->name,
                    'project_name' => $item->project?->project_name ?? $item->project?->name,
                    'days_overdue' => $item->days_overdue,
                    'end_date' => $item->end_date?->toDateString(),
                ])->values(),
                'projects' => $projects->map(fn (Project $project) => [
                    'id' => $project->id,
                    'project_name' => $project->project_name,
                    'document_number' => $project->document_number,
                ])->values(),
                'selected_project' => $projectId,
            ],
        ]);
    }

    /**
     * Return the Labor Procurement training guide as a base64-encoded PDF.
     *
     * Mirrors web LaborDashboardController@trainingGuide, which streams the
     * `labor.training-guide-pdf` view as `Labor_Procurement_Training_Guide.pdf`.
     */
    public function trainingGuide(): JsonResponse
    {
        $filename = 'Labor_Procurement_Training_Guide.pdf';

        $pdf = PDF::loadView('labor.training-guide-pdf');
        $pdf->setPaper('A4', 'portrait');

        return response()->json([
            'success' => true,
            'data' => [
                'pdf_base64' => base64_encode($pdf->output()),
                'filename' => $filename,
            ],
            'message' => 'Training guide generated successfully.',
        ]);
    }
}

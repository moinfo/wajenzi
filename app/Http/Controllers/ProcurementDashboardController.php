<?php

namespace App\Http\Controllers;

use App\Models\MaterialInspection;
use App\Models\Project;
use App\Models\ProjectBoqItem;
use App\Models\ProjectMaterialInventory;
use App\Models\ProjectMaterialMovement;
use App\Models\ProjectMaterialRequest;
use App\Models\Purchase;
use App\Models\QuotationComparison;
use App\Models\SupplierQuotation;
use App\Models\SupplierReceiving;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcurementDashboardController extends Controller
{
    /**
     * Main procurement dashboard
     */
    public function index(Request $request)
    {
        // Overall statistics
        $stats = [
            'total_requests' => ProjectMaterialRequest::count(),
            'pending_requests' => ProjectMaterialRequest::where('status', 'pending')->count(),
            'approved_requests' => ProjectMaterialRequest::whereRaw('UPPER(status) = ?', ['APPROVED'])->count(),

            'total_quotations' => SupplierQuotation::count(),
            'pending_comparisons' => QuotationComparison::where('status', 'pending')->count(),
            'approved_comparisons' => QuotationComparison::whereRaw('UPPER(status) = ?', ['APPROVED'])->count(),

            'total_purchases' => Purchase::whereNotNull('project_id')->count(),
            'pending_deliveries' => SupplierReceiving::pendingInspection()->count(),

            'total_inspections' => MaterialInspection::count(),
            'pending_inspections' => MaterialInspection::where('status', 'pending')->count(),
            'approved_inspections' => MaterialInspection::whereRaw('UPPER(status) = ?', ['APPROVED'])->count(),
        ];

        // Recent activity
        $recentRequests = ProjectMaterialRequest::with(['project', 'requester'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentComparisons = QuotationComparison::with(['materialRequest.project', 'selectedQuotation.supplier'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentInspections = MaterialInspection::with(['project', 'supplierReceiving.supplier'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Pending actions requiring attention
        $pendingActions = [
            'requests_pending_approval' => ProjectMaterialRequest::where('status', 'pending')->count(),
            'requests_needing_quotations' => ProjectMaterialRequest::whereRaw('UPPER(status) = ?', ['APPROVED'])
                ->whereDoesntHave('quotations')
                ->count(),
            'requests_ready_for_comparison' => $this->getRequestsReadyForComparison(),
            'comparisons_pending_approval' => QuotationComparison::where('status', 'pending')->count(),
            'deliveries_pending_inspection' => SupplierReceiving::pendingInspection()->count(),
            'inspections_pending_approval' => MaterialInspection::where('status', 'pending')->count(),
        ];

        // Projects with active procurement
        $activeProjects = Project::whereHas('materialRequests')
            ->withCount(['materialRequests', 'boqs'])
            ->orderBy('material_requests_count', 'desc')
            ->limit(10)
            ->get();

        // Low stock alerts
        $lowStockItems = ProjectMaterialInventory::with(['project', 'boqItem'])
            ->lowStock()
            ->limit(10)
            ->get();

        return view('pages.procurement.dashboard')->with([
            'stats' => $stats,
            'recentRequests' => $recentRequests,
            'recentComparisons' => $recentComparisons,
            'recentInspections' => $recentInspections,
            'pendingActions' => $pendingActions,
            'activeProjects' => $activeProjects,
            'lowStockItems' => $lowStockItems
        ]);
    }

    /**
     * Project-specific procurement dashboard
     */
    public function project($id)
    {
        $project = Project::with(['boqs.items', 'constructionPhases.boqItems'])->findOrFail($id);

        // BOQ Items with procurement tracking
        $boqItems = ProjectBoqItem::byProject($id)
            ->with(['category', 'materialRequests', 'purchaseItems', 'constructionPhase'])
            ->get();

        // Calculate stats
        $completeCount = $boqItems->where('procurement_status', 'complete')->count();
        $inProgressCount = $boqItems->where('procurement_status', 'in_progress')->count();
        $notStartedCount = $boqItems->where('procurement_status', 'not_started')->count();
        $totalCount = $boqItems->count();

        $stats = [
            'total_boq_items' => $totalCount,
            'total_requests' => ProjectMaterialRequest::where('project_id', $id)->count(),
            'pending_requests' => ProjectMaterialRequest::where('project_id', $id)
                ->where('status', 'pending')->count(),
            'overall_progress' => $totalCount > 0
                ? ($boqItems->sum('procurement_percentage') / $totalCount)
                : 0,
            'complete_count' => $completeCount,
            'in_progress_count' => $inProgressCount,
            'not_started_count' => $notStartedCount,
            'complete_percentage' => $totalCount > 0 ? ($completeCount / $totalCount * 100) : 0,
            'in_progress_percentage' => $totalCount > 0 ? ($inProgressCount / $totalCount * 100) : 0,
            'not_started_percentage' => $totalCount > 0 ? ($notStartedCount / $totalCount * 100) : 0,
        ];

        // Budget calculations
        $totalBudget = $boqItems->sum('total_price') ?: $boqItems->sum(fn($item) => $item->quantity * $item->unit_price);
        $totalOrdered = $boqItems->sum('budget_used') ?: 0;
        $budget = [
            'total_budget' => $totalBudget,
            'total_ordered' => $totalOrdered,
            'remaining' => $totalBudget - $totalOrdered,
            'utilization' => $totalBudget > 0 ? ($totalOrdered / $totalBudget * 100) : 0,
        ];

        // Construction phases with BOQ items
        $phases = $project->constructionPhases()->with(['boqItems' => function ($query) {
            $query->withCount('materialRequests');
        }])->withCount('boqItems')->get();

        // Pending actions
        $pendingActions = [
            'requests_pending' => ProjectMaterialRequest::where('project_id', $id)
                ->where('status', 'pending')->count(),
            'comparisons_pending' => QuotationComparison::whereHas('materialRequest', function ($q) use ($id) {
                $q->where('project_id', $id);
            })->where('status', 'pending')->count(),
            'deliveries_pending' => SupplierReceiving::where('project_id', $id)
                ->whereDoesntHave('inspection')->count(),
            'inspections_pending' => MaterialInspection::where('project_id', $id)
                ->where('status', 'pending')->count(),
        ];

        // Recent activity (combine requests, comparisons, inspections)
        $recentActivity = collect();
        ProjectMaterialRequest::with(['items.boqItem'])->where('project_id', $id)
            ->orderBy('created_at', 'desc')->limit(5)->get()
            ->each(function ($item) use (&$recentActivity) {
                $recentActivity->push((object)[
                    'type' => 'request',
                    'type_label' => 'Material Request',
                    'reference_number' => $item->request_number,
                    'description' => $item->items->first()?->boqItem?->description ?? 'Material Request',
                    'status' => $item->status,
                    'created_at' => $item->created_at
                ]);
            });
        $recentActivity = $recentActivity->sortByDesc('created_at')->take(10);

        // Low stock items
        $lowStockItems = ProjectMaterialInventory::with(['boqItem'])
            ->where('project_id', $id)
            ->where('quantity_available', '<', 10)
            ->orderBy('quantity_available')
            ->limit(5)
            ->get();

        return view('pages.procurement.project_dashboard')->with([
            'project' => $project,
            'stats' => $stats,
            'budget' => $budget,
            'phases' => $phases,
            'pendingActions' => $pendingActions,
            'recentActivity' => $recentActivity,
            'lowStockItems' => $lowStockItems
        ]);
    }

    /**
     * BOQ Item procurement detail
     */
    public function boqItem($id)
    {
        $boqItem = ProjectBoqItem::with([
            'boq.project',
            'project',
            'category',
            'constructionPhase'
        ])->findOrFail($id);

        // Material requests for this BOQ item (through items table)
        $materialRequests = ProjectMaterialRequest::with(['requester', 'approver'])
            ->whereHas('items', function ($q) use ($id) {
                $q->where('boq_item_id', $id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Quotations (through material requests)
        $requestIds = $materialRequests->pluck('id');
        $quotations = SupplierQuotation::with(['supplier', 'materialRequest'])
            ->whereIn('material_request_id', $requestIds)
            ->orderBy('grand_total')
            ->get();

        // Purchases (through comparisons or direct link)
        $purchases = Purchase::with(['supplier', 'items'])
            ->whereHas('items', function ($q) use ($id) {
                $q->where('boq_item_id', $id);
            })
            ->orWhereHas('materialRequest.items', function ($q) use ($id) {
                $q->where('boq_item_id', $id);
            })
            ->orderBy('date', 'desc')
            ->get();

        // Receivings (deliveries)
        $purchaseIds = $purchases->pluck('id');
        $receivings = SupplierReceiving::with(['supplier', 'purchase', 'inspection'])
            ->whereIn('purchase_id', $purchaseIds)
            ->orderBy('date', 'desc')
            ->get();

        // Stock movements
        $movements = ProjectMaterialMovement::with(['performedBy', 'verifiedBy'])
            ->where('boq_item_id', $id)
            ->orderBy('date', 'desc')
            ->get();

        return view('pages.procurement.boq_item_detail')->with([
            'boqItem' => $boqItem,
            'materialRequests' => $materialRequests,
            'quotations' => $quotations,
            'purchases' => $purchases,
            'receivings' => $receivings,
            'movements' => $movements
        ]);
    }

    /**
     * Helper: Count requests that have 3+ quotations but no comparison
     */
    private function getRequestsReadyForComparison(): int
    {
        return ProjectMaterialRequest::whereRaw('UPPER(status) = ?', ['APPROVED'])
            ->whereHas('quotations', function ($q) {
                $q->select(DB::raw('count(*) as count'))
                  ->groupBy('material_request_id')
                  ->havingRaw('count(*) >= 3');
            }, '>=', 3)
            ->whereDoesntHave('comparisons', function ($q) {
                $q->whereIn('status', ['pending', 'approved', 'APPROVED']);
            })
            ->count();
    }

    /**
     * Export procurement report
     */
    public function exportReport(Request $request)
    {
        $projectId = $request->input('project_id');
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));

        // Build report data
        $reportData = [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'generated_by' => auth()->user()->name
        ];

        $query = ProjectMaterialRequest::with([
            'project', 'items.boqItem', 'quotations', 'comparisons'
        ])->whereBetween('created_at', [$startDate, $endDate]);

        if ($projectId) {
            $query->where('project_id', $projectId);
            $reportData['project'] = Project::find($projectId)?->name;
        }

        $reportData['requests'] = $query->get();

        // Return as JSON for now (can be converted to PDF/Excel)
        return response()->json($reportData);
    }
}

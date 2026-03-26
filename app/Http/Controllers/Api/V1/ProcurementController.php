<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProjectMaterialRequest;
use App\Models\Project;
use App\Models\SupplierQuotation;
use App\Models\Purchase;
use App\Models\MaterialInspection;
use App\Models\ProjectMaterialInventory;
use App\Models\QuotationComparison;
use App\Models\SupplierReceiving;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcurementController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $materialRequests = ProjectMaterialRequest::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $quotations = SupplierQuotation::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $purchases = Purchase::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $inspections = MaterialInspection::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $pendingMaterialRequests = ProjectMaterialRequest::where('status', 'pending')
            ->with(['project', 'requester'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'request_number' => $r->request_number,
                'project_name' => $r->project?->project_name ?? null,
                'status' => $r->status,
                'created_at' => $r->created_at?->toISOString(),
            ]);

        $recentPurchases = Purchase::with(['supplier', 'project'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'purchase_number' => $p->purchase_number,
                'supplier_name' => $p->supplier?->name ?? null,
                'project_name' => $p->project?->project_name ?? null,
                'total_amount' => $p->total_amount,
                'status' => $p->status,
                'created_at' => $p->created_at?->toISOString(),
            ]);

        $recentComparisons = QuotationComparison::with(['materialRequest.project', 'selectedQuotation.supplier'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($comparison) => [
                'id' => $comparison->id,
                'comparison_number' => $comparison->comparison_number,
                'project_name' => $comparison->materialRequest?->project?->project_name,
                'supplier_name' => $comparison->selectedQuotation?->supplier?->name,
                'status' => $comparison->status,
                'created_at' => $comparison->created_at?->toISOString(),
            ]);

        $recentInspections = MaterialInspection::with(['project', 'supplierReceiving.supplier'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($inspection) => [
                'id' => $inspection->id,
                'inspection_number' => $inspection->inspection_number,
                'project_name' => $inspection->project?->project_name,
                'supplier_name' => $inspection->supplierReceiving?->supplier?->name,
                'status' => $inspection->status,
                'overall_result' => $inspection->overall_result,
                'inspection_date' => $inspection->inspection_date?->toDateString(),
                'created_at' => $inspection->created_at?->toISOString(),
            ]);

        $activeProjects = Project::whereHas('materialRequests')
            ->withCount(['materialRequests', 'boqs'])
            ->orderBy('material_requests_count', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($project) => [
                'id' => $project->id,
                'name' => $project->project_name ?? $project->name,
                'material_requests_count' => $project->material_requests_count,
                'boqs_count' => $project->boqs_count,
            ]);

        $lowStockItems = ProjectMaterialInventory::with(['project', 'boqItem'])
            ->lowStock()
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'project_name' => $item->project?->project_name ?? $item->project?->name,
                'item_name' => $item->boqItem?->description ?? $item->material?->name ?? 'Unknown',
                'quantity_available' => (float) $item->quantity_available,
                'stock_status' => $item->stock_status,
                'stock_status_label' => $item->stock_status_label,
                'stock_status_badge_class' => $item->stock_status_badge_class,
            ]);

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

        return response()->json([
            'success' => true,
            'data' => [
                'material_requests' => [
                    'total' => $materialRequests->sum(),
                    'pending' => $materialRequests['pending'] ?? 0,
                    'approved' => $materialRequests['approved'] ?? 0,
                    'rejected' => $materialRequests['rejected'] ?? 0,
                ],
                'quotations' => [
                    'total' => $quotations->sum(),
                    'pending' => $quotations['pending'] ?? 0,
                    'approved' => $quotations['approved'] ?? 0,
                ],
                'purchases' => [
                    'total' => $purchases->sum(),
                    'pending' => $purchases['pending'] ?? 0,
                    'approved' => $purchases['approved'] ?? 0,
                    'delivered' => $purchases['delivered'] ?? 0,
                ],
                'inspections' => [
                    'total' => $inspections->sum(),
                    'pending' => $inspections['pending'] ?? 0,
                    'approved' => $inspections['approved'] ?? 0,
                    'rejected' => $inspections['rejected'] ?? 0,
                ],
                'pending_actions' => $pendingActions,
                'active_projects' => $activeProjects,
                'low_stock_items' => $lowStockItems,
                'pending_material_requests' => $pendingMaterialRequests,
                'recent_comparisons' => $recentComparisons,
                'recent_purchases' => $recentPurchases,
                'recent_inspections' => $recentInspections,
            ],
        ]);
    }

    private function getRequestsReadyForComparison(): int
    {
        return ProjectMaterialRequest::whereRaw('UPPER(status) = ?', ['APPROVED'])
            ->whereHas('quotations', function ($query) {
                $query->select(DB::raw('count(*)'))
                    ->groupBy('material_request_id')
                    ->havingRaw('count(*) >= 3');
            })
            ->whereDoesntHave('comparisons')
            ->count();
    }
}

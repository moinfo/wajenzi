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
    public function quotationComparisons(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 100), 200);

        $query = QuotationComparison::with([
                'materialRequest.project',
                'selectedQuotation.supplier',
                'preparedBy',
            ])
            ->orderByDesc('comparison_date')
            ->orderByDesc('created_at');

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('comparison_number', 'like', "%{$search}%")
                  ->orWhereHas('materialRequest', function ($mrq) use ($search) {
                      $mrq->where('request_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('materialRequest.project', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $comparisons = $query->paginate($perPage)
            ->through(fn ($comparison) => [
                'id' => $comparison->id,
                'comparison_number' => $comparison->comparison_number,
                'comparison_date' => $comparison->comparison_date?->toDateString(),
                'status' => $comparison->status,
                'material_request_id' => $comparison->material_request_id,
                'material_request_number' => $comparison->materialRequest?->request_number,
                'project_name' => $comparison->materialRequest?->project?->project_name
                    ?? $comparison->materialRequest?->project?->name,
                'selected_supplier_name' => $comparison->selectedQuotation?->supplier?->name,
                'selected_amount' => $comparison->selectedQuotation?->grand_total ?? 0,
                'quotation_count' => $comparison->quotation_count,
                'prepared_by_name' => $comparison->preparedBy?->name,
                'created_at' => $comparison->created_at?->toISOString(),
                'can_create_purchase' => $comparison->isApproved() && !$comparison->purchases()->exists(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $comparisons,
        ]);
    }

    public function showQuotationComparison(int $id): JsonResponse
    {
        $comparison = QuotationComparison::with([
            'materialRequest.project',
            'materialRequest.items.boqItem',
            'selectedQuotation.supplier',
            'preparedBy',
            'approvedBy',
        ])->findOrFail($id);

        $quotations = SupplierQuotation::with(['supplier', 'items'])
            ->where('material_request_id', $comparison->material_request_id)
            ->orderBy('total_amount')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($quotation) => [
                'id' => $quotation->id,
                'quotation_number' => $quotation->quotation_number,
                'supplier_name' => $quotation->supplier?->name,
                'status' => $quotation->status,
                'quotation_date' => $quotation->quotation_date?->toDateString(),
                'grand_total' => $quotation->grand_total,
                'is_selected' => (int) $quotation->id === (int) $comparison->selected_quotation_id,
                'items' => $quotation->items->map(fn ($item) => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'material_request_item_id' => $item->material_request_item_id,
                ])->values(),
            ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $comparison->id,
                'comparison_number' => $comparison->comparison_number,
                'comparison_date' => $comparison->comparison_date?->toDateString(),
                'status' => $comparison->status,
                'recommendation_reason' => $comparison->recommendation_reason,
                'quotation_count' => $comparison->quotation_count,
                'average_quotation_price' => $comparison->average_quotation_price,
                'price_variance' => $comparison->price_variance,
                'savings' => $comparison->savings,
                'material_request' => [
                    'id' => $comparison->materialRequest?->id,
                    'request_number' => $comparison->materialRequest?->request_number,
                    'project_name' => $comparison->materialRequest?->project?->project_name
                        ?? $comparison->materialRequest?->project?->name,
                    'status' => $comparison->materialRequest?->status,
                ],
                'selected_quotation' => $comparison->selectedQuotation ? [
                    'id' => $comparison->selectedQuotation->id,
                    'quotation_number' => $comparison->selectedQuotation->quotation_number,
                    'supplier_name' => $comparison->selectedQuotation->supplier?->name,
                    'grand_total' => $comparison->selectedQuotation->grand_total,
                    'quotation_date' => $comparison->selectedQuotation->quotation_date?->toDateString(),
                ] : null,
                'prepared_by' => [
                    'id' => $comparison->preparedBy?->id,
                    'name' => $comparison->preparedBy?->name,
                ],
                'approved_by' => [
                    'id' => $comparison->approvedBy?->id,
                    'name' => $comparison->approvedBy?->name,
                ],
                'approved_date' => $comparison->approved_date?->toISOString(),
                'can_create_purchase' => $comparison->isApproved() && !$comparison->purchases()->exists(),
                'quotations' => $quotations,
            ],
        ]);
    }

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

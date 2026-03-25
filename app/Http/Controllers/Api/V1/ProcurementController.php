<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProjectMaterialRequest;
use App\Models\SupplierQuotation;
use App\Models\Purchase;
use App\Models\MaterialInspection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcurementController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

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
                'pending_material_requests' => $pendingMaterialRequests,
                'recent_purchases' => $recentPurchases,
            ],
        ]);
    }
}

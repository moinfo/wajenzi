<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\ClientProjectResource;
use App\Models\BillingDocument;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Dashboard with stats and projects summary.
     */
    public function index(Request $request): JsonResponse
    {
        $client = $request->user();

        $projects = Project::where('client_id', $client->id)
            ->withCount(['invoices', 'billingInvoices', 'boqs', 'dailyReports'])
            ->orderBy('created_at', 'desc')
            ->get();

        $billingTotal = BillingDocument::where('client_id', $client->id)
            ->where('document_type', 'invoice')
            ->whereNotIn('status', ['draft', 'cancelled', 'void'])
            ->sum('total_amount');

        $stats = [
            'total_projects' => $projects->count(),
            'active_projects' => $projects->where('status', 'APPROVED')->count(),
            'total_contract_value' => $projects->sum('contract_value'),
            'total_invoiced' => $billingTotal + $projects->sum(fn($p) => $p->invoices->sum('amount')),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'projects' => ClientProjectResource::collection($projects),
            ],
        ]);
    }

    /**
     * Projects list only.
     */
    public function projects(Request $request): JsonResponse
    {
        $projects = Project::where('client_id', $request->user()->id)
            ->withCount(['invoices', 'billingInvoices', 'boqs', 'dailyReports'])
            ->with(['projectType', 'serviceType', 'projectManager'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ClientProjectResource::collection($projects),
        ]);
    }
}

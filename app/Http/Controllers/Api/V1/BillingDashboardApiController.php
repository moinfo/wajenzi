<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BillingDocumentResource;
use App\Http\Resources\BillingPaymentResource;
use App\Models\BillingClient;
use App\Models\BillingDocument;
use App\Models\BillingDocumentEmail;
use App\Models\BillingPayment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class BillingDashboardApiController extends Controller
{
    public function index(): JsonResponse
    {
        $totalInvoices = BillingDocument::where('document_type', 'invoice')->count();
        $totalClients = BillingClient::active()->customers()->count();
        $totalRevenue = BillingDocument::where('document_type', 'invoice')
            ->whereNotIn('status', ['cancelled', 'void'])
            ->sum('total_amount');
        $totalEmailsSent = BillingDocumentEmail::where('status', 'sent')->count();

        $recentInvoices = BillingDocument::with(['client'])
            ->where('document_type', 'invoice')
            ->latest()
            ->limit(5)
            ->get();

        $overdueInvoices = BillingDocument::with(['client'])
            ->where('document_type', 'invoice')
            ->where('status', 'overdue')
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        $recentPayments = BillingPayment::with(['document', 'client'])
            ->where('status', 'completed')
            ->latest()
            ->limit(5)
            ->get();

        $monthlyRevenue = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthlyRevenue[] = [
                'month' => $date->format('M Y'),
                'revenue' => (float) BillingDocument::where('document_type', 'invoice')
                    ->whereNotIn('status', ['cancelled', 'void'])
                    ->whereYear('issue_date', $date->year)
                    ->whereMonth('issue_date', $date->month)
                    ->sum('total_amount'),
            ];
        }

        $statusBreakdown = BillingDocument::where('document_type', 'invoice')
            ->selectRaw('status, COUNT(*) as count, SUM(total_amount) as amount')
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'count' => (int) $row->count,
                'amount' => (float) $row->amount,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'metrics' => [
                    'total_invoices' => $totalInvoices,
                    'total_clients' => $totalClients,
                    'total_revenue' => (float) $totalRevenue,
                    'total_emails_sent' => $totalEmailsSent,
                ],
                'recent_invoices' => BillingDocumentResource::collection($recentInvoices),
                'overdue_invoices' => BillingDocumentResource::collection($overdueInvoices),
                'recent_payments' => BillingPaymentResource::collection($recentPayments),
                'monthly_revenue' => $monthlyRevenue,
                'status_breakdown' => $statusBreakdown,
            ],
        ]);
    }
}

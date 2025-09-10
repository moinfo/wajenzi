<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\BillingDocument;
use App\Models\BillingClient;
use App\Models\BillingPayment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Get key metrics
        $totalInvoices = BillingDocument::where('document_type', 'invoice')->count();
        $totalClients = BillingClient::active()->customers()->count();
        $totalRevenue = BillingDocument::where('document_type', 'invoice')
            ->whereNotIn('status', ['cancelled', 'void'])
            ->sum('total_amount');
        $outstandingAmount = BillingDocument::where('document_type', 'invoice')
            ->whereNotIn('status', ['paid', 'cancelled', 'void'])
            ->sum('balance_amount');

        // Recent invoices
        $recentInvoices = BillingDocument::with(['client'])
            ->where('document_type', 'invoice')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Overdue invoices
        $overdueInvoices = BillingDocument::with(['client'])
            ->where('document_type', 'invoice')
            ->where('status', 'overdue')
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get();

        // Recent payments
        $recentPayments = BillingPayment::with(['document', 'client'])
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Monthly revenue chart data (last 12 months)
        $monthlyRevenue = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $revenue = BillingDocument::where('document_type', 'invoice')
                ->whereNotIn('status', ['cancelled', 'void'])
                ->whereYear('issue_date', $date->year)
                ->whereMonth('issue_date', $date->month)
                ->sum('total_amount');
            
            $monthlyRevenue[] = [
                'month' => $date->format('M Y'),
                'revenue' => $revenue
            ];
        }

        // Status breakdown
        $statusBreakdown = BillingDocument::where('document_type', 'invoice')
            ->selectRaw('status, COUNT(*) as count, SUM(total_amount) as amount')
            ->groupBy('status')
            ->get();

        return view('billing.dashboard.index', compact(
            'totalInvoices',
            'totalClients', 
            'totalRevenue',
            'outstandingAmount',
            'recentInvoices',
            'overdueInvoices',
            'recentPayments',
            'monthlyRevenue',
            'statusBreakdown'
        ));
    }
}
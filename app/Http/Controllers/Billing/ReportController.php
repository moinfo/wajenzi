<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\BillingDocument;
use App\Models\BillingPayment;
use App\Models\BillingClient;
use App\Models\BillingProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        return view('billing.reports.index');
    }

    public function salesSummary(Request $request)
    {
        $from_date = $request->from_date ?? now()->startOfMonth()->toDateString();
        $to_date = $request->to_date ?? now()->toDateString();
        $group_by = $request->group_by ?? 'month';

        $query = BillingDocument::where('document_type', 'invoice')
            ->whereBetween('issue_date', [$from_date, $to_date]);

        $totalSales = $query->sum('total_amount');
        $totalPaid = $query->sum('paid_amount');
        $totalOutstanding = $query->sum('balance_amount');
        $totalInvoices = $query->count();

        $salesData = BillingDocument::select(
                DB::raw($this->getGroupBySelect($group_by) . ' as period'),
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('SUM(paid_amount) as total_paid'),
                DB::raw('SUM(balance_amount) as total_outstanding')
            )
            ->where('document_type', 'invoice')
            ->whereBetween('issue_date', [$from_date, $to_date])
            ->groupBy(DB::raw($this->getGroupBySelect($group_by)))
            ->orderBy('period')
            ->get();

        $topClients = BillingDocument::select(
                'billing_clients.company_name',
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('SUM(total_amount) as total_sales')
            )
            ->join('billing_clients', 'billing_documents.client_id', '=', 'billing_clients.id')
            ->where('document_type', 'invoice')
            ->whereBetween('issue_date', [$from_date, $to_date])
            ->groupBy('billing_clients.id', 'billing_clients.company_name')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();

        return view('billing.reports.sales-summary', compact(
            'totalSales', 'totalPaid', 'totalOutstanding', 'totalInvoices',
            'salesData', 'topClients', 'from_date', 'to_date', 'group_by'
        ));
    }

    public function paymentReport(Request $request)
    {
        $from_date = $request->from_date ?? now()->startOfMonth()->toDateString();
        $to_date = $request->to_date ?? now()->toDateString();

        $payments = BillingPayment::with(['document', 'client'])
            ->whereBetween('payment_date', [$from_date, $to_date])
            ->where('status', 'completed')
            ->orderBy('payment_date', 'desc')
            ->get();

        $totalPayments = $payments->sum('amount');
        $paymentCount = $payments->count();

        $paymentsByMethod = BillingPayment::select(
                'payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->whereBetween('payment_date', [$from_date, $to_date])
            ->where('status', 'completed')
            ->groupBy('payment_method')
            ->orderByDesc('total_amount')
            ->get();

        $dailyPayments = BillingPayment::select(
                DB::raw('DATE(payment_date) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->whereBetween('payment_date', [$from_date, $to_date])
            ->where('status', 'completed')
            ->groupBy(DB::raw('DATE(payment_date)'))
            ->orderBy('date')
            ->get();

        return view('billing.reports.payment-report', compact(
            'payments', 'totalPayments', 'paymentCount', 'paymentsByMethod',
            'dailyPayments', 'from_date', 'to_date'
        ));
    }

    public function clientStatement(Request $request, BillingClient $client)
    {
        $from_date = $request->from_date ?? now()->subMonths(6)->toDateString();
        $to_date = $request->to_date ?? now()->toDateString();

        $documents = $client->documents()
            ->whereBetween('issue_date', [$from_date, $to_date])
            ->orderBy('issue_date', 'desc')
            ->get();

        $payments = $client->payments()
            ->whereBetween('payment_date', [$from_date, $to_date])
            ->orderBy('payment_date', 'desc')
            ->get();

        $summary = [
            'total_invoiced' => $client->documents()->where('document_type', 'invoice')->sum('total_amount'),
            'total_paid' => $client->payments()->where('status', 'completed')->sum('amount'),
            'current_balance' => $client->documents()->where('document_type', 'invoice')->sum('balance_amount'),
            'overdue_amount' => $client->documents()
                ->where('document_type', 'invoice')
                ->where('due_date', '<', now())
                ->where('balance_amount', '>', 0)
                ->sum('balance_amount')
        ];

        return view('billing.reports.client-statement', compact(
            'client', 'documents', 'payments', 'summary', 'from_date', 'to_date'
        ));
    }

    public function agingReport(Request $request)
    {
        $as_of_date = $request->as_of_date ?? now()->toDateString();

        $clients = BillingClient::with(['documents' => function($query) use ($as_of_date) {
                $query->where('document_type', 'invoice')
                      ->where('issue_date', '<=', $as_of_date)
                      ->where('balance_amount', '>', 0);
            }])
            ->whereHas('documents', function($query) use ($as_of_date) {
                $query->where('document_type', 'invoice')
                      ->where('issue_date', '<=', $as_of_date)
                      ->where('balance_amount', '>', 0);
            })
            ->get();

        $agingData = [];
        $totals = [
            'current' => 0,
            '1_30' => 0,
            '31_60' => 0,
            '61_90' => 0,
            'over_90' => 0,
            'total' => 0
        ];

        foreach ($clients as $client) {
            $clientAging = [
                'client' => $client,
                'current' => 0,
                '1_30' => 0,
                '31_60' => 0,
                '61_90' => 0,
                'over_90' => 0,
                'total' => 0
            ];

            foreach ($client->documents as $document) {
                $daysOverdue = Carbon::parse($as_of_date)->diffInDays(Carbon::parse($document->due_date));
                $amount = $document->balance_amount;

                if ($daysOverdue <= 0) {
                    $clientAging['current'] += $amount;
                } elseif ($daysOverdue <= 30) {
                    $clientAging['1_30'] += $amount;
                } elseif ($daysOverdue <= 60) {
                    $clientAging['31_60'] += $amount;
                } elseif ($daysOverdue <= 90) {
                    $clientAging['61_90'] += $amount;
                } else {
                    $clientAging['over_90'] += $amount;
                }
                
                $clientAging['total'] += $amount;
            }

            if ($clientAging['total'] > 0) {
                $agingData[] = $clientAging;
                
                $totals['current'] += $clientAging['current'];
                $totals['1_30'] += $clientAging['1_30'];
                $totals['31_60'] += $clientAging['31_60'];
                $totals['61_90'] += $clientAging['61_90'];
                $totals['over_90'] += $clientAging['over_90'];
                $totals['total'] += $clientAging['total'];
            }
        }

        return view('billing.reports.aging-report', compact('agingData', 'totals', 'as_of_date'));
    }

    public function productSales(Request $request)
    {
        $from_date = $request->from_date ?? now()->startOfMonth()->toDateString();
        $to_date = $request->to_date ?? now()->toDateString();

        $productSales = DB::table('billing_document_items')
            ->join('billing_documents', 'billing_document_items.document_id', '=', 'billing_documents.id')
            ->leftJoin('billing_products_services', 'billing_document_items.product_service_id', '=', 'billing_products_services.id')
            ->select(
                DB::raw('COALESCE(billing_products_services.name, billing_document_items.item_name) as product_name'),
                DB::raw('COALESCE(billing_products_services.code, billing_document_items.item_code) as product_code'),
                DB::raw('SUM(billing_document_items.quantity) as total_quantity'),
                DB::raw('SUM(billing_document_items.line_total) as total_sales'),
                DB::raw('AVG(billing_document_items.unit_price) as avg_price'),
                DB::raw('COUNT(DISTINCT billing_documents.id) as invoice_count')
            )
            ->where('billing_documents.document_type', 'invoice')
            ->whereBetween('billing_documents.issue_date', [$from_date, $to_date])
            ->groupBy(
                DB::raw('COALESCE(billing_products_services.id, billing_document_items.item_name)'),
                DB::raw('COALESCE(billing_products_services.name, billing_document_items.item_name)'),
                DB::raw('COALESCE(billing_products_services.code, billing_document_items.item_code)')
            )
            ->orderByDesc('total_sales')
            ->get();

        return view('billing.reports.product-sales', compact('productSales', 'from_date', 'to_date'));
    }

    private function getGroupBySelect($group_by)
    {
        switch ($group_by) {
            case 'day':
                return "DATE_FORMAT(issue_date, '%Y-%m-%d')";
            case 'week':
                return "YEARWEEK(issue_date, 1)";
            case 'month':
                return "DATE_FORMAT(issue_date, '%Y-%m')";
            case 'quarter':
                return "CONCAT(YEAR(issue_date), '-Q', QUARTER(issue_date))";
            case 'year':
                return "YEAR(issue_date)";
            default:
                return "DATE_FORMAT(issue_date, '%Y-%m')";
        }
    }
}
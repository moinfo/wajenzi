<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BillingDocument;
use App\Models\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $revenueByMonth = BillingDocument::select(
            DB::raw('MONTH(issue_date) as month'),
            DB::raw('YEAR(issue_date) as year'),
            DB::raw('SUM(total_amount) as total')
        )
            ->where('status', '!=', 'draft')
            ->whereYear('issue_date', now()->year)
            ->groupBy(DB::raw('YEAR(issue_date), MONTH(issue_date)'))
            ->orderBy(DB::raw('YEAR(issue_date), MONTH(issue_date)'))
            ->get();

        $collectionsByMonth = Collection::select(
            DB::raw('MONTH(date) as month'),
            DB::raw('YEAR(date) as year'),
            DB::raw('SUM(amount) as total')
        )
            ->whereYear('date', now()->year)
            ->groupBy(DB::raw('YEAR(date), MONTH(date)'))
            ->orderBy(DB::raw('YEAR(date), MONTH(date)'))
            ->get();

        $outstandingInvoices = BillingDocument::where('status', 'unpaid')
            ->select(DB::raw('SUM(balance_amount) as total'))
            ->first();

        $overdueInvoices = BillingDocument::where('status', 'unpaid')
            ->where('due_date', '<', now())
            ->select(DB::raw('COUNT(*) as count'), DB::raw('SUM(balance_amount) as total'))
            ->first();

        $recentInvoices = BillingDocument::with(['client', 'project'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($inv) => [
                'id' => $inv->id,
                'document_number' => $inv->document_number,
                'client_name' => $inv->client?->name ?? null,
                'project_name' => $inv->project?->project_name ?? null,
                'total_amount' => $inv->total_amount,
                'balance_amount' => $inv->balance_amount,
                'status' => $inv->status,
                'issue_date' => $inv->issue_date?->format('Y-m-d'),
                'due_date' => $inv->due_date?->format('Y-m-d'),
            ]);

        $recentPayments = Collection::with(['invoice', 'receivedBy'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($col) => [
                'id' => $col->id,
                'invoice_number' => $col->invoice?->document_number ?? null,
                'amount' => $col->amount,
                'payment_method' => $col->payment_method,
                'collected_by' => $col->receivedBy?->name ?? null,
                'date' => $col->date?->format('Y-m-d'),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'revenue_by_month' => $revenueByMonth,
                'collections_by_month' => $collectionsByMonth,
                'outstanding_invoices' => [
                    'count' => BillingDocument::where('status', 'unpaid')->count(),
                    'total' => $outstandingInvoices->total ?? 0,
                ],
                'overdue_invoices' => [
                    'count' => $overdueInvoices->count ?? 0,
                    'total' => $overdueInvoices->total ?? 0,
                ],
                'recent_invoices' => $recentInvoices,
                'recent_payments' => $recentPayments,
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BillingDocument;
use App\Models\Collection;
use App\Models\CostCategory;
use App\Models\Expense;
use App\Models\ImprestRequest;
use App\Models\PettyCashRefillRequest;
use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\StatutoryPayment;
use App\Models\VatPayment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Aggregate endpoints powering the native Finance landing screen and
 * the Expenditure Dashboard. Mirrors web {@see \App\Http\Controllers\ExpenditureDashboardController}.
 */
class FinanceDashboardApiController extends Controller
{
    /**
     * Cost-category names used by the expenditure breakdown — must match the
     * cost_categories rows seeded by 2026_05_09_120000.
     */
    private const COST_CATEGORIES = ['Material', 'Labour Charge', 'Overhead Expense'];

    /**
     * GET /api/v1/finance/dashboard
     * High-level finance summary surfaced as the parent "Finance" landing screen.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $now    = now();
            $monthStart = $now->copy()->startOfMonth();
            $yearStart  = $now->copy()->startOfYear();

            // Revenue (issued, non-draft invoices).
            $revenueMtd = (float) BillingDocument::where('document_type', 'invoice')
                ->whereNotIn('status', ['draft', 'cancelled', 'void'])
                ->whereBetween('issue_date', [$monthStart, $now])
                ->sum('total_amount');
            $revenueYtd = (float) BillingDocument::where('document_type', 'invoice')
                ->whereNotIn('status', ['draft', 'cancelled', 'void'])
                ->whereBetween('issue_date', [$yearStart, $now])
                ->sum('total_amount');

            // Receivable (unpaid + partial balances).
            $receivable = (float) BillingDocument::where('document_type', 'invoice')
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->sum('balance_amount');
            $overdueCount = (int) BillingDocument::where('document_type', 'invoice')
                ->where('status', 'overdue')
                ->count();

            // Collections.
            $collectionsMtd = (float) Collection::whereBetween('date', [$monthStart, $now])->sum('amount');
            $collectionsYtd = (float) Collection::whereBetween('date', [$yearStart, $now])->sum('amount');

            // Expenses (administrative + project) — APPROVED only.
            $adminExpensesMtd = (float) Expense::whereRaw('UPPER(status) = ?', ['APPROVED'])
                ->whereBetween('date', [$monthStart, $now])
                ->sum('amount');
            $adminExpensesYtd = (float) Expense::whereRaw('UPPER(status) = ?', ['APPROVED'])
                ->whereBetween('date', [$yearStart, $now])
                ->sum('amount');

            $projectExpensesMtd = (float) ProjectExpense::where('status', 'approved')
                ->whereBetween('expense_date', [$monthStart, $now])
                ->sum('amount');
            $projectExpensesYtd = (float) ProjectExpense::where('status', 'approved')
                ->whereBetween('expense_date', [$yearStart, $now])
                ->sum('amount');

            // Petty cash + imprest.
            $pettyCashBalance = (float) PettyCashRefillRequest::getCurrentBalanceBetweenPettyCashRefillRequestAndImprestRequest();
            $pendingImprests = (int) ImprestRequest::whereRaw('UPPER(status) NOT IN (?, ?)', ['APPROVED', 'REJECTED'])->count();

            // VAT / statutory outflows YTD.
            $vatPaidYtd = (float) VatPayment::whereRaw('UPPER(status) = ?', ['APPROVED'])
                ->whereBetween('date', [$yearStart, $now])
                ->sum('amount');
            $statutoryPaidYtd = (float) StatutoryPayment::whereRaw('UPPER(status) = ?', ['APPROVED'])
                ->whereBetween('issue_date', [$yearStart, $now])
                ->sum('amount');
            $pendingStatutory = (int) StatutoryPayment::whereRaw('UPPER(status) IN (?, ?)', ['CREATED', 'PENDING'])->count();

            // 12-month revenue vs expenses trend.
            $monthlyTrend = [];
            for ($i = 11; $i >= 0; $i--) {
                $d = $now->copy()->subMonths($i);
                $monthlyTrend[] = [
                    'month' => $d->format('M Y'),
                    'revenue' => (float) BillingDocument::where('document_type', 'invoice')
                        ->whereNotIn('status', ['draft', 'cancelled', 'void'])
                        ->whereYear('issue_date', $d->year)
                        ->whereMonth('issue_date', $d->month)
                        ->sum('total_amount'),
                    'collections' => (float) Collection::whereYear('date', $d->year)
                        ->whereMonth('date', $d->month)
                        ->sum('amount'),
                    'expenses' => (float) ProjectExpense::where('status', 'approved')
                        ->whereYear('expense_date', $d->year)
                        ->whereMonth('expense_date', $d->month)
                        ->sum('amount'),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'metrics' => [
                        'revenue_mtd'         => $revenueMtd,
                        'revenue_ytd'         => $revenueYtd,
                        'collections_mtd'     => $collectionsMtd,
                        'collections_ytd'     => $collectionsYtd,
                        'receivable'          => $receivable,
                        'overdue_count'       => $overdueCount,
                        'admin_expenses_mtd'  => $adminExpensesMtd,
                        'admin_expenses_ytd'  => $adminExpensesYtd,
                        'project_expenses_mtd'=> $projectExpensesMtd,
                        'project_expenses_ytd'=> $projectExpensesYtd,
                        'petty_cash_balance'  => $pettyCashBalance,
                        'pending_imprests'    => $pendingImprests,
                        'vat_paid_ytd'        => $vatPaidYtd,
                        'statutory_paid_ytd'  => $statutoryPaidYtd,
                        'pending_statutory'   => $pendingStatutory,
                    ],
                    'monthly_trend' => $monthlyTrend,
                    'period' => [
                        'month_start' => $monthStart->toDateString(),
                        'year_start'  => $yearStart->toDateString(),
                        'as_of'       => $now->toIso8601String(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('FinanceDashboard index error: '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load finance dashboard: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/finance/expenditure-dashboard
     * Outflow-focused dashboard: project expenses by category, top sites,
     * trend, and statutory/VAT payment status.
     */
    public function expenditureDashboard(Request $request): JsonResponse
    {
        try {
            $granularity = in_array($request->query('granularity'), ['daily', 'weekly', 'monthly'], true)
                ? $request->query('granularity')
                : 'monthly';

            $defaultStart = match ($granularity) {
                'weekly'  => now()->startOfWeek()->subWeeks(7)->toDateString(),
                'monthly' => now()->startOfYear()->toDateString(),
                default   => now()->subDays(13)->toDateString(),
            };

            $startDate = $request->query('start_date', $defaultStart);
            $endDate   = $request->query('end_date', now()->toDateString());
            $projectId = $request->query('project_id');

            $categoryMap = CostCategory::whereIn('name', self::COST_CATEGORIES)->pluck('id', 'name');

            $expensesQuery = ProjectExpense::with(['project:id,project_name', 'costCategory:id,name'])
                ->where('status', 'approved')
                ->whereBetween('expense_date', [$startDate, $endDate]);
            if ($projectId) {
                $expensesQuery->where('project_id', $projectId);
            }
            $expenses = $expensesQuery->get();

            // Totals per cost category.
            $categoryTotals = collect(self::COST_CATEGORIES)->map(function ($name) use ($expenses, $categoryMap) {
                $id = $categoryMap[$name] ?? null;
                return [
                    'name'  => $name,
                    'total' => (float) ($id ? $expenses->where('cost_category_id', $id)->sum('amount') : 0),
                ];
            })->values();

            $grandTotal = (float) $expenses->sum('amount');

            // Per-site rollup, sorted by total desc, capped at top 10.
            $perSite = $expenses
                ->groupBy('project_id')
                ->map(function ($rows, $pid) use ($categoryMap) {
                    $project = $rows->first()?->project;
                    return [
                        'project_id'   => (int) $pid,
                        'project_name' => $project?->project_name ?? 'Unknown',
                        'material'     => (float) $rows->where('cost_category_id', $categoryMap['Material'] ?? 0)->sum('amount'),
                        'labour'       => (float) $rows->where('cost_category_id', $categoryMap['Labour Charge'] ?? 0)->sum('amount'),
                        'overhead'     => (float) $rows->where('cost_category_id', $categoryMap['Overhead Expense'] ?? 0)->sum('amount'),
                        'total'        => (float) $rows->sum('amount'),
                    ];
                })
                ->sortByDesc('total')
                ->take(10)
                ->values();

            // Time-bucketed series for the trend chart.
            $series = $this->buildSeries($expenses, $startDate, $endDate, $granularity, $categoryMap);

            // Statutory payment status snapshot for the dashboard footer.
            $statutorySummary = [
                'pending' => (int) StatutoryPayment::whereRaw('UPPER(status) IN (?, ?)', ['CREATED', 'PENDING'])->count(),
                'overdue' => (int) StatutoryPayment::whereRaw('UPPER(status) != ?', ['APPROVED'])
                    ->whereDate('due_date', '<', now())
                    ->count(),
                'paid_ytd' => (float) StatutoryPayment::whereRaw('UPPER(status) = ?', ['APPROVED'])
                    ->whereBetween('issue_date', [now()->startOfYear(), now()])
                    ->sum('amount'),
            ];

            $vatSummary = [
                'paid_ytd' => (float) VatPayment::whereRaw('UPPER(status) = ?', ['APPROVED'])
                    ->whereBetween('date', [now()->startOfYear(), now()])
                    ->sum('amount'),
                'pending'  => (int) VatPayment::whereRaw('UPPER(status) NOT IN (?, ?)', ['APPROVED', 'REJECTED'])->count(),
            ];

            $projects = Project::orderBy('project_name')->get(['id', 'project_name', 'document_number'])->map(fn ($p) => [
                'id'              => $p->id,
                'name'            => $p->project_name,
                'document_number' => $p->document_number,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'filters' => [
                        'granularity' => $granularity,
                        'start_date'  => $startDate,
                        'end_date'    => $endDate,
                        'project_id'  => $projectId ? (int) $projectId : null,
                    ],
                    'totals' => [
                        'grand_total' => $grandTotal,
                        'by_category' => $categoryTotals,
                    ],
                    'per_site'  => $perSite,
                    'series'    => $series,
                    'statutory' => $statutorySummary,
                    'vat'       => $vatSummary,
                    'projects'  => $projects,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('FinanceDashboard expenditure error: '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load expenditure dashboard: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build a time-bucketed series, gracefully filling empty periods with
     * zeros so the chart axis doesn't collapse.
     */
    private function buildSeries($expenses, string $startDate, string $endDate, string $granularity, $categoryMap): array
    {
        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);

        $buckets = [];
        $cursor  = $start->copy();
        while ($cursor->lte($end)) {
            [$key, $label] = $this->bucketKey($cursor, $granularity);
            $buckets[$key] = $buckets[$key] ?? [
                'label'    => $label,
                'material' => 0.0,
                'labour'   => 0.0,
                'overhead' => 0.0,
                'total'    => 0.0,
            ];
            $cursor = $this->advanceCursor($cursor, $granularity);
        }

        $matId = $categoryMap['Material'] ?? null;
        $labId = $categoryMap['Labour Charge'] ?? null;
        $ovhId = $categoryMap['Overhead Expense'] ?? null;

        foreach ($expenses as $exp) {
            $date = $exp->expense_date instanceof Carbon ? $exp->expense_date : Carbon::parse($exp->expense_date);
            [$key, $label] = $this->bucketKey($date, $granularity);
            if (!isset($buckets[$key])) {
                $buckets[$key] = ['label' => $label, 'material' => 0.0, 'labour' => 0.0, 'overhead' => 0.0, 'total' => 0.0];
            }
            $amount = (float) $exp->amount;
            if ($exp->cost_category_id === $matId) {
                $buckets[$key]['material'] += $amount;
            } elseif ($exp->cost_category_id === $labId) {
                $buckets[$key]['labour'] += $amount;
            } elseif ($exp->cost_category_id === $ovhId) {
                $buckets[$key]['overhead'] += $amount;
            }
            $buckets[$key]['total'] += $amount;
        }

        ksort($buckets);
        return array_values($buckets);
    }

    private function bucketKey(Carbon $date, string $granularity): array
    {
        return match ($granularity) {
            'monthly' => [$date->format('Y-m'), $date->format('M Y')],
            'weekly'  => [
                $date->copy()->startOfWeek()->format('Y-m-d'),
                'Wk '.$date->copy()->startOfWeek()->format('M d'),
            ],
            default => [$date->format('Y-m-d'), $date->format('M d')],
        };
    }

    private function advanceCursor(Carbon $date, string $granularity): Carbon
    {
        return match ($granularity) {
            'monthly' => $date->copy()->addMonth()->startOfMonth(),
            'weekly'  => $date->copy()->addWeek()->startOfWeek(),
            default   => $date->copy()->addDay(),
        };
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\CostCategory;
use App\Models\Project;
use App\Models\ProjectExpense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ExpenditureDashboardController extends Controller
{
    /**
     * Canonical category names used throughout the finance reports.
     * Match cost_categories.name (seeded in 2026_05_09_120000 migration).
     */
    private const CATEGORIES = ['Material', 'Labour Charge', 'Overhead Expense'];

    public function index(Request $request)
    {
        $granularity = in_array($request->get('granularity'), ['daily', 'weekly', 'monthly'])
            ? $request->get('granularity')
            : 'daily';

        // Default windows tuned to each granularity so the page is useful
        // out-of-the-box without the user having to set dates first.
        $defaultStart = match ($granularity) {
            'weekly'  => now()->startOfWeek()->subWeeks(7)->toDateString(),
            'monthly' => now()->startOfYear()->toDateString(),
            default   => now()->subDays(13)->toDateString(),
        };

        $startDate = $request->get('start_date', $defaultStart);
        $endDate   = $request->get('end_date', now()->toDateString());
        $projectId = $request->get('project_id');

        $projects = Project::orderBy('project_name')->get(['id', 'project_name', 'document_number']);
        $categoryMap = CostCategory::whereIn('name', self::CATEGORIES)
            ->pluck('id', 'name'); // [name => id]

        $expensesQuery = ProjectExpense::with(['project', 'costCategory'])
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId));

        $expenses = $expensesQuery->get();

        // Totals per category — graceful zero when no rows exist for a category.
        $categoryTotals = collect(self::CATEGORIES)->mapWithKeys(function ($name) use ($expenses, $categoryMap) {
            $id = $categoryMap[$name] ?? null;
            $total = $id ? $expenses->where('cost_category_id', $id)->sum('amount') : 0;
            return [$name => (float) $total];
        });
        $grandTotal = (float) $expenses->sum('amount');

        // Per-site rollup (rows = projects, columns = the three categories).
        $perSite = $expenses
            ->groupBy('project_id')
            ->map(function ($rows, $pid) use ($categoryMap, $projects) {
                $project = $projects->firstWhere('id', $pid);
                return [
                    'project_id'    => $pid,
                    'project_name'  => $project->project_name ?? 'Unknown',
                    'document_no'   => $project->document_number ?? null,
                    'material'      => (float) $rows->where('cost_category_id', $categoryMap['Material'] ?? 0)->sum('amount'),
                    'labour'        => (float) $rows->where('cost_category_id', $categoryMap['Labour Charge'] ?? 0)->sum('amount'),
                    'overhead'      => (float) $rows->where('cost_category_id', $categoryMap['Overhead Expense'] ?? 0)->sum('amount'),
                    'total'         => (float) $rows->sum('amount'),
                ];
            })
            ->sortByDesc('total')
            ->values();

        // Time-bucketed series — used by the line chart and the period table.
        $series = $this->buildSeries($expenses, $startDate, $endDate, $granularity, $categoryMap);

        return view('pages.finance.expenditure_dashboard', [
            'granularity'    => $granularity,
            'startDate'      => $startDate,
            'endDate'        => $endDate,
            'projectId'      => $projectId,
            'projects'       => $projects,
            'categories'     => self::CATEGORIES,
            'categoryTotals' => $categoryTotals,
            'grandTotal'     => $grandTotal,
            'perSite'        => $perSite,
            'series'         => $series,
            'expenses'       => $expenses,
        ]);
    }

    /**
     * Build a time-series bucketed by the chosen granularity.
     * Returns: ['labels' => [...], 'rows' => [['label','material','labour','overhead','total'], ...]]
     */
    private function buildSeries(Collection $expenses, string $startDate, string $endDate, string $granularity, $categoryMap): array
    {
        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);

        // Build the empty bucket map first so periods with no spend still appear.
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

        $matId  = $categoryMap['Material'] ?? null;
        $labId  = $categoryMap['Labour Charge'] ?? null;
        $ovhId  = $categoryMap['Overhead Expense'] ?? null;

        foreach ($expenses as $exp) {
            $date = $exp->expense_date instanceof Carbon ? $exp->expense_date : Carbon::parse($exp->expense_date);
            [$key, $label] = $this->bucketKey($date, $granularity);
            if (! isset($buckets[$key])) {
                $buckets[$key] = ['label' => $label, 'material' => 0.0, 'labour' => 0.0, 'overhead' => 0.0, 'total' => 0.0];
            }
            $amount = (float) $exp->amount;
            if ($exp->cost_category_id === $matId)      $buckets[$key]['material'] += $amount;
            elseif ($exp->cost_category_id === $labId)  $buckets[$key]['labour']   += $amount;
            elseif ($exp->cost_category_id === $ovhId)  $buckets[$key]['overhead'] += $amount;
            $buckets[$key]['total'] += $amount;
        }

        ksort($buckets);
        $rows = array_values($buckets);

        return [
            'labels' => array_column($rows, 'label'),
            'rows'   => $rows,
        ];
    }

    private function bucketKey(Carbon $date, string $granularity): array
    {
        return match ($granularity) {
            'monthly' => [$date->format('Y-m'), $date->format('M Y')],
            'weekly'  => [
                $date->copy()->startOfWeek()->format('Y-m-d'),
                'Wk ' . $date->copy()->startOfWeek()->format('M d'),
            ],
            default   => [$date->format('Y-m-d'), $date->format('M d')],
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

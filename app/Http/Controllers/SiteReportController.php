<?php

namespace App\Http\Controllers;

use App\Models\CostCategory;
use App\Models\LaborContract;
use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\Purchase;
use Illuminate\Http\Request;

/**
 * Per-site cost report assembled from the procurement sources:
 *   Material  — approved Purchase Orders (purchases.total_amount, status APPROVED)
 *   Labour    — committed Labour Charge contracts (LaborContract.total_amount)
 *   Overhead  — ProjectExpense rows in the "Overhead Expense" category
 *   Drawing   — ProjectExpense rows in the "Drawing Charge" category
 *
 * Reachable from the Purchase Orders page; downloadable as CSV (opens in Excel).
 */
class SiteReportController extends Controller
{
    /** Labour contracts counted as committed cost (excludes draft / terminated). */
    private const LABOUR_STATUSES = ['active', 'on_hold', 'completed'];

    public function index(Request $request)
    {
        [$rows, $totals] = $this->buildReport($request->get('project_id'));

        return view('pages.procurement.site_report', [
            'rows'      => $rows,
            'totals'    => $totals,
            'projects'  => Project::orderBy('project_name')->get(['id', 'project_name']),
            'projectId' => $request->get('project_id'),
        ]);
    }

    public function export(Request $request)
    {
        [$rows, $totals] = $this->buildReport($request->get('project_id'));

        $filename = 'site_cost_report_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($rows, $totals) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads accents/Unicode correctly

            fputcsv($file, ['Site Cost Report']);
            fputcsv($file, ['Generated on', date('Y-m-d H:i:s')]);
            fputcsv($file, ['Material', 'Approved Purchase Orders']);
            fputcsv($file, ['Labour', 'Active / on-hold / completed Labour Charge contracts']);
            fputcsv($file, ['Overhead & Drawing', 'Project expenses by category']);
            fputcsv($file, []);
            fputcsv($file, ['Site', 'Document No', 'Material Cost', 'Labour Cost', 'Overhead', 'Drawing Charges', 'Total']);

            foreach ($rows as $r) {
                fputcsv($file, [
                    $r['project_name'],
                    $r['document_no'],
                    number_format($r['material'], 2, '.', ''),
                    number_format($r['labour'],   2, '.', ''),
                    number_format($r['overhead'], 2, '.', ''),
                    number_format($r['drawing'],  2, '.', ''),
                    number_format($r['total'],    2, '.', ''),
                ]);
            }

            fputcsv($file, []);
            fputcsv($file, [
                'GRAND TOTAL', '',
                number_format($totals['material'], 2, '.', ''),
                number_format($totals['labour'],   2, '.', ''),
                number_format($totals['overhead'], 2, '.', ''),
                number_format($totals['drawing'],  2, '.', ''),
                number_format($totals['total'],    2, '.', ''),
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Aggregate per-site costs from each source with grouped SUM queries, then
     * union the project ids so a site appears if it has cost in any column.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: array}
     */
    private function buildReport($projectId = null): array
    {
        $filter = fn ($q) => $projectId ? $q->where('project_id', $projectId) : $q;

        $material = $filter(Purchase::query()->where('status', 'APPROVED')->whereNotNull('project_id'))
            ->groupBy('project_id')->selectRaw('project_id, SUM(total_amount) as total')
            ->pluck('total', 'project_id');

        $labour = $filter(LaborContract::query()->whereIn('status', self::LABOUR_STATUSES)->whereNotNull('project_id'))
            ->groupBy('project_id')->selectRaw('project_id, SUM(total_amount) as total')
            ->pluck('total', 'project_id');

        $catIds     = CostCategory::whereIn('name', ['Overhead Expense', 'Drawing Charge'])->pluck('id', 'name');
        $overheadId = $catIds['Overhead Expense'] ?? 0;
        $drawingId  = $catIds['Drawing Charge'] ?? 0;

        $overhead = $filter(ProjectExpense::query()->where('cost_category_id', $overheadId)->whereNotNull('project_id'))
            ->groupBy('project_id')->selectRaw('project_id, SUM(amount) as total')
            ->pluck('total', 'project_id');

        $drawing = $filter(ProjectExpense::query()->where('cost_category_id', $drawingId)->whereNotNull('project_id'))
            ->groupBy('project_id')->selectRaw('project_id, SUM(amount) as total')
            ->pluck('total', 'project_id');

        $projectIds = collect()
            ->merge($material->keys())->merge($labour->keys())
            ->merge($overhead->keys())->merge($drawing->keys())
            ->unique()->values();

        $projects = Project::whereIn('id', $projectIds)
            ->get(['id', 'project_name', 'document_number'])->keyBy('id');

        $rows = $projectIds->map(function ($pid) use ($material, $labour, $overhead, $drawing, $projects) {
            $m = (float) $material->get($pid, 0);
            $l = (float) $labour->get($pid, 0);
            $o = (float) $overhead->get($pid, 0);
            $d = (float) $drawing->get($pid, 0);
            $project = $projects->get($pid);
            return [
                'project_id'   => $pid,
                'project_name' => $project->project_name ?? 'Unknown',
                'document_no'  => $project->document_number ?? '',
                'material'     => $m,
                'labour'       => $l,
                'overhead'     => $o,
                'drawing'      => $d,
                'total'        => $m + $l + $o + $d,
            ];
        })->sortByDesc('total')->values();

        $totals = [
            'material' => (float) $rows->sum('material'),
            'labour'   => (float) $rows->sum('labour'),
            'overhead' => (float) $rows->sum('overhead'),
            'drawing'  => (float) $rows->sum('drawing'),
            'total'    => (float) $rows->sum('total'),
        ];

        return [$rows, $totals];
    }
}

<?php
namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectDailyReport;
use App\Models\ProjectSiteVisit;
use App\Models\User;
use App\Models\Approval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectDailyReportController extends Controller
{
    public function index(Request $request) {
        //handle crud operations
        if($this->handleCrud($request, 'ProjectDailyReport')) {
            return back();
        }

        $applyFilters = function ($query) use ($request) {
            return $query
                ->when($request->start_date, fn($q) => $q->whereDate('report_date', '>=', $request->start_date))
                ->when($request->end_date,   fn($q) => $q->whereDate('report_date', '<=', $request->end_date))
                ->when($request->project_id, fn($q) => $q->where('project_id', $request->project_id));
        };

        $reports = $applyFilters(ProjectDailyReport::with(['project', 'supervisor']))
            ->orderByDesc('report_date')
            ->get();

        $weekly  = $this->buildPeriodSummary($request, 'week');
        $monthly = $this->buildPeriodSummary($request, 'month');

        $projects = Project::all();

        $data = [
            'reports'  => $reports,
            'projects' => $projects,
            'weekly'   => $weekly,
            'monthly'  => $monthly,
        ];
        return view('pages.projects.project_daily_reports')->with($data);
    }

    /**
     * Build per-supervisor aggregation over a calendar period (week|month).
     * Each row: supervisor, period label, reports count, labor hours, distinct projects.
     */
    private function buildPeriodSummary(Request $request, string $period)
    {
        $dateExpr = $period === 'week'
            ? "DATE_FORMAT(report_date, '%x-W%v')"   // ISO week (e.g. 2026-W21)
            : "DATE_FORMAT(report_date, '%Y-%m')";   // 2026-05

        $rows = ProjectDailyReport::query()
            ->selectRaw("$dateExpr as period_label,
                MIN(report_date) as period_start,
                MAX(report_date) as period_end,
                supervisor_id,
                COUNT(*) as reports_count,
                COALESCE(SUM(labor_hours), 0) as labor_hours_total,
                COUNT(DISTINCT project_id) as projects_count")
            ->when($request->start_date, fn($q) => $q->whereDate('report_date', '>=', $request->start_date))
            ->when($request->end_date,   fn($q) => $q->whereDate('report_date', '<=', $request->end_date))
            ->when($request->project_id, fn($q) => $q->where('project_id', $request->project_id))
            ->groupBy('period_label', 'supervisor_id')
            ->orderByDesc('period_start')
            ->get();

        // Eager-load supervisors in one query, then attach.
        $supervisors = User::whereIn('id', $rows->pluck('supervisor_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($supervisors) {
            $row->supervisor_name = $supervisors->get($row->supervisor_id)->name ?? 'Unknown';
            return $row;
        });
    }

    public function show($id) {
        return $this->report($id);
    }

    public function report($id) {
        $report = ProjectDailyReport::with(['project', 'supervisor'])->findOrFail($id);

        $data = [
            'report' => $report
        ];
        return view('pages.projects.project_daily_report')->with($data);
    }

    public function generatePDF($id) {
        $report = ProjectDailyReport::with(['project', 'supervisor'])->findOrFail($id);

        // PDF generation logic here

        return response()->download($pdfPath);
    }

    public function weeklyReport(Request $request) {
        $reports = ProjectDailyReport::with(['project', 'supervisor'])
            ->whereBetween('report_date', [
                $request->start_date,
                $request->end_date
            ])
            ->when($request->project_id, function($query) use ($request) {
                return $query->where('project_id', $request->project_id);
            })
            ->get();

        $data = [
            'reports' => $reports,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date
        ];

        return view('pages.projects.weekly_report')->with($data);
    }
}

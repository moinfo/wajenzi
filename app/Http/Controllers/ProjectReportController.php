<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectDailyReport;
use App\Models\ProjectSiteVisit;
use Illuminate\Http\Request;

class ProjectReportController extends Controller
{
    public function index(Request $request)
    {
        $dailyReportsQuery = ProjectDailyReport::with(['project', 'supervisor']);
        $siteVisitsQuery = ProjectSiteVisit::with(['project', 'inspector']);

        if ($request->filled('project_id')) {
            $dailyReportsQuery->where('project_id', $request->project_id);
            $siteVisitsQuery->where('project_id', $request->project_id);
        }

        if ($request->filled('start_date')) {
            $dailyReportsQuery->whereDate('report_date', '>=', $request->start_date);
            $siteVisitsQuery->whereDate('visit_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $dailyReportsQuery->whereDate('report_date', '<=', $request->end_date);
            $siteVisitsQuery->whereDate('visit_date', '<=', $request->end_date);
        }

        $dailyReports = $dailyReportsQuery->get();
        $siteVisits = $siteVisitsQuery->get();
        $projects = Project::orderBy('project_name')->get(['id', 'project_name']);

        $items = $dailyReports->map(function ($report) {
            return [
                'kind' => 'Daily Report',
                'project_name' => $report->project?->project_name ?? '-',
                'date' => optional($report->report_date)->format('Y-m-d'),
                'owner_name' => $report->supervisor?->name ?? '-',
                'status' => 'recorded',
                'summary' => $report->work_completed ?: ($report->issues_faced ?: '-'),
            ];
        })->concat(
            $siteVisits->map(function ($visit) {
                return [
                    'kind' => 'Site Visit',
                    'project_name' => $visit->project?->project_name ?? '-',
                    'date' => optional($visit->visit_date)->format('Y-m-d'),
                    'owner_name' => $visit->inspector?->name ?? '-',
                    'status' => $visit->status ?? '-',
                    'summary' => $visit->findings ?: ($visit->description ?: '-'),
                ];
            })
        )->sortByDesc('date')->values();

        return view('pages.projects.project_reports', [
            'projects' => $projects,
            'items' => $items,
            'dailyReportsCount' => $dailyReports->count(),
            'siteVisitsCount' => $siteVisits->count(),
        ]);
    }
}

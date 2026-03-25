<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectDailyReport;
use App\Models\ProjectSiteVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ProjectReportApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $dailyReportsQuery = ProjectDailyReport::with(['project:id,project_name', 'supervisor:id,name']);
        $siteVisitsQuery = ProjectSiteVisit::with(['project:id,project_name', 'inspector:id,name']);

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

        $dailyReports = $dailyReportsQuery->get()->map(function (ProjectDailyReport $report) {
            return [
                'id' => $report->id,
                'type' => 'daily_report',
                'title' => 'Daily Report',
                'project_id' => $report->project_id,
                'project_name' => $report->project?->project_name,
                'report_date' => optional($report->report_date)->format('Y-m-d'),
                'author_name' => $report->supervisor?->name,
                'status' => 'recorded',
                'summary' => $report->work_completed ?: ($report->issues_faced ?: '-'),
                'created_at' => $report->created_at?->toIso8601String(),
            ];
        });

        $siteVisits = $siteVisitsQuery->get()->map(function (ProjectSiteVisit $visit) {
            return [
                'id' => $visit->id,
                'type' => 'site_visit',
                'title' => 'Site Visit',
                'project_id' => $visit->project_id,
                'project_name' => $visit->project?->project_name,
                'report_date' => optional($visit->visit_date)->format('Y-m-d'),
                'author_name' => $visit->inspector?->name,
                'status' => $visit->status,
                'summary' => $visit->findings ?: ($visit->description ?: '-'),
                'created_at' => $visit->created_at?->toIso8601String(),
            ];
        });

        $type = $request->input('type');
        $items = collect();

        if ($type === 'daily_report') {
            $items = $dailyReports;
        } elseif ($type === 'site_visit') {
            $items = $siteVisits;
        } else {
            $items = $dailyReports->concat($siteVisits);
        }

        $sorted = $items
            ->sortByDesc(fn (array $item) => $item['report_date'] ?? $item['created_at'])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $sorted,
            'meta' => [
                'total' => $sorted->count(),
                'daily_reports' => $dailyReports->count(),
                'site_visits' => $siteVisits->count(),
                'projects' => $sorted->pluck('project_id')->filter()->unique()->count(),
            ],
        ]);
    }

    public function projects(): JsonResponse
    {
        $projects = Project::orderBy('project_name')->get(['id', 'project_name']);

        return response()->json([
            'success' => true,
            'data' => $projects,
        ]);
    }
}

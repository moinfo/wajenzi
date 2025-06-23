<?php
namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectDailyReport;
use App\Models\ProjectSiteVisit;
use App\Models\User;
use App\Models\Approval;
use Illuminate\Http\Request;

class ProjectDailyReportController extends Controller
{
    public function index(Request $request) {
        //handle crud operations
        if($this->handleCrud($request, 'ProjectDailyReport')) {
            return back();
        }

        $reports = ProjectDailyReport::with(['project', 'supervisor'])
            ->when($request->start_date, function($query) use ($request) {
                return $query->whereDate('report_date', '>=', $request->start_date);
            })
            ->when($request->end_date, function($query) use ($request) {
                return $query->whereDate('report_date', '<=', $request->end_date);
            })
            ->when($request->project_id, function($query) use ($request) {
                return $query->where('project_id', $request->project_id);
            })
            ->get();

        $projects = Project::all();

        $data = [
            'reports' => $reports,
            'projects' => $projects
        ];
        return view('pages.projects.project_daily_reports')->with($data);
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

<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\ProjectMaterialInventory;
use App\Models\ProjectSiteVisit;
use Illuminate\Http\Request;

class ProjectDashboardController extends Controller
{
    public function index(Request $request) {
        // Get summary statistics
        $totalProjects = Project::count();
        $inProgressProjects = Project::where('status', 'in_progress')->count();
        $totalClients = \App\Models\ProjectClient::count();
        $thisMonthExpenses = ProjectExpense::whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        // Get latest projects
        $latestProjects = Project::with(['client'])
            ->latest()
            ->take(5)
            ->get();

        // Get recent activities
        $recentActivities = \App\Models\ProjectActivityLog::with(['user'])
            ->latest()
            ->take(10)
            ->get();

        // Get upcoming site visits
        $upcomingVisits = ProjectSiteVisit::with(['project', 'inspector'])
            ->where('visit_date', '>=', now())
            ->where('status', 'scheduled')
            ->orderBy('visit_date')
            ->take(5)
            ->get();

        // Get low inventory alerts
        $lowInventory = ProjectMaterialInventory::with(['project', 'material'])
            ->whereRaw('quantity <= materials.minimum_quantity')
            ->join('project_materials as materials', 'materials.id', '=', 'project_material_inventory.material_id')
            ->get();

        $data = [
            'totalProjects' => $totalProjects,
            'inProgressProjects' => $inProgressProjects,
            'totalClients' => $totalClients,
            'thisMonthExpenses' => $thisMonthExpenses,
            'latestProjects' => $latestProjects,
            'recentActivities' => $recentActivities,
            'upcomingVisits' => $upcomingVisits,
            'lowInventory' => $lowInventory
        ];

        return view('pages.projects.project_dashboard')->with($data);
    }

    public function getStats(Request $request) {
        $projectId = $request->project_id;

        $stats = [
            'expenses' => ProjectExpense::when($projectId, function($query) use ($projectId) {
                return $query->where('project_id', $projectId);
            })->sum('amount'),

            'materials' => ProjectMaterialInventory::when($projectId, function($query) use ($projectId) {
                return $query->where('project_id', $projectId);
            })->count(),

            'site_visits' => ProjectSiteVisit::when($projectId, function($query) use ($projectId) {
                return $query->where('project_id', $projectId);
            })->count(),

            'team_members' => \App\Models\ProjectTeamMember::when($projectId, function($query) use ($projectId) {
                return $query->where('project_id', $projectId);
            })->where('status', 'active')->count()
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    public function getCharts(Request $request) {
        // Get expense trends
        $expenseTrend = ProjectExpense::selectRaw('DATE_FORMAT(expense_date, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Get project status distribution
        $projectStatus = Project::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        // Get material usage
        $materialUsage = ProjectMaterialInventory::with('material')
            ->selectRaw('material_id, SUM(quantity) as total')
            ->groupBy('material_id')
            ->get();

        return response()->json([
            'success' => true,
            'expense_trend' => $expenseTrend,
            'project_status' => $projectStatus,
            'material_usage' => $materialUsage
        ]);
    }

    public function exportReport(Request $request) {
        $type = $request->type ?? 'summary';
        $projectId = $request->project_id;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // Report generation logic based on type
        switch($type) {
            case 'summary':
                // Generate summary report
                break;
            case 'detailed':
                // Generate detailed report
                break;
            case 'financial':
                // Generate financial report
                break;
        }

        return response()->download($reportPath);
    }
}

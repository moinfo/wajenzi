<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Expense;
use App\Models\Gross;
use App\Models\ProjectSchedule;
use App\Models\ProjectScheduleActivity;
use App\Models\TransactionMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    //
    public function index(Request $request) {
        $monday = strtotime("last monday");
        $monday = date('w', $monday)==date('w') ? $monday+7*86400 : $monday;
        $sunday = strtotime(date("Y-m-d",$monday)." +6 days");
        $this_week_sd = date("Y-m-d",$monday);
        $this_week_ed = date("Y-m-d",$sunday);
        $collection_in_week = Collection::whereBetween('date', [$this_week_sd, $this_week_ed])->select([DB::raw("SUM(amount) as total_amount")])->get()->first();
        $expenses_in_week = Expense::whereBetween('date', [$this_week_sd, $this_week_ed])->select([DB::raw("SUM(amount) as total_amount")])->get()->first();
        $collections = Collection::Where('date',DB::raw('CURDATE()'))->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first();
        $collection_in_month = Collection::whereMonth('date', date('m'))->whereYear('date', date('Y'))->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first();
        $expenses_in_month = Expense::whereMonth('date', date('m'))->whereYear('date', date('Y'))->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first();
        $transactions = TransactionMovement::Where('date',DB::raw('CURDATE()'))->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first();
        $expenses = Expense::Where('date',DB::raw('CURDATE()'))->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first();
        $gross = Gross::Where('date',DB::raw('CURDATE()'))->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first();
        // Get project schedule activities for current user (if architect)
        $user = Auth::user();
        $projectActivities = ProjectScheduleActivity::with(['schedule.lead'])
            ->whereHas('schedule', function($query) use ($user) {
                $query->where('assigned_architect_id', $user->id)
                      ->whereIn('status', ['confirmed', 'in_progress']);
            })
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('start_date', 'asc')
            ->get();

        // Activities for calendar (all activities for architect's schedules)
        $calMonth = $request->input('cal_month', now()->month);
        $calYear = $request->input('cal_year', now()->year);
        $calendarActivities = ProjectScheduleActivity::with(['schedule.lead'])
            ->whereHas('schedule', function($query) use ($user) {
                $query->where('assigned_architect_id', $user->id)
                      ->whereIn('status', ['confirmed', 'in_progress']);
            })
            ->whereYear('start_date', $calYear)
            ->whereMonth('start_date', $calMonth)
            ->get()
            ->groupBy(function($activity) {
                return $activity->start_date->format('Y-m-d');
            });

        // Count activities by status
        $overdueActivitiesCount = $projectActivities->filter(fn($a) => $a->isOverdue())->count();
        $todayActivitiesCount = $projectActivities->filter(fn($a) => $a->start_date->isToday())->count();
        $pendingActivitiesCount = $projectActivities->where('status', 'pending')->count();
        $inProgressActivitiesCount = $projectActivities->where('status', 'in_progress')->count();

        // Get active project schedules with progress for current architect
        $activeSchedules = ProjectSchedule::with(['lead', 'activities'])
            ->where('assigned_architect_id', $user->id)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->orderBy('start_date', 'asc')
            ->get();

        // Calculate overall progress across all active projects
        $overallProgress = [
            'total_projects' => $activeSchedules->count(),
            'total_activities' => 0,
            'completed_activities' => 0,
            'in_progress_activities' => 0,
            'overdue_activities' => 0,
            'overall_percentage' => 0,
        ];

        foreach ($activeSchedules as $schedule) {
            $details = $schedule->progress_details;
            $overallProgress['total_activities'] += $details['total'];
            $overallProgress['completed_activities'] += $details['completed'];
            $overallProgress['in_progress_activities'] += $details['in_progress'];
            $overallProgress['overdue_activities'] += $details['overdue'];
        }

        if ($overallProgress['total_activities'] > 0) {
            $overallProgress['overall_percentage'] = round(
                ($overallProgress['completed_activities'] / $overallProgress['total_activities']) * 100, 1
            );
        }

        $data = [
            'collections' => $collections,
            'collection_in_month' => $collection_in_month,
            'expenses_in_month' => $expenses_in_month,
            'expenses_in_week' => $expenses_in_week,
            'collection_in_week' => $collection_in_week,
            'transactions' => $transactions,
            'expenses' => $expenses,
            'gross' => $gross,
            'projectActivities' => $projectActivities,
            'calendarActivities' => $calendarActivities,
            'overdueActivitiesCount' => $overdueActivitiesCount,
            'todayActivitiesCount' => $todayActivitiesCount,
            'pendingActivitiesCount' => $pendingActivitiesCount,
            'inProgressActivitiesCount' => $inProgressActivitiesCount,
            'activeSchedules' => $activeSchedules,
            'overallProgress' => $overallProgress,
        ];

        $this->notify('Welcome to a Financial Analysis System', 'Hello'.' '.$user->name, 'success');
//        $this->notify_toast('success','hello');
        session()->put('success','Item created successfully.');
        return view('pages.dashboard')->with($data);
    }

}

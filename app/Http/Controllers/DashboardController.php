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

    /**
     * Export follow-ups to ICS (iCalendar) format for Google Calendar import
     */
    public function exportFollowupsToCalendar(Request $request)
    {
        $user = Auth::user();

        // Get pending follow-ups
        $query = \App\Models\SalesLeadFollowup::with(['lead'])
            ->where('status', 'pending')
            ->whereNotNull('followup_date')
            ->orderBy('followup_date', 'asc');

        // If user is salesperson, filter by their leads
        if ($user->hasRole('Sales and Marketing')) {
            $query->whereHas('lead', function($q) use ($user) {
                $q->where('salesperson_id', $user->id);
            });
        }

        $followups = $query->get();

        // Generate ICS content
        $icsContent = $this->generateICS($followups);

        // Return as downloadable file
        $filename = 'followups_' . date('Y-m-d') . '.ics';

        return response($icsContent)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Generate ICS content from follow-ups
     */
    private function generateICS($followups)
    {
        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//Wajenzi Professional//Follow-up Calendar//EN\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:PUBLISH\r\n";
        $ics .= "X-WR-CALNAME:Wajenzi Follow-ups\r\n";

        foreach ($followups as $followup) {
            $uid = 'followup-' . $followup->id . '@wajenzi.com';
            $title = 'Follow-up: ' . ($followup->lead->name ?? $followup->lead_name ?? 'Lead');

            // Set time to 9 AM - 10 AM
            $startDate = $followup->followup_date->copy()->setTime(9, 0, 0);
            $endDate = $followup->followup_date->copy()->setTime(10, 0, 0);

            // Format dates for ICS (UTC)
            $dtStart = $startDate->utc()->format('Ymd\THis\Z');
            $dtEnd = $endDate->utc()->format('Ymd\THis\Z');
            $dtStamp = now()->utc()->format('Ymd\THis\Z');

            // Build description
            $description = [];
            if ($followup->next_step) {
                $description[] = "Action: " . $followup->next_step;
            }
            if ($followup->details_discussion) {
                $description[] = "Notes: " . $followup->details_discussion;
            }
            if ($followup->lead) {
                $description[] = "Lead: " . $followup->lead->name;
                if ($followup->lead->phone) {
                    $description[] = "Phone: " . $followup->lead->phone;
                }
                if ($followup->lead->email) {
                    $description[] = "Email: " . $followup->lead->email;
                }
            }
            $descText = $this->escapeICS(implode("\\n", $description));
            $titleText = $this->escapeICS($title);

            $ics .= "BEGIN:VEVENT\r\n";
            $ics .= "UID:{$uid}\r\n";
            $ics .= "DTSTAMP:{$dtStamp}\r\n";
            $ics .= "DTSTART:{$dtStart}\r\n";
            $ics .= "DTEND:{$dtEnd}\r\n";
            $ics .= "SUMMARY:{$titleText}\r\n";
            $ics .= "DESCRIPTION:{$descText}\r\n";
            $ics .= "STATUS:CONFIRMED\r\n";
            $ics .= "BEGIN:VALARM\r\n";
            $ics .= "TRIGGER:-PT30M\r\n";
            $ics .= "ACTION:DISPLAY\r\n";
            $ics .= "DESCRIPTION:Reminder: {$titleText}\r\n";
            $ics .= "END:VALARM\r\n";
            $ics .= "END:VEVENT\r\n";
        }

        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }

    /**
     * Escape special characters for ICS format
     */
    private function escapeICS($text)
    {
        $text = str_replace(['\\', ';', ',', "\n", "\r"], ['\\\\', '\\;', '\\,', '\\n', ''], $text);
        return $text;
    }

}

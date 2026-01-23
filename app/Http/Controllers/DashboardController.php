<?php

namespace App\Http\Controllers;

use App\Models\BillingDocument;
use App\Models\Collection;
use App\Models\Expense;
use App\Models\Gross;
use App\Models\ProjectSchedule;
use App\Models\ProjectScheduleActivity;
use App\Models\TransactionMovement;
use App\Models\User;
use App\Notifications\InvoiceDueReminderNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

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

        // Get invoice due dates for accountants
        $invoiceDueDates = collect();
        $overdueInvoicesCount = 0;
        $todayInvoicesCount = 0;
        $upcomingInvoicesCount = 0;
        $calendarInvoices = collect();

        // Check if user is Accountant or has permission to view invoices
        $canViewInvoices = $user->hasRole('Accountant')
            || $user->hasRole('Admin')
            || $user->hasRole('Super Admin')
            || $user->hasRole('System Administrator')
            || $user->can('Invoice List')
            || $user->can('view invoices');

        if ($canViewInvoices) {
            // Get all unpaid invoices with due dates
            $invoiceDueDates = BillingDocument::with(['client', 'project', 'lead'])
                ->unpaidWithDueDate()
                ->orderByRaw("CASE WHEN due_date < CURDATE() THEN 0 WHEN due_date = CURDATE() THEN 1 ELSE 2 END")
                ->orderBy('due_date', 'asc')
                ->limit(20)
                ->get();

            // Count invoices by status
            $overdueInvoicesCount = BillingDocument::overdueInvoices()->count();
            $todayInvoicesCount = BillingDocument::dueToday()->count();
            $upcomingInvoicesCount = BillingDocument::upcomingDue()->count();

            // Calendar invoices for selected month
            $calendarInvoices = BillingDocument::with(['client', 'project'])
                ->unpaidWithDueDate()
                ->whereYear('due_date', $calYear)
                ->whereMonth('due_date', $calMonth)
                ->get()
                ->groupBy(function($invoice) {
                    return $invoice->due_date->format('Y-m-d');
                });
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
            'invoiceDueDates' => $invoiceDueDates,
            'overdueInvoicesCount' => $overdueInvoicesCount,
            'todayInvoicesCount' => $todayInvoicesCount,
            'upcomingInvoicesCount' => $upcomingInvoicesCount,
            'calendarInvoices' => $calendarInvoices,
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

    /**
     * Export invoice due dates to ICS (iCalendar) format for Google Calendar import
     */
    public function exportInvoicesToCalendar(Request $request)
    {
        $user = Auth::user();

        // Check permission
        if (!$user->hasRole('Accountant') && !$user->hasRole('Admin') && !$user->can('Invoice List')) {
            abort(403, 'Unauthorized');
        }

        // Get unpaid invoices with due dates
        $invoices = BillingDocument::with(['client', 'project'])
            ->unpaidWithDueDate()
            ->orderBy('due_date', 'asc')
            ->get();

        // Generate ICS content
        $icsContent = $this->generateInvoiceICS($invoices);

        // Return as downloadable file
        $filename = 'invoice_due_dates_' . date('Y-m-d') . '.ics';

        return response($icsContent)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Generate ICS content from invoices
     */
    private function generateInvoiceICS($invoices)
    {
        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//Wajenzi Professional//Invoice Due Dates//EN\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:PUBLISH\r\n";
        $ics .= "X-WR-CALNAME:Wajenzi Invoice Due Dates\r\n";

        foreach ($invoices as $invoice) {
            $uid = 'invoice-' . $invoice->id . '@wajenzi.com';
            $title = 'Invoice Due: ' . $invoice->document_number;

            // Set time to 9 AM - 10 AM
            $startDate = $invoice->due_date->copy()->setTime(9, 0, 0);
            $endDate = $invoice->due_date->copy()->setTime(10, 0, 0);

            // Format dates for ICS (UTC)
            $dtStart = $startDate->utc()->format('Ymd\THis\Z');
            $dtEnd = $endDate->utc()->format('Ymd\THis\Z');
            $dtStamp = now()->utc()->format('Ymd\THis\Z');

            // Build description
            $description = [];
            $description[] = "Invoice: " . $invoice->document_number;
            $description[] = "Amount: TZS " . number_format($invoice->total_amount, 2);
            $description[] = "Balance: TZS " . number_format($invoice->balance_amount, 2);
            if ($invoice->client) {
                $description[] = "Client: " . $invoice->client->name;
                if ($invoice->client->phone) {
                    $description[] = "Phone: " . $invoice->client->phone;
                }
            }
            if ($invoice->project) {
                $description[] = "Project: " . $invoice->project->name;
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
            $ics .= "TRIGGER:-P1D\r\n"; // Remind 1 day before
            $ics .= "ACTION:DISPLAY\r\n";
            $ics .= "DESCRIPTION:Reminder: {$titleText}\r\n";
            $ics .= "END:VALARM\r\n";
            $ics .= "END:VEVENT\r\n";
        }

        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }

    /**
     * Attend to an invoice (mark action taken)
     */
    public function attendInvoice(Request $request, $id)
    {
        $user = Auth::user();

        // Check permission - same as view permission
        $canAttend = $user->hasRole('Accountant')
            || $user->hasRole('Admin')
            || $user->hasRole('Super Admin')
            || $user->hasRole('System Administrator')
            || $user->can('Invoice Edit')
            || $user->can('Invoice List');

        if (!$canAttend) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'action' => 'required|in:paid,partial,reschedule',
            'notes' => 'nullable|string|max:1000',
            'new_due_date' => 'required_if:action,reschedule|date|after:today',
            'paid_amount' => 'required_if:action,partial|numeric|min:0',
        ]);

        $invoice = BillingDocument::findOrFail($id);
        $action = $request->input('action');

        if ($action === 'paid') {
            // Mark as fully paid
            $invoice->status = 'paid';
            $invoice->paid_amount = $invoice->total_amount;
            $invoice->balance_amount = 0;
            $invoice->paid_at = now();
            $invoice->attended_at = now();
            $invoice->attended_by = $user->id;
            $invoice->attendance_notes = $request->input('notes');
            $invoice->save();

            return response()->json([
                'success' => true,
                'message' => 'Invoice marked as paid successfully.'
            ]);

        } elseif ($action === 'partial') {
            // Record partial payment
            $paidAmount = $request->input('paid_amount');
            $invoice->paid_amount = ($invoice->paid_amount ?? 0) + $paidAmount;
            $invoice->balance_amount = $invoice->total_amount - $invoice->paid_amount;

            if ($invoice->balance_amount <= 0) {
                $invoice->status = 'paid';
                $invoice->paid_at = now();
            } else {
                $invoice->status = 'partial_paid';
            }

            $invoice->attended_at = now();
            $invoice->attended_by = $user->id;
            $invoice->attendance_notes = $request->input('notes');
            $invoice->save();

            return response()->json([
                'success' => true,
                'message' => 'Partial payment recorded successfully.',
                'balance' => $invoice->balance_amount
            ]);

        } elseif ($action === 'reschedule') {
            // Reschedule due date
            if (!$invoice->original_due_date) {
                $invoice->original_due_date = $invoice->due_date;
            }
            $invoice->due_date = $request->input('new_due_date');
            $invoice->rescheduled_at = now();
            $invoice->rescheduled_by = $user->id;
            $invoice->reschedule_reason = $request->input('notes');
            $invoice->attended_at = now();
            $invoice->attended_by = $user->id;
            $invoice->save();

            return response()->json([
                'success' => true,
                'message' => 'Invoice due date rescheduled successfully.',
                'new_due_date' => $invoice->due_date->format('d M Y')
            ]);
        }

        return response()->json(['error' => 'Invalid action'], 400);
    }

    /**
     * Get invoice details for attend modal
     */
    public function getInvoiceForAttend($id)
    {
        $user = Auth::user();

        // Check permission - same as view permission
        $canView = $user->hasRole('Accountant')
            || $user->hasRole('Admin')
            || $user->hasRole('Super Admin')
            || $user->hasRole('System Administrator')
            || $user->can('Invoice Edit')
            || $user->can('Invoice List');

        if (!$canView) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $invoice = BillingDocument::with(['client', 'project'])->findOrFail($id);

        return response()->json([
            'id' => $invoice->id,
            'document_number' => $invoice->document_number ?? 'N/A',
            'client_name' => $invoice->client->name ?? 'No Client',
            'project_name' => $invoice->project->name ?? 'N/A',
            'total_amount' => floatval($invoice->total_amount ?? 0),
            'paid_amount' => floatval($invoice->paid_amount ?? 0),
            'balance_amount' => floatval($invoice->balance_amount ?? 0),
            'due_date' => $invoice->due_date->format('Y-m-d'),
            'due_date_formatted' => $invoice->due_date->format('d M Y'),
            'status' => $invoice->status,
            'is_overdue' => $invoice->is_overdue,
            'original_due_date' => $invoice->original_due_date ? $invoice->original_due_date->format('d M Y') : null,
        ]);
    }

    /**
     * Send invoice due reminders to accountants (can be called via scheduler)
     */
    public function sendInvoiceReminders()
    {
        // Get invoices due today or overdue that haven't been reminded today
        $invoices = BillingDocument::with(['client', 'project'])
            ->unpaidWithDueDate()
            ->where(function($query) {
                $query->whereDate('due_date', '<=', now()->toDateString());
            })
            ->where(function($query) {
                $query->whereNull('last_reminder_sent_at')
                      ->orWhereDate('last_reminder_sent_at', '<', now()->toDateString());
            })
            ->get();

        if ($invoices->isEmpty()) {
            return response()->json(['message' => 'No reminders to send']);
        }

        // Get all accountants
        $accountants = User::role('Accountant')->get();

        if ($accountants->isEmpty()) {
            return response()->json(['message' => 'No accountants found']);
        }

        // Send notification
        foreach ($invoices as $invoice) {
            try {
                Notification::send($accountants, new InvoiceDueReminderNotification($invoice));

                // Update reminder tracking
                $invoice->last_reminder_sent_at = now();
                $invoice->reminder_count = ($invoice->reminder_count ?? 0) + 1;
                $invoice->save();
            } catch (\Exception $e) {
                \Log::error('Failed to send invoice reminder: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Reminders sent successfully',
            'count' => $invoices->count()
        ]);
    }

}

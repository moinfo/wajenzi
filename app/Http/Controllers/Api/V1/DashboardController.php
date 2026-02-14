<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BillingDocument;
use App\Models\Collection;
use App\Models\Project;
use App\Models\ProjectBoq;
use App\Models\ProjectExpense;
use App\Models\ProjectMaterialRequest;
use App\Models\ProjectSchedule;
use App\Models\ProjectScheduleActivity;
use App\Models\ProjectSiteVisit;
use App\Models\SalesLeadFollowup;
use App\Models\SiteDailyReport;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * GET /api/v1/dashboard
     * Main dashboard summary — stats, approvals, and lightweight aggregates.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $this->getStats(),
                'pending_approvals' => $this->getPendingApprovals(),
                'followup_summary' => $this->getFollowupSummary($user),
                'activities_summary' => $this->getActivitiesSummary($user),
                'invoices_summary' => $this->getInvoicesSummary($user),
                'project_progress' => $this->getProjectProgress($user),
            ],
        ]);
    }

    /**
     * GET /api/v1/dashboard/activities
     * Paginated project activity cards.
     */
    public function activities(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $this->buildActivityQuery($user);

        $activities = $query
            ->orderBy('start_date', 'asc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $activities->map(fn($a) => $this->formatActivity($a)),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/dashboard/invoices
     * Invoice due dates list.
     */
    public function invoices(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->can('View All Invoice Due Dates')) {
            return response()->json([
                'success' => true,
                'data' => [],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 0],
            ]);
        }

        $invoices = BillingDocument::with(['client', 'project'])
            ->unpaidWithDueDate()
            ->orderByRaw("CASE WHEN due_date < CURDATE() THEN 0 WHEN due_date = CURDATE() THEN 1 ELSE 2 END")
            ->orderBy('due_date', 'asc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $invoices->map(fn($inv) => $this->formatInvoice($inv)),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/dashboard/followups
     * Follow-up to-do list.
     */
    public function followups(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $this->buildFollowupQuery($user);

        $followups = $query
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('followup_date', 'asc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $followups->map(fn($f) => $this->formatFollowup($f)),
            'meta' => [
                'current_page' => $followups->currentPage(),
                'last_page' => $followups->lastPage(),
                'per_page' => $followups->perPage(),
                'total' => $followups->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/dashboard/calendar?month=2&year=2026
     * Calendar events grouped by date.
     */
    public function calendar(Request $request): JsonResponse
    {
        $user = $request->user();
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $events = [];

        // Activities
        $activityQuery = ProjectScheduleActivity::with(['schedule.lead', 'assignedUser'])
            ->whereHas('schedule', fn($q) => $q->whereIn('status', ['confirmed', 'in_progress']))
            ->whereYear('start_date', $year)
            ->whereMonth('start_date', $month);

        if (!$user->can('View All Project Activities')) {
            $this->applyActivityPermissionFilter($activityQuery, $user);
        }

        $activityQuery->get()->each(function ($a) use (&$events) {
            $date = $a->start_date->format('Y-m-d');
            $events[$date]['activities'][] = [
                'id' => $a->id,
                'name' => $a->name,
                'status' => $a->status,
                'assigned_to' => $a->assignedUser?->name,
            ];
        });

        // Follow-ups
        $followupQuery = SalesLeadFollowup::with(['lead'])
            ->whereYear('followup_date', $year)
            ->whereMonth('followup_date', $month)
            ->whereHas('lead');

        if (!$user->can('View All Follow-ups')) {
            $followupQuery->whereHas('lead', fn($q) => $q->where('salesperson_id', $user->id));
        }

        $followupQuery->get()->each(function ($f) use (&$events) {
            $date = $f->followup_date->format('Y-m-d');
            $events[$date]['followups'][] = [
                'id' => $f->id,
                'lead_name' => $f->lead?->name ?? $f->lead_name,
                'status' => $f->status,
            ];
        });

        // Invoices
        if ($user->can('View All Invoice Due Dates')) {
            BillingDocument::with(['client', 'project'])
                ->unpaidWithDueDate()
                ->whereYear('due_date', $year)
                ->whereMonth('due_date', $month)
                ->get()
                ->each(function ($inv) use (&$events) {
                    $date = $inv->due_date->format('Y-m-d');
                    $events[$date]['invoices'][] = [
                        'id' => $inv->id,
                        'document_number' => $inv->document_number,
                        'status' => $inv->status,
                        'total_amount' => (float) $inv->total_amount,
                    ];
                });
        }

        // Sort events by date
        ksort($events);

        return response()->json([
            'success' => true,
            'data' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'events' => $events,
            ],
        ]);
    }

    /**
     * GET /api/v1/dashboard/project-status
     * Project status cards with progress.
     */
    public function projectStatus(Request $request): JsonResponse
    {
        $projects = Project::where('status', 'APPROVED')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $projects->map(function ($p) {
                // Calculate progress from schedule if exists
                $schedule = ProjectSchedule::where('lead_id', $p->lead_id)
                    ->whereIn('status', ['confirmed', 'in_progress'])
                    ->with('activities')
                    ->first();

                $progress = 0;
                $statusLabel = 'not_started';

                if ($schedule) {
                    $details = $schedule->progress_details;
                    $progress = $details['percentage'];

                    if ($details['overdue'] > 0) {
                        $statusLabel = 'at_risk';
                    } elseif ($details['in_progress'] > 0 || $details['completed'] > 0) {
                        $statusLabel = 'on_track';
                    }
                }

                return [
                    'id' => $p->id,
                    'name' => $p->project_name,
                    'description' => $p->description,
                    'status' => $statusLabel,
                    'progress_percentage' => $progress,
                ];
            }),
            'meta' => [
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/dashboard/recent-activities
     * Recent system activities from the last 7 days.
     */
    public function recentActivities(Request $request): JsonResponse
    {
        $since = now()->subDays(7);
        $items = collect();

        // Recent projects
        Project::where('created_at', '>=', $since)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->each(function ($p) use ($items) {
                $items->push([
                    'type' => 'project_created',
                    'icon' => 'add_circle',
                    'color' => 'green',
                    'message' => 'New project "' . $p->project_name . '" added to portfolio',
                    'timestamp' => $p->created_at->toISOString(),
                    'time_ago' => $p->created_at->diffForHumans(),
                ]);
            });

        // Recent invoices
        BillingDocument::where('document_type', 'invoice')
            ->where('created_at', '>=', $since)
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->each(function ($inv) use ($items) {
                $items->push([
                    'type' => 'invoice_created',
                    'icon' => 'receipt',
                    'color' => 'blue',
                    'message' => 'Invoice ' . $inv->document_number . ' created for ' . ($inv->client?->name ?? 'client'),
                    'timestamp' => $inv->created_at->toISOString(),
                    'time_ago' => $inv->created_at->diffForHumans(),
                ]);
            });

        // Recent material requests
        ProjectMaterialRequest::where('created_at', '>=', $since)
            ->with('project')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->each(function ($mr) use ($items) {
                $items->push([
                    'type' => 'material_request',
                    'icon' => 'inventory',
                    'color' => 'orange',
                    'message' => 'Material request for ' . ($mr->project?->project_name ?? 'project'),
                    'timestamp' => $mr->created_at->toISOString(),
                    'time_ago' => $mr->created_at->diffForHumans(),
                ]);
            });

        // Recent site visits
        ProjectSiteVisit::where('created_at', '>=', $since)
            ->with('project')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->each(function ($sv) use ($items) {
                $items->push([
                    'type' => 'site_visit',
                    'icon' => 'location_on',
                    'color' => 'teal',
                    'message' => 'Site visit recorded for ' . ($sv->project?->project_name ?? 'project'),
                    'timestamp' => $sv->created_at->toISOString(),
                    'time_ago' => $sv->created_at->diffForHumans(),
                ]);
            });

        // Recent completed activities
        ProjectScheduleActivity::where('status', 'completed')
            ->where('completed_at', '>=', $since)
            ->with('schedule.lead')
            ->orderBy('completed_at', 'desc')
            ->limit(5)
            ->get()
            ->each(function ($a) use ($items) {
                $items->push([
                    'type' => 'activity_completed',
                    'icon' => 'check_circle',
                    'color' => 'green',
                    'message' => 'Activity "' . $a->name . '" completed',
                    'timestamp' => $a->completed_at->toISOString(),
                    'time_ago' => $a->completed_at->diffForHumans(),
                ]);
            });

        // Sort all by timestamp descending, limit 20
        $sorted = $items->sortByDesc('timestamp')->values()->take(20);

        return response()->json([
            'success' => true,
            'data' => $sorted,
        ]);
    }

    // ─── Private helpers ────────────────────────────────────────────────

    private function getStats(): array
    {
        // Revenue this month (approved collections)
        $thisMonthRevenue = Collection::whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->where('status', 'APPROVED')
            ->sum('amount');

        // Revenue last month for comparison
        $lastMonthRevenue = Collection::whereMonth('date', now()->subMonth()->month)
            ->whereYear('date', now()->subMonth()->year)
            ->where('status', 'APPROVED')
            ->sum('amount');

        $revenueChange = $lastMonthRevenue > 0
            ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        // Active projects
        $activeProjects = Project::where('status', 'APPROVED')->count();
        $newProjectsThisMonth = Project::where('status', 'APPROVED')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Team members
        $userCounts = User::getUserCounts();

        // Budget utilization
        $totalBudget = Project::where('status', 'APPROVED')->sum('contract_value');
        $totalSpent = ProjectExpense::whereHas('project', fn($q) => $q->where('status', 'APPROVED'))
            ->sum('amount');
        $budgetPercentage = $totalBudget > 0 ? round(($totalSpent / $totalBudget) * 100) : 0;

        return [
            'total_revenue' => (float) $thisMonthRevenue,
            'revenue_change_percent' => $revenueChange,
            'active_projects' => $activeProjects,
            'new_projects_this_month' => $newProjectsThisMonth,
            'team_members' => [
                'total' => (int) ($userCounts->total ?? 0),
                'male' => (int) ($userCounts->total_male ?? 0),
                'female' => (int) ($userCounts->total_female ?? 0),
            ],
            'budget_utilization' => [
                'total_budget' => (float) $totalBudget,
                'total_spent' => (float) $totalSpent,
                'percentage' => $budgetPercentage,
            ],
        ];
    }

    private function getPendingApprovals(): array
    {
        $items = [
            [
                'type' => 'material_request',
                'label' => 'Material Request',
                'count' => ProjectMaterialRequest::where('status', 'pending')->count(),
                'icon' => 'inventory',
            ],
            [
                'type' => 'project_boq',
                'label' => 'Project BOQ',
                'count' => ProjectBoq::whereHas('approvalStatus', fn($q) => $q->whereIn('status', ['Submitted', 'Pending']))->count(),
                'icon' => 'receipt',
            ],
            [
                'type' => 'project_expense',
                'label' => 'Project Expense',
                'count' => 0, // project_expenses table has no status column yet
                'icon' => 'payments',
            ],
            [
                'type' => 'site_visit',
                'label' => 'Site Visit',
                'count' => ProjectSiteVisit::where('status', 'pending')->count(),
                'icon' => 'location_on',
            ],
            [
                'type' => 'site_daily_report',
                'label' => 'Daily Report',
                'count' => SiteDailyReport::where('status', 'pending')->count(),
                'icon' => 'description',
            ],
        ];

        return [
            'total' => collect($items)->sum('count'),
            'items' => $items,
        ];
    }

    private function getFollowupSummary($user): array
    {
        $canViewAll = $user->can('View All Follow-ups');

        $baseQuery = function () use ($canViewAll, $user) {
            $q = SalesLeadFollowup::where('status', 'pending')->whereHas('lead');
            if (!$canViewAll) {
                $q->whereHas('lead', fn($lq) => $lq->where('salesperson_id', $user->id));
            }
            return $q;
        };

        $completedQuery = SalesLeadFollowup::where('status', 'completed')
            ->whereMonth('followup_date', now()->month)
            ->whereYear('followup_date', now()->year)
            ->whereHas('lead');

        if (!$canViewAll) {
            $completedQuery->whereHas('lead', fn($lq) => $lq->where('salesperson_id', $user->id));
        }

        return [
            'overdue' => $baseQuery()->whereDate('followup_date', '<', now()->toDateString())->count(),
            'today' => $baseQuery()->whereDate('followup_date', now()->toDateString())->count(),
            'upcoming' => $baseQuery()->whereDate('followup_date', '>', now()->toDateString())->count(),
            'completed_this_month' => $completedQuery->count(),
        ];
    }

    private function getActivitiesSummary($user): array
    {
        $activities = $this->buildActivityQuery($user)->get();

        return [
            'overdue' => $activities->filter(fn($a) => $a->isOverdue())->count(),
            'due_today' => $activities->filter(fn($a) => $a->start_date->isToday())->count(),
            'pending' => $activities->where('status', 'pending')->count(),
            'in_progress' => $activities->where('status', 'in_progress')->count(),
        ];
    }

    private function getInvoicesSummary($user): array
    {
        if (!$user->can('View All Invoice Due Dates')) {
            return ['overdue' => 0, 'due_today' => 0, 'upcoming' => 0, 'paid_this_month' => 0];
        }

        return [
            'overdue' => BillingDocument::overdueInvoices()->count(),
            'due_today' => BillingDocument::dueToday()->count(),
            'upcoming' => BillingDocument::upcomingDue()->count(),
            'paid_this_month' => BillingDocument::where('document_type', 'invoice')
                ->where('status', 'paid')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->count(),
        ];
    }

    private function getProjectProgress($user): array
    {
        $scheduleQuery = ProjectSchedule::with(['lead', 'activities'])
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->orderBy('start_date', 'asc');

        if (!$user->can('View All Project Activities')) {
            $scheduleQuery->where(function ($q) use ($user) {
                $q->where('assigned_architect_id', $user->id)
                    ->orWhereHas('activities', fn($aq) => $aq->where('assigned_to', $user->id));
            });
        }

        $schedules = $scheduleQuery->get();

        $totalActivities = 0;
        $completed = 0;
        $inProgress = 0;
        $pending = 0;
        $overdue = 0;
        $projects = [];

        foreach ($schedules as $schedule) {
            $details = $schedule->progress_details;
            $totalActivities += $details['total'];
            $completed += $details['completed'];
            $inProgress += $details['in_progress'];
            $pending += $details['pending'];
            $overdue += $details['overdue'];

            $projects[] = [
                'id' => $schedule->id,
                'name' => $schedule->lead?->name,
                'lead_name' => $schedule->assignedArchitect?->name,
                'percentage' => $details['percentage'],
                'completed' => $details['completed'],
                'in_progress' => $details['in_progress'],
                'pending' => $details['pending'],
                'overdue' => $details['overdue'],
            ];
        }

        $overallPercentage = $totalActivities > 0
            ? round(($completed / $totalActivities) * 100, 1)
            : 0;

        return [
            'overall_percentage' => $overallPercentage,
            'total_activities' => $totalActivities,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'pending' => $pending,
            'overdue' => $overdue,
            'projects' => $projects,
        ];
    }

    /**
     * Build activity query with permission filtering.
     * Mirrors the web DashboardController logic exactly.
     */
    private function buildActivityQuery($user)
    {
        $query = ProjectScheduleActivity::with(['schedule.lead', 'assignedUser'])
            ->whereHas('schedule', fn($q) => $q->whereIn('status', ['confirmed', 'in_progress']))
            ->whereIn('status', ['pending', 'in_progress']);

        if (!$user->can('View All Project Activities')) {
            $this->applyActivityPermissionFilter($query, $user);
        }

        return $query;
    }

    /**
     * Apply user-scoped activity filter (own + unassigned on own schedules).
     */
    private function applyActivityPermissionFilter($query, $user): void
    {
        $query->where(function ($q) use ($user) {
            $q->where('assigned_to', $user->id)
                ->orWhere(function ($q2) use ($user) {
                    $q2->whereNull('assigned_to')
                        ->whereHas('schedule', fn($sq) => $sq->where('assigned_architect_id', $user->id));
                });
        });
    }

    /**
     * Build follow-up query with permission filtering.
     */
    private function buildFollowupQuery($user)
    {
        $query = SalesLeadFollowup::with(['lead.salesperson', 'lead.leadStatus'])
            ->whereHas('lead');

        if (!$user->can('View All Follow-ups')) {
            $query->whereHas('lead', fn($q) => $q->where('salesperson_id', $user->id));
        }

        return $query;
    }

    private function formatActivity($a): array
    {
        return [
            'id' => $a->id,
            'activity_code' => $a->activity_code,
            'name' => $a->name,
            'phase' => $a->phase,
            'assigned_to' => $a->assignedUser?->name,
            'start_date' => $a->start_date->format('Y-m-d'),
            'end_date' => $a->end_date->format('Y-m-d'),
            'duration_days' => $a->duration_days,
            'status' => $a->status,
            'is_overdue' => $a->isOverdue(),
            'project_name' => $a->schedule?->lead?->name,
        ];
    }

    private function formatInvoice($inv): array
    {
        $isOverdue = $inv->due_date->isPast() && !in_array($inv->status, ['paid', 'cancelled', 'void']);
        $daysOverdue = $isOverdue ? $inv->due_date->diffInDays(now()) : 0;

        return [
            'id' => $inv->id,
            'document_number' => $inv->document_number,
            'client_name' => $inv->client?->name,
            'project_name' => $inv->project?->name ?? 'General',
            'due_date' => $inv->due_date->format('Y-m-d'),
            'total_amount' => (float) $inv->total_amount,
            'balance_amount' => (float) $inv->balance_amount,
            'status' => $inv->status,
            'is_overdue' => $isOverdue,
            'days_overdue' => $daysOverdue,
        ];
    }

    private function formatFollowup($f): array
    {
        return [
            'id' => $f->id,
            'lead_name' => $f->lead?->name ?? $f->lead_name,
            'client_name' => $f->client?->name ?? $f->lead?->client_name,
            'followup_date' => $f->followup_date?->format('Y-m-d'),
            'details' => $f->details_discussion,
            'next_step' => $f->next_step,
            'status' => $f->status,
            'is_overdue' => $f->status === 'pending' && $f->followup_date?->isPast(),
        ];
    }
}

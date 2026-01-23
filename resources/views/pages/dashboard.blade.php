@extends('layouts.backend')

@section('content')
    <?php
    use App\Classes\Utility;
    use App\Models\AdvanceSalary;
    use App\Models\Loan;
    use App\Models\Payroll;
    use App\Models\Staff;
    use App\Models\User;
    use App\Models\SalesLeadFollowup;
    use Illuminate\Support\Str;

    // Date calculations
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
    $last_month_last_date = date("Y-m-t", strtotime("last month"));
    $last_month_first_date = date("Y-m-01", strtotime("last month"));

    // Financial data
    $sales = \App\Models\Sale::getTotalTax($start_date, $end_date);
    $purchases = \App\Models\Purchase::getTotalPurchasesWithVAT($end_date, null, null, $start_date);
    $vat_analysis = new \App\Models\VatAnalysis();
    $this_month_tax_payable = $vat_analysis->getTaxPayable($end_date);
    $last_month_tax_payable = \App\Models\VatPayment::getTotalPaymentOfLastMonth($last_month_first_date, $last_month_last_date);

    // Approval counts
    $advance_salary_counting = AdvanceSalary::countUnapproved();
    $staff_loan_counting = Loan::countUnapproved();
    $payroll_counting = Payroll::countUnapproved();

    // Links
    $advance_salary_link = "settings/advance_salaries";
    $staff_loan_link = 'settings/staff_loans';
    $payroll_link = 'payroll/payroll_administration';

    // Status documents for approvals
    $status_docs = [
        ['name' => 'Payroll', 'count' => $payroll_counting, 'icon' => 'fa fa-money-bill-wave', 'link' => $payroll_link, 'color' => 'blue'],
        ['name' => 'Advance Salary', 'count' => $advance_salary_counting, 'icon' => 'fa fa-hand-holding-usd', 'link' => $advance_salary_link, 'color' => 'green'],
        ['name' => 'Staff Loan', 'count' => $staff_loan_counting, 'icon' => 'fa fa-credit-card', 'link' => $staff_loan_link, 'color' => 'orange'],
        ['name' => 'Material Request', 'count' => 2, 'icon' => 'fa fa-boxes', 'link' => '#', 'color' => 'purple'],
        ['name' => 'Project BOQ', 'count' => 3, 'icon' => 'fa fa-file-invoice', 'link' => '#', 'color' => 'indigo'],
        ['name' => 'Project Expense', 'count' => 1, 'icon' => 'fa fa-receipt', 'link' => '#', 'color' => 'red'],
        ['name' => 'Site Visit', 'count' => 4, 'icon' => 'fa fa-map-marker-alt', 'link' => '#', 'color' => 'teal'],
    ];

    // User and department data
    $counts = User::getUserCounts();
    $departmentCounts = User::getDepartmentMemberCounts();

    // Follow-ups for current user (salesperson) or all if admin
    // Show ALL follow-ups (pending first, then completed)
    $followupsQuery = SalesLeadFollowup::with(['lead.salesperson', 'lead.leadStatus'])
        ->whereHas('lead')
        ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END") // Pending first
        ->orderBy('followup_date', 'asc');

    // If user is a salesperson, show only their follow-ups
    if (Auth::user()->hasRole('Sales and Marketing')) {
        $followupsQuery->whereHas('lead', function($q) {
            $q->where('salesperson_id', Auth::id());
        });
    }

    $allFollowups = $followupsQuery->limit(20)->get();

    // Counts for PENDING follow-ups only
    $todayFollowupsCount = SalesLeadFollowup::where('status', 'pending')
        ->whereDate('followup_date', now()->toDateString())->count();
    $overdueFollowupsCount = SalesLeadFollowup::where('status', 'pending')
        ->whereDate('followup_date', '<', now()->toDateString())->count();
    $upcomingFollowupsCount = SalesLeadFollowup::where('status', 'pending')
        ->whereDate('followup_date', '>', now()->toDateString())->count();
    $completedFollowupsCount = SalesLeadFollowup::where('status', 'completed')
        ->whereMonth('followup_date', now()->month)->count();

    // Calendar data - get ALL followups for selected month (from request or current)
    $calendarMonth = request('cal_month', now()->month);
    $calendarYear = request('cal_year', now()->year);
    $calendarFollowups = SalesLeadFollowup::with(['lead'])
        ->whereYear('followup_date', $calendarYear)
        ->whereMonth('followup_date', $calendarMonth)
        ->whereHas('lead')
        ->get()
        ->groupBy(function($item) {
            return $item->followup_date->format('Y-m-d');
        });
    ?>

    <!-- Modern Wajenzi Dashboard -->
    <div class="wajenzi-dashboard">
        <!-- Welcome Header -->
        <div class="dashboard-welcome">
            <div class="welcome-content">
                <h1 class="welcome-title">Welcome back, {{ Auth::user()->name }}! 👋</h1>
                <p class="welcome-subtitle">Here's what's happening with your construction projects today</p>
            </div>
            <div class="welcome-actions">
                <button class="action-btn primary">
                    <i class="fa fa-plus"></i>
                    New Project
                </button>
                <button class="action-btn secondary">
                    <i class="fa fa-chart-line"></i>
                    View Reports
                </button>
            </div>
        </div>

        <!-- Key Metrics Grid -->
        <div class="metrics-grid">
            <!-- Financial Overview -->
            <div class="metric-card financial">
                <div class="metric-header">
                    <div class="metric-icon">
                        <i class="fa fa-chart-line"></i>
                    </div>
                    <div class="metric-trend up">
                        <i class="fa fa-arrow-up"></i>
                        <span>+12.5%</span>
                    </div>
                </div>
                <div class="metric-content">
                    <h3>Total Revenue</h3>
                    <div class="metric-value">TZS {{ number_format($sales ?? 0, 2) }}</div>
                    <p class="metric-period">This month</p>
                </div>
            </div>

            <!-- Projects Overview -->
            <div class="metric-card projects">
                <div class="metric-header">
                    <div class="metric-icon">
                        <i class="fa fa-building"></i>
                    </div>
                    <div class="metric-trend up">
                        <i class="fa fa-arrow-up"></i>
                        <span>+3</span>
                    </div>
                </div>
                <div class="metric-content">
                    <h3>Active Projects</h3>
                    <div class="metric-value">24</div>
                    <p class="metric-period">Currently running</p>
                </div>
            </div>

            <!-- Team Overview -->
            <div class="metric-card team">
                <div class="metric-header">
                    <div class="metric-icon">
                        <i class="fa fa-users"></i>
                    </div>
                    <div class="metric-badge">
                        <span>{{$counts->total}}</span>
                    </div>
                </div>
                <div class="metric-content">
                    <h3>Team Members</h3>
                    <div class="metric-breakdown">
                        <span class="breakdown-item">
                            <i class="fa fa-male"></i>
                            {{$counts->total_male}} Male
                        </span>
                        <span class="breakdown-item">
                            <i class="fa fa-female"></i>
                            {{$counts->total_female}} Female
                        </span>
                    </div>
                </div>
            </div>

            <!-- Budget Overview -->
            <div class="metric-card budget">
                <div class="metric-header">
                    <div class="metric-icon">
                        <i class="fa fa-wallet"></i>
                    </div>
                    <div class="metric-progress">
                        <span>68%</span>
                    </div>
                </div>
                <div class="metric-content">
                    <h3>Budget Utilization</h3>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 68%"></div>
                    </div>
                    <p class="metric-period">TZS 2.4M of 3.5M used</p>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Pending Approvals -->
            <div class="dashboard-section approvals">
                <div class="section-header">
                    <h2>Pending Approvals</h2>
                    <span class="section-count">{{ array_sum(array_column($status_docs, 'count')) }}</span>
                </div>
                <div class="approval-list">
                    @foreach($status_docs as $doc)
                        @if($doc['count'] > 0)
                            <div class="approval-item">
                                <div class="approval-icon {{ $doc['color'] }}">
                                    <i class="{{ $doc['icon'] }}"></i>
                                </div>
                                <div class="approval-content">
                                    <span class="approval-name">{{ $doc['name'] }}</span>
                                    <span class="approval-desc">Requires your attention</span>
                                </div>
                                <div class="approval-badge">{{ $doc['count'] }}</div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Follow-up To-Do List -->
            <div class="dashboard-section followups-todo">
                <div class="section-header">
                    <h2><i class="fa fa-phone-alt mr-2"></i>Follow-up To-Do</h2>
                </div>
                <div class="followup-stats-row">
                    <div class="followup-stat-card overdue">
                        <div class="stat-icon"><i class="fa fa-exclamation-triangle"></i></div>
                        <div class="stat-info">
                            <span class="stat-number">{{ $overdueFollowupsCount }}</span>
                            <span class="stat-label">Overdue</span>
                        </div>
                    </div>
                    <div class="followup-stat-card today">
                        <div class="stat-icon"><i class="fa fa-clock"></i></div>
                        <div class="stat-info">
                            <span class="stat-number">{{ $todayFollowupsCount }}</span>
                            <span class="stat-label">Today</span>
                        </div>
                    </div>
                    <div class="followup-stat-card upcoming">
                        <div class="stat-icon"><i class="fa fa-calendar-alt"></i></div>
                        <div class="stat-info">
                            <span class="stat-number">{{ $upcomingFollowupsCount }}</span>
                            <span class="stat-label">Upcoming</span>
                        </div>
                    </div>
                    <div class="followup-stat-card completed">
                        <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
                        <div class="stat-info">
                            <span class="stat-number">{{ $completedFollowupsCount }}</span>
                            <span class="stat-label">Completed</span>
                        </div>
                    </div>
                </div>
                <div class="followup-list">
                    @forelse($allFollowups as $followup)
                        @php
                            $isCompleted = $followup->status === 'completed';
                            $isCancelled = $followup->status === 'cancelled';
                            $isToday = $followup->followup_date && $followup->followup_date->isToday();
                            $isOverdue = !$isCompleted && !$isCancelled && $followup->followup_date && $followup->followup_date->isPast() && !$isToday;
                            $isTomorrow = $followup->followup_date && $followup->followup_date->isTomorrow();
                        @endphp
                        <a href="{{ route('leads.show', $followup->lead->id) }}" class="followup-item {{ $isCompleted ? 'completed' : ($isCancelled ? 'cancelled' : ($isOverdue ? 'overdue' : ($isToday ? 'today' : ($isTomorrow ? 'tomorrow' : '')))) }}">
                            <div class="followup-date-badge {{ $isCompleted ? 'completed' : '' }}">
                                <span class="day">{{ $followup->followup_date->format('d') }}</span>
                                <span class="month">{{ $followup->followup_date->format('M') }}</span>
                            </div>
                            <div class="followup-content {{ $isCompleted ? 'completed' : '' }}">
                                <span class="followup-lead-name">{{ $followup->lead->name }}</span>
                                <span class="followup-details">{{ Str::limit($followup->next_step ?: $followup->details_discussion, 40) }}</span>
                                @if($followup->lead->salesperson)
                                    <span class="followup-assignee">
                                        <i class="fa fa-user"></i> {{ $followup->lead->salesperson->name }}
                                    </span>
                                @endif
                            </div>
                            <div class="followup-status">
                                @if($isCompleted)
                                    <span class="status-label completed"><i class="fa fa-check-circle"></i> Done</span>
                                @elseif($isCancelled)
                                    <span class="status-label cancelled"><i class="fa fa-times-circle"></i> Cancelled</span>
                                @elseif($isOverdue)
                                    <span class="status-label overdue"><i class="fa fa-exclamation-circle"></i> Overdue</span>
                                @elseif($isToday)
                                    <span class="status-label today"><i class="fa fa-clock"></i> Today</span>
                                @elseif($isTomorrow)
                                    <span class="status-label tomorrow"><i class="fa fa-calendar"></i> Tomorrow</span>
                                @else
                                    <span class="status-label upcoming">{{ $followup->followup_date->format('D') }}</span>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="no-followups">
                            <i class="fa fa-check-circle"></i>
                            <p>No follow-ups scheduled</p>
                        </div>
                    @endforelse
                </div>
                @if($allFollowups->count() > 0)
                    <div class="followup-footer">
                        <a href="{{ route('leads.index') }}" class="view-all-btn">
                            <i class="fa fa-list"></i> View All Leads
                        </a>
                    </div>
                @endif
            </div>

            <!-- Project Activities To-Do List -->
            @if(isset($projectActivities) && $projectActivities->count() > 0)
            <div class="dashboard-section project-activities-todo">
                <div class="section-header">
                    <h2><i class="fa fa-tasks mr-2"></i>Project Activities</h2>
                </div>
                <div class="followup-stats-row">
                    <div class="followup-stat-card overdue">
                        <div class="stat-icon"><i class="fa fa-exclamation-triangle"></i></div>
                        <div class="stat-info">
                            <span class="stat-number">{{ $overdueActivitiesCount ?? 0 }}</span>
                            <span class="stat-label">Overdue</span>
                        </div>
                    </div>
                    <div class="followup-stat-card today">
                        <div class="stat-icon"><i class="fa fa-clock"></i></div>
                        <div class="stat-info">
                            <span class="stat-number">{{ $todayActivitiesCount ?? 0 }}</span>
                            <span class="stat-label">Due Today</span>
                        </div>
                    </div>
                    <div class="followup-stat-card upcoming">
                        <div class="stat-icon"><i class="fa fa-calendar-alt"></i></div>
                        <div class="stat-info">
                            <span class="stat-number">{{ $pendingActivitiesCount ?? 0 }}</span>
                            <span class="stat-label">Pending</span>
                        </div>
                    </div>
                    <div class="followup-stat-card completed">
                        <div class="stat-icon"><i class="fa fa-spinner"></i></div>
                        <div class="stat-info">
                            <span class="stat-number">{{ $inProgressActivitiesCount ?? 0 }}</span>
                            <span class="stat-label">In Progress</span>
                        </div>
                    </div>
                </div>
                <div class="followup-list">
                    @foreach($projectActivities->take(8) as $activity)
                        @php
                            $isOverdue = $activity->isOverdue();
                            $isToday = $activity->start_date->isToday();
                            $isInProgress = $activity->status === 'in_progress';
                            $isTomorrow = $activity->start_date->isTomorrow();
                        @endphp
                        <a href="{{ route('project-schedules.show', $activity->project_schedule_id) }}" class="followup-item {{ $isOverdue ? 'overdue' : ($isInProgress ? 'today' : ($isToday ? 'today' : ($isTomorrow ? 'tomorrow' : ''))) }}">
                            <div class="followup-date-badge">
                                <span class="day">{{ $activity->start_date->format('d') }}</span>
                                <span class="month">{{ $activity->start_date->format('M') }}</span>
                            </div>
                            <div class="followup-content">
                                <span class="followup-lead-name">{{ $activity->activity_code }}: {{ Str::limit($activity->name, 30) }}</span>
                                <span class="followup-details">{{ $activity->schedule->lead->lead_number ?? '' }} - {{ Str::limit($activity->schedule->lead->name ?? '', 25) }}</span>
                                <span class="followup-assignee">
                                    <i class="fa fa-layer-group"></i> {{ $activity->phase }}
                                </span>
                            </div>
                            <div class="followup-status">
                                @if($isInProgress)
                                    <span class="status-label today"><i class="fa fa-spinner fa-spin"></i> In Progress</span>
                                @elseif($isOverdue)
                                    <span class="status-label overdue"><i class="fa fa-exclamation-circle"></i> Overdue</span>
                                @elseif($isToday)
                                    <span class="status-label today"><i class="fa fa-clock"></i> Due Today</span>
                                @elseif($isTomorrow)
                                    <span class="status-label tomorrow"><i class="fa fa-calendar"></i> Tomorrow</span>
                                @else
                                    <span class="status-label upcoming">{{ $activity->start_date->format('d M') }}</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
                @if($projectActivities->count() > 0)
                    <div class="followup-footer">
                        <a href="{{ route('project-schedules.index') }}" class="view-all-btn">
                            <i class="fa fa-list"></i> View All Schedules
                        </a>
                    </div>
                @endif
            </div>
            @endif

            <!-- Project Progress Overview -->
            @if(isset($activeSchedules) && $activeSchedules->count() > 0)
            <div class="dashboard-section project-progress-section">
                <div class="section-header">
                    <h2><i class="fa fa-chart-pie mr-2"></i>Project Progress</h2>
                    <span class="section-count">{{ $overallProgress['total_projects'] }} Active</span>
                </div>

                <!-- Overall Progress Summary -->
                <div class="overall-progress-card">
                    <div class="overall-progress-circle">
                        <svg viewBox="0 0 36 36" class="circular-chart">
                            <path class="circle-bg"
                                d="M18 2.0845
                                   a 15.9155 15.9155 0 0 1 0 31.831
                                   a 15.9155 15.9155 0 0 1 0 -31.831"
                            />
                            <path class="circle-progress"
                                stroke-dasharray="{{ $overallProgress['overall_percentage'] }}, 100"
                                d="M18 2.0845
                                   a 15.9155 15.9155 0 0 1 0 31.831
                                   a 15.9155 15.9155 0 0 1 0 -31.831"
                            />
                            <text x="18" y="20.35" class="progress-percentage">{{ $overallProgress['overall_percentage'] }}%</text>
                        </svg>
                    </div>
                    <div class="overall-progress-stats">
                        <div class="progress-stat-item">
                            <span class="stat-value text-success">{{ $overallProgress['completed_activities'] }}</span>
                            <span class="stat-label">Completed</span>
                        </div>
                        <div class="progress-stat-item">
                            <span class="stat-value text-primary">{{ $overallProgress['in_progress_activities'] }}</span>
                            <span class="stat-label">In Progress</span>
                        </div>
                        <div class="progress-stat-item">
                            <span class="stat-value text-warning">{{ $overallProgress['total_activities'] - $overallProgress['completed_activities'] - $overallProgress['in_progress_activities'] }}</span>
                            <span class="stat-label">Pending</span>
                        </div>
                        <div class="progress-stat-item">
                            <span class="stat-value text-danger">{{ $overallProgress['overdue_activities'] }}</span>
                            <span class="stat-label">Overdue</span>
                        </div>
                    </div>
                </div>

                <!-- Individual Project Progress -->
                <div class="project-progress-list">
                    @foreach($activeSchedules as $schedule)
                        @php
                            $progressDetails = $schedule->progress_details;
                            $progressClass = $progressDetails['percentage'] >= 75 ? 'success' : ($progressDetails['percentage'] >= 50 ? 'info' : ($progressDetails['percentage'] >= 25 ? 'warning' : 'danger'));
                        @endphp
                        <a href="{{ route('project-schedules.show', $schedule) }}" class="project-progress-item">
                            <div class="project-progress-header">
                                <div class="project-info">
                                    <span class="project-name">{{ $schedule->lead->lead_number ?? 'N/A' }}</span>
                                    <span class="project-client">{{ \Illuminate\Support\Str::limit($schedule->lead->name ?? 'Unknown', 30) }}</span>
                                </div>
                                <div class="project-percentage {{ $progressClass }}">
                                    {{ $progressDetails['percentage'] }}%
                                </div>
                            </div>
                            <div class="project-progress-bar">
                                <div class="progress-track">
                                    <div class="progress-fill {{ $progressClass }}" style="width: {{ $progressDetails['percentage'] }}%"></div>
                                </div>
                            </div>
                            <div class="project-progress-details">
                                <span class="detail-item"><i class="fa fa-check-circle text-success"></i> {{ $progressDetails['completed'] }}</span>
                                <span class="detail-item"><i class="fa fa-spinner text-primary"></i> {{ $progressDetails['in_progress'] }}</span>
                                <span class="detail-item"><i class="fa fa-clock text-muted"></i> {{ $progressDetails['pending'] }}</span>
                                @if($progressDetails['overdue'] > 0)
                                    <span class="detail-item text-danger"><i class="fa fa-exclamation-triangle"></i> {{ $progressDetails['overdue'] }}</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="followup-footer">
                    <a href="{{ route('project-schedules.index') }}" class="view-all-btn">
                        <i class="fa fa-list"></i> View All Projects
                    </a>
                </div>
            </div>
            @endif

            <!-- Follow-up Calendar -->
            @php
                // Get month/year from request or use current
                $calMonth = request('cal_month', now()->month);
                $calYear = request('cal_year', now()->year);
                $calendarDate = \Carbon\Carbon::createFromDate($calYear, $calMonth, 1);
                $prevMonth = $calendarDate->copy()->subMonth();
                $nextMonth = $calendarDate->copy()->addMonth();
                $isCurrentMonth = $calendarDate->month == now()->month && $calendarDate->year == now()->year;
            @endphp
            <div class="dashboard-section followup-calendar">
                <div class="section-header">
                    <h2><i class="fa fa-calendar-alt mr-2"></i>Follow-up Calendar</h2>
                    <div class="calendar-nav">
                        <a href="?cal_month={{ $prevMonth->month }}&cal_year={{ $prevMonth->year }}" class="cal-nav-btn" title="Previous Month">
                            <i class="fa fa-chevron-left"></i>
                        </a>
                        <span class="calendar-month">{{ $calendarDate->format('F Y') }}</span>
                        <a href="?cal_month={{ $nextMonth->month }}&cal_year={{ $nextMonth->year }}" class="cal-nav-btn" title="Next Month">
                            <i class="fa fa-chevron-right"></i>
                        </a>
                        @if(!$isCurrentMonth)
                            <a href="{{ route('dashboard') }}" class="cal-today-btn" title="Go to Today">Today</a>
                        @endif
                    </div>
                </div>
                <div class="calendar-container">
                    <div class="calendar-header">
                        <div class="calendar-day-name">Sun</div>
                        <div class="calendar-day-name">Mon</div>
                        <div class="calendar-day-name">Tue</div>
                        <div class="calendar-day-name">Wed</div>
                        <div class="calendar-day-name">Thu</div>
                        <div class="calendar-day-name">Fri</div>
                        <div class="calendar-day-name">Sat</div>
                    </div>
                    <div class="calendar-grid">
                        @php
                            $firstDay = $calendarDate->copy()->startOfMonth();
                            $lastDay = $calendarDate->copy()->endOfMonth();
                            $startDayOfWeek = $firstDay->dayOfWeek;
                            $daysInMonth = $lastDay->day;
                            $today = now()->day;
                            $isViewingCurrentMonth = $isCurrentMonth;
                        @endphp

                        {{-- Empty cells for days before the first of the month --}}
                        @for($i = 0; $i < $startDayOfWeek; $i++)
                            <div class="calendar-day empty"></div>
                        @endfor

                        {{-- Days of the month --}}
                        @for($day = 1; $day <= $daysInMonth; $day++)
                            @php
                                $dateKey = $calendarDate->format('Y-m') . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                                $dayFollowups = $calendarFollowups[$dateKey] ?? collect();
                                $dayActivities = isset($calendarActivities) ? ($calendarActivities[$dateKey] ?? collect()) : collect();
                                $isToday = $isViewingCurrentMonth && $day == $today;
                                $currentDate = $calendarDate->copy()->setDay($day);
                                $isPast = $currentDate->isPast() && !$currentDate->isToday();
                                $hasFollowups = $dayFollowups->count() > 0;
                                $hasActivities = $dayActivities->count() > 0;
                                $hasEvents = $hasFollowups || $hasActivities;
                                $pendingCount = $dayFollowups->where('status', 'pending')->count();
                                $completedCount = $dayFollowups->where('status', 'completed')->count();
                            @endphp
                            <div class="calendar-day {{ $isToday ? 'today' : '' }} {{ $isPast ? 'past' : '' }} {{ $hasEvents ? 'has-events' : '' }}">
                                <span class="day-number">{{ $day }}</span>
                                @if($hasEvents)
                                    <div class="event-dots">
                                        {{-- Follow-up dots --}}
                                        @foreach($dayFollowups->take(2) as $fu)
                                            @php
                                                $dotClass = $fu->status === 'completed' ? 'completed' : ($isPast && !$isToday ? 'overdue' : 'pending');
                                            @endphp
                                            <span class="event-dot {{ $dotClass }}"></span>
                                        @endforeach
                                        {{-- Activity dots (blue) --}}
                                        @foreach($dayActivities->take(2) as $act)
                                            @php
                                                $actDotClass = $act->status === 'completed' ? 'completed' : ($act->status === 'in_progress' ? 'activity-progress' : 'activity');
                                            @endphp
                                            <span class="event-dot {{ $actDotClass }}"></span>
                                        @endforeach
                                        @if(($dayFollowups->count() + $dayActivities->count()) > 4)
                                            <span class="more-events">+{{ ($dayFollowups->count() + $dayActivities->count()) - 4 }}</span>
                                        @endif
                                    </div>
                                    {{-- Hover Tooltip --}}
                                    <div class="calendar-tooltip">
                                        <div class="tooltip-header">
                                            <strong>{{ $currentDate->format('d M Y') }}</strong>
                                            <span class="tooltip-badge">{{ $dayFollowups->count() + $dayActivities->count() }} events</span>
                                        </div>
                                        @if($hasFollowups)
                                            <div class="tooltip-section">
                                                <div class="tooltip-section-title"><i class="fa fa-phone-alt"></i> Follow-ups</div>
                                                @foreach($dayFollowups->take(3) as $fu)
                                                    <div class="tooltip-item followup-item {{ $fu->status }}">
                                                        <span class="item-status {{ $fu->status === 'completed' ? 'completed' : ($isPast ? 'overdue' : 'pending') }}"></span>
                                                        <span class="item-text">{{ Str::limit($fu->lead->name, 20) }}</span>
                                                    </div>
                                                @endforeach
                                                @if($dayFollowups->count() > 3)
                                                    <div class="tooltip-more">+{{ $dayFollowups->count() - 3 }} more</div>
                                                @endif
                                            </div>
                                        @endif
                                        @if($hasActivities)
                                            <div class="tooltip-section">
                                                <div class="tooltip-section-title"><i class="fa fa-tasks"></i> Activities</div>
                                                @foreach($dayActivities->take(3) as $act)
                                                    <div class="tooltip-item activity-item {{ $act->status }}">
                                                        <span class="item-status {{ $act->status === 'completed' ? 'completed' : ($act->status === 'in_progress' ? 'in-progress' : 'pending') }}"></span>
                                                        <span class="item-code">{{ $act->activity_code }}</span>
                                                        <span class="item-text">{{ Str::limit($act->name, 18) }}</span>
                                                    </div>
                                                @endforeach
                                                @if($dayActivities->count() > 3)
                                                    <div class="tooltip-more">+{{ $dayActivities->count() - 3 }} more</div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endfor
                    </div>
                </div>
                <div class="calendar-legend">
                    <div class="legend-item">
                        <span class="legend-dot today"></span>
                        <span>Today</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot pending"></span>
                        <span>Follow-up</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot activity"></span>
                        <span>Activity</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot completed"></span>
                        <span>Done</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot overdue"></span>
                        <span>Overdue</span>
                    </div>
                </div>

                {{-- Today's Follow-ups Detail --}}
                @php
                    $todayKey = now()->format('Y-m-d');
                    $todayFollowupsList = $calendarFollowups[$todayKey] ?? collect();
                @endphp
                @if($todayFollowupsList->count() > 0)
                    <div class="today-followups-detail">
                        <h4><i class="fa fa-clock"></i> Today's Follow-ups ({{ $todayFollowupsList->count() }})</h4>
                        <div class="today-list">
                            @foreach($todayFollowupsList as $tfu)
                                <a href="{{ route('leads.show', $tfu->lead->id) }}" class="today-item {{ $tfu->status === 'completed' ? 'completed' : '' }}">
                                    <span class="lead-name">{{ $tfu->lead->name }}</span>
                                    <span class="lead-action">{{ Str::limit($tfu->next_step ?: 'Follow up', 30) }}</span>
                                    @if($tfu->status === 'completed')
                                        <span class="completed-badge"><i class="fa fa-check"></i> Done</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Today's Project Activities Detail --}}
                @php
                    $todayActivitiesList = isset($calendarActivities) ? ($calendarActivities[$todayKey] ?? collect()) : collect();
                @endphp
                @if($todayActivitiesList->count() > 0)
                    <div class="today-followups-detail today-activities">
                        <h4><i class="fa fa-tasks"></i> Today's Activities ({{ $todayActivitiesList->count() }})</h4>
                        <div class="today-list">
                            @foreach($todayActivitiesList as $tact)
                                <a href="{{ route('project-schedules.show', $tact->project_schedule_id) }}" class="today-item activity-item {{ $tact->status === 'completed' ? 'completed' : ($tact->status === 'in_progress' ? 'in-progress' : '') }}">
                                    <span class="lead-name">{{ $tact->activity_code }}: {{ Str::limit($tact->name, 25) }}</span>
                                    <span class="lead-action">{{ $tact->schedule->lead->lead_number ?? '' }}</span>
                                    @if($tact->status === 'completed')
                                        <span class="completed-badge"><i class="fa fa-check"></i> Done</span>
                                    @elseif($tact->status === 'in_progress')
                                        <span class="progress-badge"><i class="fa fa-spinner"></i> In Progress</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Project Status -->
            <div class="dashboard-section projects-status">
                <div class="section-header">
                    <h2>Project Status</h2>
                    <button class="view-all-btn">View All</button>
                </div>
                <div class="project-list">
                    <div class="project-item">
                        <div class="project-info">
                            <h4>Mwanza Shopping Complex</h4>
                            <p>Structural work in progress</p>
                        </div>
                        <div class="project-status on-track">
                            <span class="status-indicator"></span>
                            <span>On Track</span>
                        </div>
                        <div class="project-progress">
                            <div class="progress-circle" data-progress="75">
                                <span>75%</span>
                            </div>
                        </div>
                    </div>

                    <div class="project-item">
                        <div class="project-info">
                            <h4>Dodoma Office Building</h4>
                            <p>Interior finishing phase</p>
                        </div>
                        <div class="project-status warning">
                            <span class="status-indicator"></span>
                            <span>At Risk</span>
                        </div>
                        <div class="project-progress">
                            <div class="progress-circle" data-progress="45">
                                <span>45%</span>
                            </div>
                        </div>
                    </div>

                    <div class="project-item">
                        <div class="project-info">
                            <h4>Dar es Salaam Bridge</h4>
                            <p>Foundation work complete</p>
                        </div>
                        <div class="project-status completed">
                            <span class="status-indicator"></span>
                            <span>Completed</span>
                        </div>
                        <div class="project-progress">
                            <div class="progress-circle" data-progress="100">
                                <span>100%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="dashboard-section activities">
                <div class="section-header">
                    <h2>Recent Activities</h2>
                    <button class="view-all-btn">View All</button>
                </div>
                <div class="activity-timeline">
                    <div class="activity-item">
                        <div class="activity-icon new">
                            <i class="fa fa-plus"></i>
                        </div>
                        <div class="activity-content">
                            <p><strong>New project</strong> "Arusha Hospital" added to portfolio</p>
                            <span class="activity-time">2 hours ago</span>
                        </div>
                    </div>

                    <div class="activity-item">
                        <div class="activity-icon update">
                            <i class="fa fa-edit"></i>
                        </div>
                        <div class="activity-content">
                            <p><strong>Budget updated</strong> for Mwanza Shopping Complex</p>
                            <span class="activity-time">4 hours ago</span>
                        </div>
                    </div>

                    <div class="activity-item">
                        <div class="activity-icon completion">
                            <i class="fa fa-check"></i>
                        </div>
                        <div class="activity-content">
                            <p><strong>Milestone achieved</strong> Foundation completed for Dodoma Office</p>
                            <span class="activity-time">1 day ago</span>
                        </div>
                    </div>

                    <div class="activity-item">
                        <div class="activity-icon alert">
                            <i class="fa fa-exclamation"></i>
                        </div>
                        <div class="activity-content">
                            <p><strong>Material shortage</strong> reported at Dar es Salaam site</p>
                            <span class="activity-time">2 days ago</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="dashboard-section charts">
                <div class="section-header">
                    <h2>Financial Analytics</h2>
                    <div class="chart-filters">
                        <button class="filter-btn active">Week</button>
                        <button class="filter-btn">Month</button>
                        <button class="filter-btn">Year</button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas class="js-chartjs-dashboard-lines" height="300"></canvas>
                </div>
                <div class="chart-legend">
                    <div class="legend-item">
                        <span class="legend-color sales"></span>
                        <span>Sales Revenue</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color expenses"></span>
                        <span>Project Expenses</span>
                    </div>
                </div>
            </div>

            <!-- BOQ Analytics -->
            <div class="dashboard-section charts boq-analytics">
                <div class="section-header">
                    <h2>BOQ Analytics</h2>
                    <div class="chart-filters">
                        <button class="filter-btn active">Projects</button>
                        <button class="filter-btn">Materials</button>
                        <button class="filter-btn">Labor</button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas class="js-chartjs-boq-chart" height="300"></canvas>
                </div>
                <div class="chart-legend">
                    <div class="legend-item">
                        <span class="legend-color boq-budget"></span>
                        <span>Budgeted Amount</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color boq-actual"></span>
                        <span>Actual Spending</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color boq-variance"></span>
                        <span>Variance</span>
                    </div>
                </div>
                <div class="chart-stats">
                    <div class="stat-item">
                        <span class="stat-label">Total BOQ Value</span>
                        <span class="stat-value">TZS 15.2M</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Completed Items</span>
                        <span class="stat-value">342</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Pending Items</span>
                        <span class="stat-value">89</span>
                    </div>
                </div>
            </div>

            <!-- Statutory Analytics -->
            <div class="dashboard-section charts statutory-analytics">
                <div class="section-header">
                    <h2>Statutory & Compliance</h2>
                    <div class="chart-filters">
                        <button class="filter-btn active">VAT</button>
                        <button class="filter-btn">PAYE</button>
                        <button class="filter-btn">NSSF</button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas class="js-chartjs-statutory-chart" height="300"></canvas>
                </div>
                <div class="chart-legend">
                    <div class="legend-item">
                        <span class="legend-color statutory-collected"></span>
                        <span>Tax Collected</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color statutory-paid"></span>
                        <span>Tax Paid</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color statutory-pending"></span>
                        <span>Pending Payment</span>
                    </div>
                </div>
                <div class="chart-stats">
                    <div class="stat-item">
                        <span class="stat-label">Monthly VAT</span>
                        <span class="stat-value">TZS {{ number_format($this_month_tax_payable ?? 0, 2) }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Compliance Rate</span>
                        <span class="stat-value">98.5%</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Next Due</span>
                        <span class="stat-value">{{ date('M d', strtotime('+5 days')) }}</span>
                    </div>
                </div>
            </div>

            <!-- Team Performance -->
            <div class="dashboard-section team-performance">
                <div class="section-header">
                    <h2>Department Overview</h2>
                    <span class="section-count">{{ count($departmentCounts) }} depts</span>
                </div>
                <div class="department-grid">
                    @foreach($departmentCounts as $dept)
                        <div class="department-card">
                            <div class="dept-icon">
                                <i class="fa fa-building"></i>
                            </div>
                            <div class="dept-info">
                                <h4>{{ $dept->name ?? 'Department' }}</h4>
                                <p>{{ $dept->total_members }} members</p>
                            </div>
                            <div class="dept-status active">
                                <span class="status-dot"></span>
                                <span>Active</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Quick Actions Bar -->
        <div class="quick-actions">
            <h3>Quick Actions</h3>
            <div class="action-grid">
                <button class="quick-action-btn">
                    <i class="fa fa-plus"></i>
                    <span>Add Project</span>
                </button>
                <button class="quick-action-btn">
                    <i class="fa fa-users"></i>
                    <span>Manage Team</span>
                </button>
                <button class="quick-action-btn">
                    <i class="fa fa-file-invoice"></i>
                    <span>Create Invoice</span>
                </button>
                <button class="quick-action-btn">
                    <i class="fa fa-calendar"></i>
                    <span>Schedule Visit</span>
                </button>
                <button class="quick-action-btn">
                    <i class="fa fa-chart-bar"></i>
                    <span>View Reports</span>
                </button>
                <button class="quick-action-btn">
                    <i class="fa fa-cog"></i>
                    <span>Settings</span>
                </button>
            </div>
        </div>
    </div>

    <style>
        :root {
            --wajenzi-blue-primary: #2563EB;
            --wajenzi-blue-dark: #1D4ED8;
            --wajenzi-green: #22C55E;
            --wajenzi-green-dark: #16A34A;
            --wajenzi-gray-50: #F8FAFC;
            --wajenzi-gray-100: #F1F5F9;
            --wajenzi-gray-200: #E2E8F0;
            --wajenzi-gray-300: #CBD5E1;
            --wajenzi-gray-600: #475569;
            --wajenzi-gray-700: #334155;
            --wajenzi-gray-800: #1E293B;
            --wajenzi-gray-900: #0F172A;
            --wajenzi-orange: #F97316;
            --wajenzi-purple: #7C3AED;
            --wajenzi-indigo: #4F46E5;
            --wajenzi-red: #EF4444;
            --wajenzi-teal: #14B8A6;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* Main Dashboard Container */
        .wajenzi-dashboard {
            padding: 2rem;
            background: var(--wajenzi-gray-50);
            min-height: calc(100vh - 140px);
        }

        /* Welcome Header */
        .dashboard-welcome {
            background: linear-gradient(135deg, var(--wajenzi-blue-primary) 0%, var(--wajenzi-green) 100%);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .dashboard-welcome::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="40" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="80" r="1" fill="rgba(255,255,255,0.1)"/></svg>');
            animation: float 20s linear infinite;
        }

        @keyframes float {
            0% { transform: translateY(0px) translateX(0px); }
            33% { transform: translateY(-10px) translateX(10px); }
            66% { transform: translateY(5px) translateX(-5px); }
            100% { transform: translateY(0px) translateX(0px); }
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .welcome-title {
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
            line-height: 1.2;
        }

        .welcome-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
        }

        .welcome-actions {
            display: flex;
            gap: 1rem;
            position: relative;
            z-index: 1;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .action-btn.primary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            backdrop-filter: blur(10px);
        }

        .action-btn.secondary {
            background: white;
            color: var(--wajenzi-blue-primary);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Metrics Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--wajenzi-gray-200);
            transition: all 0.3s ease;
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .metric-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .metric-card.financial .metric-icon { background: linear-gradient(135deg, var(--wajenzi-blue-primary) 0%, var(--wajenzi-blue-dark) 100%); }
        .metric-card.projects .metric-icon { background: linear-gradient(135deg, var(--wajenzi-green) 0%, var(--wajenzi-green-dark) 100%); }
        .metric-card.team .metric-icon { background: linear-gradient(135deg, var(--wajenzi-purple) 0%, var(--wajenzi-indigo) 100%); }
        .metric-card.budget .metric-icon { background: linear-gradient(135deg, var(--wajenzi-orange) 0%, var(--wajenzi-red) 100%); }

        .metric-trend {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .metric-trend.up {
            background: rgba(34, 197, 94, 0.1);
            color: var(--wajenzi-green);
        }

        .metric-badge {
            background: var(--wajenzi-gray-100);
            color: var(--wajenzi-gray-700);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .metric-progress {
            background: var(--wajenzi-gray-100);
            color: var(--wajenzi-gray-700);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .metric-content h3 {
            font-size: 1rem;
            color: var(--wajenzi-gray-600);
            margin: 0 0 0.5rem 0;
            font-weight: 500;
        }

        .metric-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--wajenzi-gray-900);
            margin-bottom: 0.5rem;
        }

        .metric-period {
            font-size: 0.875rem;
            color: var(--wajenzi-gray-600);
            margin: 0;
        }

        .metric-breakdown {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .breakdown-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.875rem;
            color: var(--wajenzi-gray-600);
        }

        .progress-bar {
            background: var(--wajenzi-gray-200);
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin: 0.5rem 0;
        }

        .progress-fill {
            background: linear-gradient(90deg, var(--wajenzi-orange) 0%, var(--wajenzi-red) 100%);
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        /* Team Performance section spans remaining columns */
        .dashboard-section.team-performance {
            grid-column: 1 / -1; /* Span from first to last column */
        }

        .dashboard-section {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--wajenzi-gray-200);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--wajenzi-gray-200);
        }

        .section-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--wajenzi-gray-900);
            margin: 0;
        }

        .section-count {
            background: var(--wajenzi-blue-primary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .view-all-btn {
            background: transparent;
            border: 1px solid var(--wajenzi-gray-300);
            color: var(--wajenzi-gray-600);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .view-all-btn:hover {
            background: var(--wajenzi-blue-primary);
            color: white;
            border-color: var(--wajenzi-blue-primary);
        }

        /* Approval List */
        .approval-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .approval-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid var(--wajenzi-gray-200);
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .approval-item:hover {
            background: var(--wajenzi-gray-50);
            border-color: var(--wajenzi-blue-primary);
        }

        .approval-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }

        .approval-icon.blue { background: var(--wajenzi-blue-primary); }
        .approval-icon.green { background: var(--wajenzi-green); }
        .approval-icon.orange { background: var(--wajenzi-orange); }
        .approval-icon.purple { background: var(--wajenzi-purple); }
        .approval-icon.indigo { background: var(--wajenzi-indigo); }
        .approval-icon.red { background: var(--wajenzi-red); }
        .approval-icon.teal { background: var(--wajenzi-teal); }

        .approval-content {
            flex: 1;
        }

        .approval-name {
            font-weight: 600;
            color: var(--wajenzi-gray-900);
            display: block;
            margin-bottom: 0.25rem;
        }

        .approval-desc {
            font-size: 0.875rem;
            color: var(--wajenzi-gray-600);
        }

        .approval-badge {
            background: var(--wajenzi-red);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            min-width: 24px;
            text-align: center;
        }

        /* Project List */
        .project-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .project-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid var(--wajenzi-gray-200);
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .project-item:hover {
            background: var(--wajenzi-gray-50);
        }

        .project-info {
            flex: 1;
        }

        .project-info h4 {
            margin: 0 0 0.25rem 0;
            font-weight: 600;
            color: var(--wajenzi-gray-900);
        }

        .project-info p {
            margin: 0;
            font-size: 0.875rem;
            color: var(--wajenzi-gray-600);
        }

        .project-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .project-status.on-track {
            background: rgba(34, 197, 94, 0.1);
            color: var(--wajenzi-green);
        }

        .project-status.warning {
            background: rgba(249, 115, 22, 0.1);
            color: var(--wajenzi-orange);
        }

        .project-status.completed {
            background: rgba(37, 99, 235, 0.1);
            color: var(--wajenzi-blue-primary);
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }

        .progress-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: conic-gradient(var(--wajenzi-blue-primary) 0deg, var(--wajenzi-gray-200) 0deg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--wajenzi-gray-700);
        }

        /* Activity Timeline */
        .activity-timeline {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .activity-item {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--wajenzi-gray-200);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            color: white;
            flex-shrink: 0;
        }

        .activity-icon.new { background: var(--wajenzi-green); }
        .activity-icon.update { background: var(--wajenzi-blue-primary); }
        .activity-icon.completion { background: var(--wajenzi-purple); }
        .activity-icon.alert { background: var(--wajenzi-red); }

        .activity-content p {
            margin: 0 0 0.25rem 0;
            color: var(--wajenzi-gray-900);
        }

        .activity-time {
            font-size: 0.875rem;
            color: var(--wajenzi-gray-600);
        }

        /* Charts Section */
        .chart-filters {
            display: flex;
            gap: 0.5rem;
        }

        .filter-btn {
            background: transparent;
            border: 1px solid var(--wajenzi-gray-300);
            color: var(--wajenzi-gray-600);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-btn.active,
        .filter-btn:hover {
            background: var(--wajenzi-blue-primary);
            color: white;
            border-color: var(--wajenzi-blue-primary);
        }

        .chart-container {
            margin: 1rem 0;
            height: 300px;
        }

        .chart-legend {
            display: flex;
            gap: 2rem;
            justify-content: center;
            margin-top: 1rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--wajenzi-gray-600);
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
        }

        .legend-color.sales { background: var(--wajenzi-blue-primary); }
        .legend-color.expenses { background: var(--wajenzi-green); }
        
        /* BOQ Analytics Legend Colors */
        .legend-color.boq-budget { background: var(--wajenzi-blue-primary); }
        .legend-color.boq-actual { background: var(--wajenzi-green); }
        .legend-color.boq-variance { background: var(--wajenzi-orange); }
        
        /* Statutory Analytics Legend Colors */
        .legend-color.statutory-collected { background: var(--wajenzi-blue-primary); }
        .legend-color.statutory-paid { background: var(--wajenzi-green); }
        .legend-color.statutory-pending { background: var(--wajenzi-red); }

        /* Chart Statistics Section */
        .chart-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--wajenzi-gray-200);
            gap: 1rem;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            flex: 1;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--wajenzi-gray-600);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--wajenzi-gray-900);
        }

        /* Responsive chart stats */
        @media (max-width: 768px) {
            .chart-stats {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .stat-item {
                flex-direction: row;
                justify-content: space-between;
                text-align: left;
            }
            
            .stat-label {
                margin-bottom: 0;
            }
        }

        /* Department Grid */
        .department-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
        }

        .department-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid var(--wajenzi-gray-200);
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .department-card:hover {
            background: var(--wajenzi-gray-50);
        }

        .dept-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--wajenzi-blue-primary) 0%, var(--wajenzi-green) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dept-info h4 {
            margin: 0 0 0.25rem 0;
            font-weight: 600;
            color: var(--wajenzi-gray-900);
            font-size: 1rem;
        }

        .dept-info p {
            margin: 0;
            font-size: 0.875rem;
            color: var(--wajenzi-gray-600);
        }

        .dept-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-left: auto;
            font-size: 0.875rem;
            color: var(--wajenzi-green);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--wajenzi-green);
        }

        /* Follow-up To-Do List Styles */
        .followups-todo .followup-badges {
            display: flex;
            gap: 0.5rem;
        }

        .followups-todo .badge-today {
            background: var(--wajenzi-orange);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .followups-todo .badge-overdue {
            background: var(--wajenzi-red);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .followups-todo .badge-upcoming {
            background: #17a2b8;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .followups-todo .badge-completed {
            background: #28a745;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .followup-stats-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .followup-stat-card {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            border: 1px solid var(--wajenzi-gray-200);
            transition: all 0.2s ease;
            flex: 1;
            min-width: 100px;
        }

        .followup-stat-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .followup-stat-card .stat-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
        }

        .followup-stat-card .stat-info {
            display: flex;
            flex-direction: column;
        }

        .followup-stat-card .stat-number {
            font-size: 1.1rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .followup-stat-card .stat-label {
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            opacity: 0.8;
        }

        .followup-stat-card.overdue {
            background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
            border-color: #fc8181;
        }
        .followup-stat-card.overdue .stat-icon {
            background: #e53e3e;
            color: white;
        }
        .followup-stat-card.overdue .stat-number {
            color: #c53030;
        }
        .followup-stat-card.overdue .stat-label {
            color: #9b2c2c;
        }

        .followup-stat-card.today {
            background: linear-gradient(135deg, #fffaf0 0%, #feebc8 100%);
            border-color: #f6ad55;
        }
        .followup-stat-card.today .stat-icon {
            background: #ed8936;
            color: white;
        }
        .followup-stat-card.today .stat-number {
            color: #c05621;
        }
        .followup-stat-card.today .stat-label {
            color: #9c4221;
        }

        .followup-stat-card.upcoming {
            background: linear-gradient(135deg, #ebf8ff 0%, #bee3f8 100%);
            border-color: #63b3ed;
        }
        .followup-stat-card.upcoming .stat-icon {
            background: #3182ce;
            color: white;
        }
        .followup-stat-card.upcoming .stat-number {
            color: #2b6cb0;
        }
        .followup-stat-card.upcoming .stat-label {
            color: #2c5282;
        }

        .followup-stat-card.completed {
            background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
            border-color: #68d391;
        }
        .followup-stat-card.completed .stat-icon {
            background: #38a169;
            color: white;
        }
        .followup-stat-card.completed .stat-number {
            color: #276749;
        }
        .followup-stat-card.completed .stat-label {
            color: #22543d;
        }

        .followup-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            max-height: 400px;
            overflow-y: auto;
        }

        .followup-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid var(--wajenzi-gray-200);
            border-radius: 12px;
            transition: all 0.2s ease;
            text-decoration: none;
            color: inherit;
        }

        .followup-item:hover {
            background: var(--wajenzi-gray-50);
            border-color: var(--wajenzi-blue-primary);
            transform: translateX(4px);
        }

        .followup-item.overdue {
            border-left: 4px solid var(--wajenzi-red);
            background: rgba(239, 68, 68, 0.05);
        }

        .followup-item.today {
            border-left: 4px solid var(--wajenzi-orange);
            background: rgba(249, 115, 22, 0.05);
        }

        .followup-item.tomorrow {
            border-left: 4px solid var(--wajenzi-blue-primary);
            background: rgba(37, 99, 235, 0.05);
        }

        .followup-item.completed {
            border-left: 4px solid #28a745;
            background: rgba(40, 167, 69, 0.08);
            opacity: 0.7;
        }

        .followup-item.completed .followup-content {
            text-decoration: line-through;
            color: #6c757d;
        }

        .followup-item.completed .followup-date-badge {
            background: #28a745;
            color: white;
        }

        .followup-item.cancelled {
            border-left: 4px solid #6c757d;
            background: rgba(108, 117, 125, 0.08);
            opacity: 0.6;
        }

        .followup-item.cancelled .followup-content {
            text-decoration: line-through;
            color: #999;
        }

        .followup-date-badge {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 50px;
            height: 50px;
            background: var(--wajenzi-gray-100);
            border-radius: 10px;
            padding: 0.5rem;
        }

        .followup-item.overdue .followup-date-badge {
            background: var(--wajenzi-red);
            color: white;
        }

        .followup-item.today .followup-date-badge {
            background: var(--wajenzi-orange);
            color: white;
        }

        .followup-date-badge .day {
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1;
        }

        .followup-date-badge .month {
            font-size: 0.65rem;
            text-transform: uppercase;
            font-weight: 600;
        }

        .followup-content {
            flex: 1;
            min-width: 0;
        }

        .followup-lead-name {
            display: block;
            font-weight: 600;
            color: var(--wajenzi-gray-900);
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .followup-details {
            display: block;
            font-size: 0.875rem;
            color: var(--wajenzi-gray-600);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .followup-assignee {
            display: block;
            font-size: 0.75rem;
            color: var(--wajenzi-gray-500);
            margin-top: 0.25rem;
        }

        .followup-assignee i {
            margin-right: 0.25rem;
        }

        .followup-status .status-label {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .followup-status .status-label.overdue {
            background: rgba(239, 68, 68, 0.1);
            color: var(--wajenzi-red);
        }

        .followup-status .status-label.today {
            background: rgba(249, 115, 22, 0.1);
            color: var(--wajenzi-orange);
        }

        .followup-status .status-label.tomorrow {
            background: rgba(37, 99, 235, 0.1);
            color: var(--wajenzi-blue-primary);
        }

        .followup-status .status-label.upcoming {
            background: var(--wajenzi-gray-100);
            color: var(--wajenzi-gray-600);
        }

        .followup-status .status-label.completed {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }

        .followup-status .status-label.cancelled {
            background: rgba(108, 117, 125, 0.15);
            color: #6c757d;
        }

        .no-followups {
            text-align: center;
            padding: 2rem;
            color: var(--wajenzi-gray-500);
        }

        .no-followups i {
            font-size: 3rem;
            color: var(--wajenzi-green);
            margin-bottom: 1rem;
        }

        .no-followups p {
            margin: 0;
        }

        .followup-footer {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--wajenzi-gray-200);
            text-align: center;
        }

        .followup-footer .view-all-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Project Progress Section Styles */
        .project-progress-section {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
        }

        .overall-progress-card {
            display: flex;
            align-items: center;
            gap: 2rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8f9fe 0%, #e8f4f8 100%);
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }

        .overall-progress-circle {
            flex-shrink: 0;
            width: 100px;
            height: 100px;
        }

        .circular-chart {
            display: block;
            margin: 0 auto;
            max-width: 100%;
            max-height: 100%;
        }

        .circle-bg {
            fill: none;
            stroke: #e6e6e6;
            stroke-width: 2.5;
        }

        .circle-progress {
            fill: none;
            stroke: var(--wajenzi-blue-primary, #007bff);
            stroke-width: 2.5;
            stroke-linecap: round;
            animation: progress-animation 1s ease-out forwards;
        }

        @keyframes progress-animation {
            0% { stroke-dasharray: 0 100; }
        }

        .progress-percentage {
            fill: var(--wajenzi-gray-800, #2d3748);
            font-size: 0.5em;
            font-weight: 700;
            text-anchor: middle;
        }

        .overall-progress-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            flex: 1;
        }

        .progress-stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 70px;
            padding: 0.5rem 0.75rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .progress-stat-item .stat-value {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .progress-stat-item .stat-label {
            font-size: 0.7rem;
            color: var(--wajenzi-gray-500, #718096);
            margin-top: 0.25rem;
        }

        .project-progress-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .project-progress-item {
            display: block;
            padding: 1rem;
            background: var(--wajenzi-gray-50, #f7fafc);
            border-radius: 10px;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .project-progress-item:hover {
            background: white;
            border-color: var(--wajenzi-blue-light, #ebf5ff);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }

        .project-progress-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }

        .project-info {
            display: flex;
            flex-direction: column;
        }

        .project-name {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--wajenzi-blue-primary, #007bff);
        }

        .project-client {
            font-size: 0.85rem;
            color: var(--wajenzi-gray-700, #4a5568);
            font-weight: 500;
        }

        .project-percentage {
            font-size: 1rem;
            font-weight: 700;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
        }

        .project-percentage.success {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .project-percentage.info {
            background: rgba(0, 123, 255, 0.1);
            color: #007bff;
        }

        .project-percentage.warning {
            background: rgba(255, 193, 7, 0.15);
            color: #d39e00;
        }

        .project-percentage.danger {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .project-progress-bar {
            margin-bottom: 0.5rem;
        }

        .project-progress-bar .progress-track {
            height: 8px;
            background: var(--wajenzi-gray-200, #e2e8f0);
            border-radius: 4px;
            overflow: hidden;
        }

        .project-progress-bar .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .project-progress-bar .progress-fill.success {
            background: linear-gradient(90deg, #28a745, #5cb85c);
        }

        .project-progress-bar .progress-fill.info {
            background: linear-gradient(90deg, #007bff, #17a2b8);
        }

        .project-progress-bar .progress-fill.warning {
            background: linear-gradient(90deg, #ffc107, #ffca2c);
        }

        .project-progress-bar .progress-fill.danger {
            background: linear-gradient(90deg, #dc3545, #e4606d);
        }

        .project-progress-details {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .project-progress-details .detail-item {
            font-size: 0.75rem;
            color: var(--wajenzi-gray-600, #718096);
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .project-progress-details .detail-item i {
            font-size: 0.7rem;
        }

        /* Follow-up Calendar Styles */
        .followup-calendar .calendar-nav {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .followup-calendar .cal-nav-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: var(--wajenzi-gray-100);
            color: var(--wajenzi-gray-600);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .followup-calendar .cal-nav-btn:hover {
            background: var(--wajenzi-blue-primary);
            color: white;
        }

        .followup-calendar .cal-today-btn {
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            background: var(--wajenzi-orange);
            color: white;
            text-decoration: none;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 0.5rem;
            transition: all 0.2s ease;
        }

        .followup-calendar .cal-today-btn:hover {
            background: #e67e22;
        }

        .followup-calendar .calendar-month {
            background: var(--wajenzi-blue-primary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            min-width: 120px;
            text-align: center;
        }

        .calendar-container {
            margin-bottom: 1rem;
        }

        .calendar-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
            margin-bottom: 0.5rem;
        }

        .calendar-day-name {
            text-align: center;
            font-weight: 600;
            font-size: 0.75rem;
            color: var(--wajenzi-gray-600);
            padding: 0.5rem;
            text-transform: uppercase;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.25rem;
            border-radius: 8px;
            background: var(--wajenzi-gray-50);
            cursor: default;
            position: relative;
            min-height: 45px;
        }

        .calendar-day.empty {
            background: transparent;
        }

        .calendar-day .day-number {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--wajenzi-gray-700);
        }

        .calendar-day.past .day-number {
            color: var(--wajenzi-gray-400);
        }

        .calendar-day.today {
            background: var(--wajenzi-blue-primary);
        }

        .calendar-day.today .day-number {
            color: white;
            font-weight: 700;
        }

        .calendar-day.has-events {
            cursor: pointer;
            border: 2px solid var(--wajenzi-green);
        }

        .calendar-day.has-events:hover {
            background: rgba(34, 197, 94, 0.1);
            transform: scale(1.05);
            z-index: 10;
        }

        .calendar-day.has-events:hover .calendar-tooltip {
            display: block;
            animation: tooltipFadeIn 0.2s ease;
        }

        @keyframes tooltipFadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .calendar-tooltip {
            display: none;
            position: absolute;
            bottom: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
            min-width: 220px;
            max-width: 280px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15), 0 2px 10px rgba(0,0,0,0.1);
            z-index: 100;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.08);
        }

        .calendar-tooltip::before {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            border-width: 8px 8px 0;
            border-style: solid;
            border-color: white transparent transparent;
        }

        .calendar-tooltip::after {
            content: '';
            position: absolute;
            bottom: -9px;
            left: 50%;
            transform: translateX(-50%);
            border-width: 9px 9px 0;
            border-style: solid;
            border-color: rgba(0,0,0,0.08) transparent transparent;
            z-index: -1;
        }

        .tooltip-header {
            background: linear-gradient(135deg, var(--wajenzi-blue-primary), #1e40af);
            color: white;
            padding: 10px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .tooltip-header strong {
            font-size: 0.85rem;
        }

        .tooltip-badge {
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
        }

        .tooltip-section {
            padding: 8px 12px;
            border-bottom: 1px solid #f0f0f0;
        }

        .tooltip-section:last-child {
            border-bottom: none;
        }

        .tooltip-section-title {
            font-size: 0.7rem;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .tooltip-section-title i {
            font-size: 0.65rem;
        }

        .tooltip-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 4px 0;
            font-size: 0.75rem;
            color: #333;
        }

        .tooltip-item .item-status {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .tooltip-item .item-status.pending {
            background: var(--wajenzi-green);
        }

        .tooltip-item .item-status.completed {
            background: #28a745;
        }

        .tooltip-item .item-status.overdue {
            background: var(--wajenzi-red);
        }

        .tooltip-item .item-status.in-progress {
            background: #9b59b6;
        }

        .tooltip-item .item-code {
            font-weight: 600;
            color: var(--wajenzi-blue-primary);
            font-size: 0.7rem;
        }

        .tooltip-item .item-text {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .tooltip-item.completed .item-text {
            text-decoration: line-through;
            color: #888;
        }

        .tooltip-more {
            font-size: 0.7rem;
            color: #888;
            font-style: italic;
            padding-top: 4px;
        }

        /* Tooltip position adjustments for edge days */
        .calendar-day:nth-child(7n+1) .calendar-tooltip,
        .calendar-day:nth-child(7n+2) .calendar-tooltip {
            left: 0;
            transform: translateX(0);
        }

        .calendar-day:nth-child(7n+1) .calendar-tooltip::before,
        .calendar-day:nth-child(7n+1) .calendar-tooltip::after,
        .calendar-day:nth-child(7n+2) .calendar-tooltip::before,
        .calendar-day:nth-child(7n+2) .calendar-tooltip::after {
            left: 20px;
            transform: none;
        }

        .calendar-day:nth-child(7n) .calendar-tooltip,
        .calendar-day:nth-child(7n-1) .calendar-tooltip {
            left: auto;
            right: 0;
            transform: translateX(0);
        }

        .calendar-day:nth-child(7n) .calendar-tooltip::before,
        .calendar-day:nth-child(7n) .calendar-tooltip::after,
        .calendar-day:nth-child(7n-1) .calendar-tooltip::before,
        .calendar-day:nth-child(7n-1) .calendar-tooltip::after {
            left: auto;
            right: 20px;
            transform: none;
        }

        .calendar-day.today.has-events {
            border-color: white;
            box-shadow: 0 0 0 2px var(--wajenzi-green);
        }

        .calendar-day.past.has-events {
            border-color: var(--wajenzi-red);
        }

        .event-dots {
            display: flex;
            gap: 2px;
            margin-top: 2px;
        }

        .event-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--wajenzi-green);
        }

        .calendar-day.today .event-dot {
            background: white;
        }

        .event-dot.overdue {
            background: var(--wajenzi-red);
        }

        .event-dot.completed {
            background: #28a745;
        }

        .event-dot.pending {
            background: var(--wajenzi-green);
        }

        .event-dot.activity {
            background: #3498db;
        }

        .event-dot.activity-progress {
            background: #9b59b6;
        }

        .more-events {
            font-size: 0.6rem;
            color: var(--wajenzi-gray-600);
            font-weight: 600;
        }

        .calendar-day.today .more-events {
            color: rgba(255,255,255,0.8);
        }

        .calendar-legend {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            padding: 0.75rem;
            background: var(--wajenzi-gray-50);
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .calendar-legend .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: var(--wajenzi-gray-600);
        }

        .calendar-legend .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 4px;
        }

        .calendar-legend .legend-dot.today {
            background: var(--wajenzi-blue-primary);
        }

        .calendar-legend .legend-dot.has-event,
        .calendar-legend .legend-dot.pending {
            background: var(--wajenzi-green);
        }

        .calendar-legend .legend-dot.completed {
            background: #28a745;
        }

        .calendar-legend .legend-dot.overdue {
            background: var(--wajenzi-red);
        }

        .calendar-legend .legend-dot.activity {
            background: #3498db;
        }

        .today-followups-detail {
            background: rgba(37, 99, 235, 0.05);
            border: 1px solid rgba(37, 99, 235, 0.2);
            border-radius: 12px;
            padding: 1rem;
        }

        .today-followups-detail h4 {
            margin: 0 0 0.75rem 0;
            font-size: 0.9rem;
            color: var(--wajenzi-blue-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .today-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .today-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0.75rem;
            background: white;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .today-item:hover {
            background: var(--wajenzi-blue-primary);
            color: white;
        }

        .today-item .lead-name {
            font-weight: 600;
            color: var(--wajenzi-gray-900);
        }

        .today-item:hover .lead-name {
            color: white;
        }

        .today-item .lead-action {
            font-size: 0.75rem;
            color: var(--wajenzi-gray-500);
        }

        .today-item:hover .lead-action {
            color: rgba(255,255,255,0.8);
        }

        .today-item.completed {
            background: rgba(40, 167, 69, 0.1);
            text-decoration: line-through;
            opacity: 0.7;
        }

        .today-item.completed .lead-name,
        .today-item.completed .lead-action {
            color: #6c757d;
        }

        .today-item .completed-badge {
            font-size: 0.65rem;
            background: #28a745;
            color: white;
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            margin-left: auto;
        }

        .today-item .progress-badge {
            font-size: 0.65rem;
            background: #9b59b6;
            color: white;
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            margin-left: auto;
        }

        .today-item.in-progress {
            background: rgba(155, 89, 182, 0.1);
            border-left: 3px solid #9b59b6;
        }

        .today-activities {
            background: rgba(52, 152, 219, 0.05);
            border: 1px solid rgba(52, 152, 219, 0.2);
            margin-top: 1rem;
        }

        .today-activities h4 {
            color: #3498db;
        }

        .today-activities .today-item.activity-item {
            border-left: 3px solid #3498db;
        }

        .today-activities .today-item.activity-item:hover {
            background: #3498db;
        }

        /* Quick Actions */
        .quick-actions {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--wajenzi-gray-200);
        }

        .quick-actions h3 {
            margin: 0 0 1rem 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--wajenzi-gray-900);
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .quick-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            background: var(--wajenzi-gray-50);
            border: 1px solid var(--wajenzi-gray-200);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            color: var(--wajenzi-gray-700);
        }

        .quick-action-btn:hover {
            background: var(--wajenzi-blue-primary);
            color: white;
            border-color: var(--wajenzi-blue-primary);
            transform: translateY(-2px);
        }

        .quick-action-btn i {
            font-size: 1.5rem;
        }

        .quick-action-btn span {
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .wajenzi-dashboard {
                padding: 1.5rem;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .metrics-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }

            .welcome-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 768px) {
            .wajenzi-dashboard {
                padding: 1rem;
            }

            .dashboard-welcome {
                flex-direction: column;
                text-align: center;
                gap: 1.5rem;
            }

            .welcome-title {
                font-size: 1.5rem;
            }

            .metrics-grid {
                grid-template-columns: 1fr;
            }

            .action-grid {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }

            .department-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .dashboard-welcome {
                padding: 1.5rem;
            }

            .metric-card {
                padding: 1rem;
            }

            .dashboard-section {
                padding: 1rem;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .department-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

@endsection

<?php
use App\Models\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

$monday = strtotime("last monday");
$monday = date('w', $monday) == date('w') ? $monday + 7 * 86400 : $monday;
$sunday = strtotime(date("Y-m-d", $monday) . " +6 days");
$this_week_sd = date("Y-m-d", $monday);
$this_week_ed = date("Y-m-d", $sunday);

$first_date = explode("-", $this_week_sd);
$last_date = explode("-", $this_week_ed);

for ($i = $first_date[2]; $i <= $last_date[2]; $i++) {
    $dates[] = date('Y') . "-" . date('m') . "-" . str_pad($i, 2, '0', STR_PAD_LEFT);
}

if (isset($dates)) {
    foreach ($dates as $index => $date) {
        $collections_per_week[] = \App\Models\Sale::Where('date', $date)->select([DB::raw("SUM(tax) as total_amount")])->groupBy('date')->get()->first()['total_amount'] ?? 0;
        $expenses_per_week[] = \App\Models\Purchase::Where('date', $date)->select([DB::raw("SUM(vat_amount) as total_amount")])->groupBy('date')->get()->first()['total_amount'] ?? 0;
    }
}

if (!empty($collections_per_week)) {
    $collection_in_a_day_per_week = implode(", ", $collections_per_week);
}
if (!empty($expenses_per_week)) {
    $expense_in_a_day_per_week = implode(", ", $expenses_per_week);
}
?>

@section('js_after')
    <script src="{{ asset('js/plugins/chartjs/Chart.bundle.min.js')}}"></script>
    <script>
        var salesChart = new Chart($('.js-chartjs-dashboard-lines'), {
            type: "line",
            data: {
                labels: ["MON", "TUE", "WED", "THU", "FRI", "SAT", "SUN"],
                datasets: [{
                    label: "This Week",
                    fill: true,
                    backgroundColor: "rgba(37,99,235,.15)",
                    borderColor: "rgba(37,99,235,1)",
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: "rgba(37,99,235,1)",
                    pointBorderColor: "#fff",
                    pointHoverBackgroundColor: "#fff",
                    pointHoverBorderColor: "rgba(37,99,235,1)",
                    data: [<?=$collection_in_a_day_per_week ?? 0?>],
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            suggestedMax: 50,
                            beginAtZero: true,
                            padding: 10
                        },
                        gridLines: {
                            drawBorder: false
                        }
                    }],
                    xAxes: [{
                        gridLines: {
                            drawBorder: false,
                            display: false
                        }
                    }]
                },
                legend: {
                    display: false
                },
                tooltips: {
                    backgroundColor: '#fff',
                    titleFontColor: '#333',
                    bodyFontColor: '#666',
                    bodySpacing: 4,
                    xPadding: 12,
                    yPadding: 12,
                    borderColor: '#e9ecef',
                    borderWidth: 1,
                    caretSize: 6,
                    caretPadding: 10,
                    callbacks: {
                        label: function (e, r) {
                            return " " + e.yLabel + " Sales";
                        }
                    }
                }
            }
        });

        // BOQ Analytics Chart
        var boqChart = new Chart($('.js-chartjs-boq-chart'), {
            type: "bar",
            data: {
                labels: ["Mwanza Complex", "Dodoma Office", "Dar Bridge", "Arusha Hospital", "Kilimanjaro Resort"],
                datasets: [{
                    label: "Budgeted",
                    backgroundColor: "rgba(37,99,235,.7)",
                    borderColor: "rgba(37,99,235,1)",
                    borderWidth: 1,
                    data: [8500000, 4200000, 6800000, 3500000, 5200000],
                }, {
                    label: "Actual",
                    backgroundColor: "rgba(34,197,94,.7)",
                    borderColor: "rgba(34,197,94,1)",
                    borderWidth: 1,
                    data: [7800000, 4500000, 6200000, 3200000, 4800000],
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function(value) {
                                return 'TZS ' + (value / 1000000).toFixed(1) + 'M';
                            }
                        },
                        gridLines: {
                            drawBorder: false
                        }
                    }],
                    xAxes: [{
                        gridLines: {
                            drawBorder: false,
                            display: false
                        }
                    }]
                },
                legend: {
                    display: false
                },
                tooltips: {
                    backgroundColor: '#fff',
                    titleFontColor: '#333',
                    bodyFontColor: '#666',
                    borderColor: '#e9ecef',
                    borderWidth: 1,
                    callbacks: {
                        label: function(tooltipItem, data) {
                            return data.datasets[tooltipItem.datasetIndex].label + ': TZS ' + 
                                   (tooltipItem.yLabel / 1000000).toFixed(1) + 'M';
                        }
                    }
                }
            }
        });

        // Statutory Analytics Chart
        var statutoryChart = new Chart($('.js-chartjs-statutory-chart'), {
            type: "doughnut",
            data: {
                labels: ["VAT Collected", "PAYE Deducted", "NSSF Contributions", "Other Taxes"],
                datasets: [{
                    data: [<?=$this_month_tax_payable ?? 450000?>, 280000, 180000, 95000],
                    backgroundColor: [
                        "rgba(37,99,235,.8)",
                        "rgba(34,197,94,.8)", 
                        "rgba(249,115,22,.8)",
                        "rgba(239,68,68,.8)"
                    ],
                    borderColor: [
                        "rgba(37,99,235,1)",
                        "rgba(34,197,94,1)",
                        "rgba(249,115,22,1)",
                        "rgba(239,68,68,1)"
                    ],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                tooltips: {
                    backgroundColor: '#fff',
                    titleFontColor: '#333',
                    bodyFontColor: '#666',
                    borderColor: '#e9ecef',
                    borderWidth: 1,
                    callbacks: {
                        label: function(tooltipItem, data) {
                            const label = data.labels[tooltipItem.index];
                            const value = data.datasets[0].data[tooltipItem.index];
                            return label + ': TZS ' + value.toLocaleString();
                        }
                    }
                }
            }
        });

        // Chart filter functionality
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Progress circle animations
        document.querySelectorAll('.progress-circle').forEach(circle => {
            const progress = circle.getAttribute('data-progress');
            circle.style.background = `conic-gradient(var(--wajenzi-blue-primary) ${progress * 3.6}deg, var(--wajenzi-gray-200) 0deg)`;
        });
    </script>
@endsection

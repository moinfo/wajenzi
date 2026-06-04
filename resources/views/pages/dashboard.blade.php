@extends('layouts.backend')

@section('content')
    <?php
    use App\Classes\Utility;
    use App\Models\AdvanceSalary;
    use App\Models\FieldMarketingVisit;
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
    $last_month_sales = \App\Models\Sale::getTotalTax($last_month_first_date, $last_month_last_date);
    $purchases = \App\Models\Purchase::getTotalPurchasesWithVAT($end_date, null, null, $start_date);
    $vat_analysis = new \App\Models\VatAnalysis();
    $this_month_tax_payable = $vat_analysis->getTaxPayable($end_date);
    $last_month_tax_payable = \App\Models\VatPayment::getTotalPaymentOfLastMonth($last_month_first_date, $last_month_last_date);

    // Revenue trend (month-over-month)
    $revenueTrend = ($last_month_sales > 0) ? round((($sales - $last_month_sales) / $last_month_sales) * 100, 1) : null;

    // Project counts
    $activeProjectCount = \App\Models\Project::where('status', 'APPROVED')->count();
    $totalProjectCount = \App\Models\Project::count();

    // Budget: total project expenses vs total contract values
    $totalProjectExpenses = \Illuminate\Support\Facades\DB::table('project_expenses')->sum('amount') + \App\Models\Expense::sum('amount');
    $totalContractValue = \App\Models\Project::sum('contract_value');
    $budgetPercent = ($totalContractValue > 0) ? round(($totalProjectExpenses / $totalContractValue) * 100) : 0;

    // Approval counts
    $advance_salary_counting = AdvanceSalary::countUnapproved();
    $staff_loan_counting = Loan::countUnapproved();
    $payroll_counting = Payroll::countUnapproved();
    $material_request_counting = \App\Models\ProjectMaterialRequest::whereDoesntHave('approvalStatus', function($q) {
        $q->where('status', 'approved');
    })->count();
    $boq_pending_counting = \Illuminate\Support\Facades\DB::table('project_boqs')->where('status', 'draft')->count();
    $purchase_pending_counting = \App\Models\Purchase::where('status', 'PENDING')->count();

    // Links
    $advance_salary_link = "settings/advance_salaries";
    $staff_loan_link = 'settings/staff_loans';
    $payroll_link = 'payroll/payroll_administration';

    // Status documents for approvals
    $status_docs = [
        ['name' => 'Payroll', 'count' => $payroll_counting, 'icon' => 'fa fa-money-bill-wave', 'link' => $payroll_link, 'color' => 'blue'],
        ['name' => 'Advance Salary', 'count' => $advance_salary_counting, 'icon' => 'fa fa-hand-holding-usd', 'link' => $advance_salary_link, 'color' => 'green'],
        ['name' => 'Staff Loan', 'count' => $staff_loan_counting, 'icon' => 'fa fa-credit-card', 'link' => $staff_loan_link, 'color' => 'orange'],
        ['name' => 'Material Request', 'count' => $material_request_counting, 'icon' => 'fa fa-boxes', 'link' => '#', 'color' => 'purple'],
        ['name' => 'Project BOQ', 'count' => $boq_pending_counting, 'icon' => 'fa fa-file-invoice', 'link' => '#', 'color' => 'indigo'],
        ['name' => 'Purchases', 'count' => $purchase_pending_counting, 'icon' => 'fa fa-shopping-cart', 'link' => '#', 'color' => 'red'],
    ];

    // User and department data
    $counts = User::getUserCounts();
    $departmentCounts = User::getDepartmentMemberCounts();

    // Follow-ups: per-user by default, all with permission
    $canViewAllFollowups = Auth::user()->can('View All Follow-ups');

    $followupsQuery = SalesLeadFollowup::with(['lead.salesperson', 'lead.leadStatus'])
        ->whereHas('lead')
        ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
        ->orderBy('followup_date', 'asc');

    if (!$canViewAllFollowups) {
        $followupsQuery->whereHas('lead', function($q) {
            $q->where('salesperson_id', Auth::id());
        });
    }

    $allFollowups = $followupsQuery->limit(20)->get();

    // Counts for PENDING follow-ups (filtered same way)
    $followupCountQuery = function() use ($canViewAllFollowups) {
        $q = SalesLeadFollowup::where('status', 'pending')->whereHas('lead');
        if (!$canViewAllFollowups) {
            $q->whereHas('lead', function($lq) {
                $lq->where('salesperson_id', Auth::id());
            });
        }
        return $q;
    };

    $todayFollowupsCount = $followupCountQuery()->whereDate('followup_date', now()->toDateString())->count();
    $overdueFollowupsCount = $followupCountQuery()->whereDate('followup_date', '<', now()->toDateString())->count();
    $upcomingFollowupsCount = $followupCountQuery()->whereDate('followup_date', '>', now()->toDateString())->count();

    $completedCountQuery = SalesLeadFollowup::where('status', 'completed')
        ->whereMonth('followup_date', now()->month)->whereHas('lead');
    if (!$canViewAllFollowups) {
        $completedCountQuery->whereHas('lead', function($lq) {
            $lq->where('salesperson_id', Auth::id());
        });
    }
    $completedFollowupsCount = $completedCountQuery->count();

    // Calendar data - filtered same way
    $calendarMonth = request('cal_month', now()->month);
    $calendarYear = request('cal_year', now()->year);
    $calendarFollowupsQuery = SalesLeadFollowup::with(['lead'])
        ->whereYear('followup_date', $calendarYear)
        ->whereMonth('followup_date', $calendarMonth)
        ->whereHas('lead');

    if (!$canViewAllFollowups) {
        $calendarFollowupsQuery->whereHas('lead', function($lq) {
            $lq->where('salesperson_id', Auth::id());
        });
    }

    $calendarFollowups = $calendarFollowupsQuery->get()
        ->groupBy(function($item) {
            return $item->followup_date->format('Y-m-d');
        });

    // Field marketing follow-ups for calendar
    $fmCalQuery = FieldMarketingVisit::with(['session.officer'])
        ->where('status', 'follow_up')
        ->whereNotNull('next_followup_date')
        ->whereYear('next_followup_date', $calendarYear)
        ->whereMonth('next_followup_date', $calendarMonth);

    if (!$canViewAllFollowups) {
        $fmCalQuery->whereHas('session', fn($q) => $q->where('officer_id', Auth::id()));
    }

    $calendarFmFollowups = $fmCalQuery->get()
        ->groupBy(fn($v) => $v->next_followup_date->format('Y-m-d'));
    ?>

    <!-- Modern Wajenzi Dashboard -->
    <div class="wajenzi-dashboard">
        <!-- Welcome Header -->
        <div class="dashboard-welcome">
            {{-- decorative shapes --}}
            <div class="dw-shape dw-shape-1"></div>
            <div class="dw-shape dw-shape-2"></div>
            <div class="dw-shape dw-shape-3"></div>

            <div class="dw-left">
                <div class="dw-greeting-row">
                    <span class="dw-greeting-icon" id="dw-time-icon">☀️</span>
                    <span class="dw-greeting-text" id="dw-greeting">Good morning</span>
                </div>
                <h1 class="dw-name">{{ Auth::user()->name }}</h1>
                <p class="dw-subtitle">Here's what's happening with your construction projects today</p>
            </div>

            <div class="dw-right">
                <div class="dw-date-card">
                    <div class="dw-date-day" id="dw-day"></div>
                    <div class="dw-date-month" id="dw-month-year"></div>
                    <div class="dw-date-time" id="dw-time"></div>
                </div>
                <div class="dw-role-badge">
                    <i class="fa fa-id-badge mr-1"></i>
                    {{ Auth::user()->roles->first()?->name ?? 'User' }}
                </div>
            </div>
        </div>

        <script>
        (function() {
            function updateWelcomeClock() {
                var now = new Date();
                var h = now.getHours();
                var greeting = h < 12 ? 'Good morning' : (h < 17 ? 'Good afternoon' : 'Good evening');
                var icon = h < 12 ? '☀️' : (h < 17 ? '🌤️' : '🌙');
                var days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
                var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                var mins = now.getMinutes().toString().padStart(2,'0');
                var timeStr = (h % 12 || 12) + ':' + mins + ' ' + (h < 12 ? 'AM' : 'PM');

                document.getElementById('dw-greeting').textContent = greeting;
                document.getElementById('dw-time-icon').textContent = icon;
                document.getElementById('dw-day').textContent = days[now.getDay()] + ', ' + now.getDate();
                document.getElementById('dw-month-year').textContent = months[now.getMonth()] + ' ' + now.getFullYear();
                document.getElementById('dw-time').textContent = timeStr;
            }
            updateWelcomeClock();
            setInterval(updateWelcomeClock, 30000);
        })();
        </script>

        <!-- Key Metrics Grid -->
        <div class="metrics-grid">
            <!-- Financial Overview -->
            <div class="metric-card financial">
                <div class="metric-header">
                    <div class="metric-icon">
                        <i class="fa fa-chart-line"></i>
                    </div>
                    @if($revenueTrend !== null)
                        <div class="metric-trend {{ $revenueTrend >= 0 ? 'up' : 'down' }}">
                            <i class="fa fa-arrow-{{ $revenueTrend >= 0 ? 'up' : 'down' }}"></i>
                            <span>{{ ($revenueTrend >= 0 ? '+' : '') . $revenueTrend }}%</span>
                        </div>
                    @endif
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
                    <div class="metric-badge">
                        <span>{{ $totalProjectCount }}</span>
                    </div>
                </div>
                <div class="metric-content">
                    <h3>Active Projects</h3>
                    <div class="metric-value">{{ $activeProjectCount }}</div>
                    <p class="metric-period">{{ $totalProjectCount }} total</p>
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
                        <span>{{ $budgetPercent }}%</span>
                    </div>
                </div>
                <div class="metric-content">
                    <h3>Budget Utilization</h3>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: {{ min($budgetPercent, 100) }}%"></div>
                    </div>
                    <p class="metric-period">TZS {{ number_format($totalProjectExpenses / 1000000, 1) }}M of {{ number_format($totalContractValue / 1000000, 1) }}M used</p>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════
             CEO / Chief Executive Officer Executive Dashboard
        ══════════════════════════════════════════════════════════════ --}}
        @if(Auth::user()->hasAnyRole(['CEO', 'Chief Executive Officer']))
        <div class="ceo-dashboard">
            <div class="ceo-dashboard-header">
                <div class="ceo-header-left">
                    <i class="fa fa-crown ceo-crown-icon"></i>
                    <div>
                        <h2 class="ceo-title">Executive Summary</h2>
                        <p class="ceo-subtitle">{{ now()->format('l, F j Y') }}</p>
                    </div>
                </div>
                <div class="ceo-quick-actions">
                    <a href="{{ route('reports') }}" class="ceo-action-btn">
                        <i class="fa fa-chart-bar"></i> View Reports
                    </a>
                    <a href="{{ route('hr_settings') }}" class="ceo-action-btn secondary">
                        <i class="fa fa-cog"></i> Settings
                    </a>
                </div>
            </div>

            <div class="ceo-grid">

                {{-- ── 1. Pending Approvals ──────────────────────────── --}}
                <div class="ceo-card" id="ceo-approvals-card">
                    <div class="ceo-card-header" onclick="ceotoggle('ceo-approvals-body')">
                        <span><i class="fa fa-check-square mr-2"></i>Pending Approvals</span>
                        <span class="ceo-badge">{{ array_sum(array_column($status_docs, 'count')) }}</span>
                        <i class="fa fa-chevron-down ceo-toggle-icon" id="ceo-approvals-chevron"></i>
                    </div>
                    <div class="ceo-card-body" id="ceo-approvals-body" style="display:none;">
                        @foreach($status_docs as $doc)
                            @if($doc['count'] > 0)
                            <a href="{{ $doc['link'] }}" class="ceo-list-row">
                                <div class="ceo-dot {{ $doc['color'] }}"></div>
                                <span class="ceo-row-label">{{ $doc['name'] }}</span>
                                <span class="ceo-row-count">{{ $doc['count'] }}</span>
                            </a>
                            @endif
                        @endforeach
                        @if(array_sum(array_column($status_docs, 'count')) === 0)
                            <div class="ceo-empty"><i class="fa fa-check-circle text-success"></i> All caught up!</div>
                        @endif
                    </div>
                </div>

                {{-- ── 2. Project Activities ─────────────────────────── --}}
                <div class="ceo-card">
                    <div class="ceo-card-header" onclick="ceotoggle('ceo-activities-body')">
                        <span><i class="fa fa-drafting-compass mr-2"></i>Project Activities</span>
                        <i class="fa fa-chevron-down ceo-toggle-icon" id="ceo-activities-chevron"></i>
                    </div>
                    <div class="ceo-card-body" id="ceo-activities-body" style="display:none;">
                        <div class="ceo-sub-section">
                            <div class="ceo-sub-title"><i class="fa fa-pencil-ruler mr-1"></i>Design Pipeline</div>
                            <a href="{{ route('project_site_visits') }}" class="ceo-list-row">
                                <div class="ceo-dot blue"></div>
                                <span class="ceo-row-label">Site Visits</span>
                                <span class="ceo-row-count">{{ $ceoDesignStats['site_visits'] ?? 0 }}</span>
                            </a>
                            <a href="{{ route('structural_design.index') }}" class="ceo-list-row">
                                <div class="ceo-dot purple"></div>
                                <span class="ceo-row-label">Structural Design</span>
                                <span class="ceo-row-count">{{ $ceoDesignStats['structural'] ?? 0 }}</span>
                            </a>
                            <a href="{{ route('project-boq-plans.index') }}" class="ceo-list-row">
                                <div class="ceo-dot indigo"></div>
                                <span class="ceo-row-label">BOQ Plans</span>
                                <span class="ceo-row-count">{{ $ceoDesignStats['boq_plans'] ?? 0 }}</span>
                            </a>
                            <a href="{{ route('service_design.index') }}" class="ceo-list-row">
                                <div class="ceo-dot green"></div>
                                <span class="ceo-row-label">Service Design</span>
                                <span class="ceo-row-count">{{ $ceoDesignStats['service_designs'] ?? 0 }}</span>
                            </a>
                        </div>
                        <div class="ceo-sub-section mt-3">
                            <div class="ceo-sub-title"><i class="fa fa-hard-hat mr-1"></i>Construction Schedules</div>
                            <div class="ceo-list-row no-link">
                                <div class="ceo-dot green"></div>
                                <span class="ceo-row-label">Completed</span>
                                <span class="ceo-row-count">{{ $ceoConstructionStats['completed'] ?? 0 }}</span>
                            </div>
                            <div class="ceo-list-row no-link">
                                <div class="ceo-dot blue"></div>
                                <span class="ceo-row-label">In Progress</span>
                                <span class="ceo-row-count">{{ $ceoConstructionStats['in_progress'] ?? 0 }}</span>
                            </div>
                            <div class="ceo-list-row no-link">
                                <div class="ceo-dot orange"></div>
                                <span class="ceo-row-label">Confirmed / Pending</span>
                                <span class="ceo-row-count">{{ $ceoConstructionStats['confirmed'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── 3. High Priority Invoices ─────────────────────── --}}
                <div class="ceo-card">
                    <div class="ceo-card-header">
                        <span><i class="fa fa-file-invoice-dollar mr-2"></i>Invoices</span>
                    </div>
                    <div class="ceo-card-body" id="ceo-invoices-body">
                        <div class="ceo-inv-stats">
                            <div class="ceo-inv-stat overdue" onclick="ceotoggle('ceo-inv-detail')">
                                <span class="ceo-inv-num">{{ \App\Models\BillingDocument::overdueInvoices()->count() }}</span>
                                <span class="ceo-inv-lbl">Overdue</span>
                            </div>
                            <div class="ceo-inv-stat today" onclick="ceotoggle('ceo-inv-detail')">
                                <span class="ceo-inv-num">{{ \App\Models\BillingDocument::dueToday()->count() }}</span>
                                <span class="ceo-inv-lbl">Due Today</span>
                            </div>
                            <div class="ceo-inv-stat upcoming" onclick="ceotoggle('ceo-inv-detail')">
                                <span class="ceo-inv-num">{{ \App\Models\BillingDocument::upcomingDue()->count() }}</span>
                                <span class="ceo-inv-lbl">Upcoming</span>
                            </div>
                            <div class="ceo-inv-stat paid" onclick="ceotoggle('ceo-inv-detail')">
                                <span class="ceo-inv-num">{{ $ceoPaidThisMonth }}</span>
                                <span class="ceo-inv-lbl">Paid</span>
                            </div>
                        </div>
                        <div id="ceo-inv-detail" style="display:none;">
                            @forelse($ceoInvoiceDetails as $inv)
                            @php
                                $invIsOverdue = $inv->is_overdue;
                                $invDaysLeft = $inv->due_date ? now()->startOfDay()->diffInDays($inv->due_date->startOfDay(), false) : null;
                            @endphp
                            <a href="{{ route('billing.invoices.show', $inv->id) }}" class="ceo-list-row {{ $invIsOverdue ? 'is-overdue' : '' }}">
                                <div class="ceo-dot {{ $invIsOverdue ? 'red' : ($inv->due_date?->isToday() ? 'orange' : 'blue') }}"></div>
                                <div class="ceo-inv-info">
                                    <span class="ceo-row-label">{{ $inv->document_number }}</span>
                                    <span class="ceo-inv-meta">{{ $inv->client?->name ?? $inv->lead?->name ?? 'No Client' }}
                                        @if($inv->creator) · {{ $inv->creator->name }}@endif</span>
                                </div>
                                <span class="ceo-row-count">
                                    @if($invIsOverdue)
                                        <span style="color:#dc3545;font-size:0.7rem;">{{ abs($invDaysLeft) }}d ago</span>
                                    @elseif($inv->due_date?->isToday())
                                        <span style="color:#fd7e14;font-size:0.7rem;">Today</span>
                                    @else
                                        <span style="font-size:0.7rem;">{{ $inv->due_date?->format('d M') }}</span>
                                    @endif
                                </span>
                            </a>
                            @empty
                                <div class="ceo-empty"><i class="fa fa-check-circle text-success"></i> No outstanding invoices</div>
                            @endforelse
                            <div class="ceo-card-footer">
                                <a href="{{ route('billing.invoices.index') }}" class="ceo-view-all">View All Invoices <i class="fa fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── 4. Total Revenue Breakdown ───────────────────── --}}
                <div class="ceo-card">
                    <div class="ceo-card-header" onclick="ceotoggle('ceo-revenue-body')">
                        <span><i class="fa fa-chart-line mr-2"></i>Total Revenue</span>
                        <span class="ceo-badge">TZS {{ number_format($sales ?? 0, 0) }}</span>
                        <i class="fa fa-chevron-down ceo-toggle-icon" id="ceo-revenue-chevron"></i>
                    </div>
                    <div class="ceo-card-body" id="ceo-revenue-body" style="display:none;">
                        <p class="ceo-period-note">Paid invoices this month · categorised by description</p>
                        <div class="ceo-list-row no-link">
                            <div class="ceo-dot blue"></div>
                            <span class="ceo-row-label"><i class="fa fa-walking mr-1"></i>Site Visit Income</span>
                            <span class="ceo-row-count">TZS {{ number_format($ceoRevenueBreakdown['site_visit'] ?? 0, 0) }}</span>
                        </div>
                        <div class="ceo-list-row no-link">
                            <div class="ceo-dot purple"></div>
                            <span class="ceo-row-label"><i class="fa fa-pencil-ruler mr-1"></i>Drawing / Design</span>
                            <span class="ceo-row-count">TZS {{ number_format($ceoRevenueBreakdown['drawing'] ?? 0, 0) }}</span>
                        </div>
                        <div class="ceo-list-row no-link">
                            <div class="ceo-dot orange"></div>
                            <span class="ceo-row-label"><i class="fa fa-hard-hat mr-1"></i>Construction</span>
                            <span class="ceo-row-count">TZS {{ number_format($ceoRevenueBreakdown['construction'] ?? 0, 0) }}</span>
                        </div>
                        <div class="ceo-list-row no-link">
                            <div class="ceo-dot green"></div>
                            <span class="ceo-row-label"><i class="fa fa-comments mr-1"></i>Consultation</span>
                            <span class="ceo-row-count">TZS {{ number_format($ceoRevenueBreakdown['consultation'] ?? 0, 0) }}</span>
                        </div>
                    </div>
                </div>

                {{-- ── 5. Team Members by Department ────────────────── --}}
                <div class="ceo-card">
                    <div class="ceo-card-header" onclick="ceotoggle('ceo-team-body')">
                        <span><i class="fa fa-users mr-2"></i>Team Members</span>
                        <span class="ceo-badge">{{ $counts->total }}</span>
                        <i class="fa fa-chevron-down ceo-toggle-icon" id="ceo-team-chevron"></i>
                    </div>
                    <div class="ceo-card-body" id="ceo-team-body" style="display:none;">
                        @foreach($ceoTeamByDept as $dept => $members)
                        <div class="ceo-dept-group">
                            <div class="ceo-dept-header" onclick="ceotoggle('ceo-dept-{{ Str::slug($dept) }}')">
                                <span>{{ $dept }}</span>
                                <span class="ceo-badge-sm">{{ $members->count() }}</span>
                            </div>
                            <div class="ceo-dept-members" id="ceo-dept-{{ Str::slug($dept) }}" style="display:none;">
                                @foreach($members as $member)
                                <div class="ceo-member-row">
                                    <i class="fa fa-user-circle ceo-member-icon"></i>
                                    <span class="ceo-member-name">{{ $member->name }}</span>
                                    <span class="ceo-member-role">{{ $member->roles->first()?->name ?? '—' }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- ── 6. Active Projects ────────────────────────────── --}}
                <div class="ceo-card">
                    <div class="ceo-card-header" onclick="ceotoggle('ceo-projects-body')">
                        <span><i class="fa fa-building mr-2"></i>Active Projects</span>
                        <span class="ceo-badge">{{ $ceoActiveProjects->count() }}</span>
                        <i class="fa fa-chevron-down ceo-toggle-icon" id="ceo-projects-chevron"></i>
                    </div>
                    <div class="ceo-card-body" id="ceo-projects-body" style="display:none;">
                        @forelse($ceoActiveProjects as $proj)
                        <a href="{{ route('project.show', $proj->id) }}" class="ceo-list-row">
                            <div class="ceo-dot green"></div>
                            <div class="ceo-inv-info">
                                <span class="ceo-row-label">{{ $proj->project_name }}</span>
                                @if($proj->client)
                                    <span class="ceo-inv-meta">{{ $proj->client->first_name }} {{ $proj->client->last_name }}</span>
                                @endif
                            </div>
                            @if($proj->contract_value)
                            <span class="ceo-row-count" style="font-size:0.7rem;">TZS {{ number_format($proj->contract_value, 0) }}</span>
                            @endif
                        </a>
                        @empty
                            <div class="ceo-empty">No active projects</div>
                        @endforelse
                        <div class="ceo-card-footer">
                            <a href="{{ route('projects') }}" class="ceo-view-all">View All Projects <i class="fa fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

            </div>{{-- end ceo-grid --}}
        </div>{{-- end ceo-dashboard --}}

        <style>
            .ceo-dashboard {
                margin-bottom: 1.5rem;
            }
            .ceo-dashboard-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 1rem;
                padding: 0.75rem 1rem;
                background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
                border-radius: 12px;
                color: #fff;
            }
            .ceo-header-left {
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }
            .ceo-crown-icon {
                font-size: 1.75rem;
                color: #ffd700;
            }
            .ceo-title {
                margin: 0;
                font-size: 1.25rem;
                font-weight: 700;
                color: #fff;
            }
            .ceo-subtitle {
                margin: 0;
                font-size: 0.8rem;
                color: rgba(255,255,255,0.65);
            }
            .ceo-quick-actions {
                display: flex;
                gap: 0.5rem;
            }
            .ceo-action-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.45rem 0.9rem;
                background: rgba(255,255,255,0.15);
                border: 1px solid rgba(255,255,255,0.25);
                color: #fff;
                border-radius: 8px;
                font-size: 0.82rem;
                text-decoration: none;
                transition: background 0.2s;
            }
            .ceo-action-btn:hover { background: rgba(255,255,255,0.28); color:#fff; text-decoration:none; }
            .ceo-action-btn.secondary { background: rgba(255,255,255,0.07); }

            .ceo-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                gap: 1rem;
            }
            .ceo-card {
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                overflow: hidden;
                border: 1px solid #e9ecef;
            }
            .ceo-card-header {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.85rem 1rem;
                background: #f8f9fa;
                border-bottom: 1px solid #e9ecef;
                cursor: pointer;
                font-weight: 600;
                font-size: 0.9rem;
                color: #343a40;
                user-select: none;
            }
            .ceo-card-header:hover { background: #e9ecef; }
            .ceo-toggle-icon {
                margin-left: auto;
                font-size: 0.75rem;
                color: #6c757d;
                transition: transform 0.25s;
            }
            .ceo-toggle-icon.open { transform: rotate(180deg); }
            .ceo-badge {
                margin-left: auto;
                background: #0f3460;
                color: #fff;
                font-size: 0.72rem;
                padding: 0.2rem 0.55rem;
                border-radius: 20px;
                font-weight: 600;
            }
            .ceo-badge-sm {
                background: #6c757d;
                color: #fff;
                font-size: 0.65rem;
                padding: 0.1rem 0.4rem;
                border-radius: 20px;
                font-weight: 600;
            }
            .ceo-card-body {
                padding: 0.5rem 0;
            }
            .ceo-list-row {
                display: flex;
                align-items: center;
                gap: 0.6rem;
                padding: 0.5rem 1rem;
                text-decoration: none;
                color: #343a40;
                transition: background 0.15s;
                border-bottom: 1px solid #f0f0f0;
            }
            .ceo-list-row:last-child { border-bottom: none; }
            .ceo-list-row:hover:not(.no-link) { background: #f8f9fa; }
            .ceo-list-row.is-overdue { background: #fff5f5; }
            .ceo-list-row.no-link { cursor: default; }
            .ceo-row-label { flex: 1; font-size: 0.85rem; }
            .ceo-row-count { font-weight: 700; font-size: 0.85rem; color: #495057; white-space: nowrap; }
            .ceo-inv-info { flex: 1; display: flex; flex-direction: column; gap: 0.1rem; }
            .ceo-inv-meta { font-size: 0.72rem; color: #6c757d; }
            .ceo-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                flex-shrink: 0;
            }
            .ceo-dot.blue   { background: #0d6efd; }
            .ceo-dot.purple { background: #6f42c1; }
            .ceo-dot.green  { background: #28a745; }
            .ceo-dot.orange { background: #fd7e14; }
            .ceo-dot.red    { background: #dc3545; }
            .ceo-dot.indigo { background: #3d5a80; }

            .ceo-inv-stats {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 0.5rem;
                padding: 0.75rem 1rem;
            }
            .ceo-inv-stat {
                text-align: center;
                padding: 0.6rem 0.4rem;
                border-radius: 8px;
                cursor: pointer;
                transition: opacity 0.2s;
            }
            .ceo-inv-stat:hover { opacity: 0.8; }
            .ceo-inv-stat.overdue  { background: #fff5f5; }
            .ceo-inv-stat.today    { background: #fff8ec; }
            .ceo-inv-stat.upcoming { background: #f0f7ff; }
            .ceo-inv-stat.paid     { background: #f0fff4; }
            .ceo-inv-num {
                display: block;
                font-size: 1.4rem;
                font-weight: 700;
                line-height: 1;
            }
            .ceo-inv-stat.overdue  .ceo-inv-num { color: #dc3545; }
            .ceo-inv-stat.today    .ceo-inv-num { color: #fd7e14; }
            .ceo-inv-stat.upcoming .ceo-inv-num { color: #0d6efd; }
            .ceo-inv-stat.paid     .ceo-inv-num { color: #28a745; }
            .ceo-inv-lbl {
                display: block;
                font-size: 0.68rem;
                color: #6c757d;
                margin-top: 0.2rem;
                text-transform: uppercase;
                letter-spacing: 0.03em;
            }
            .ceo-period-note { font-size: 0.75rem; color: #6c757d; padding: 0.25rem 1rem 0; margin: 0; }
            .ceo-sub-section { padding: 0 0; }
            .ceo-sub-title {
                padding: 0.35rem 1rem;
                font-size: 0.72rem;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                font-weight: 700;
                color: #6c757d;
                background: #f8f9fa;
                border-bottom: 1px solid #e9ecef;
                border-top: 1px solid #e9ecef;
            }
            .ceo-empty {
                padding: 1rem;
                text-align: center;
                color: #6c757d;
                font-size: 0.85rem;
            }
            .ceo-card-footer {
                padding: 0.5rem 1rem;
                border-top: 1px solid #f0f0f0;
            }
            .ceo-view-all {
                font-size: 0.78rem;
                color: #0d6efd;
                text-decoration: none;
                font-weight: 500;
            }
            .ceo-view-all:hover { text-decoration: underline; }

            .ceo-dept-group { border-bottom: 1px solid #f0f0f0; }
            .ceo-dept-group:last-child { border-bottom: none; }
            .ceo-dept-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0.55rem 1rem;
                cursor: pointer;
                font-weight: 600;
                font-size: 0.85rem;
                color: #495057;
            }
            .ceo-dept-header:hover { background: #f8f9fa; }
            .ceo-dept-members { padding: 0.25rem 0 0.25rem 1rem; background: #fafafa; }
            .ceo-member-row {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.3rem 0;
            }
            .ceo-member-icon { color: #6c757d; font-size: 0.85rem; }
            .ceo-member-name { flex: 1; font-size: 0.82rem; color: #343a40; }
            .ceo-member-role { font-size: 0.7rem; color: #6c757d; }

            @media (max-width: 768px) {
                .ceo-grid { grid-template-columns: 1fr; }
                .ceo-dashboard-header { flex-direction: column; gap: 0.75rem; align-items: flex-start; }
                .ceo-inv-stats { grid-template-columns: repeat(2, 1fr); }
            }
        </style>

        <script>
            function ceotoggle(id) {
                var el = document.getElementById(id);
                if (!el) return;
                var isOpen = el.style.display !== 'none';
                el.style.display = isOpen ? 'none' : 'block';
                // rotate corresponding chevron if it exists
                var chevronId = id.replace('-body', '-chevron').replace('ceo-dept-', 'ceo-dept-chevron-');
                var chevron = document.getElementById(chevronId);
                if (chevron) {
                    chevron.classList.toggle('open', !isOpen);
                }
            }
        </script>
        @endif

        <!-- Main Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Pending Approvals -->
            <div class="dashboard-section approvals">
                <div class="section-header">
                    <h2><i class="fa fa-clock"></i>Pending Approvals</h2>
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
                                <span class="day">{{ $followup->followup_date?->format('d') ?? '--' }}</span>
                                <span class="month">{{ $followup->followup_date?->format('M') ?? '' }}</span>
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
                                    <span class="status-label upcoming">{{ $followup->followup_date?->format('D') ?? 'Unscheduled' }}</span>
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
                                <span class="followup-details">{{ $activity->schedule->display_name }}</span>
                                <span class="followup-assignee">
                                    <i class="fa fa-layer-group"></i> {{ $activity->phase }}
                                    @if($activity->assignedUser)
                                        &middot; <i class="fa fa-user"></i> {{ $activity->assignedUser->name }}
                                    @endif
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

            <!-- Completed Design Stages (for Sales Team visibility) -->
            @if(isset($completedActivities) && $completedActivities->count() > 0)
            <div class="dashboard-section project-activities-todo">
                <div class="section-header">
                    <h2><i class="fa fa-check-circle"></i>Completed Design Stages</h2>
                    <small class="text-muted">Recent completed milestones across all active projects</small>
                </div>
                <div class="followup-list">
                    @foreach($completedActivities as $activity)
                    <a href="{{ route('project-schedules.show', $activity->project_schedule_id) }}" class="followup-item completed" style="border-left-color:#28a745;">
                        <div class="followup-date-badge" style="background:#28a745;">
                            <span class="day">{{ $activity->completed_at->format('d') }}</span>
                            <span class="month">{{ $activity->completed_at->format('M') }}</span>
                        </div>
                        <div class="followup-content">
                            <span class="followup-lead-name">{{ $activity->activity_code }}: {{ Str::limit($activity->name, 35) }}</span>
                            <span class="followup-details">
                                {{ $activity->schedule->display_name ?? 'N/A' }}
                                @if($activity->schedule->client)
                                    &middot; {{ $activity->schedule->client->name }}
                                @endif
                            </span>
                            <span class="followup-assignee">
                                <i class="fa fa-layer-group"></i> {{ $activity->phase }}
                                @if($activity->completedByUser)
                                    &middot; <i class="fa fa-user"></i> {{ $activity->completedByUser->name }}
                                @endif
                            </span>
                        </div>
                        <div class="followup-status">
                            <span class="status-label" style="background:#d4edda;color:#155724;border-color:#c3e6cb;">
                                <i class="fa fa-check"></i> Done
                            </span>
                            @if($activity->completion_notes)
                                <span class="d-block small text-muted mt-1" style="max-width:120px;">{{ Str::limit($activity->completion_notes, 40) }}</span>
                            @endif
                        </div>
                    </a>
                    @endforeach
                </div>
                <div class="followup-footer">
                    <a href="{{ route('project-schedules.index') }}" class="view-all-btn">
                        <i class="fa fa-list"></i> View All Schedules
                    </a>
                </div>
            </div>
            @endif

            <!-- Civil Engineer: Structural Design Handoffs -->
            @if(isset($structuralHandoffs) && $structuralHandoffs->count() > 0)
            <div class="dashboard-section project-activities-todo">
                <div class="section-header">
                    <h2><i class="fa fa-drafting-compass mr-2" style="color:#6f42c1;"></i>Structural Design Work</h2>
                    <small class="text-muted">Design handoffs awaiting your structural analysis and drawings</small>
                </div>
                <div class="followup-list">
                    @foreach($structuralHandoffs as $handoff)
                    @php
                        $stagesDone = $handoff->stages->where('status','completed')->count();
                        $stagesTotal = $handoff->stages->count();
                        $statusColors = ['pending'=>'#ffc107','in_progress'=>'#17a2b8','submitted'=>'#007bff','rejected'=>'#dc3545'];
                        $borderColor = $statusColors[$handoff->status] ?? '#6c757d';
                    @endphp
                    <a href="{{ route('structural_design.show', $handoff) }}" class="followup-item" style="border-left-color:{{ $borderColor }};">
                        <div class="followup-date-badge" style="background:{{ $borderColor }}; font-size:0.7rem; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:2px;">
                            <span style="font-size:1.1rem; font-weight:700;">{{ $stagesDone }}/{{ $stagesTotal }}</span>
                            <span>stages</span>
                        </div>
                        <div class="followup-content">
                            <span class="followup-lead-name">{{ $handoff->document_number }}</span>
                            <span class="followup-details">
                                {{ $handoff->project->project_name ?? 'N/A' }}
                                @if($handoff->project->projectType)
                                    &middot; <span class="badge badge-secondary badge-sm">{{ $handoff->project->projectType->name }}</span>
                                @endif
                            </span>
                            <span class="followup-assignee">
                                <i class="fa fa-calendar-alt"></i> Received {{ $handoff->created_at->diffForHumans() }}
                                @if($handoff->triggeringActivity)
                                    &middot; from B7 ({{ $handoff->triggeringActivity->schedule->display_name }})
                                @endif
                            </span>
                        </div>
                        <div class="followup-status">
                            @php $pct = $stagesTotal > 0 ? round(($stagesDone/$stagesTotal)*100) : 0; @endphp
                            <div style="width:80px;">
                                <div style="background:#e9ecef;border-radius:4px;height:6px;margin-bottom:4px;">
                                    <div style="background:{{ $borderColor }};width:{{ $pct }}%;height:6px;border-radius:4px;"></div>
                                </div>
                                <span class="status-label" style="background:{{ $borderColor }}1a;color:{{ $borderColor }};border-color:{{ $borderColor }}40;font-size:0.7rem;">
                                    {{ ucfirst(str_replace('_',' ',$handoff->status)) }}
                                </span>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
                <div class="followup-footer">
                    <a href="{{ route('structural_design.index') }}" class="view-all-btn">
                        <i class="fa fa-list"></i> View All Structural Designs
                    </a>
                </div>
            </div>
            @endif

            <!-- Quantity Surveyor: Approved structural designs ready for BOQ -->
            @if(isset($qsReadyDesigns) && $qsReadyDesigns->count() > 0)
            <div class="dashboard-section project-activities-todo">
                <div class="section-header">
                    <h2><i class="fa fa-clipboard-list mr-2" style="color:#fd7e14;"></i>BOQ Ready — Approved Structural Designs</h2>
                    <small class="text-muted">Structural design has been approved — prepare the Bill of Quantities</small>
                </div>
                <div class="followup-list">
                    @foreach($qsReadyDesigns as $design)
                    <a href="{{ route('structural_design.show', $design) }}" class="followup-item" style="border-left-color:#fd7e14;">
                        <div class="followup-date-badge" style="background:#fd7e14;">
                            <span class="day" style="font-size:0.75rem;">BOQ</span>
                            <span class="month" style="font-size:0.6rem;">Ready</span>
                        </div>
                        <div class="followup-content">
                            <span class="followup-lead-name">{{ $design->document_number }}</span>
                            <span class="followup-details">
                                {{ $design->project->project_name ?? 'N/A' }}
                                @if($design->project->projectType)
                                    &middot; <span class="badge badge-secondary badge-sm">{{ $design->project->projectType->name }}</span>
                                @endif
                            </span>
                            <span class="followup-assignee">
                                <i class="fa fa-calendar-check"></i> Approved {{ $design->approved_at->format('d M Y') }}
                                &middot; <i class="fa fa-file"></i> {{ $design->stages->where('file_path','!=',null)->count() }} file(s) attached
                            </span>
                        </div>
                        <div class="followup-status">
                            <span class="status-label" style="background:#fff3cd;color:#856404;border-color:#ffc107;">
                                <i class="fa fa-file-invoice"></i> BOQ Needed
                            </span>
                        </div>
                    </a>
                    @endforeach
                </div>
                <div class="followup-footer">
                    <a href="{{ route('structural_design.index') }}" class="view-all-btn">
                        <i class="fa fa-list"></i> View All Structural Designs
                    </a>
                </div>
            </div>
            @endif

            <!-- Sales Team: Approved structural designs to share with clients -->
            @if(isset($salesApprovedDesigns) && $salesApprovedDesigns->count() > 0)
            <div class="dashboard-section project-activities-todo">
                <div class="section-header">
                    <h2><i class="fa fa-share-alt mr-2" style="color:#20c997;"></i>Ready to Share — Approved Structural Designs</h2>
                    <small class="text-muted">Final structural drawings approved — share via Client Portal or download for client</small>
                </div>
                <div class="followup-list">
                    @foreach($salesApprovedDesigns as $design)
                    @php
                        $filesCount = $design->stages->whereNotNull('file_path')->count();
                    @endphp
                    <a href="{{ route('structural_design.show', $design) }}" class="followup-item" style="border-left-color:#20c997;">
                        <div class="followup-date-badge" style="background:#20c997;">
                            <span class="day" style="font-size:0.75rem;"><i class="fa fa-check"></i></span>
                            <span class="month" style="font-size:0.6rem;">Approved</span>
                        </div>
                        <div class="followup-content">
                            <span class="followup-lead-name">{{ $design->document_number }}</span>
                            <span class="followup-details">
                                {{ $design->project->project_name ?? 'N/A' }}
                                @if($design->project->projectType)
                                    &middot; <span class="badge badge-secondary badge-sm">{{ $design->project->projectType->name }}</span>
                                @endif
                                @if($design->project->client)
                                    &middot; Client: {{ $design->project->client->first_name }} {{ $design->project->client->last_name }}
                                @endif
                            </span>
                            <span class="followup-assignee">
                                <i class="fa fa-calendar-check"></i> {{ $design->approved_at->format('d M Y') }}
                                &middot; <i class="fa fa-paperclip"></i> {{ $filesCount }} downloadable file(s)
                            </span>
                        </div>
                        <div class="followup-status">
                            <span class="status-label" style="background:#d1f2eb;color:#0e6655;border-color:#1abc9c;">
                                <i class="fa fa-share"></i> Share
                            </span>
                        </div>
                    </a>
                    @endforeach
                </div>
                <div class="followup-footer">
                    <a href="{{ route('structural_design.index') }}" class="view-all-btn">
                        <i class="fa fa-list"></i> View All Structural Designs
                    </a>
                </div>
            </div>
            @endif

            <!-- QS: BOQ Plans status (draft/submitted/rejected) -->
            @if(isset($qsBoqPlans) && $qsBoqPlans->count() > 0)
            <div class="dashboard-section project-activities-todo">
                <div class="section-header">
                    <h2><i class="fa fa-clipboard-check mr-2" style="color:#6610f2;"></i>BOQ Preparation Plans</h2>
                    <small class="text-muted">Plans awaiting submission or approval</small>
                </div>
                <div class="followup-list">
                    @foreach($qsBoqPlans as $plan)
                    @php
                        $planColors = ['draft'=>'#6c757d','submitted'=>'#17a2b8','rejected'=>'#dc3545'];
                        $pc = $planColors[$plan->status] ?? '#6c757d';
                    @endphp
                    <a href="{{ route('project-boq-plans.show', $plan) }}" class="followup-item" style="border-left-color:{{ $pc }};">
                        <div class="followup-date-badge" style="background:{{ $pc }}; font-size:0.7rem;">
                            <span style="font-size:0.65rem;text-transform:uppercase;">{{ $plan->status }}</span>
                        </div>
                        <div class="followup-content">
                            <span class="followup-lead-name">{{ $plan->document_number }}</span>
                            <span class="followup-details">{{ $plan->project->project_name ?? 'N/A' }}</span>
                            <span class="followup-assignee">
                                @if($plan->planned_start)
                                    <i class="fa fa-calendar"></i> {{ $plan->planned_start->format('d M Y') }} – {{ $plan->planned_end?->format('d M Y') }}
                                @endif
                            </span>
                        </div>
                        <div class="followup-status">
                            @if($plan->status === 'submitted')
                                <span class="status-label" style="background:#cff4fc;color:#055160;border-color:#9eeaf9;"><i class="fa fa-clock"></i> Awaiting MD</span>
                            @elseif($plan->status === 'rejected')
                                <span class="status-label" style="background:#f8d7da;color:#842029;border-color:#f5c2c7;"><i class="fa fa-times"></i> Rejected</span>
                            @else
                                <span class="status-label"><i class="fa fa-pencil"></i> Draft</span>
                            @endif
                        </div>
                    </a>
                    @endforeach
                </div>
                <div class="followup-footer">
                    <a href="{{ route('project-boq-plans.index') }}" class="view-all-btn">
                        <i class="fa fa-list"></i> View All BOQ Plans
                    </a>
                </div>
            </div>
            @endif

            <!-- Sales Team: Approved BOQs ready to share with clients -->
            @if(isset($salesApprovedBoqs) && $salesApprovedBoqs->count() > 0)
            <div class="dashboard-section project-activities-todo">
                <div class="section-header">
                    <h2><i class="fa fa-file-invoice mr-2" style="color:#0d6efd;"></i>Final BOQ Approved — Share with Client</h2>
                    <small class="text-muted">CEO/MD approved — share the BOQ with the client via the portal or download PDF</small>
                </div>
                <div class="followup-list">
                    @foreach($salesApprovedBoqs as $boq)
                    <a href="{{ route('project_boq.show', $boq->id) }}" class="followup-item" style="border-left-color:#0d6efd;">
                        <div class="followup-date-badge" style="background:#0d6efd;">
                            <span class="day" style="font-size:0.75rem;"><i class="fa fa-check"></i></span>
                            <span class="month" style="font-size:0.6rem;">BOQ</span>
                        </div>
                        <div class="followup-content">
                            <span class="followup-lead-name">{{ $boq->document_number }}</span>
                            <span class="followup-details">
                                {{ $boq->project->project_name ?? 'N/A' }}
                                @if($boq->project->client)
                                    &middot; {{ $boq->project->client->first_name }} {{ $boq->project->client->last_name }}
                                @endif
                            </span>
                            <span class="followup-assignee">
                                TZS {{ number_format($boq->total_amount ?? 0, 0) }}
                            </span>
                        </div>
                        <div class="followup-status">
                            <a href="{{ route('project_boq.pdf', $boq->id) }}" target="_blank"
                               class="status-label"
                               style="background:#e7f1ff;color:#0d6efd;border-color:#b6d4fe;text-decoration:none;"
                               onclick="event.stopPropagation();">
                                <i class="fa fa-download"></i> PDF
                            </a>
                        </div>
                    </a>
                    @endforeach
                </div>
                <div class="followup-footer">
                    <a href="{{ route('project_boqs') }}" class="view-all-btn">
                        <i class="fa fa-list"></i> View All BOQs
                    </a>
                </div>
            </div>
            @endif

            <!-- Invoice Due Dates To-Do List (for Accountants) -->
            @php
                // Check if user can view invoices
                $canViewInvoices = Auth::user()->can('View All Invoice Due Dates');

                $paidThisMonthCount = 0;
                if ($canViewInvoices) {
                    $paidThisMonthCount = \App\Models\BillingDocument::where('document_type', 'invoice')
                        ->where('status', 'paid')
                        ->whereMonth('paid_at', now()->month)
                        ->whereYear('paid_at', now()->year)
                        ->count();
                }
            @endphp
            @if($canViewInvoices)
            <div class="dashboard-section invoice-due-todo">
                <div class="section-header">
                    <h2><i class="fa fa-file-invoice-dollar mr-2"></i>Invoice Due Dates</h2>
                    <a href="{{ route('export.invoices.calendar') }}" class="gcal-export-btn" title="Export invoice due dates to Google Calendar">
                        <i class="fa-brands fa-google"></i> Export
                    </a>
                </div>
                <div class="inv-stats-grid">
                    <div class="inv-stat overdue">
                        <i class="fa fa-exclamation-triangle"></i>
                        <span class="inv-stat-num">{{ $overdueInvoicesCount ?? 0 }}</span>
                        <span class="inv-stat-lbl">Overdue</span>
                    </div>
                    <div class="inv-stat today">
                        <i class="fa fa-clock"></i>
                        <span class="inv-stat-num">{{ $todayInvoicesCount ?? 0 }}</span>
                        <span class="inv-stat-lbl">Due Today</span>
                    </div>
                    <div class="inv-stat upcoming">
                        <i class="fa fa-calendar-alt"></i>
                        <span class="inv-stat-num">{{ $upcomingInvoicesCount ?? 0 }}</span>
                        <span class="inv-stat-lbl">Upcoming</span>
                    </div>
                    <div class="inv-stat paid">
                        <i class="fa fa-check-circle"></i>
                        <span class="inv-stat-num">{{ $paidThisMonthCount }}</span>
                        <span class="inv-stat-lbl">Paid</span>
                    </div>
                </div>
                <div class="inv-list">
                    @forelse($invoiceDueDates ?? collect() as $invoice)
                        @php
                            $isOverdue = $invoice->is_overdue;
                            $isToday = $invoice->due_date && $invoice->due_date->isToday();
                            $isTomorrow = $invoice->due_date && $invoice->due_date->isTomorrow();
                            $isPartialPaid = $invoice->status === 'partial_paid';
                            $daysUntilDue = now()->startOfDay()->diffInDays($invoice->due_date->startOfDay(), false);
                        @endphp
                        <div class="inv-card {{ $isOverdue ? 'is-overdue' : ($isToday ? 'is-today' : '') }}">
                            <div class="inv-card-row">
                                <a href="{{ route('billing.invoices.show', $invoice->id) }}" class="inv-card-link">
                                    <div class="inv-date {{ $isOverdue ? 'overdue' : ($isPartialPaid ? 'partial' : '') }}">
                                        <span class="inv-day">{{ $invoice->due_date->format('d') }}</span>
                                        <span class="inv-mon">{{ strtoupper($invoice->due_date->format('M')) }}</span>
                                    </div>
                                    <div class="inv-info">
                                        <div class="inv-top-row">
                                            <span class="inv-num">{{ $invoice->document_number }}</span>
                                            <span class="inv-badge {{ $isOverdue ? 'overdue' : ($isToday ? 'today' : ($isTomorrow ? 'tomorrow' : ($daysUntilDue <= 7 ? 'upcoming' : ''))) }}">
                                                @if($isOverdue)
                                                    {{ abs($daysUntilDue) }}d overdue
                                                @elseif($isToday)
                                                    Due Today
                                                @elseif($isTomorrow)
                                                    Tomorrow
                                                @elseif($daysUntilDue <= 7)
                                                    {{ $daysUntilDue }}d left
                                                @else
                                                    {{ $invoice->due_date->format('d M') }}
                                                @endif
                                            </span>
                                        </div>
                                        <span class="inv-client">{{ $invoice->client->name ?? ($invoice->lead->name ?? 'No Client') }}@if($invoice->project) - {{ Str::limit($invoice->project->name, 20) }}@endif</span>
                                        <span class="inv-amt">TZS {{ number_format($invoice->balance_amount, 0) }}@if($isPartialPaid) <span class="inv-partial">Partial</span>@endif</span>
                                    </div>
                                </a>
                                <div class="inv-actions">
                                    <button type="button" class="inv-btn-attend" onclick="openAttendModal({{ $invoice->id }})" title="Mark as Paid / Reschedule">
                                        <i class="fa fa-check-circle"></i>
                                    </button>
                                    <a href="{{ $invoice->google_calendar_link }}" target="_blank" class="inv-btn-cal" title="Add to Google Calendar">
                                        <i class="fa-solid fa-calendar-plus"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="no-invoices">
                            <i class="fa fa-check-circle"></i>
                            <p>All invoices are paid - Great job!</p>
                        </div>
                    @endforelse
                </div>
                <div class="followup-footer">
                    <a href="{{ route('billing.invoices.index') }}" class="view-all-btn">
                        <i class="fa fa-list"></i> View All Invoices
                    </a>
                </div>
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

                <!-- Individual Project Progress (latest 5) -->
                <div class="project-progress-list">
                    @foreach($activeSchedules->take(5) as $schedule)
                        @php
                            $progressDetails = $schedule->progress_details;
                            $progressClass = $progressDetails['percentage'] >= 75 ? 'success' : ($progressDetails['percentage'] >= 50 ? 'info' : ($progressDetails['percentage'] >= 25 ? 'warning' : 'danger'));
                        @endphp
                        <a href="{{ route('project-schedules.show', $schedule) }}" class="project-progress-item">
                            <div class="project-progress-header">
                                <div class="project-info">
                                    <span class="project-name">{{ $schedule->display_name }}</span>
                                    <span class="project-client">{{ $schedule->client ? \Illuminate\Support\Str::limit($schedule->client->first_name . ' ' . $schedule->client->last_name, 30) : '' }}</span>
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
                        <a href="{{ route('export.followups.calendar') }}" class="gcal-export-btn" title="Export all pending follow-ups to Google Calendar">
                            <i class="fa-brands fa-google"></i> Export to Google Calendar
                        </a>
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
                                $dayInvoices = isset($calendarInvoices) ? ($calendarInvoices[$dateKey] ?? collect()) : collect();
                                $dayFmFollowups = $calendarFmFollowups[$dateKey] ?? collect();
                                $isToday = $isViewingCurrentMonth && $day == $today;
                                $currentDate = $calendarDate->copy()->setDay($day);
                                $isPast = $currentDate->isPast() && !$currentDate->isToday();
                                $hasFollowups = $dayFollowups->count() > 0;
                                $hasActivities = $dayActivities->count() > 0;
                                $hasInvoices = $dayInvoices->count() > 0;
                                $hasFmFollowups = $dayFmFollowups->count() > 0;
                                $hasEvents = $hasFollowups || $hasActivities || $hasInvoices || $hasFmFollowups;
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
                                        {{-- Invoice dots (gold/orange) --}}
                                        @foreach($dayInvoices->take(2) as $inv)
                                            @php
                                                $invDotClass = $inv->status === 'paid' ? 'completed' : ($isPast && !$isToday ? 'invoice-overdue' : 'invoice');
                                            @endphp
                                            <span class="event-dot {{ $invDotClass }}"></span>
                                        @endforeach
                                        {{-- FM Follow-up dots (teal) --}}
                                        @foreach($dayFmFollowups->take(2) as $fm)
                                            <span class="event-dot fm-followup"></span>
                                        @endforeach
                                        @if(($dayFollowups->count() + $dayActivities->count() + $dayInvoices->count() + $dayFmFollowups->count()) > 4)
                                            <span class="more-events">+{{ ($dayFollowups->count() + $dayActivities->count() + $dayInvoices->count() + $dayFmFollowups->count()) - 4 }}</span>
                                        @endif
                                    </div>
                                    {{-- Hover Tooltip --}}
                                    <div class="calendar-tooltip">
                                        <div class="tooltip-header">
                                            <strong>{{ $currentDate->format('d M Y') }}</strong>
                                            <span class="tooltip-badge">{{ $dayFollowups->count() + $dayActivities->count() + $dayInvoices->count() + $dayFmFollowups->count() }} events</span>
                                        </div>
                                        @if($hasFollowups)
                                            <div class="tooltip-section">
                                                <div class="tooltip-section-title"><i class="fa fa-phone-alt"></i> Follow-ups</div>
                                                @foreach($dayFollowups->take(3) as $fu)
                                                    <div class="tooltip-item followup-item {{ $fu->status }}">
                                                        <span class="item-status {{ $fu->status === 'completed' ? 'completed' : ($isPast ? 'overdue' : 'pending') }}"></span>
                                                        <span class="item-text">{{ Str::limit($fu->lead->name, 20) }}</span>
                                                        @if($fu->status !== 'completed')
                                                        <a href="{{ $fu->google_calendar_link }}" target="_blank" class="tooltip-gcal" title="Add to Google Calendar" onclick="event.stopPropagation();">
                                                            <i class="fa-solid fa-calendar-plus"></i>
                                                        </a>
                                                        @endif
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
                                        @if($hasInvoices)
                                            <div class="tooltip-section">
                                                <div class="tooltip-section-title"><i class="fa fa-file-invoice-dollar"></i> Invoices Due</div>
                                                @foreach($dayInvoices->take(3) as $inv)
                                                    <div class="tooltip-item invoice-item {{ $inv->status }}">
                                                        <span class="item-status {{ $inv->status === 'paid' ? 'completed' : ($isPast ? 'overdue' : 'invoice') }}"></span>
                                                        <span class="item-code">{{ $inv->document_number }}</span>
                                                        <span class="item-text">TZS {{ number_format($inv->balance_amount, 0) }}</span>
                                                        @if($inv->status !== 'paid')
                                                        <a href="{{ $inv->google_calendar_link }}" target="_blank" class="tooltip-gcal" title="Add to Google Calendar" onclick="event.stopPropagation();">
                                                            <i class="fa-solid fa-calendar-plus"></i>
                                                        </a>
                                                        @endif
                                                    </div>
                                                @endforeach
                                                @if($dayInvoices->count() > 3)
                                                    <div class="tooltip-more">+{{ $dayInvoices->count() - 3 }} more</div>
                                                @endif
                                            </div>
                                        @endif
                                        @if($hasFmFollowups)
                                            <div class="tooltip-section">
                                                <div class="tooltip-section-title"><i class="fa fa-map-marked-alt"></i> FM Follow-ups</div>
                                                @foreach($dayFmFollowups->take(3) as $fm)
                                                    <div class="tooltip-item">
                                                        <span class="item-status fm-followup"></span>
                                                        <span class="item-text">{{ Str::limit($fm->business_name, 22) }}</span>
                                                        <a href="{{ route('field_marketing.sessions.show', $fm->session_id) }}" target="_blank" class="tooltip-gcal" title="View Session" onclick="event.stopPropagation();">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                    </div>
                                                @endforeach
                                                @if($dayFmFollowups->count() > 3)
                                                    <div class="tooltip-more">+{{ $dayFmFollowups->count() - 3 }} more</div>
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
                        <span class="legend-dot invoice"></span>
                        <span>Invoice</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot completed"></span>
                        <span>Done</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot overdue"></span>
                        <span>Overdue</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot fm-followup"></span>
                        <span>FM Follow-up</span>
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
                                <div class="today-item-wrapper {{ $tfu->status === 'completed' ? 'completed' : '' }}">
                                    <a href="{{ route('leads.show', $tfu->lead->id) }}" class="today-item">
                                        <span class="lead-name">{{ $tfu->lead->name }}</span>
                                        <span class="lead-action">{{ Str::limit($tfu->next_step ?: 'Follow up', 30) }}</span>
                                        @if($tfu->status === 'completed')
                                            <span class="completed-badge"><i class="fa fa-check"></i> Done</span>
                                        @endif
                                    </a>
                                    @if($tfu->status !== 'completed')
                                    <a href="{{ $tfu->google_calendar_link }}" target="_blank" class="gcal-btn" title="Add to Google Calendar">
                                        <i class="fa-solid fa-calendar-plus"></i>
                                    </a>
                                    @endif
                                </div>
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
                                    <span class="lead-action">{{ $tact->schedule->display_name }}</span>
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

            {{-- Hidden for now — will be re-enabled later
            <!-- Project Status -->
            <div class="dashboard-section projects-status">
                <div class="section-header">
                    <h2><i class="fa fa-building"></i>Project Status</h2>
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
                    <h2><i class="fa fa-stream"></i>Recent Activities</h2>
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
                    <h2><i class="fa fa-chart-line"></i>Financial Analytics</h2>
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
            --}}
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
        /* ── Welcome Banner ─────────────────────────────── */
        .dashboard-welcome {
            background: linear-gradient(135deg, #1565c0 0%, #0277bd 40%, #00897b 100%);
            border-radius: 20px;
            padding: 2rem 2.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(21,101,192,0.25);
            min-height: 130px;
        }

        /* geometric decoration shapes */
        .dw-shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.08;
            pointer-events: none;
        }
        .dw-shape-1 {
            width: 220px; height: 220px;
            background: #fff;
            top: -70px; right: 120px;
        }
        .dw-shape-2 {
            width: 140px; height: 140px;
            background: #fff;
            bottom: -50px; right: 60px;
            opacity: 0.06;
        }
        .dw-shape-3 {
            width: 80px; height: 80px;
            background: #fff;
            top: 20px; left: 48%;
            opacity: 0.05;
        }

        /* left side */
        .dw-left {
            position: relative;
            z-index: 1;
            flex: 1;
        }
        .dw-greeting-row {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 0.35rem;
        }
        .dw-greeting-icon { font-size: 1.25rem; line-height: 1; }
        .dw-greeting-text {
            font-size: 0.95rem;
            font-weight: 500;
            opacity: 0.85;
            letter-spacing: 0.02em;
        }
        .dw-name {
            font-size: 1.75rem;
            font-weight: 800;
            margin: 0 0 0.4rem 0;
            line-height: 1.2;
            letter-spacing: -0.01em;
            color: #fff;
            text-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .dw-subtitle {
            font-size: 0.92rem;
            opacity: 0.78;
            margin: 0;
        }

        /* right side */
        .dw-right {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.75rem;
        }
        .dw-date-card {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.25);
            backdrop-filter: blur(6px);
            border-radius: 12px;
            padding: 0.6rem 1.1rem;
            text-align: center;
            min-width: 130px;
        }
        .dw-date-day {
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.2;
        }
        .dw-date-month {
            font-size: 0.78rem;
            opacity: 0.8;
        }
        .dw-date-time {
            font-size: 1.15rem;
            font-weight: 800;
            margin-top: 0.2rem;
            letter-spacing: 0.04em;
        }
        .dw-role-badge {
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 20px;
            padding: 0.3rem 0.9rem;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            white-space: nowrap;
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
        /* ── Metric Cards ─────────────────────────────── */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.1rem;
            margin-bottom: 1.5rem;
        }

        .metric-card {
            background: #fff;
            border-radius: 16px;
            padding: 0;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            border: 1px solid #e9ecef;
            transition: transform 0.22s ease, box-shadow 0.22s ease;
            overflow: hidden;
            position: relative;
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.13);
        }

        /* coloured top accent bar per card type */
        .metric-card::before {
            content: '';
            display: block;
            height: 4px;
            width: 100%;
        }
        .metric-card.financial::before { background: linear-gradient(90deg,#1565c0,#42a5f5); }
        .metric-card.projects::before  { background: linear-gradient(90deg,#2e7d32,#66bb6a); }
        .metric-card.team::before       { background: linear-gradient(90deg,#6a1b9a,#ab47bc); }
        .metric-card.budget::before     { background: linear-gradient(90deg,#e65100,#ffa726); }

        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 1.1rem 1.25rem 0;
        }

        .metric-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .metric-card.financial .metric-icon { background: linear-gradient(135deg,#1565c0,#42a5f5); }
        .metric-card.projects .metric-icon  { background: linear-gradient(135deg,#2e7d32,#66bb6a); }
        .metric-card.team .metric-icon       { background: linear-gradient(135deg,#6a1b9a,#ab47bc); }
        .metric-card.budget .metric-icon     { background: linear-gradient(135deg,#e65100,#ffa726); }

        .metric-trend {
            display: flex;
            align-items: center;
            gap: 0.2rem;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .metric-trend.up   { background: #e8f5e9; color: #2e7d32; }
        .metric-trend.down { background: #fce4ec; color: #c62828; }

        .metric-badge {
            background: #f1f3f5;
            color: #495057;
            padding: 0.2rem 0.65rem;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .metric-progress {
            background: #f1f3f5;
            color: #495057;
            padding: 0.2rem 0.65rem;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .metric-content {
            padding: 0.65rem 1.25rem 1.1rem;
        }

        .metric-content h3 {
            font-size: 0.72rem;
            color: #868e96;
            margin: 0 0 0.3rem 0;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .metric-value {
            font-size: 1.45rem;
            font-weight: 800;
            color: #212529;
            margin-bottom: 0.2rem;
            line-height: 1.1;
        }

        .metric-period {
            font-size: 0.74rem;
            color: #868e96;
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
            gap: 0.3rem;
            font-size: 0.82rem;
            color: #495057;
            font-weight: 500;
        }

        .progress-bar {
            background: #e9ecef;
            height: 6px;
            border-radius: 4px;
            overflow: hidden;
            margin: 0.55rem 0;
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

        .followup-calendar .gcal-export-btn {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            background: #4285f4;
            color: white;
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 1rem;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(66, 133, 244, 0.3);
        }

        .followup-calendar .gcal-export-btn:hover {
            background: #1a73e8;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(66, 133, 244, 0.4);
        }

        .followup-calendar .gcal-export-btn i {
            font-size: 0.85rem;
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

        .tooltip-item .item-status.fm-followup {
            background: #17a2b8;
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

        .tooltip-gcal {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            background: #4285f4;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-size: 10px;
            flex-shrink: 0;
            opacity: 0.8;
            transition: all 0.2s ease;
        }

        .tooltip-gcal:hover {
            opacity: 1;
            transform: scale(1.1);
            color: white;
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

        .event-dot.fm-followup {
            background: #17a2b8;
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

        .calendar-legend .legend-dot.fm-followup {
            background: #17a2b8;
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

        .today-item-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            border-radius: 8px;
            padding-right: 0.5rem;
        }

        .today-item-wrapper.completed {
            opacity: 0.7;
        }

        .today-item {
            display: flex;
            flex: 1;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0.75rem;
            background: transparent;
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

        .gcal-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            background: #4285f4;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .gcal-btn:hover {
            background: #1a73e8;
            color: white;
            transform: scale(1.1);
        }

        .gcal-btn i {
            font-size: 12px;
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

        /* ===== Invoice Due Dates Section ===== */
        .invoice-due-todo {
            border-left: 4px solid #f39c12;
        }
        .invoice-due-todo .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .invoice-due-todo .section-header h2 { color: #e67e22; }
        .invoice-due-todo .section-header h2 i { color: #f39c12; }
        .invoice-due-todo .section-header .gcal-export-btn {
            font-size: 0.75rem;
            padding: 0.35rem 0.75rem;
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        .invoice-due-todo .section-header .gcal-export-btn:hover {
            background: linear-gradient(135deg, #e67e22, #d35400);
        }

        /* Stat grid - always 4 columns */
        .inv-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .inv-stat {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.6rem 0.25rem;
            border-radius: 10px;
            color: white;
            text-align: center;
            transition: transform 0.15s ease;
        }
        .inv-stat:hover { transform: translateY(-1px); }
        .inv-stat i { font-size: 0.9rem; margin-bottom: 2px; opacity: 0.85; }
        .inv-stat-num { font-size: 1.3rem; font-weight: 700; line-height: 1.2; }
        .inv-stat-lbl { font-size: 0.55rem; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9; }
        .inv-stat.overdue { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .inv-stat.today { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .inv-stat.upcoming { background: linear-gradient(135deg, #3498db, #2980b9); }
        .inv-stat.paid { background: linear-gradient(135deg, #27ae60, #229954); }

        /* Invoice list - scrollable */
        .inv-list {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            max-height: 480px;
            overflow-y: auto;
            padding-right: 2px;
        }
        .inv-list::-webkit-scrollbar { width: 4px; }
        .inv-list::-webkit-scrollbar-track { background: transparent; }
        .inv-list::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }
        .inv-list::-webkit-scrollbar-thumb:hover { background: #ccc; }

        /* Invoice card - single row */
        .inv-card {
            background: white;
            border-radius: 8px;
            border: 1px solid #eee;
            transition: all 0.15s ease;
        }
        .inv-card:hover {
            border-color: rgba(243, 156, 18, 0.35);
            box-shadow: 0 2px 8px rgba(243, 156, 18, 0.1);
        }
        .inv-card.is-overdue { border-left: 3px solid #e74c3c; }
        .inv-card.is-today { border-left: 3px solid #f39c12; }

        .inv-card-row {
            display: flex;
            align-items: center;
        }

        .inv-card-link {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.5rem 0.6rem;
            text-decoration: none;
            color: inherit;
            flex: 1;
            min-width: 0;
        }
        .inv-card-link:hover { background: rgba(0,0,0,0.01); border-radius: 8px 0 0 8px; }

        /* Date badge - compact */
        .inv-date {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: linear-gradient(135deg, #f39c12, #e67e22);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }
        .inv-date.overdue { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .inv-date.partial { background: linear-gradient(135deg, #f39c12, #e74c3c); }
        .inv-day { font-size: 1.05rem; font-weight: 700; line-height: 1; }
        .inv-mon { font-size: 0.5rem; font-weight: 600; opacity: 0.9; margin-top: 1px; }

        /* Info area */
        .inv-info {
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }
        .inv-top-row {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 1px;
        }
        .inv-num {
            font-weight: 700;
            font-size: 0.8rem;
            color: #2c3e50;
            white-space: nowrap;
        }
        .inv-badge {
            font-size: 0.55rem;
            padding: 0.1rem 0.35rem;
            border-radius: 8px;
            font-weight: 600;
            white-space: nowrap;
            background: #eee;
            color: #666;
            flex-shrink: 0;
        }
        .inv-badge.overdue { background: #e74c3c; color: white; }
        .inv-badge.today { background: #f39c12; color: white; }
        .inv-badge.tomorrow { background: #3498db; color: white; }
        .inv-badge.upcoming { background: #95a5a6; color: white; }

        .inv-client {
            display: block;
            font-size: 0.68rem;
            color: #95a5a6;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .inv-amt {
            display: block;
            font-size: 0.75rem;
            color: #27ae60;
            font-weight: 600;
        }
        .inv-partial {
            font-size: 0.5rem;
            background: #e74c3c;
            color: white;
            padding: 0.1rem 0.25rem;
            border-radius: 3px;
            margin-left: 0.25rem;
            font-weight: 500;
        }

        /* Actions - inline right side */
        .inv-actions {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            padding: 0.4rem 0.5rem;
            flex-shrink: 0;
            border-left: 1px dashed rgba(0,0,0,0.06);
        }
        .inv-btn-attend {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            border: none;
            background: #27ae60;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            transition: all 0.15s ease;
        }
        .inv-btn-attend:hover {
            background: #219a52;
            transform: scale(1.05);
        }
        .inv-btn-cal {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: #4285f4;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 0.7rem;
            transition: all 0.15s ease;
        }
        .inv-btn-cal:hover {
            background: #3367d6;
            color: white;
            transform: scale(1.05);
            color: white;
        }

        .btn-calendar i {
            font-size: 13px;
        }

        /* No invoices message */
        .no-invoices {
            text-align: center;
            padding: 2rem;
            color: #27ae60;
        }

        .no-invoices i {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
            opacity: 0.6;
        }

        .no-invoices p {
            font-size: 0.9rem;
            margin: 0;
            font-weight: 500;
        }

        .invoice-actions {
            display: flex;
            gap: 0.5rem;
            margin-left: auto;
        }

        .btn-attend {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: none;
            background: #27ae60;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .btn-attend:hover {
            background: #219a52;
            transform: scale(1.1);
        }

        .btn-attend i {
            font-size: 11px;
        }

        .gcal-btn-sm {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #4285f4;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .gcal-btn-sm:hover {
            background: #3367d6;
            transform: scale(1.1);
            color: white;
        }

        .gcal-btn-sm i {
            font-size: 11px;
        }

        /* Calendar Invoice Dot */
        .event-dot.invoice {
            background: #f39c12;
        }

        .event-dot.invoice-overdue {
            background: #e74c3c;
            animation: pulse 1.5s ease-in-out infinite;
        }

        .legend-dot.invoice {
            background: #f39c12;
        }

        /* Tooltip Invoice Item */
        .tooltip-item.invoice-item .item-status.invoice {
            background: #f39c12;
        }

        .tooltip-item.invoice-item .item-code {
            font-weight: 600;
            color: #f39c12;
        }

        /* Attend Modal Styles */
        .attend-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .attend-modal-overlay.active {
            display: flex;
        }

        .attend-modal {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .attend-modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--wajenzi-gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .attend-modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--wajenzi-gray-900);
        }

        .attend-modal-close {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: var(--wajenzi-gray-100);
            color: var(--wajenzi-gray-600);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .attend-modal-close:hover {
            background: var(--wajenzi-gray-200);
            color: var(--wajenzi-gray-900);
        }

        .attend-modal-body {
            padding: 1.5rem;
        }

        .invoice-summary {
            background: var(--wajenzi-gray-50);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .invoice-summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--wajenzi-gray-200);
        }

        .invoice-summary-row:last-child {
            border-bottom: none;
        }

        .invoice-summary-row .label {
            color: var(--wajenzi-gray-600);
            font-size: 0.875rem;
        }

        .invoice-summary-row .value {
            font-weight: 600;
            color: var(--wajenzi-gray-900);
        }

        .invoice-summary-row .value.amount {
            color: #27ae60;
        }

        .invoice-summary-row .value.overdue {
            color: #e74c3c;
        }

        .action-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .action-tab {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid var(--wajenzi-gray-200);
            border-radius: 8px;
            background: white;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s ease;
        }

        .action-tab:hover {
            border-color: var(--wajenzi-blue-primary);
        }

        .action-tab.active {
            border-color: var(--wajenzi-blue-primary);
            background: rgba(74, 144, 226, 0.1);
        }

        .action-tab i {
            display: block;
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }

        .action-tab.paid i { color: #27ae60; }
        .action-tab.partial i { color: #f39c12; }
        .action-tab.reschedule i { color: #3498db; }

        .action-tab span {
            font-size: 0.875rem;
            font-weight: 500;
        }

        .action-form {
            display: none;
        }

        .action-form.active {
            display: block;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--wajenzi-gray-700);
            margin-bottom: 0.5rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--wajenzi-gray-300);
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--wajenzi-blue-primary);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        .attend-modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--wajenzi-gray-200);
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn-cancel {
            padding: 0.75rem 1.5rem;
            border: 1px solid var(--wajenzi-gray-300);
            border-radius: 8px;
            background: white;
            color: var(--wajenzi-gray-700);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-cancel:hover {
            background: var(--wajenzi-gray-100);
        }

        .btn-submit {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            background: var(--wajenzi-blue-primary);
            color: white;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-submit:hover {
            background: var(--wajenzi-blue-dark);
        }

        .btn-submit:disabled {
            background: var(--wajenzi-gray-400);
            cursor: not-allowed;
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
                grid-template-columns: repeat(2, 1fr);
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
                gap: 1.25rem;
                min-height: unset;
            }

            .dw-right { align-items: center; }

            .dw-name { font-size: 1.3rem; }
            .dw-date-card { min-width: unset; }

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

        /* ============================================================
           Design Refresh — modern visual layer (overrides above)
           Goals: cleaner cards, tighter typography, mobile-first grid,
           reduced visual noise, accessible contrast.
           ============================================================ */
        :root {
            --wd-radius: 14px;
            --wd-radius-sm: 10px;
            --wd-shadow-1: 0 1px 2px rgba(15, 23, 42, 0.04), 0 1px 1px rgba(15, 23, 42, 0.03);
            --wd-shadow-2: 0 6px 16px -8px rgba(15, 23, 42, 0.12), 0 2px 6px -2px rgba(15, 23, 42, 0.06);
            --wd-shadow-hover: 0 12px 28px -12px rgba(15, 23, 42, 0.18);
            --wd-border: #E5E7EB;
            --wd-surface: #FFFFFF;
            --wd-bg: #F7F8FA;
            --wd-ink: #0F172A;
            --wd-ink-2: #334155;
            --wd-mute: #64748B;
            --wd-brand: #2563EB;
            --wd-brand-2: #1E40AF;
            --wd-success: #16A34A;
            --wd-warning: #D97706;
            --wd-danger: #DC2626;
            --wd-info: #0EA5E9;
        }

        /* Container */
        .wajenzi-dashboard {
            padding: clamp(1rem, 2.5vw, 2rem);
            background: var(--wd-bg);
            font-family: 'Nunito Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, system-ui, sans-serif;
        }
        .wajenzi-dashboard,
        .wajenzi-dashboard * {
            -webkit-font-smoothing: antialiased;
        }

        .welcome-actions { gap: 0.6rem; flex-wrap: wrap; }
        .action-btn {
            padding: 0.6rem 1.1rem;
            border-radius: var(--wd-radius-sm);
            font-weight: 600;
            font-size: 0.875rem;
            letter-spacing: 0.005em;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }
        .action-btn.primary {
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.24);
            color: #fff;
        }
        .action-btn.primary:hover {
            background: rgba(255, 255, 255, 0.24);
            transform: translateY(-1px);
        }
        .action-btn.secondary {
            background: #fff;
            color: var(--wd-brand-2);
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
        }
        .action-btn.secondary:hover { transform: translateY(-1px); box-shadow: var(--wd-shadow-hover); }

        /* Dashboard Grid — auto-fit responsive */
        .dashboard-grid {
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 360px), 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .dashboard-section {
            background: var(--wd-surface);
            border: 1px solid var(--wd-border);
            border-radius: var(--wd-radius);
            padding: clamp(1rem, 2vw, 1.4rem);
            box-shadow: var(--wd-shadow-1);
            transition: box-shadow 0.15s ease;
        }
        .dashboard-section:hover { box-shadow: var(--wd-shadow-2); }

        /* Section Header */
        .section-header {
            margin-bottom: 1rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid #F1F5F9;
        }
        .section-header h2 {
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: -0.005em;
            color: var(--wd-ink);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .section-header h2 i { font-size: 0.95rem; color: var(--wd-brand); }
        .section-count {
            background: var(--wd-brand);
            color: #fff;
            padding: 0.18rem 0.6rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            box-shadow: 0 2px 6px -2px rgba(37, 99, 235, 0.4);
        }

        /* View All Button */
        .view-all-btn {
            background: #F8FAFC;
            border: 1px solid var(--wd-border);
            color: var(--wd-ink-2);
            padding: 0.5rem 0.95rem;
            border-radius: var(--wd-radius-sm);
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.15s ease;
        }
        .view-all-btn:hover {
            background: var(--wd-brand);
            color: #fff;
            border-color: var(--wd-brand);
            transform: translateY(-1px);
        }

        /* Approval / Followup / Project items — unified card style */
        .approval-list, .project-list, .followup-list, .today-list {
            gap: 0.6rem;
        }
        .approval-item, .project-item, .followup-item, .today-item {
            border: 1px solid var(--wd-border);
            border-radius: var(--wd-radius-sm);
            padding: 0.85rem 0.95rem;
            transition: background 0.12s ease, border-color 0.12s ease, transform 0.12s ease;
        }
        .approval-item:hover, .project-item:hover, .followup-item:hover, .today-item:hover {
            background: #F8FAFC;
            border-color: #C7D2FE;
            transform: translateX(2px);
        }
        .approval-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            font-size: 1rem;
            box-shadow: 0 3px 8px -3px rgba(15, 23, 42, 0.2);
        }
        .approval-icon.blue   { background: var(--wd-brand); }
        .approval-icon.green  { background: var(--wd-success); }
        .approval-icon.orange { background: var(--wd-warning); }
        .approval-icon.purple { background: #7C3AED; }
        .approval-icon.indigo { background: #4F46E5; }
        .approval-icon.red    { background: var(--wd-danger); }
        .approval-icon.teal   { background: #14B8A6; }
        .approval-name { font-size: 0.92rem; font-weight: 600; color: var(--wd-ink); }
        .approval-desc { font-size: 0.78rem; color: var(--wd-mute); }
        .approval-badge {
            background: var(--wd-danger);
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.22rem 0.55rem;
            min-width: 22px;
            box-shadow: 0 2px 6px -2px rgba(220, 38, 38, 0.5);
        }

        /* Followup stats row */
        .followup-stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 0.5rem;
            margin-bottom: 0.9rem;
        }
        .followup-stat-card {
            background: #F8FAFC;
            border: 1px solid var(--wd-border);
            border-radius: var(--wd-radius-sm);
            padding: 0.7rem;
            display: flex;
            align-items: center;
            gap: 0.55rem;
            transition: transform 0.12s ease, box-shadow 0.12s ease;
        }
        .followup-stat-card:hover { transform: translateY(-1px); box-shadow: var(--wd-shadow-1); }
        .followup-stat-card.overdue   { background: #FEF2F2; border-color: #FECACA; }
        .followup-stat-card.today     { background: #FFFBEB; border-color: #FDE68A; }
        .followup-stat-card.upcoming  { background: #EFF6FF; border-color: #BFDBFE; }
        .followup-stat-card.completed { background: #F0FDF4; border-color: #BBF7D0; }
        .followup-stat-card .stat-icon {
            width: 30px; height: 30px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem;
            color: #fff;
            flex-shrink: 0;
        }
        .followup-stat-card.overdue   .stat-icon { background: var(--wd-danger); }
        .followup-stat-card.today     .stat-icon { background: var(--wd-warning); }
        .followup-stat-card.upcoming  .stat-icon { background: var(--wd-brand); }
        .followup-stat-card.completed .stat-icon { background: var(--wd-success); }
        .followup-stat-card .stat-number { font-size: 1.1rem; font-weight: 700; line-height: 1; color: var(--wd-ink); }
        .followup-stat-card .stat-label  { font-size: 0.7rem; color: var(--wd-mute); font-weight: 600; }

        /* Followup item (compact) */
        .followup-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }
        .followup-item.overdue   { border-left: 3px solid var(--wd-danger); }
        .followup-item.today     { border-left: 3px solid var(--wd-warning); }
        .followup-item.tomorrow  { border-left: 3px solid var(--wd-info); }
        .followup-item.completed { opacity: 0.65; }
        .followup-item.cancelled { opacity: 0.5; text-decoration: line-through; }
        .followup-date-badge {
            min-width: 44px;
            background: #F1F5F9;
            border-radius: var(--wd-radius-sm);
            padding: 0.35rem 0.45rem;
            text-align: center;
            line-height: 1.1;
            color: var(--wd-ink-2);
        }
        .followup-date-badge .day   { display: block; font-size: 1rem; font-weight: 700; color: var(--wd-ink); }
        .followup-date-badge .month { display: block; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--wd-mute); }
        .followup-content { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 0.18rem; }
        .followup-lead-name { font-size: 0.88rem; font-weight: 600; color: var(--wd-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .followup-details   { font-size: 0.76rem; color: var(--wd-mute); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .followup-assignee  { font-size: 0.7rem; color: var(--wd-mute); }
        .followup-status .status-label {
            font-size: 0.68rem;
            font-weight: 700;
            padding: 0.22rem 0.55rem;
            border-radius: 999px;
            white-space: nowrap;
        }
        .status-label.overdue   { background: #FEE2E2; color: #991B1B; }
        .status-label.today     { background: #FEF3C7; color: #92400E; }
        .status-label.tomorrow  { background: #DBEAFE; color: #1E40AF; }
        .status-label.completed { background: #D1FAE5; color: #065F46; }
        .status-label.cancelled { background: #F1F5F9; color: var(--wd-mute); }
        .status-label.upcoming  { background: #F1F5F9; color: var(--wd-ink-2); }

        .no-followups, .no-invoices {
            text-align: center;
            padding: 2rem 1rem;
            color: var(--wd-mute);
        }
        .no-followups i, .no-invoices i {
            font-size: 2.2rem;
            color: #CBD5E1;
            margin-bottom: 0.5rem;
            display: block;
        }

        /* Quick actions */
        .quick-actions {
            background: var(--wd-surface);
            border: 1px solid var(--wd-border);
            border-radius: var(--wd-radius);
            padding: 1.25rem;
            box-shadow: var(--wd-shadow-1);
        }
        .quick-actions h3 {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--wd-ink);
            margin: 0 0 0.85rem 0;
        }
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.6rem;
        }
        .quick-action-btn {
            background: #F8FAFC;
            border: 1px solid var(--wd-border);
            border-radius: var(--wd-radius-sm);
            padding: 1rem 0.75rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.45rem;
            color: var(--wd-ink-2);
            font-weight: 600;
            font-size: 0.82rem;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .quick-action-btn i { font-size: 1.15rem; color: var(--wd-brand); }
        .quick-action-btn:hover {
            background: var(--wd-brand);
            color: #fff;
            border-color: var(--wd-brand);
            transform: translateY(-2px);
            box-shadow: var(--wd-shadow-hover);
        }
        .quick-action-btn:hover i { color: #fff; }

        /* Mobile-first refinements */
        @media (max-width: 1024px) {
            .metrics-grid { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
            .dashboard-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .dashboard-welcome {
                flex-direction: column;
                text-align: left;
                align-items: flex-start;
                gap: 1rem;
            }
            .dw-right { align-items: flex-start; flex-direction: row; flex-wrap: wrap; }
            .dw-name { font-size: 1.3rem; }
            .welcome-actions { width: 100%; }
            .action-btn { flex: 1; justify-content: center; }
            .metrics-grid { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.75rem; }
            .metric-header { padding: 0.9rem 1rem 0; }
            .metric-content { padding: 0.5rem 1rem 0.9rem; }
            .metric-value { font-size: 1.15rem; }
            .followup-stats-row { grid-template-columns: repeat(2, 1fr); }
            .section-header { flex-wrap: wrap; }
            .section-header h2 { font-size: 0.95rem; }
        }
        @media (max-width: 480px) {
            .metrics-grid { grid-template-columns: 1fr 1fr; }
            .followup-item { flex-wrap: wrap; }
            .followup-status { width: 100%; margin-top: 0.35rem; }
            .quick-action-btn { padding: 0.85rem 0.5rem; font-size: 0.75rem; }
        }

        /* Reduced motion */
        @media (prefers-reduced-motion: reduce) {
            .wajenzi-dashboard *,
            .wajenzi-dashboard *::before,
            .wajenzi-dashboard *::after {
                transition: none !important;
                animation: none !important;
            }
        }

        /* ── Section Visual Identity ───────────────────────────────────
           Each dashboard section gets a 3px left accent + tinted header
           strip to visually separate sections at a glance.
        ──────────────────────────────────────────────────────────────── */
        .dashboard-section {
            border-radius: var(--wd-radius, 14px);
            border: 1px solid var(--wd-border, #E5E7EB);
            box-shadow: 0 1px 3px rgba(15,23,42,0.05), 0 1px 2px rgba(15,23,42,0.03);
            overflow: hidden;
            padding: 0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.9rem 1.15rem 0.85rem;
            margin-bottom: 0;
            border-bottom: 1px solid #F1F5F9;
            background: #FAFBFC;
        }

        .section-header h2 {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--wd-ink, #0F172A);
            margin: 0;
            letter-spacing: -0.005em;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .section-header h2 i { font-size: 0.88rem; }

        /* Accent left border per section type */
        .dashboard-section.approvals              { border-left: 3px solid #3B82F6; }
        .dashboard-section.followups-todo         { border-left: 3px solid #F59E0B; }
        .dashboard-section.invoice-due-todo       { border-left: 3px solid #EF4444; }
        .dashboard-section.project-progress-section { border-left: 3px solid #22C55E; }
        .dashboard-section.project-activities-todo  { border-left: 3px solid #0EA5E9; }
        .dashboard-section.followup-calendar      { border-left: 3px solid #8B5CF6; }
        .dashboard-section.projects-status        { border-left: 3px solid #3B82F6; }
        .dashboard-section.activities             { border-left: 3px solid #14B8A6; }
        .dashboard-section.team-performance       { border-left: 3px solid #F59E0B; }
        .dashboard-section.charts                 { border-left: 3px solid #3B82F6; }

        /* Tinted header strip matching accent */
        .dashboard-section.approvals              .section-header { background: #EFF6FF; border-bottom-color: #DBEAFE; }
        .dashboard-section.followups-todo         .section-header { background: #FFFBEB; border-bottom-color: #FDE68A; }
        .dashboard-section.invoice-due-todo       .section-header { background: #FEF2F2; border-bottom-color: #FECACA; }
        .dashboard-section.project-progress-section .section-header { background: #F0FDF4; border-bottom-color: #BBF7D0; }
        .dashboard-section.project-activities-todo  .section-header { background: #F0F9FF; border-bottom-color: #BAE6FD; }
        .dashboard-section.followup-calendar      .section-header { background: #F5F3FF; border-bottom-color: #DDD6FE; }
        .dashboard-section.projects-status        .section-header { background: #EFF6FF; border-bottom-color: #DBEAFE; }
        .dashboard-section.activities             .section-header { background: #F0FDFA; border-bottom-color: #99F6E4; }
        .dashboard-section.team-performance       .section-header { background: #FFFBEB; border-bottom-color: #FDE68A; }
        .dashboard-section.charts                 .section-header { background: #EFF6FF; border-bottom-color: #DBEAFE; }

        /* Section icon tint matching accent */
        .dashboard-section.approvals              .section-header h2 i { color: #3B82F6; }
        .dashboard-section.followups-todo         .section-header h2 i { color: #F59E0B; }
        .dashboard-section.invoice-due-todo       .section-header h2 i { color: #EF4444; }
        .dashboard-section.project-progress-section .section-header h2 i { color: #22C55E; }
        .dashboard-section.project-activities-todo  .section-header h2 i { color: #0EA5E9; }
        .dashboard-section.followup-calendar      .section-header h2 i { color: #8B5CF6; }
        .dashboard-section.projects-status        .section-header h2 i { color: #3B82F6; }
        .dashboard-section.activities             .section-header h2 i { color: #14B8A6; }
        .dashboard-section.team-performance       .section-header h2 i { color: #F59E0B; }
        .dashboard-section.charts                 .section-header h2 i { color: #3B82F6; }

        /* Inner content padding — all direct content children need padding since section has padding:0 */
        .dashboard-section .approval-list,
        .dashboard-section .followup-list,
        .dashboard-section .followup-stats-row,
        .dashboard-section .inv-stats-grid,
        .dashboard-section .inv-list,
        .dashboard-section .project-list,
        .dashboard-section .project-progress-list,
        .dashboard-section .department-grid,
        .dashboard-section .activity-timeline,
        .dashboard-section .chart-container,
        .dashboard-section .chart-legend,
        .dashboard-section .chart-stats,
        .dashboard-section .no-followups,
        .dashboard-section .no-invoices,
        .dashboard-section .calendar-container { padding-left: 1.1rem; padding-right: 1.1rem; }
        .dashboard-section .calendar-container { padding-top: 0.85rem; padding-bottom: 0.85rem; }

        .dashboard-section .chart-container { padding-top: 0.5rem; }
        .dashboard-section .followup-stats-row { padding-bottom: 0; padding-top: 0.85rem; }
        .dashboard-section .approval-list,
        .dashboard-section .activity-timeline,
        .dashboard-section .project-list { padding-top: 0.85rem; padding-bottom: 0.85rem; }
        .dashboard-section .inv-stats-grid { padding-top: 0.85rem; }

        .dashboard-section .followup-footer {
            padding: 0.65rem 1.1rem 0.85rem;
            border-top: 1px solid #F1F5F9;
            display: flex;
            justify-content: center;
        }

        /* Approval items — tighter, left accent bar on hover */
        .approval-item {
            padding: 0.75rem 0.9rem;
            gap: 0.75rem;
            border-radius: 10px;
        }
        .approval-item:hover {
            border-color: #BFDBFE;
            background: #EFF6FF;
            transform: translateX(2px);
        }
        .approval-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            font-size: 1rem;
            box-shadow: 0 3px 8px -3px rgba(0,0,0,0.22);
        }
        .approval-name { font-size: 0.9rem; }
        .approval-desc { font-size: 0.76rem; }
        .approval-badge {
            background: #EF4444;
            padding: 0.22rem 0.65rem;
            border-radius: 999px;
            font-size: 0.72rem;
            box-shadow: 0 2px 6px -2px rgba(239,68,68,0.45);
        }

        /* Section count badge */
        .section-count {
            background: var(--wd-brand, #2563EB);
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-size: 0.7rem;
            box-shadow: 0 2px 6px -2px rgba(37,99,235,0.4);
        }

        /* Followup stat cards — taller with more visual weight */
        .followup-stat-card {
            border-radius: 10px;
            padding: 0.75rem 0.65rem;
            gap: 0.55rem;
        }
        .followup-stat-card .stat-number { font-size: 1.35rem; font-weight: 800; }
        .followup-stat-card .stat-label  { font-size: 0.65rem; font-weight: 700; letter-spacing: 0.04em; }
        .followup-stat-card .stat-icon   { width: 28px; height: 28px; border-radius: 7px; font-size: 0.8rem; }

        /* Invoice stats — softer style matching followup-stat-cards */
        .inv-stats-grid { gap: 0.6rem; margin-bottom: 0; }
        .inv-stat {
            border-radius: 10px;
            padding: 0.75rem 0.5rem;
        }
        .inv-stat-num { font-size: 1.45rem; font-weight: 800; }
        .inv-stat-lbl { font-size: 0.6rem; letter-spacing: 0.05em; }

        /* View All button — more refined */
        .followup-footer { display: flex; justify-content: center; }
        .view-all-btn {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            color: #475569;
            padding: 0.5rem 1.1rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
            gap: 0.4rem;
            letter-spacing: 0.01em;
        }
        .view-all-btn:hover {
            background: var(--wd-brand, #2563EB);
            color: #fff;
            border-color: var(--wd-brand, #2563EB);
            box-shadow: 0 4px 12px -4px rgba(37,99,235,0.45);
        }

        /* Quick Action — per-button icon color via :nth-child */
        .quick-actions {
            border-radius: var(--wd-radius, 14px);
            border: 1px solid var(--wd-border, #E5E7EB);
            box-shadow: 0 1px 3px rgba(15,23,42,0.05);
            padding: 0;
            overflow: hidden;
        }
        .quick-actions h3 {
            padding: 0.85rem 1.15rem;
            background: #FAFBFC;
            border-bottom: 1px solid #F1F5F9;
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--wd-ink, #0F172A);
            margin: 0;
            letter-spacing: -0.005em;
        }
        .action-grid { padding: 0.85rem 1.1rem; gap: 0.65rem; }
        .quick-action-btn {
            padding: 0.95rem 0.6rem;
            border-radius: 12px;
            background: #F8FAFC;
            border: 1px solid #E5E7EB;
            font-size: 0.8rem;
        }
        .quick-action-btn i { font-size: 1.2rem; }
        .quick-action-btn:nth-child(1) i { color: #3B82F6; }
        .quick-action-btn:nth-child(2) i { color: #8B5CF6; }
        .quick-action-btn:nth-child(3) i { color: #F59E0B; }
        .quick-action-btn:nth-child(4) i { color: #0EA5E9; }
        .quick-action-btn:nth-child(5) i { color: #22C55E; }
        .quick-action-btn:nth-child(6) i { color: #64748B; }
        .quick-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px -6px rgba(15,23,42,0.18);
        }
        .quick-action-btn:nth-child(1):hover { background: #EFF6FF; border-color: #BFDBFE; color: #1E40AF; }
        .quick-action-btn:nth-child(2):hover { background: #F5F3FF; border-color: #DDD6FE; color: #5B21B6; }
        .quick-action-btn:nth-child(3):hover { background: #FFFBEB; border-color: #FDE68A; color: #92400E; }
        .quick-action-btn:nth-child(4):hover { background: #F0F9FF; border-color: #BAE6FD; color: #0C4A6E; }
        .quick-action-btn:nth-child(5):hover { background: #F0FDF4; border-color: #BBF7D0; color: #14532D; }
        .quick-action-btn:nth-child(6):hover { background: #F8FAFC; border-color: #CBD5E1; color: #334155; }
        .quick-action-btn:hover i { color: inherit; }
    </style>

    <!-- Attend Invoice Modal -->
    <div class="attend-modal-overlay" id="attendModal">
        <div class="attend-modal">
            <div class="attend-modal-header">
                <h3><i class="fa fa-file-invoice-dollar mr-2"></i>Attend to Invoice</h3>
                <button type="button" class="attend-modal-close" onclick="closeAttendModal()">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <div class="attend-modal-body">
                <div class="invoice-summary">
                    <div class="invoice-summary-row">
                        <span class="label">Invoice Number</span>
                        <span class="value" id="modal-invoice-number">-</span>
                    </div>
                    <div class="invoice-summary-row">
                        <span class="label">Client</span>
                        <span class="value" id="modal-client-name">-</span>
                    </div>
                    <div class="invoice-summary-row">
                        <span class="label">Total Amount</span>
                        <span class="value amount" id="modal-total-amount">-</span>
                    </div>
                    <div class="invoice-summary-row">
                        <span class="label">Balance Due</span>
                        <span class="value" id="modal-balance-amount">-</span>
                    </div>
                    <div class="invoice-summary-row">
                        <span class="label">Due Date</span>
                        <span class="value" id="modal-due-date">-</span>
                    </div>
                </div>

                <div class="action-tabs">
                    <div class="action-tab paid active" data-action="paid" onclick="selectAction('paid')">
                        <i class="fa fa-check-circle"></i>
                        <span>Mark Paid</span>
                    </div>
                    <div class="action-tab partial" data-action="partial" onclick="selectAction('partial')">
                        <i class="fa fa-coins"></i>
                        <span>Partial</span>
                    </div>
                    <div class="action-tab reschedule" data-action="reschedule" onclick="selectAction('reschedule')">
                        <i class="fa fa-calendar-alt"></i>
                        <span>Reschedule</span>
                    </div>
                </div>

                <form id="attendForm">
                    <input type="hidden" id="attend-invoice-id" name="invoice_id">
                    <input type="hidden" id="attend-action" name="action" value="paid">

                    <!-- Paid Form -->
                    <div class="action-form active" id="form-paid">
                        <div class="form-group">
                            <label for="paid-notes">Notes (Optional)</label>
                            <textarea id="paid-notes" name="notes" rows="3" placeholder="Add any notes about this payment..."></textarea>
                        </div>
                    </div>

                    <!-- Partial Payment Form -->
                    <div class="action-form" id="form-partial">
                        <div class="form-group">
                            <label for="partial-amount">Amount Paid (TZS)</label>
                            <input type="number" id="partial-amount" name="paid_amount" placeholder="Enter amount paid" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label for="partial-notes">Notes (Optional)</label>
                            <textarea id="partial-notes" name="notes" rows="3" placeholder="Add any notes about this partial payment..."></textarea>
                        </div>
                    </div>

                    <!-- Reschedule Form -->
                    <div class="action-form" id="form-reschedule">
                        <div class="form-group">
                            <label for="new-due-date">New Due Date</label>
                            <input type="date" id="new-due-date" name="new_due_date" min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                        </div>
                        <div class="form-group">
                            <label for="reschedule-notes">Reason for Reschedule</label>
                            <textarea id="reschedule-notes" name="notes" rows="3" placeholder="Explain why this invoice is being rescheduled..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="attend-modal-footer">
                <button type="button" class="btn-cancel" onclick="closeAttendModal()">Cancel</button>
                <button type="button" class="btn-submit" id="submitAttend" onclick="submitAttend()">
                    <i class="fa fa-check mr-1" id="submitIcon"></i>
                    <span id="submitText">Confirm</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentInvoiceId = null;

        function openAttendModal(invoiceId) {
            currentInvoiceId = invoiceId;
            document.getElementById('attend-invoice-id').value = invoiceId;

            // Fetch invoice data
            fetch(`/invoice/${invoiceId}/attend-data`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('modal-invoice-number').textContent = data.document_number;
                    document.getElementById('modal-client-name').textContent = data.client_name;
                    document.getElementById('modal-total-amount').textContent = 'TZS ' + Number(data.total_amount).toLocaleString();
                    document.getElementById('modal-balance-amount').textContent = 'TZS ' + Number(data.balance_amount).toLocaleString();
                    document.getElementById('modal-balance-amount').className = 'value ' + (data.is_overdue ? 'overdue' : '');
                    document.getElementById('modal-due-date').textContent = data.due_date_formatted;
                    document.getElementById('modal-due-date').className = 'value ' + (data.is_overdue ? 'overdue' : '');

                    // Show modal
                    document.getElementById('attendModal').classList.add('active');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load invoice data');
                });
        }

        function closeAttendModal() {
            document.getElementById('attendModal').classList.remove('active');
            currentInvoiceId = null;
            // Reset form
            document.getElementById('attendForm').reset();
            selectAction('paid');
        }

        function selectAction(action) {
            // Update hidden field
            document.getElementById('attend-action').value = action;

            // Update tabs
            document.querySelectorAll('.action-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`.action-tab[data-action="${action}"]`).classList.add('active');

            // Show/hide forms
            document.querySelectorAll('.action-form').forEach(form => {
                form.classList.remove('active');
            });
            document.getElementById(`form-${action}`).classList.add('active');
        }

        function setSubmitButtonState(loading) {
            const submitBtn = document.getElementById('submitAttend');
            const submitIcon = document.getElementById('submitIcon');
            const submitText = document.getElementById('submitText');

            submitBtn.disabled = loading;
            submitIcon.className = loading ? 'fa fa-spinner fa-spin mr-1' : 'fa fa-check mr-1';
            submitText.textContent = loading ? 'Processing...' : 'Confirm';
        }

        function submitAttend() {
            const action = document.getElementById('attend-action').value;
            const invoiceId = document.getElementById('attend-invoice-id').value;

            // Collect form data based on action
            let formData = {
                action: action,
                notes: ''
            };

            if (action === 'paid') {
                formData.notes = document.getElementById('paid-notes').value;
            } else if (action === 'partial') {
                formData.paid_amount = document.getElementById('partial-amount').value;
                formData.notes = document.getElementById('partial-notes').value;

                if (!formData.paid_amount || parseFloat(formData.paid_amount) <= 0) {
                    alert('Please enter a valid amount');
                    return;
                }
            } else if (action === 'reschedule') {
                formData.new_due_date = document.getElementById('new-due-date').value;
                formData.notes = document.getElementById('reschedule-notes').value;

                if (!formData.new_due_date) {
                    alert('Please select a new due date');
                    return;
                }
            }

            // Set loading state
            setSubmitButtonState(true);

            // Send request
            fetch(`/invoice/${invoiceId}/attend`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeAttendModal();
                    // Reload page to refresh data
                    window.location.reload();
                } else {
                    alert(data.error || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to process request');
            })
            .finally(() => {
                setSubmitButtonState(false);
            });
        }

        // Close modal on overlay click
        document.getElementById('attendModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAttendModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('attendModal').classList.contains('active')) {
                closeAttendModal();
            }
        });
    </script>

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

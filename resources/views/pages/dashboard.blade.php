@extends('layouts.backend')

@section('content')
    <?php
    use App\Classes\Utility;
    use App\Models\AdvanceSalary;
    use App\Models\Loan;
    use App\Models\Payroll;
    use App\Models\Staff;
    use App\Models\User;

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

        /* Department Grid */
        .department-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.8rem;
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
                grid-template-columns: 1fr;
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

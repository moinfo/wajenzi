@extends('layouts.backend')

@section('content')
    <?php

    use App\Classes\Utility;
    use App\Models\AdvanceSalary;

    //    use App\Models\LeavePlanRequest;
    //     use App\Models\LeaveRequest;
    //     use App\Models\Loan;use App\Models\OperatingExpense;
    //     use App\Models\Payroll;use App\Models\RentPayment;
    use App\Models\Loan;
    use App\Models\Payroll;
    use App\Models\Staff;
    use App\Models\User;

    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
    $last_month_last_date = date("Y-m-t", strtotime("last month"));
    $last_month_first_date = date("Y-m-01", strtotime("last month"));
    $sales = \App\Models\Sale::getTotalTax($start_date, $end_date);
    $purchases = \App\Models\Purchase::getTotalPurchasesWithVAT($end_date, null, null, $start_date,);

    $vat_analysis = new \App\Models\VatAnalysis();

    $this_month_tax_payable = $vat_analysis->getTaxPayable($end_date);
    $last_month_tax_payable = \App\Models\VatPayment::getTotalPaymentOfLastMonth($last_month_first_date, $last_month_last_date);


    $statutory_payment_link = "un_approved_payments";
    $operating_expenses_link = "operating_expenses";
    $advance_salary_link = "settings/advance_salaries";
    $leave_request_link = "payroll/leaves_management";
    $leave_plan_request_link = "payroll/leave_plan_requests";
    $staff_loan_link = 'settings/staff_loans';
    $payroll_link = 'payroll/payroll_administration';
    $rent_payment_link = 'rent_payments';
    $user_id = Auth::user()->id;
    $user_group_ids = Utility::userGroupsArray($user_id);
    $user_id = Auth::user()->id;
    //    $user_department_id = Auth::user()->department_id;
    //    $operating_expenses_counting = OperatingExpense::countUnapproved();
    //    $statutory_payment_counting = StatutoryPayment::countUnapproved();
    $advance_salary_counting = AdvanceSalary::countUnapproved();
    //    $leave_request_counting = LeaveRequest::countUnapproved();
    //        $leave_plan_request_counting = LeavePlanRequest::countUnapproved();
    $staff_loan_counting = Loan::countUnapproved();
    $payroll_counting = Payroll::countUnapproved();
    //    $rent_payment_counting = RentPayment::countUnapproved();

    $status_docs = [
//        ['name' => 'Statutory Payment', 'count' => "$statutory_payment_counting", 'class' => 'StatutoryPayment', 'icon' => 'fa fa-envelope-o', 'list_link' => "$statutory_payment_link"],
//        ['name' => 'Operating Expenses', 'count' => "$operating_expenses_counting", 'class' => 'OperatingExpenses', 'icon' => 'fa fa-envelope-o', 'list_link' => "$operating_expenses_link"],
        ['name' => 'Payroll', 'class' => 'Payroll', 'count' => "$payroll_counting", 'icon' => 'fa fa-envelope-o', 'list_link' => "$payroll_link"],
//        ['name' => 'Leave Request', 'class' => 'LeaveRequest', 'count' => "$leave_request_counting", 'icon' => 'fa fa-envelope-o', 'list_link' => "$leave_request_link"],
//        ['name' => 'Leave Plan Request', 'class' => 'LeavePlanRequest', 'count' => "$leave_plan_request_counting", 'icon' => 'fa fa-envelope-o', 'list_link' => "$leave_plan_request_link"],
        ['name' => 'Advance Salary', 'class' => 'AdvanceSalary', 'count' => "$advance_salary_counting", 'icon' => 'fa fa-envelope-o', 'list_link' => "$advance_salary_link"],
        ['name' => 'Staff Loan', 'class' => 'Loan', 'count' => "$staff_loan_counting", 'icon' => 'fa fa-envelope-o', 'list_link' => "$staff_loan_link"],
//        ['name' => 'Rent Payment', 'class' => 'RentPayment', 'count' => "$rent_payment_counting", 'icon' => 'fa fa-envelope-o', 'list_link' => "$rent_payment_link"],
    ];
    //    $staffSummary = Staff::staffSummary();
    //    $staff_board = Staff::staffBoard();
    //    $staff_count = $staffSummary['staff_gender']['male'] + $staffSummary['staff_gender']['female'];

    //    return;
    $counts = User::getUserCounts();
    $departmentCounts = User::getDepartmentMemberCounts();
    ?>

    <style>
        :root {
            --primary-blue: #4169E1;
            --primary-green: #32CD32;
            --chart-blue: rgba(66, 165, 245, 1);
            --chart-green: rgba(156, 204, 101, 1);
            --bg-light: #f8f9fa;
            --text-primary: #333;
            --text-secondary: #666;
            --border-color: #e5e7eb;
            --success-color: #10B981;
            --danger-color: #EF4444;
        }

        /* Dashboard Layout */
        .modern-dashboard {
            padding: 1.5rem;
            background: var(--bg-light);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        /* Stats Card */
        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-2px);
        }

        .stats-content {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--primary-blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .purchases-icon {
            background: var(--chart-green);
        }

        .vat-icon {
            background: var(--primary-green);
        }

        .current-vat-icon {
            background: var(--chart-blue);
        }

        .stats-info {
            display: flex;
            flex-direction: column;
        }

        .stats-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .stats-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .stats-trend {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.875rem;
        }

        .stats-trend-up {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .stats-trend-down {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
        }

        /* Chart Card */
        .chart-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .chart-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chart-title {
            display: flex;
            flex-direction: column;
        }

        .chart-title h3 {
            margin: 0;
            font-size: 1.25rem;
            color: var(--text-primary);
        }

        .chart-title span {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .chart-actions {
            display: flex;
            gap: 0.5rem;
        }

        .chart-action {
            background: transparent;
            border: none;
            padding: 0.5rem;
            color: var(--text-secondary);
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .chart-action:hover {
            color: var(--primary-blue);
        }

        .chart-body {
            padding: 1.5rem;
            height: 300px;
        }

        .chart-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .chart-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            text-align: center;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-trend {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .stat-trend.up {
            color: var(--success-color);
        }

        .stat-trend.down {
            color: var(--danger-color);
        }

        .stat-value {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            text-transform: uppercase;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .modern-dashboard {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }

            .stats-card {
                padding: 1rem;
            }

            .stats-icon {
                width: 40px;
                height: 40px;
                font-size: 1.25rem;
            }

            .stats-value {
                font-size: 1.25rem;
            }
        }
    </style>
    <style>
        .dashboard-container {
            padding: 1.5rem;
            background: #f8f9fa;
        }

        /* Stats Overview Cards */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 1rem;
        }

        .bg-indigo-600 { background: #4F46E5; }
        .bg-green-500 { background: #22C55E; }
        .bg-lime-500 { background: #84CC16; }
        .bg-blue-400 { background: #60A5FA; }

        .stat-content {
            flex: 1;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: #6B7280;
            font-size: 0.875rem;
        }

        .trend {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .trend.up {
            background: rgba(34, 197, 94, 0.1);
            color: #22C55E;
        }

        .trend.down {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
        }

        /* Approvals Section */
        .approvals-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .section-desc {
            color: #6B7280;
            margin-bottom: 1rem;
        }

        .approvals-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            background: #F3F4F6;
            padding: 1.5rem;
            border-radius: 10px;
        }

        .approval-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
        }

        .approval-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .approval-badge {
            background: #EF4444;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
        }

        /* HR Dashboard */
        .hr-dashboard {
            background: #F3F4F6;
            border-radius: 10px;
            padding: 1.5rem;
        }

        .hr-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .staff-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .total-staff-card {
            background: #2196F3;
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            position: relative;
        }

        .staff-icon {
            position: absolute;
            right: 1rem;
            top: 1rem;
            font-size: 2.5rem;
            opacity: 0.2;
        }

        .staff-count {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .staff-gender-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .gender-card {
            background: #2196F3;
            color: white;
            padding: 1.25rem;
            border-radius: 8px;
            position: relative;
        }

        .gender-icon {
            position: absolute;
            right: 1rem;
            top: 1rem;
            font-size: 1.5rem;
            opacity: 0.2;
        }

        .gender-count {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        @media (max-width: 1024px) {
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }

            .hr-content {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .stats-overview,
            .approvals-grid {
                grid-template-columns: 1fr;
            }

            .staff-gender-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="modern-dashboard">
        <!-- Stats Cards Row -->
        <div class="stats-grid" data-toggle="appear">
            <!-- Sales Card -->
            <div class="stats-card">
                <div class="stats-content">
                    <div class="stats-icon">
                        <i class="si si-bag"></i>
                    </div>
                    <div class="stats-info">
                        <div class="stats-value" data-toggle="countTo" data-speed="1000" data-to="{{$sales}}">
                            {{$sales}}
                        </div>
                        <div class="stats-label">Sales</div>
                    </div>
                </div>
                <div class="stats-chart">
                    <div class="stats-trend stats-trend-up">
                        <i class="fa fa-arrow-up"></i>
                        <span>12%</span>
                    </div>
                </div>
            </div>

            <!-- Purchases Card -->
            <div class="stats-card">
                <div class="stats-content">
                    <div class="stats-icon purchases-icon">
                        <i class="si si-wallet"></i>
                    </div>
                    <div class="stats-info">
                        <div class="stats-value" data-toggle="countTo" data-speed="1000" data-to="{{$purchases}}">
                            {{$purchases}}
                        </div>
                        <div class="stats-label">Purchases</div>
                    </div>
                </div>
                <div class="stats-chart">
                    <div class="stats-trend stats-trend-down">
                        <i class="fa fa-arrow-down"></i>
                        <span>5%</span>
                    </div>
                </div>
            </div>

            <!-- Last Month VAT -->
            <div class="stats-card">
                <div class="stats-content">
                    <div class="stats-icon vat-icon">
                        <i class="si si-globe-alt"></i>
                    </div>
                    <div class="stats-info">
                        <div class="stats-value" data-toggle="countTo" data-speed="1000"
                             data-to="{{$last_month_tax_payable}}">
                            {{$last_month_tax_payable}}
                        </div>
                        <div class="stats-label">Last Month VAT</div>
                    </div>
                </div>
                <div class="stats-chart">
                    <div class="stats-trend stats-trend-up">
                        <i class="fa fa-arrow-up"></i>
                        <span>8%</span>
                    </div>
                </div>
            </div>

            <!-- This Month VAT -->
            <div class="stats-card">
                <div class="stats-content">
                    <div class="stats-icon current-vat-icon">
                        <i class="si si-bar-chart"></i>
                    </div>
                    <div class="stats-info">
                        <div class="stats-value" data-toggle="countTo" data-speed="1000"
                             data-to="{{$this_month_tax_payable}}">
                            {{$this_month_tax_payable}}
                        </div>
                        <div class="stats-label">This Month VAT</div>
                    </div>
                </div>
                <div class="stats-chart">
                    <div class="stats-trend stats-trend-up">
                        <i class="fa fa-arrow-up"></i>
                        <span>15%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approvals Section -->
        <div class="approvals-section">
            <h2 class="section-title">Approvals</h2>
            <p class="section-desc">Things that are waiting for your action</p>

            <div class="approvals-grid">
                <div class="approval-card">
                    <div class="approval-content">
                        <span class="approval-icon"><i class="fas fa-file-invoice"></i></span>
                        <span class="approval-text">Payroll</span>
                        <span class="approval-badge">0</span>
                    </div>
                </div>

                <div class="approval-card">
                    <div class="approval-content">
                        <span class="approval-icon"><i class="fas fa-money-bill-wave"></i></span>
                        <span class="approval-text">Advance Salary</span>
                        <span class="approval-badge">0</span>
                    </div>
                </div>

                <div class="approval-card">
                    <div class="approval-content">
                        <span class="approval-icon"><i class="fas fa-hand-holding-usd"></i></span>
                        <span class="approval-text">Staff Loan</span>
                        <span class="approval-badge">0</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- HR Dashboard Section -->
        <div class="hr-dashboard">
            <h2 class="section-title">HR & Payroll Dashboard</h2>
            <p class="section-desc">Important analytics related to Human Resources management</p>

            <div class="hr-content">
                <div class="staff-section">
                    <div class="total-staff-card">
                        <div class="staff-icon">
                            <i class="fa fa-users"></i>
                        </div>
                        <div class="staff-count">{{$counts->total}}</div>
                        <div class="staff-label">All staff in the organization</div>
                    </div>

                    <div class="staff-gender-grid">
                        <div class="gender-card male">
                            <div class="gender-icon">
                                <i class="fa fa-male"></i>
                            </div>
                            <div class="gender-count">{{$counts->total_male}}</div>
                            <div class="gender-label">Male staff</div>
                        </div>

                        <div class="gender-card female">
                            <div class="gender-icon">
                                <i class="fa fa-female"></i>
                            </div>
                            <div class="gender-count">{{$counts->total_female}}</div>
                            <div class="gender-label">Female staff</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="charts-grid" data-toggle="appear">
            <!-- Sales Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">
                        <h3>Sales</h3>
                        <span>This week</span>
                    </div>
                    <div class="chart-actions">
                        <button type="button" class="chart-action" data-toggle="block-option" data-action="state_toggle"
                                data-action-mode="demo">
                            <i class="si si-refresh"></i>
                        </button>
                        <button type="button" class="chart-action">
                            <i class="si si-wrench"></i>
                        </button>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas class="js-chartjs-dashboard-lines"></canvas>
                </div>
                <div class="chart-footer">
                    <div class="chart-stats">
                        <div class="stat-item">
                            <div class="stat-trend up">
                                <i class="fa fa-caret-up"></i>
                                +16%
                            </div>
                            <div class="stat-value">{{$collection_in_month['total_amount']}}</div>
                            <div class="stat-label">This Month</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-trend down">
                                <i class="fa fa-caret-down"></i>
                                -3%
                            </div>
                            <div class="stat-value">{{$collection_in_week['total_amount']}}</div>
                            <div class="stat-label">This Week</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchases Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">
                        <h3>Purchases</h3>
                        <span>This week</span>
                    </div>
                    <div class="chart-actions">
                        <button type="button" class="chart-action" data-toggle="block-option" data-action="state_toggle"
                                data-action-mode="demo">
                            <i class="si si-refresh"></i>
                        </button>
                        <button type="button" class="chart-action">
                            <i class="si si-wrench"></i>
                        </button>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas class="js-chartjs-dashboard-lines2"></canvas>
                </div>
                <div class="chart-footer">
                    <div class="chart-stats">
                        <div class="stat-item">
                            <div class="stat-trend up">
                                <i class="fa fa-caret-up"></i>
                                +4%
                            </div>
                            <div class="stat-value">{{$expenses_in_month['total_amount']}}</div>
                            <div class="stat-label">This Month</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-trend down">
                                <i class="fa fa-caret-down"></i>
                                -7%
                            </div>
                            <div class="stat-value">{{$expenses_in_month['total_amount']}}</div>
                            <div class="stat-label">This Week</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



@endsection
<?php

use App\Models\Collection;
//use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

$monday = strtotime("last monday");
$monday = date('w', $monday) == date('w') ? $monday + 7 * 86400 : $monday;
$sunday = strtotime(date("Y-m-d", $monday) . " +6 days");
$this_week_sd = date("Y-m-d", $monday);
$this_week_ed = date("Y-m-d", $sunday);
//        echo "Current week range from $this_week_sd to $this_week_ed ";

$first_date = explode("-", $this_week_sd);
$last_date = explode("-", $this_week_ed);

for ($i = $first_date[2]; $i <= $last_date[2]; $i++) {
    // add the date to the dates array
    $dates[] = date('Y') . "-" . date('m') . "-" . str_pad($i, 2, '0', STR_PAD_LEFT);
}
//dump($dates);
//                    $no = 1;
if (isset($dates)) {
    foreach ($dates as $index => $date) {
        // echo $date;
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
                    backgroundColor: "rgba(66,165,245,.15)",
                    borderColor: "rgba(66,165,245,1)",
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: "rgba(66,165,245,1)",
                    pointBorderColor: "#fff",
                    pointHoverBackgroundColor: "#fff",
                    pointHoverBorderColor: "rgba(66,165,245,1)",
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

        var purchasesChart = new Chart($('.js-chartjs-dashboard-lines2'), {
            type: "line",
            data: {
                labels: ["MON", "TUE", "WED", "THU", "FRI", "SAT", "SUN"],
                datasets: [{
                    label: "This Week",
                    fill: true,
                    backgroundColor: "rgba(156,204,101,.15)",
                    borderColor: "rgba(156,204,101,1)",
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: "rgba(156,204,101,1)",
                    pointBorderColor: "#fff",
                    pointHoverBackgroundColor: "#fff",
                    pointHoverBorderColor: "rgba(156,204,101,1)",
                    data: [],
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            suggestedMax: 480,
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
                            return " $ " + e.yLabel;
                        }
                    }
                }
            }
@endsection

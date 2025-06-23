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
    $advance_salary_counting = AdvanceSalary::countUnapproved();
    $staff_loan_counting = Loan::countUnapproved();
    $payroll_counting = Payroll::countUnapproved();

    $status_docs = [
        ['name' => 'Payroll', 'class' => 'Payroll', 'count' => "$payroll_counting", 'icon' => 'fa fa-envelope-o', 'list_link' => "$payroll_link"],
        ['name' => 'Advance Salary', 'class' => 'AdvanceSalary', 'count' => "$advance_salary_counting", 'icon' => 'fa fa-envelope-o', 'list_link' => "$advance_salary_link"],
        ['name' => 'Staff Loan', 'class' => 'Loan', 'count' => "$staff_loan_counting", 'icon' => 'fa fa-envelope-o', 'list_link' => "$staff_loan_link"],
        ['name' => 'Material Request', 'class' => 'Loan', 'count' => "$staff_loan_counting", 'icon' => 'fa fa-envelope-o', 'list_link' => "$staff_loan_link"],
        ['name' => 'Project BOQ', 'class' => 'Loan', 'count' => "$staff_loan_counting", 'icon' => 'fa fa-envelope-o', 'list_link' => "$staff_loan_link"],
        ['name' => 'Project Expense', 'class' => 'Loan', 'count' => "$staff_loan_counting", 'icon' => 'fa fa-envelope-o', 'list_link' => "$staff_loan_link"],
        ['name' => 'Project Invoice', 'class' => 'Loan', 'count' => "$staff_loan_counting", 'icon' => 'fa fa-envelope-o', 'list_link' => "$staff_loan_link"],
        ['name' => 'Site Visit', 'class' => 'Loan', 'count' => "$staff_loan_counting", 'icon' => 'fa fa-envelope-o', 'list_link' => "$staff_loan_link"],

        ];
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
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            background: #F3F4F6;
            padding: 1.5rem;
            border-radius: 10px;
        }

        .approval-card {
            background: white;
            border-radius: 8px;
            padding: 10px;
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
    <style>
        :root {
            --primary-color: #4169E1;
            --secondary-color: #32CD32;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --chart-blue: rgba(66, 165, 245, 1);
            --chart-green: rgba(156, 204, 101, 1);
        }

        /* Dashboard Grid Layout */
        .modern-dashboard {
            padding: 1.5rem;
            background: var(--light-color);
            display: grid;
            grid-gap: 1.5rem;
        }

        /* Stats Overview Section */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 1.25rem;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .stat-icon.sales { background: var(--primary-color); }
        .stat-icon.projects { background: var(--success-color); }
        .stat-icon.tasks { background: var(--warning-color); }
        .stat-icon.expenses { background: var(--danger-color); }

        .stat-content {
            margin-top: 0.5rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
        }

        .stat-trend {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
        }

        .trend-up {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }

        .trend-down {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }

        /* Project Overview Section */
        .project-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 1.5rem;
        }

        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .project-title {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .project-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }

        .project-card {
            background: var(--light-color);
            border-radius: 8px;
            padding: 1rem;
        }

        .project-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .status-ongoing { background: rgba(255, 193, 7, 0.1); color: var(--warning-color); }
        .status-completed { background: rgba(40, 167, 69, 0.1); color: var(--success-color); }
        .status-delayed { background: rgba(220, 53, 69, 0.1); color: var(--danger-color); }

        /* Activity Section */
        .activity-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 1.5rem;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }

        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: white;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .activity-time {
            color: #6c757d;
            font-size: 0.875rem;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .modern-dashboard {
                padding: 1rem;
            }

            .stats-overview {
                grid-template-columns: 1fr;
            }

            .project-grid {
                grid-template-columns: 1fr;
            }
        }


    </style>
    <div class="modern-dashboard">
        <!-- Approvals Section -->
        <!-- Approvals Section -->
        <div class="approvals-container">
            <div class="approvals-header">
                <h2 class="approvals-title">Approvals</h2>
                <p class="approvals-subtitle">Things that are waiting for your action</p>
            </div>

            <div class="approvals-grid">
                @foreach($status_docs as $doc)
                    <div class="approval-card">
                        <div class="approval-content">
                            <div class="approval-icon">
                                <i class="fa fa-envelope-o"></i>
                            </div>
                            <span class="approval-text">{{ $doc['name'] }}</span>
                            <span class="approval-badge">{{ $doc['count'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <style>
            .approvals-container {
                background-color: #f8f9fa;
                padding: 1.5rem;
                border-radius: 0.75rem;
                /*margin-bottom: 2rem;*/
            }

            .approvals-header {
                margin-bottom: 1.5rem;
            }

            .approvals-title {
                font-size: 1.5rem;
                font-weight: 600;
                color: #1a202c;
                margin-bottom: 0.5rem;
            }

            .approvals-subtitle {
                color: #4a5568;
                font-size: 0.875rem;
            }

            .approvals-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1rem;
                background-color: #fff;
                padding: 1.5rem;
                border-radius: 0.75rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }

            .approval-card {
                background-color: #ffffff;
                border-radius: 0.5rem;
                padding: 1rem;
                transition: all 0.3s ease;
                border: 1px solid #e5e7eb;
            }

            .approval-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }

            .approval-content {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
            }

            .approval-icon {
                color: #4169E1;
                font-size: 1.25rem;
                width: 2.5rem;
                height: 2.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: rgba(65, 105, 225, 0.1);
                border-radius: 0.5rem;
            }

            .approval-text {
                flex: 1;
                font-size: 0.875rem;
                color: #4b5563;
                font-weight: 500;
            }

            .approval-badge {
                background-color: #4169E1;
                color: white;
                padding: 0.25rem 0.75rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 600;
                min-width: 1.5rem;
                text-align: center;
            }

            /* Responsive Design */
            @media (max-width: 768px) {
                .approvals-container {
                    padding: 1rem;
                }

                .approvals-grid {
                    grid-template-columns: 1fr;
                    padding: 1rem;
                }

                .approval-card {
                    padding: 0.75rem;
                }
            }
        </style>

        <!-- HR Dashboard Section -->
        <!-- HR Dashboard Section -->
        <!-- HR Dashboard -->
        <div class="modern-dashboard">
            <div class="dashboard-header">
                <h2 class="section-title">HR & Payroll Dashboard</h2>
                <p class="section-desc">Important analytics related to Human Resources management</p>
            </div>

            <div class="dashboard-content">
                <!-- Staff Overview Cards -->
                <div class="stats-section">
                    <div class="stat-card total-staff">
                        <div class="stat-content">
                            <div class="stat-icon">
                                <i class="fa fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-count">{{$counts->total}}</div>
                                <div class="stat-label">All staff in the organization</div>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card male-staff">
                        <div class="stat-content">
                            <div class="stat-icon">
                                <i class="fa fa-male"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-count">{{$counts->total_male}}</div>
                                <div class="stat-label">Male staff</div>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card female-staff">
                        <div class="stat-content">
                            <div class="stat-icon">
                                <i class="fa fa-female"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-count">{{$counts->total_female}}</div>
                                <div class="stat-label">Female staff</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Department Section -->
                <div class="info-section">
                    <div class="info-card departments">
                        <div class="card-header">
                            <h3>DEPARTMENTS</h3>
                            <span class="total-count">{{count($departmentCounts)}}</span>
                        </div>
                        <div class="card-content">
                            @foreach($departmentCounts as $dept => $count)
                                <div class="list-item">
                                    <span class="item-name">{{$count->name ?? null}}</span>
                                    <span class="item-count">{{$count->total_members}}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Staff Board Section -->
                    <div class="info-card staff-board">
                        <div class="card-header">
                            <h3>STAFF BOARD</h3>
                        </div>
                        <div class="card-content">
                            <div class="list-item">
                                <div class="status-info">
                                    <span class="status-indicator"></span>
                                    <span>On Leave</span>
                                </div>
                                <span class="status-count">0</span>
                            </div>
                            <div class="list-item">
                                <div class="status-info">
                                    <span class="status-indicator"></span>
                                    <span>Out of Office</span>
                                </div>
                                <span class="status-count">0</span>
                            </div>
                            <div class="list-item">
                                <div class="status-info">
                                    <span class="status-indicator"></span>
                                    <span>In Office Premises</span>
                                </div>
                                <span class="status-count">0</span>
                            </div>
                            <div class="list-item">
                                <div class="status-info">
                                    <span class="status-indicator"></span>
                                    <span>On the field</span>
                                </div>
                                <span class="status-count">0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .modern-dashboard {
                background-color: #f8f9fa;
                padding: 1.5rem;
                border-radius: 8px;
            }

            .dashboard-header {
                /*margin-bottom: 1.5rem;*/
            }

            .section-title {
                font-size: 1.25rem;
                font-weight: 600;
                color: #1a202c;
                margin-bottom: 0.5rem;
            }

            .section-desc {
                color: #6b7280;
                font-size: 0.875rem;
            }

            /* Stats Section */
            .stats-section {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 1rem;
                margin-bottom: 1.5rem;
            }

            .stat-card {
                background: white;
                border-radius: 8px;
                padding: 1rem;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
                border: 1px solid #e5e7eb;
            }

            .stat-content {
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            .stat-icon {
                width: 2.5rem;
                height: 2.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: #e0e7ff;
                border-radius: 8px;
                color: #2563eb;
            }

            .stat-count {
                font-size: 1.5rem;
                font-weight: 600;
                color: #1a202c;
                margin-bottom: 0.25rem;
            }

            .stat-label {
                color: #6b7280;
                font-size: 0.875rem;
            }

            /* Info Section */
            .info-section {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 1.5rem;
            }

            .info-card {
                background: white;
                border-radius: 8px;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
                border: 1px solid #e5e7eb;
            }

            .card-header {
                padding: 1rem;
                border-bottom: 1px solid #e5e7eb;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .card-header h3 {
                font-size: 0.875rem;
                font-weight: 600;
                color: #1a202c;
            }

            .total-count {
                background: #2563eb;
                color: white;
                padding: 0.25rem 0.75rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 600;
            }

            .card-content {
                padding: 1rem;
            }

            .list-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.5rem 0;
                border-bottom: 1px solid #e5e7eb;
            }

            .list-item:last-child {
                border-bottom: none;
            }

            .item-name {
                color: #4b5563;
                font-size: 0.875rem;
            }

            .item-count {
                background: #e0e7ff;
                color: #2563eb;
                padding: 0.25rem 0.75rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 500;
            }

            .status-info {
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }

            .status-indicator {
                width: 0.5rem;
                height: 0.5rem;
                border-radius: 9999px;
                background-color: #fbbf24;
            }

            .status-count {
                color: #6b7280;
                font-weight: 500;
            }

            /* Responsive Design */
            @media (max-width: 1024px) {
                .modern-dashboard {
                    padding: 1rem;
                }

                .stats-section {
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                }

                .info-section {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 640px) {
                .stat-count {
                    font-size: 1.25rem;
                }
            }
        </style>
        <!-- Main Dashboard Container -->
        <div class="dashboard-container">
            <!-- Header Section -->
            <div class="dashboard-header">
                <div class="header-left">
                    <h2 class="main-title">Construction Management System</h2>
                    <p class="subtitle">Project Overview & Analytics</p>
                </div>
                <div class="header-right">
                    <div class="year-selector">
                        <span>YEAR</span>
                        <select class="year-dropdown">
                            <option value="2024">2024</option>
                            <option value="2023">2023</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Project Progress Section -->
            <div class="progress-section">
                <div class="progress-card main-progress">
                    <div class="progress-info">
                        <h3>Project Completion</h3>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path d="M18 2.0845
                            a 15.9155 15.9155 0 0 1 0 31.831
                            a 15.9155 15.9155 0 0 1 0 -31.831"
                                      fill="none"
                                      stroke="#eee"
                                      stroke-width="3"/>
                                <path d="M18 2.0845
                            a 15.9155 15.9155 0 0 1 0 31.831
                            a 15.9155 15.9155 0 0 1 0 -31.831"
                                      fill="none"
                                      stroke="#2563eb"
                                      stroke-width="3"
                                      stroke-dasharray="75, 100"/>
                            </svg>
                            <div class="percentage">75%</div>
                        </div>
                        <p class="progress-desc">Overall project completion rate</p>
                    </div>
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fa fa-building"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">12</div>
                            <div class="stat-label">Active Projects</div>
                        </div>
                    </div>
                    <div class="stat-trend positive">
                        <i class="fa fa-arrow-up"></i>
                        <span>20% vs last month</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fa fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">45</div>
                            <div class="stat-label">Site Workers</div>
                        </div>
                    </div>
                    <div class="stat-trend positive">
                        <i class="fa fa-arrow-up"></i>
                        <span>15% vs last month</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fa fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">85%</div>
                            <div class="stat-label">On-Time Completion</div>
                        </div>
                    </div>
                    <div class="stat-trend negative">
                        <i class="fa fa-arrow-down"></i>
                        <span>5% vs last month</span>
                    </div>
                </div>
            </div>

            <!-- Project Details Section -->
            <div class="details-grid">
                <!-- Contracts Section -->
                <div class="detail-card">
                    <div class="card-header">
                        <h3>CONTRACTS</h3>
                        <p class="card-subtitle">All entered contracts and encumbrance</p>
                    </div>
                    <div class="card-content">
                        <div class="metric-group">
                            <div class="metric">
                                <span class="metric-value">24</span>
                                <span class="metric-label">Contracts Entered</span>
                            </div>
                            <div class="metric">
                                <span class="metric-value">18</span>
                                <span class="metric-label"># of Encumbrance</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Budget Section -->
                <div class="detail-card">
                    <div class="card-header">
                        <h3>BUDGET UTILIZATION</h3>
                        <p class="card-subtitle">Current budget status and utilization</p>
                    </div>
                    <div class="card-content">
                        <div class="budget-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart orange">
                                <path d="M18 2.0845
                            a 15.9155 15.9155 0 0 1 0 31.831
                            a 15.9155 15.9155 0 0 1 0 -31.831"
                                      fill="none"
                                      stroke="#eee"
                                      stroke-width="3"/>
                                <path d="M18 2.0845
                            a 15.9155 15.9155 0 0 1 0 31.831
                            a 15.9155 15.9155 0 0 1 0 -31.831"
                                      fill="none"
                                      stroke="#ff6b6b"
                                      stroke-width="3"
                                      stroke-dasharray="65, 100"/>
                            </svg>
                            <div class="percentage">65%</div>
                        </div>
                        <div class="budget-info">
                            <div class="budget-amount">TZS 1,850,000</div>
                            <div class="budget-label">Total Budget Used</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            /* Base Styles */
            .dashboard-container {
                padding: 1.5rem;
                background-color: #f8f9fa;
            }

            .dashboard-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                /*margin-bottom: 2rem;*/
            }

            .main-title {
                font-size: 1.5rem;
                font-weight: 600;
                color: #1a202c;
                margin: 0;
            }

            .subtitle {
                color: #6b7280;
                font-size: 0.875rem;
                margin: 0.25rem 0 0 0;
            }

            .year-selector {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                background: #fff;
                padding: 0.5rem 1rem;
                border-radius: 0.5rem;
                border: 1px solid #e5e7eb;
            }

            .year-dropdown {
                padding: 0.25rem 0.5rem;
                border: none;
                background: #fbbf24;
                color: #000;
                border-radius: 0.25rem;
                font-weight: 500;
            }

            /* Progress Section */
            .progress-section {
                margin-bottom: 2rem;
            }

            .progress-card {
                background: white;
                border-radius: 0.75rem;
                padding: 1.5rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }

            .progress-info {
                text-align: center;
            }

            .progress-circle {
                width: 150px;
                height: 150px;
                margin: 1rem auto;
                position: relative;
            }

            .circular-chart {
                width: 150px;
                height: 150px;
            }

            .percentage {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: 1.5rem;
                font-weight: 600;
                color: #2563eb;
            }

            /* Stats Grid */
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1.5rem;
                margin-bottom: 2rem;
            }

            .stat-card {
                background: white;
                border-radius: 0.75rem;
                padding: 1.5rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }

            .stat-content {
                display: flex;
                align-items: center;
                gap: 1rem;
                margin-bottom: 1rem;
            }

            .stat-icon {
                width: 3rem;
                height: 3rem;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #e0e7ff;
                color: #2563eb;
                border-radius: 0.75rem;
                font-size: 1.25rem;
            }

            .stat-info {
                flex: 1;
            }

            .stat-value {
                font-size: 1.5rem;
                font-weight: 600;
                color: #1a202c;
            }

            .stat-label {
                color: #6b7280;
                font-size: 0.875rem;
            }

            .stat-trend {
                font-size: 0.875rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .stat-trend.positive {
                color: #10b981;
            }

            .stat-trend.negative {
                color: #ef4444;
            }

            /* Details Grid */
            .details-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 1.5rem;
            }

            .detail-card {
                background: white;
                border-radius: 0.75rem;
                padding: 1.5rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }

            .card-header {
                margin-bottom: 1.5rem;
            }

            .card-header h3 {
                font-size: 1rem;
                font-weight: 600;
                color: #1a202c;
                margin: 0;
            }

            .card-subtitle {
                color: #6b7280;
                font-size: 0.875rem;
                margin: 0.25rem 0 0 0;
            }

            .metric-group {
                display: flex;
                justify-content: space-around;
                text-align: center;
            }

            .metric-value {
                font-size: 1.5rem;
                font-weight: 600;
                color: #1a202c;
                display: block;
            }

            .metric-label {
                color: #6b7280;
                font-size: 0.875rem;
            }

            .budget-circle {
                width: 120px;
                height: 120px;
                margin: 0 auto 1rem auto;
                position: relative;
            }

            .budget-info {
                text-align: center;
            }

            .budget-amount {
                font-size: 1.25rem;
                font-weight: 600;
                color: #1a202c;
            }

            .budget-label {
                color: #6b7280;
                font-size: 0.875rem;
            }

            /* Responsive Design */
            @media (max-width: 768px) {
                .dashboard-container {
                    padding: 1rem;
                }

                .stats-grid {
                    grid-template-columns: 1fr;
                }

                .details-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

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

@extends('layouts.backend')

@php
    $staffList = collect($staffs)->sortBy('name')->values();
    $latestApprovedPayroll = \App\Models\Payroll::query()
        ->where('status', 'APPROVED')
        ->orderByDesc('year')
        ->orderByDesc('month')
        ->orderByDesc('id')
        ->first();

    $this_year = (int) request()->input('year', $latestApprovedPayroll?->year ?? date('Y'));
    $this_month = (int) request()->input('month', $latestApprovedPayroll?->month ?? date('m'));

    $months = [
        1 => 'January',
        2 => 'February',
        3 => 'March',
        4 => 'April',
        5 => 'May',
        6 => 'June',
        7 => 'July',
        8 => 'August',
        9 => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December',
    ];

    $payrollYears = \App\Models\Payroll::query()
        ->whereNotNull('year')
        ->distinct()
        ->pluck('year')
        ->map(fn ($year) => (int) $year);

    $yearOptions = collect(range((int) date('Y') - 4, (int) date('Y') + 1))
        ->merge($payrollYears)
        ->unique()
        ->sortDesc()
        ->values();

    $payroll = \App\Models\Payroll::getThisPayrollApproved($this_month, $this_year);
    $payroll_id = $payroll?->id;
    $payrollStaffId = $payroll_id
        ? \App\Models\PayrollSalary::query()
            ->join('users', 'users.id', '=', 'payroll_salaries.staff_id')
            ->where('payroll_salaries.payroll_id', $payroll_id)
            ->where('users.status', 'ACTIVE')
            ->where('users.type', 'STAFF')
            ->orderBy('users.name')
            ->value('payroll_salaries.staff_id')
        : null;
    $defaultStaffId = $payrollStaffId ?? optional($staffList->first())->id;
    $this_employee = (int) request()->input('staff_id', $defaultStaffId);
    $staff_id = $this_employee;
    $employee = $this_employee ? \App\Models\User::with('department')->find($this_employee) : null;
    $employee_bank_details = $this_employee
        ? \App\Models\StaffBankDetail::with('bank')->where('staff_id', $this_employee)->first()
        : null;

    $basic_salary = $payroll_id ? \App\Models\Staff::getStaffSalaryPaid($this_employee, $payroll_id) : 0;
    $gross_salary = $payroll_id ? \App\Models\Staff::getStaffGrossPayPaid($staff_id, $payroll_id) : 0;
    $net_salary = $payroll_id ? \App\Models\Staff::getStaffNetPaid($staff_id, $payroll_id) : 0;
    $advance_salary = $payroll_id ? \App\Models\Staff::getStaffAdvanceSalaryPaid($staff_id, $payroll_id) : 0;
    $loan_balance = $payroll_id ? \App\Models\Staff::getStaffLoanBalancePaid($staff_id, $payroll_id) : 0;
    $current_loan = $payroll_id ? \App\Models\Staff::getStaffLoanPaid($staff_id, $payroll_id) : 0;
    $loan_deduction = $payroll_id ? \App\Models\Staff::getStaffLoanDeductionPaid($staff_id, $payroll_id) : 0;
    $total_deduction = 0;

    $allowances = $staff_id
        ? \App\Models\Allowance::select(
            'allowance_subscriptions.amount as amount',
            'allowances.name as allowance_name',
            'allowances.allowance_type as allowance_type'
        )
            ->join('allowance_subscriptions', 'allowance_subscriptions.allowance_id', '=', 'allowances.id')
            ->where('allowance_subscriptions.staff_id', $staff_id)
            ->get()
        : collect();

    $deductions = $staff_id
        ? \App\Models\Deduction::select(
            'deduction_settings.employee_percentage as employee_deducted_percentage',
            'deductions.id as deduction_id',
            'deductions.name as keyword'
        )
            ->join('deduction_settings', 'deduction_settings.deduction_id', '=', 'deductions.id')
            ->join('deduction_subscriptions', 'deduction_subscriptions.deduction_id', 'deductions.id')
            ->where('deductions.id', '!=', 1)
            ->where('deduction_subscriptions.staff_id', $staff_id)
            ->get()
        : collect();

    $left_side = [
        ['name' => 'Basic Salary', 'value' => $basic_salary],
    ];

    $advance_salary_balance = $payroll_id ? \App\Models\Staff::getStaffAdvanceSalaryBalance($staff_id, $payroll_id) : 0;

    $right_side = [
        ['name' => 'Advance Salary', 'value' => $advance_salary],
    ];
    if ($advance_salary > 0 || $advance_salary_balance > 0) {
        $right_side[] = ['name' => 'Advance Salary Balance', 'value' => $advance_salary_balance];
    }
    $right_side[] = ['name' => 'Loan', 'value' => $loan_balance];
    $right_side[] = ['name' => 'Loan Deduction', 'value' => $loan_deduction];
    $right_side[] = ['name' => 'Loan Balance', 'value' => ($current_loan - $loan_deduction)];

    foreach ($allowances as $allowance) {
        $allowance_amount = \App\Models\Allowance::getAllowanceAmountPerType(
            $allowance->allowance_type,
            $allowance->amount,
            $this_month
        );

        if ($allowance_amount > 0) {
            $left_side[] = [
                'name' => strtoupper($allowance->allowance_name),
                'value' => $allowance_amount,
            ];
        }
    }

    $employee_deducted_amount_payee = $payroll_id
        ? \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id, 1, 'employee_deduction_amount')
        : 0;

    if ($employee_deducted_amount_payee > 0) {
        $total_deduction += $employee_deducted_amount_payee;
        $right_side[] = ['name' => 'PAYE', 'value' => $employee_deducted_amount_payee];
    }

    foreach ($deductions as $deduction) {
        $deduction_id = $deduction['deduction_id'];
        $deducted_amount = $payroll_id
            ? \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id, $deduction_id, 'employee_deduction_amount')
            : 0;

        $total_deduction += $deducted_amount;
        $percentage = $deduction['employee_deducted_percentage'] > 0 ? " ({$deduction['employee_deducted_percentage']}%)" : '';

        if ($deducted_amount > 0 && $deduction_id != 1) {
            $right_side[] = [
                'name' => strtoupper($deduction['keyword']) . $percentage,
                'value' => $deducted_amount,
            ];
        }
    }

    $total_deductions_display = $total_deduction + $advance_salary + $loan_deduction;
    $salaryRows = max(count($left_side), count($right_side));
    $selectedMonthName = $months[$this_month] ?? date('F');
    $payrollMonthLabel = $payroll
        ? date('F Y', strtotime($payroll->year . '-' . $payroll->month . '-01'))
        : $selectedMonthName . ' ' . $this_year;
    $employeeHasPayroll = $payroll_id && $staff_id
        ? \App\Models\PayrollSalary::where('payroll_id', $payroll_id)->where('staff_id', $staff_id)->exists()
        : false;
    $hasPayslip = $payroll && $employee && $employeeHasPayroll;
    $emptyPayslipMessage = $payroll && $employee && ! $employeeHasPayroll
        ? 'An approved payroll exists for ' . $payrollMonthLabel . ', but ' . $employee->name . ' was not included in that payroll.'
        : 'No approved payroll was found for ' . $selectedMonthName . ' ' . $this_year . ($employee ? ' for ' . $employee->name : '') . '.';
@endphp

@section('css_after')
    <style>
        .salary-slip-page {
            background: #f4f7fb;
            color: #172033;
            min-height: calc(100vh - 80px);
            padding-bottom: 2rem;
        }

        .salary-slip-header {
            align-items: center;
            background: linear-gradient(135deg, #12304d 0%, #165f6b 58%, #17a99a 100%);
            border: 0;
            border-radius: 8px;
            box-shadow: 0 16px 38px rgba(18, 48, 77, 0.18);
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            overflow: hidden;
            padding: 1.25rem;
            position: relative;
        }

        .salary-slip-header:before {
            background: rgba(255, 255, 255, 0.22);
            content: "";
            height: 100%;
            left: 0;
            position: absolute;
            top: 0;
            width: 6px;
        }

        .salary-slip-title {
            margin-left: 0.5rem;
        }

        .salary-slip-title h1 {
            color: #ffffff;
            font-size: 1.55rem;
            font-weight: 800;
            letter-spacing: 0;
            margin: 0;
        }

        .salary-slip-title p {
            color: rgba(255, 255, 255, 0.78);
            font-size: 0.92rem;
            margin: 0.25rem 0 0;
        }

        .salary-slip-period {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 8px;
            min-width: 180px;
            padding: 0.75rem 1rem;
            text-align: right;
        }

        .salary-slip-period span {
            color: rgba(255, 255, 255, 0.72);
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .salary-slip-period strong {
            color: #ffffff;
            display: block;
            font-size: 1.1rem;
            margin-top: 0.15rem;
        }

        .salary-slip-toolbar {
            background: #ffffff;
            border: 1px solid #e3e8f0;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
            margin-bottom: 1rem;
            padding: 1rem;
        }

        .salary-slip-toolbar label {
            color: #475569;
            font-size: 0.76rem;
            font-weight: 800;
            margin-bottom: 0.35rem;
            text-transform: uppercase;
        }

        .salary-slip-toolbar .form-control {
            border-color: #d8e0eb;
            border-radius: 7px;
            color: #172033;
            min-height: 42px;
        }

        .salary-slip-toolbar .form-control:focus {
            border-color: #1bc5bd;
            box-shadow: 0 0 0 0.15rem rgba(27, 197, 189, 0.15);
        }

        .salary-slip-toolbar .btn-view-slip,
        .slip-actions .btn-print-slip,
        .slip-actions .btn-export-slip {
            align-items: center;
            border: 0;
            border-radius: 7px;
            display: inline-flex;
            font-weight: 800;
            gap: 0.45rem;
            justify-content: center;
            min-height: 42px;
            padding: 0.55rem 1rem;
        }

        .salary-slip-toolbar .btn-view-slip {
            background: #1bc5bd;
            color: #ffffff;
            width: 100%;
        }

        .salary-slip-toolbar .btn-view-slip:hover {
            background: #0f9f99;
            color: #ffffff;
        }

        .slip-actions {
            align-items: center;
            display: flex;
            gap: 0.65rem;
            justify-content: flex-end;
            margin-bottom: 1rem;
        }

        .slip-actions .btn-print-slip {
            background: #172033;
            color: #ffffff;
        }

        .slip-actions .btn-print-slip:hover {
            background: #0f172a;
            color: #ffffff;
        }

        .slip-actions .btn-export-slip {
            background: #f59e0b;
            color: #1f2937;
        }

        .slip-actions .btn-export-slip:hover {
            background: #d97706;
            color: #ffffff;
        }

        .salary-summary-grid {
            display: grid;
            gap: 0.9rem;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-bottom: 1rem;
        }

        .salary-summary-item {
            align-items: center;
            background: #ffffff;
            border: 1px solid #e3e8f0;
            border-radius: 8px;
            display: flex;
            gap: 0.85rem;
            padding: 1rem;
        }

        .salary-summary-icon {
            align-items: center;
            border-radius: 8px;
            display: flex;
            flex: 0 0 44px;
            height: 44px;
            justify-content: center;
            width: 44px;
        }

        .salary-summary-icon.gross {
            background: #e0f2fe;
            color: #0369a1;
        }

        .salary-summary-icon.deductions {
            background: #fff7ed;
            color: #c2410c;
        }

        .salary-summary-icon.net {
            background: #dcfce7;
            color: #15803d;
        }

        .salary-summary-item span {
            color: #64748b;
            display: block;
            font-size: 0.76rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .salary-summary-item strong {
            color: #172033;
            display: block;
            font-family: Consolas, "Courier New", monospace;
            font-size: 1.25rem;
            margin-top: 0.25rem;
        }

        .salary-summary-item.net strong {
            color: #0f766e;
        }

        .payslip-document {
            background: #ffffff;
            border: 1px solid #dfe7f1;
            border-radius: 8px;
            box-shadow: 0 18px 48px rgba(15, 23, 42, 0.12);
            margin: 0 auto 2rem;
            max-width: 1040px;
            overflow: hidden;
        }

        .payslip-document-header {
            align-items: center;
            background: #ffffff;
            border-bottom: 1px solid #e3e8f0;
            border-top: 6px solid #12304d;
            display: flex;
            justify-content: space-between;
            padding: 1.4rem 1.6rem;
        }

        .payslip-brand {
            align-items: center;
            display: flex;
            gap: 1rem;
            min-width: 0;
        }

        .payslip-brand-logo {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #d9e3ef;
            border-radius: 8px;
            display: flex;
            flex: 0 0 78px;
            height: 78px;
            justify-content: center;
            padding: 0.45rem;
            width: 78px;
        }

        .payslip-brand-logo img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }

        .payslip-company-name {
            color: #111827;
            font-size: 1.15rem;
            font-weight: 800;
            margin: 0 0 0.2rem;
        }

        .payslip-company-meta {
            color: #64748b;
            font-size: 0.85rem;
            line-height: 1.45;
            margin: 0;
        }

        .payslip-label {
            min-width: 220px;
            text-align: right;
        }

        .payslip-label span {
            background: #e6fffb;
            border: 1px solid #a7f3d0;
            border-radius: 999px;
            color: #0f766e;
            display: inline-block;
            font-size: 0.72rem;
            font-weight: 900;
            padding: 0.35rem 0.7rem;
            text-transform: uppercase;
        }

        .payslip-label strong {
            color: #172033;
            display: block;
            font-size: 1.75rem;
            font-weight: 900;
            margin-top: 0.35rem;
            text-transform: uppercase;
        }

        .payslip-label small {
            color: #64748b;
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            margin-top: 0.1rem;
        }

        .employee-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .employee-field {
            border-bottom: 1px solid #e3e8f0;
            border-right: 1px solid #e3e8f0;
            min-width: 0;
            padding: 0.9rem 1rem;
        }

        .employee-field:nth-child(4n) {
            border-right: 0;
        }

        .employee-field span {
            color: #64748b;
            display: block;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .employee-field strong {
            color: #172033;
            display: block;
            font-size: 0.95rem;
            margin-top: 0.25rem;
            overflow-wrap: anywhere;
        }

        .salary-details {
            padding: 1.35rem;
        }

        .salary-breakdown-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .salary-panel {
            border: 1px solid #e3e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        .salary-panel-header {
            align-items: center;
            display: flex;
            justify-content: space-between;
            padding: 0.9rem 1rem;
        }

        .salary-panel-header span {
            font-size: 0.78rem;
            font-weight: 900;
            text-transform: uppercase;
        }

        .salary-panel-header strong {
            font-family: Consolas, "Courier New", monospace;
            font-size: 0.98rem;
        }

        .earnings-panel .salary-panel-header {
            background: #eefaf7;
            color: #0f766e;
        }

        .deductions-panel .salary-panel-header {
            background: #fff7ed;
            color: #c2410c;
        }

        .salary-line-table {
            border-collapse: collapse;
            margin: 0;
            width: 100%;
        }

        .salary-line-table td,
        .salary-line-table th {
            border-top: 1px solid #edf2f7;
            padding: 0.72rem 0.9rem;
            vertical-align: middle;
        }

        .salary-line-table td:first-child,
        .salary-line-table th:first-child {
            color: #334155;
            font-weight: 650;
        }

        .salary-line-table tbody tr:nth-child(even) td {
            background: #fbfdff;
        }

        .salary-panel-total th,
        .salary-panel-total td {
            background: #f8fafc;
            color: #172033;
            font-weight: 900;
        }

        .salary-net-banner {
            align-items: center;
            background: linear-gradient(135deg, #12304d 0%, #0f766e 100%);
            border-radius: 8px;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            padding: 1.05rem 1.15rem;
        }

        .salary-net-banner span {
            color: rgba(255, 255, 255, 0.76);
            display: block;
            font-size: 0.74rem;
            font-weight: 900;
            text-transform: uppercase;
        }

        .salary-net-banner strong {
            display: block;
            font-family: Consolas, "Courier New", monospace;
            font-size: 1.65rem;
            line-height: 1.15;
            margin-top: 0.2rem;
        }

        .salary-net-bank {
            max-width: 320px;
            text-align: right;
        }

        .salary-net-bank small {
            color: rgba(255, 255, 255, 0.76);
            display: block;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .salary-net-bank b {
            color: #ffffff;
            display: block;
            font-size: 0.92rem;
            margin-top: 0.15rem;
            overflow-wrap: anywhere;
        }

        .money {
            font-family: Consolas, "Courier New", monospace;
            white-space: nowrap;
        }

        .text-right {
            text-align: right !important;
        }

        .payslip-footer {
            align-items: center;
            background: #f8fafc;
            border-top: 1px solid #e3e8f0;
            color: #64748b;
            display: flex;
            font-size: 0.85rem;
            justify-content: space-between;
            padding: 0.9rem 1rem;
        }

        .payslip-footer strong {
            color: #172033;
        }

        .empty-payslip {
            align-items: center;
            background: #ffffff;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            display: flex;
            gap: 1rem;
            padding: 1.4rem;
        }

        .empty-payslip-icon {
            align-items: center;
            background: #fff7ed;
            border-radius: 8px;
            color: #c2410c;
            display: flex;
            flex: 0 0 54px;
            font-size: 1.4rem;
            height: 54px;
            justify-content: center;
            width: 54px;
        }

        .empty-payslip h3 {
            color: #172033;
            font-size: 1.05rem;
            font-weight: 800;
            margin: 0;
        }

        .empty-payslip p {
            color: #64748b;
            margin: 0.25rem 0 0;
        }

        @media (max-width: 991.98px) {
            .salary-slip-header,
            .payslip-document-header {
                align-items: flex-start;
                flex-direction: column;
                gap: 1rem;
            }

            .salary-slip-period,
            .payslip-label {
                text-align: left;
                width: 100%;
            }

            .salary-summary-grid {
                grid-template-columns: 1fr;
            }

            .salary-breakdown-grid {
                grid-template-columns: 1fr;
            }

            .employee-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .employee-field:nth-child(2n) {
                border-right: 0;
            }
        }

        @media (max-width: 575.98px) {
            .slip-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .slip-actions .btn-print-slip,
            .slip-actions .btn-export-slip {
                width: 100%;
            }

            .payslip-brand {
                align-items: flex-start;
            }

            .employee-grid {
                grid-template-columns: 1fr;
            }

            .employee-field {
                border-right: 0;
            }

            .salary-net-banner,
            .payslip-footer {
                align-items: flex-start;
                flex-direction: column;
                gap: 0.75rem;
            }

            .salary-net-bank {
                max-width: none;
                text-align: left;
            }
        }

        @media print {
            .no-print,
            .wajenzi-header,
            .wajenzi-footer,
            .sidebar,
            .main-sidebar {
                display: none !important;
            }

            .wajenzi-main,
            .main-content,
            .container-fluid,
            .content {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .payslip-document {
                border: 1px solid #94a3b8;
                border-radius: 0;
                box-shadow: none;
                margin: 0;
                max-width: none;
            }

            .salary-slip-page {
                background: #ffffff !important;
            }

            .salary-net-banner,
            .salary-panel-header,
            .salary-panel-total th,
            .salary-panel-total td,
            .payslip-document-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid salary-slip-page">
        <div class="content">
            <div class="salary-slip-header no-print">
                <div class="salary-slip-title">
                    <h1>Salary Slip</h1>
                    <p>{{ settings('ORGANIZATION_NAME') }} payroll document</p>
                </div>
                <div class="salary-slip-period">
                    <span>Selected Period</span>
                    <strong>{{ $payrollMonthLabel }}</strong>
                </div>
            </div>

            <div class="salary-slip-toolbar no-print">
                <form name="gross_search" action="{{ route('salary_slips') }}" id="filter-form" method="get" autocomplete="off">
                    <div class="row align-items-end">
                        <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                            <label for="month">Month</label>
                            <select name="month" id="month" class="form-control">
                                @foreach($months as $monthNumber => $monthName)
                                    <option value="{{ $monthNumber }}" {{ (int) $this_month === (int) $monthNumber ? 'selected' : '' }}>
                                        {{ $monthName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6 mb-3 mb-lg-0">
                            <label for="year">Year</label>
                            <select name="year" id="year" class="form-control">
                                @foreach($yearOptions as $year)
                                    <option value="{{ $year }}" {{ (int) $this_year === (int) $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-5 col-md-8 mb-3 mb-lg-0">
                            <label for="input-staff-id">Employee</label>
                            <select name="staff_id" id="input-staff-id" class="form-control select2" required>
                                @foreach($staffList as $staff)
                                    <option value="{{ $staff->id }}" {{ (int) $staff->id === (int) $staff_id ? 'selected' : '' }}>
                                        {{ $staff->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <button type="submit" name="submit" class="btn btn-view-slip">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                Show
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            @if($hasPayslip)
                <div class="salary-summary-grid no-print">
                    <div class="salary-summary-item">
                        <div class="salary-summary-icon gross">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                        <div>
                            <span>Gross Salary</span>
                            <strong>{{ \App\Classes\Utility::money_format($gross_salary) }}</strong>
                        </div>
                    </div>
                    <div class="salary-summary-item">
                        <div class="salary-summary-icon deductions">
                            <i class="fa-solid fa-minus-circle"></i>
                        </div>
                        <div>
                            <span>Total Deductions</span>
                            <strong>{{ \App\Classes\Utility::money_format($total_deductions_display) }}</strong>
                        </div>
                    </div>
                    <div class="salary-summary-item net">
                        <div class="salary-summary-icon net">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>
                        <div>
                            <span>Net Salary</span>
                            <strong>{{ \App\Classes\Utility::money_format($net_salary) }}</strong>
                        </div>
                    </div>
                </div>

                <div class="slip-actions no-print">
                    <button type="button" class="btn btn-print-slip" onclick="printPayslip()">
                        <i class="fa-solid fa-print"></i>
                        Print Payslip
                    </button>
                    <button type="button" class="btn btn-export-slip" onclick="exportToPDF()">
                        <i class="fa-solid fa-file-pdf"></i>
                        Export PDF
                    </button>
                </div>

                <div id="payslip-container">
                    <div class="payslip-document">
                        <div class="payslip-document-header">
                            <div class="payslip-brand">
                                <div class="payslip-brand-logo">
                                    <img src="{{ asset('media/logo/wajenzilogo.png') }}" alt="Company Logo">
                                </div>
                                <div>
                                    <p class="payslip-company-name">{{ settings('ORGANIZATION_NAME') }}</p>
                                    <p class="payslip-company-meta">
                                        {{ settings('COMPANY_ADDRESS_LINE_1') }}<br>
                                        {{ settings('COMPANY_ADDRESS_LINE_2') }}<br>
                                        {{ settings('COMPANY_PHONE_NUMBER') }} · {{ settings('COMPANY_EMAIL_ADDRESS') }}
                                    </p>
                                </div>
                            </div>
                            <div class="payslip-label">
                                <span>Approved Payroll</span>
                                <strong>Payslip</strong>
                                <small>{{ $payrollMonthLabel }}</small>
                            </div>
                        </div>

                        <div class="employee-grid">
                            <div class="employee-field">
                                <span>Payroll Number</span>
                                <strong>{{ $payroll->payroll_number ?? '-' }}</strong>
                            </div>
                            <div class="employee-field">
                                <span>Payroll Month</span>
                                <strong>{{ $payrollMonthLabel }}</strong>
                            </div>
                            <div class="employee-field">
                                <span>Employee Number</span>
                                <strong>HRM/LE/PO-{{ $employee->employee_number ?? '-' }}</strong>
                            </div>
                            <div class="employee-field">
                                <span>Employee Name</span>
                                <strong>{{ $employee->name ?? '-' }}</strong>
                            </div>
                            <div class="employee-field">
                                <span>Department</span>
                                <strong>{{ $employee?->department?->name ?? 'Human Resources & Administration' }}</strong>
                            </div>
                            <div class="employee-field">
                                <span>Designation</span>
                                <strong>{{ $employee?->designation ?? '-' }}</strong>
                            </div>
                            <div class="employee-field">
                                <span>Bank Name</span>
                                <strong>{{ $employee_bank_details?->bank?->name ?? '-' }}</strong>
                            </div>
                            <div class="employee-field">
                                <span>Account Number</span>
                                <strong>{{ $employee_bank_details?->account_number ?? '-' }}</strong>
                            </div>
                        </div>

                        <div class="salary-details">
                            <div class="salary-breakdown-grid">
                                <div class="salary-panel earnings-panel">
                                    <div class="salary-panel-header">
                                        <span>Employee Income</span>
                                        <strong>{{ \App\Classes\Utility::money_format($gross_salary) }}</strong>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="salary-line-table">
                                            <tbody>
                                                @foreach($left_side as $income)
                                                    <tr>
                                                        <td>{{ $income['name'] }}</td>
                                                        <td class="money text-right">{{ \App\Classes\Utility::money_format($income['value']) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr class="salary-panel-total">
                                                    <th>Gross Salary</th>
                                                    <td class="money text-right">{{ \App\Classes\Utility::money_format($gross_salary) }}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>

                                <div class="salary-panel deductions-panel">
                                    <div class="salary-panel-header">
                                        <span>Deductions</span>
                                        <strong>{{ \App\Classes\Utility::money_format($total_deductions_display) }}</strong>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="salary-line-table">
                                            <tbody>
                                                @foreach($right_side as $deduction)
                                                    <tr>
                                                        <td>{{ $deduction['name'] }}</td>
                                                        <td class="money text-right">{{ \App\Classes\Utility::money_format($deduction['value']) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr class="salary-panel-total">
                                                    <th>Total Deductions</th>
                                                    <td class="money text-right">{{ \App\Classes\Utility::money_format($total_deductions_display) }}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="salary-net-banner">
                                <div>
                                    <span>Net Salary</span>
                                    <strong>{{ \App\Classes\Utility::money_format($net_salary) }}</strong>
                                </div>
                                <div class="salary-net-bank">
                                    <small>Payment Account</small>
                                    <b>{{ $employee_bank_details?->bank?->name ?? 'Bank not set' }} / {{ $employee_bank_details?->account_number ?? 'Account not set' }}</b>
                                </div>
                            </div>
                        </div>

                        <div class="payslip-footer">
                            <span>This is a computer-generated payslip and does not require a signature.</span>
                            <strong>{{ settings('ORGANIZATION_NAME') }}</strong>
                        </div>
                    </div>
                </div>
            @else
                <div class="empty-payslip no-print">
                    <div class="empty-payslip-icon">
                        <i class="fa-solid fa-circle-exclamation"></i>
                    </div>
                    <div>
                        <h3>Salary slip not available</h3>
                        <p>{{ $emptyPayslipMessage }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('js_after')
    <script>
        function printPayslip() {
            window.print();
        }

        function exportToPDF() {
            if (typeof window.jspdf === 'undefined') {
                var script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
                script.onload = function () {
                    var script2 = document.createElement('script');
                    script2.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
                    script2.onload = function () {
                        generatePDF();
                    };
                    document.head.appendChild(script2);
                };
                document.head.appendChild(script);
                return;
            }

            generatePDF();
        }

        function generatePDF() {
            var element = document.getElementById('payslip-container');

            if (!element) {
                return;
            }

            const {jsPDF} = window.jspdf;
            var doc = new jsPDF('p', 'mm', 'a4');
            var actionButtons = document.querySelector('.slip-actions');

            if (actionButtons) {
                actionButtons.style.display = 'none';
            }

            html2canvas(element, {
                scale: 2,
                logging: false,
                useCORS: true
            }).then(function (canvas) {
                if (actionButtons) {
                    actionButtons.style.display = '';
                }

                var imgWidth = 210;
                var pageHeight = 297;
                var imgHeight = canvas.height * imgWidth / canvas.width;
                var heightLeft = imgHeight;
                var imgData = canvas.toDataURL('image/png');
                var position = 0;

                doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;

                while (heightLeft >= 0) {
                    position = heightLeft - imgHeight;
                    doc.addPage();
                    doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }

                var employeeName = @json($employee->name ?? 'Employee');
                var payrollMonth = @json(str_replace(' ', '_', $payrollMonthLabel));
                var filename = 'Payslip_' + employeeName.replace(/\s+/g, '_') + '_' + payrollMonth + '.pdf';

                doc.save(filename);
            }).catch(function () {
                if (actionButtons) {
                    actionButtons.style.display = '';
                }
            });
        }
    </script>
@endsection

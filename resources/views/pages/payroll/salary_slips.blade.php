@extends('layouts.backend')

@php
    $staffList = collect($staffs)->sortBy('name')->values();
    $defaultStaffId = optional($staffList->first())->id;

    $this_year = (int) request()->input('year', date('Y'));
    $this_month = (int) request()->input('month', date('m'));
    $this_employee = (int) request()->input('staff_id', $defaultStaffId);
    $staff_id = $this_employee;

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

    $right_side = [
        ['name' => 'Advance Salary', 'value' => $advance_salary],
        ['name' => 'Loan', 'value' => $loan_balance],
        ['name' => 'Loan Deduction', 'value' => $loan_deduction],
        ['name' => 'Loan Balance', 'value' => ($current_loan - $loan_deduction)],
    ];

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
    $hasPayslip = $payroll && $employee;
@endphp

@section('css_after')
    <style>
        .salary-slip-page {
            color: #172033;
            padding-bottom: 2rem;
        }

        .salary-slip-header {
            align-items: center;
            background: #ffffff;
            border: 1px solid #e3e8f0;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            overflow: hidden;
            padding: 1.25rem;
            position: relative;
        }

        .salary-slip-header:before {
            background: linear-gradient(180deg, #1bc5bd, #2563eb);
            content: "";
            height: 100%;
            left: 0;
            position: absolute;
            top: 0;
            width: 5px;
        }

        .salary-slip-title {
            margin-left: 0.5rem;
        }

        .salary-slip-title h1 {
            color: #111827;
            font-size: 1.55rem;
            font-weight: 800;
            letter-spacing: 0;
            margin: 0;
        }

        .salary-slip-title p {
            color: #64748b;
            font-size: 0.92rem;
            margin: 0.25rem 0 0;
        }

        .salary-slip-period {
            background: #f8fafc;
            border: 1px solid #dbe3ee;
            border-radius: 8px;
            min-width: 180px;
            padding: 0.75rem 1rem;
            text-align: right;
        }

        .salary-slip-period span {
            color: #64748b;
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .salary-slip-period strong {
            color: #0f766e;
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
            background: #ffffff;
            border: 1px solid #e3e8f0;
            border-radius: 8px;
            padding: 1rem;
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
            box-shadow: 0 14px 40px rgba(15, 23, 42, 0.10);
            margin: 0 auto 2rem;
            max-width: 1120px;
            overflow: hidden;
        }

        .payslip-document-header {
            align-items: center;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 55%, #eefaf8 100%);
            border-bottom: 1px solid #e3e8f0;
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
            background: #ffffff;
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

        .salary-table {
            border-collapse: separate;
            border-spacing: 0;
            margin: 0;
            width: 100%;
        }

        .salary-table th,
        .salary-table td {
            border-bottom: 1px solid #e3e8f0;
            padding: 0.8rem 0.95rem;
            vertical-align: middle;
        }

        .salary-table thead th {
            background: #172033;
            color: #ffffff;
            font-size: 0.78rem;
            font-weight: 900;
            text-transform: uppercase;
        }

        .salary-table thead th:first-child {
            border-top-left-radius: 8px;
        }

        .salary-table thead th:last-child {
            border-top-right-radius: 8px;
        }

        .salary-table tbody tr:nth-child(even) td {
            background: #fbfdff;
        }

        .salary-section-row th,
        .salary-section-row td {
            background: #edf7f6;
            color: #0f766e;
            font-weight: 900;
            text-transform: uppercase;
        }

        .money {
            font-family: Consolas, "Courier New", monospace;
            white-space: nowrap;
        }

        .text-right {
            text-align: right !important;
        }

        .summary-row th,
        .summary-row td {
            background: #f8fafc;
            color: #172033;
            font-weight: 900;
        }

        .net-salary-row td {
            background: #0f766e;
            border-bottom: 0;
            color: #ffffff;
            font-weight: 900;
            padding: 1rem 0.95rem;
        }

        .net-salary-value {
            font-size: 1.25rem;
        }

        .payslip-footer {
            background: #f8fafc;
            border-top: 1px solid #e3e8f0;
            color: #64748b;
            font-size: 0.85rem;
            padding: 0.9rem 1rem;
            text-align: center;
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
                        <span>Gross Salary</span>
                        <strong>{{ \App\Classes\Utility::money_format($gross_salary) }}</strong>
                    </div>
                    <div class="salary-summary-item">
                        <span>Total Deductions</span>
                        <strong>{{ \App\Classes\Utility::money_format($total_deductions_display) }}</strong>
                    </div>
                    <div class="salary-summary-item net">
                        <span>Net Salary</span>
                        <strong>{{ \App\Classes\Utility::money_format($net_salary) }}</strong>
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
                            <div class="table-responsive">
                                <table class="salary-table">
                                    <thead>
                                        <tr>
                                            <th>Employee Income</th>
                                            <th class="text-right">Amount</th>
                                            <th>Deductions</th>
                                            <th class="text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="salary-section-row">
                                            <th>Details</th>
                                            <td></td>
                                            <th>Details</th>
                                            <td></td>
                                        </tr>

                                        @for($index = 0; $index < $salaryRows; $index++)
                                            <tr>
                                                <td>{{ $left_side[$index]['name'] ?? '' }}</td>
                                                <td class="money text-right">
                                                    {{ isset($left_side[$index]) ? \App\Classes\Utility::money_format($left_side[$index]['value']) : '' }}
                                                </td>
                                                <td>{{ $right_side[$index]['name'] ?? '' }}</td>
                                                <td class="money text-right">
                                                    {{ isset($right_side[$index]) ? \App\Classes\Utility::money_format($right_side[$index]['value']) : '' }}
                                                </td>
                                            </tr>
                                        @endfor

                                        <tr class="summary-row">
                                            <th>Gross Salary</th>
                                            <td class="money text-right">{{ \App\Classes\Utility::money_format($gross_salary) }}</td>
                                            <th>Total Deductions</th>
                                            <td class="money text-right">{{ \App\Classes\Utility::money_format($total_deductions_display) }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="net-salary-row">
                                            <td colspan="3">NET SALARY</td>
                                            <td class="money text-right net-salary-value">
                                                {{ \App\Classes\Utility::money_format($net_salary) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="payslip-footer">
                            This is a computer-generated payslip and does not require a signature.
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
                        <p>No approved payroll was found for {{ $selectedMonthName }} {{ $this_year }}{{ $employee ? ' for ' . $employee->name : '' }}.</p>
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

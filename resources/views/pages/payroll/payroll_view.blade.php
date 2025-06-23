@extends('layouts.backend')

@section('content')
    <?php

    use App\Models\Approval;
    use Illuminate\Http\Request;

    ?>
    @if($payroll == null)
        @php
            header("Location: " . URL::to('/404'), true, 302);
            exit();
        @endphp
    @endif

    <style>
        .details-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .card-body {
            padding: 25px;
        }

        .card-header {
            background-color: #f8f9fa;
            padding: 15px 25px;
            border-bottom: 1px solid #e9ecef;
        }

        .card-header h3 {
            margin: 0;
            color: #0066cc;
            font-weight: 600;
            font-size: 18px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .info-item {
            margin-bottom: 5px;
        }

        .info-item label {
            display: block;
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 6px;
            font-weight: 500;
        }

        .info-value {
            font-size: 16px;
            color: #212529;
            font-weight: 500;
            padding: 8px 12px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #0066cc;
        }

        .file-link {
            display: inline-flex;
            align-items: center;
            color: #0066cc;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .file-link:hover {
            color: #004c99;
            text-decoration: underline;
        }

        .file-link i {
            margin-right: 8px;
            font-size: 18px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-created {
            background-color: #e3f2fd;
            color: #0d6efd;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #ffc107;
        }

        .status-approved {
            background-color: #d1e7dd;
            color: #198754;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #dc3545;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .card-body {
                padding: 15px;
            }
        }
    </style>

    <div class="main-container">

        <div class="content">
            <div class="page-header">
                <style>
                    /* Custom styles for the header */
                    .page-header {
                        font-family: 'Segoe UI', Tahoma, sans-serif;
                    }

                    .header-container {
                        background-color: white;
                        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
                        border-radius: 8px;
                        overflow: hidden;
                        border: 1px solid rgba(0, 0, 0, 0.05);
                        margin-bottom: 25px;
                    }

                    .header-top {
                        padding: 12px 20px;
                        background: linear-gradient(90deg, #f8f9fa, #ffffff);
                        border-bottom: 1px solid #dee2e6;
                        display: flex;
                        justify-content: space-between;
                    }

                    .btn-action {
                        padding: 8px 16px;
                        border-radius: 6px;
                        font-weight: 600;
                        font-size: 14px;
                        transition: all 0.3s ease;
                        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
                    }

                    .btn-print {
                        background-color: #0066cc;
                        color: white;
                        border: none;
                    }

                    .btn-print:hover {
                        background-color: #0052a3;
                        transform: translateY(-2px);
                        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
                    }

                    .btn-back {
                        background-color: white;
                        color: #212529;
                        border: 1px solid #dee2e6;
                    }

                    .btn-back:hover {
                        background-color: #f1f3f5;
                        transform: translateY(-2px);
                        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
                    }

                    .header-main {
                        padding: 25px 20px;
                        position: relative;
                    }

                    .header-main::after {
                        content: '';
                        position: absolute;
                        top: 0;
                        right: 0;
                        bottom: 0;
                        width: 40%;
                        background: linear-gradient(135deg, transparent, rgba(0, 102, 204, 0.03));
                        z-index: 0;
                        pointer-events: none;
                    }

                    .company-logo {
                        max-height: 80px;
                        padding: 4px;
                        background: white;
                        border-radius: 50%;
                        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
                    }

                    .company-info h2 {
                        color: #0066cc;
                        margin-bottom: 8px;
                        font-weight: 700;
                        font-size: 20px;
                    }

                    .company-address p {
                        margin-bottom: 3px;
                        color: #555;
                        font-size: 14px;
                    }

                    .project-title {
                        font-weight: 800;
                        color: #212529;
                        font-size: 28px;
                        margin-bottom: 15px;
                        position: relative;
                        display: inline-block;
                    }

                    .project-title::after {
                        content: '';
                        position: absolute;
                        left: 0;
                        bottom: -6px;
                        height: 3px;
                        width: 40px;
                        background-color: #ff9900;
                        border-radius: 2px;
                    }

                    .document-number-container {
                        border: 2px solid #0066cc;
                        background-color: #f0f7ff;
                        padding: 10px 15px;
                        border-radius: 6px;
                        display: inline-block;
                        box-shadow: 0 3px 10px rgba(0, 102, 204, 0.1);
                    }

                    .document-number-container p {
                        margin-bottom: 0;
                        font-weight: 700;
                        color: #0066cc;
                    }

                    .meta-info {
                        margin-top: 20px;
                        padding-top: 15px;
                        border-top: 1px dashed #dee2e6;
                        font-size: 14px;
                        color: #666;
                    }

                    .meta-info p {
                        margin-bottom: 3px;
                    }

                    .meta-info i {
                        margin-right: 5px;
                        color: #0066cc;
                    }

                    @media (max-width: 768px) {
                        .project-title {
                            text-align: center;
                            margin: 10px auto;
                            display: block;
                            font-size: 24px;
                        }

                        .project-title::after {
                            left: 50%;
                            transform: translateX(-50%);
                        }

                        .document-number-container {
                            display: block;
                            text-align: center;
                            margin: 0 auto;
                            max-width: 200px;
                        }
                    }
                </style>

                <div class="header-container">
                    <!-- Action Buttons -->
                    <div class="header-top">
                        <button class="btn btn-action btn-print" onclick="window.print()">
                            <i class="fas fa-print me-2"></i> Print
                        </button>
                        <button class="btn btn-action btn-back" onclick="history.back()">
                            <i class="fas fa-arrow-left me-2"></i> Back
                        </button>
                    </div>

                    <div class="header-content">
                        <div class="header-main">
                            <div class="row align-items-center">
                                <!-- Logo Section -->
                                <div class="col-md-1 text-center text-md-start mb-3 mb-md-0">
                                    <img src="{{ asset('media/logo/wajenzilogo.png') }}" alt="Company Logo"
                                         class="company-logo">
                                </div>

                                <!-- Address Section -->
                                <div class="col-md-7 company-info mb-3 mb-md-0">
                                    <h2>{{settings('ORGANIZATION_NAME')}}</h2>
                                    <div class="company-address">
                                        <p><i class="fas fa-building me-2"></i> {{settings('COMPANY_ADDRESS_LINE_1')}}
                                        </p>
                                        <p><i class="fas fa-phone-alt me-2"></i>
                                            Tel: {{settings('COMPANY_PHONE_NUMBER')}}</p>
                                        <p><i class="fas fa-envelope me-2"></i> P. O.
                                            Box {{settings('COMPANY_ADDRESS_LINE_2')}}</p>
                                    </div>
                                </div>

                                <!-- Title Section -->
                                <div class="col-md-4 text-md-end">
                                    <h2 class="project-title">Payroll</h2>
                                    @if($payroll->document_number)
                                        <div class="document-number-container">
                                            <p><i class="fas fa-file-contract me-2"></i>
                                                No. {{$payroll->document_number}}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Request Information -->
                            <div class="row meta-info">
                                <div class="col-md-6">
                                    <p><i class="fas fa-user"></i> Requested by
                                        : {{$payroll->user->name ?? 'System Admin'}}</p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <p><i class="fas fa-calendar-alt"></i> Created Time : {{$payroll->created_at}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="details-card">


                <div class="card-header">
                    <h3><i class="fas fa-clipboard-list me-2"></i> Project Details</h3>
                </div>

                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Payroll Number</label>
                            <div class="info-value">{{$payroll->payroll_number ?? null}}</div>
                        </div>

                        <div class="info-item">
                            <label>Month</label>
                            <div class="info-value">{{date('F',strtotime($payroll->year.'-'.$payroll->month.'-'.'01')).' '.$payroll->year}}</div>
                        </div>

                        <div class="info-item">
                            <label>Payroll Number</label>
                            <div class="info-value">{{$payroll->payroll_number ?? null}}</div>
                        </div>


                        <div class="info-item">
                            <label>Status</label>
                            <div class="info-value">
                                {{$payroll->status}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="details-card">
                <div class="block ">
                    <div class="card-header">
                        <h3><i class="fas fa-clipboard-list me-2"></i> Staff List</h3>
                    </div>
                    <div class="block-content">

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full"
                                   data-ordering="false" data-sorting="false">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Name</th>
                                    <th>Basic Salary</th>
                                    <th>Allowance</th>
                                    <th>Gross Pay</th>
                                    <th>Employer Pension</th>
                                    <th>Employee Pension</th>
                                    <th>Taxable</th>
                                    <th>PAYE</th>
                                    <th>WCF</th>
                                    <th>SDL</th>
                                    <th>HESLB</th>
                                    <th>Employer Health</th>
                                    <th>Employee Health</th>
                                    <th>Advance Salary</th>
                                    <th>Total Loan</th>
                                    <th>Loan Deduction</th>
                                    <th>Loan Balance</th>
                                    <th>Adjustment</th>
                                    <th>NET</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php
                                    $start_date = date('Y-m-d',strtotime($payroll->year.'-'.$payroll->month.'-'.'01'));
                                    $end_date = date('Y-m-d',strtotime($payroll->year.'-'.$payroll->month.'-'.'31'));
                                    $sum_total_basic_salary = 0;
                                    $sum_total_advance_salary = 0;
                                    $sum_total_allowance = 0;
                                    $sum_total_gross_pay = 0;
                                    $sum_total_employee_deducted_amount_pension = 0;
                                    $sum_total_employer_deducted_amount_pension = 0;
                                    $sum_total_employee_deducted_amount_health = 0;
                                    $sum_total_employer_deducted_amount_health = 0;
                                    $sum_total_employee_deducted_amount_wcf = 0;
                                    $sum_total_employer_deducted_amount_wcf = 0;
                                    $sum_total_employee_deducted_amount_sdl = 0;
                                    $sum_total_employer_deducted_amount_sdl = 0;
                                    $sum_total_employee_deducted_amount_heslb = 0;
                                    $sum_total_employer_deducted_amount_heslb = 0;
                                    $sum_total_employee_deducted_amount_payee = 0;
                                    $sum_total_employer_deducted_amount_payee = 0;
                                    $sum_total_loan_balance = 0;
                                    $sum_total_current_loan = 0;
                                    $sum_total_loan_deduction = 0;
                                    $sum_total_taxable = 0;
                                    $sum_total_net = 0;
                                    $sum_total_adjustment = 0;
                                @endphp
                                @foreach($payroll_types as $payroll_type)
                                    @php
                                        $payroll_type_id = $payroll_type->id;
                                        $staffs = \App\Models\Staff::getAllStaffSalaryPaid($payroll_id,$payroll_type_id);
                                    @endphp
                                    <tr>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td colspan="20" class="text-center">{{$payroll_type->name}}'s Payroll</td>
                                    </tr>
                                    @php
                                        $total_basic_salary = 0;
                                        $total_advance_salary = 0;
                                        $total_allowance = 0;
                                        $total_gross_pay = 0;
                                        $total_employee_deducted_amount_pension = 0;
                                        $total_employer_deducted_amount_pension = 0;
                                        $total_employee_deducted_amount_health = 0;
                                        $total_employer_deducted_amount_health = 0;
                                        $total_employee_deducted_amount_wcf = 0;
                                        $total_employer_deducted_amount_wcf = 0;
                                        $total_employee_deducted_amount_sdl = 0;
                                        $total_employer_deducted_amount_sdl = 0;
                                        $total_employee_deducted_amount_heslb = 0;
                                        $total_employer_deducted_amount_heslb = 0;
                                        $total_employee_deducted_amount_payee = 0;
                                        $total_employer_deducted_amount_payee = 0;
                                        $total_loan_balance = 0;
                                        $total_current_loan = 0;
                                        $total_loan_deduction = 0;
                                        $total_taxable = 0;
                                        $total_net = 0;
                                        $total_adjustment = 0;
                                    @endphp
                                    @foreach($staffs as $staff)
                                        @php

                                            $month = date('m');
                                            $staff_id = $staff->staff_id;
                                            $basic_salary = \App\Models\Staff::getStaffSalaryPaid($staff_id,$payroll_id);
                                            $total_basic_salary += $basic_salary;
                                            $staff_salary_id = \App\Models\Staff::getStaffSalaryId($staff_id) ?? 0;
                                            $advance_salary = \App\Models\Staff::getStaffAdvanceSalaryPaid($staff_id,$payroll_id) ?? 0;
                                            $total_advance_salary += $advance_salary;
                                            $adjustment = \App\Models\Staff::getStaffAdjustmentPaid($staff_id,$payroll_id) ?? 0;
                                            $total_adjustment += $adjustment;
                                            $allowance = \App\Models\Staff::getStaffAllowancePaid($staff_id,$payroll_id) ?? 0;
                                            $total_allowance += $allowance;
                                            $gross_pay = \App\Models\Staff::getStaffGrossPayPaid($staff_id,$payroll_id) ?? 0;
                                            $total_gross_pay += $gross_pay;
                                            $employee_deducted_amount_pension = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,2,'employee_deduction_amount') ?? 0;
                                            $total_employee_deducted_amount_pension += $employee_deducted_amount_pension;
                                            $employer_deducted_amount_pension = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,2,'employer_deduction_amount') ?? 0;
                                            $total_employer_deducted_amount_pension += $employer_deducted_amount_pension;
                                            $employee_deducted_amount_health = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,6,'employee_deduction_amount') ?? 0;
                                            $total_employee_deducted_amount_health += $employee_deducted_amount_health;
                                            $employer_deducted_amount_health = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,6,'employer_deduction_amount') ?? 0;
                                            $total_employer_deducted_amount_health += $employer_deducted_amount_health;
                                            $employee_deducted_amount_wcf = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,3,'employee_deduction_amount') ?? 0;
                                            $total_employee_deducted_amount_wcf += $employee_deducted_amount_wcf;
                                            $employer_deducted_amount_wcf = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,3,'employer_deduction_amount') ?? 0;
                                            $total_employer_deducted_amount_wcf += $employer_deducted_amount_wcf;
                                            $employee_deducted_amount_sdl = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,5,'employee_deduction_amount') ?? 0;
                                            $total_employee_deducted_amount_sdl += $employee_deducted_amount_sdl;
                                            $employer_deducted_amount_sdl = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,5,'employer_deduction_amount') ?? 0;
                                            $total_employer_deducted_amount_sdl += $employer_deducted_amount_sdl;
                                            $employee_deducted_amount_heslb = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,4,'employee_deduction_amount') ?? 0;
                                            $total_employee_deducted_amount_heslb += $employee_deducted_amount_heslb;
                                            $employer_deducted_amount_heslb = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,4,'employer_deduction_amount') ?? 0;
                                            $total_employer_deducted_amount_heslb += $employer_deducted_amount_heslb;
                                            $employee_deducted_amount_payee = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,1,'employee_deduction_amount') ?? 0;
                                            $total_employee_deducted_amount_payee += $employee_deducted_amount_payee;
                                            $employer_deducted_amount_payee = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,1,'employer_deduction_amount') ?? 0;
                                            $total_employer_deducted_amount_payee += $employer_deducted_amount_payee;
                                            $loan_balance = \App\Models\Staff::getStaffLoanBalancePaid($staff_id,$payroll_id) ?? 0;
                                            $total_loan_balance += $loan_balance;
                                            $current_loan = \App\Models\Staff::getStaffLoanPaid($staff_id,$payroll_id) ?? 0;
                                            $total_current_loan += $current_loan;
                                            $loan_deduction = \App\Models\Staff::getStaffLoanDeductionPaid($staff_id,$payroll_id) ?? 0;
                                            $total_loan_deduction += $loan_deduction;
                                            $taxable = \App\Models\Staff::getStaffTaxablePaid($staff_id,$payroll_id) ?? 0;
                                            $total_taxable += $taxable;
                                            $net = \App\Models\Staff::getStaffNetPaid($staff_id,$payroll_id) ?? 0;
                                            $total_net += $net;
                                        @endphp
                                        <tr>
                                            <td>{{$loop->iteration}}</td>
                                            <td>{{$staff->name ?? null}}</td>
                                            <td class="text-right">{{number_format($basic_salary)}}</td>
                                            <td class="text-right">{{number_format($allowance)}}</td>
                                            <td class="text-right">{{number_format($gross_pay)}}</td>
                                            <td class="text-right">{{number_format($employer_deducted_amount_pension)}}</td>
                                            <td class="text-right">{{number_format($employee_deducted_amount_pension)}}</td>
                                            <td class="text-right">{{number_format($taxable)}}</td>
                                            <td class="text-right">{{number_format($employee_deducted_amount_payee)}}</td>
                                            <td class="text-right">{{number_format($employer_deducted_amount_wcf)}}</td>
                                            <td class="text-right">{{number_format($employer_deducted_amount_sdl)}}</td>
                                            <td class="text-right">{{number_format($employee_deducted_amount_heslb)}}</td>
                                            <td class="text-right">{{number_format($employer_deducted_amount_health)}}</td>
                                            <td class="text-right">{{number_format($employee_deducted_amount_health)}}</td>
                                            <td class="text-right">{{number_format($advance_salary)}}</td>
                                            <td class="text-right">{{number_format($current_loan)}}</td>
                                            <td class="text-right">{{number_format($loan_deduction)}}</td>
                                            <td class="text-right">{{number_format($loan_balance)}}</td>
                                            <td class="text-right">{{number_format($adjustment)}}</td>
                                            <td class="text-right">{{number_format($net)}}</td>
                                        </tr>
                                    @endforeach
                                    @php
                                        $sum_total_basic_salary += $total_basic_salary;
                                        $sum_total_advance_salary += $total_advance_salary;
                                        $sum_total_allowance += $total_allowance;
                                        $sum_total_gross_pay += $total_gross_pay;
                                        $sum_total_employee_deducted_amount_pension += $total_employee_deducted_amount_pension;
                                        $sum_total_employer_deducted_amount_pension += $total_employer_deducted_amount_pension;
                                        $sum_total_employee_deducted_amount_health += $total_employee_deducted_amount_health;
                                        $sum_total_employer_deducted_amount_health += $total_employer_deducted_amount_health;
                                        $sum_total_employee_deducted_amount_wcf += $total_employee_deducted_amount_wcf;
                                        $sum_total_employer_deducted_amount_wcf += $total_employer_deducted_amount_wcf;
                                        $sum_total_employee_deducted_amount_sdl += $total_employee_deducted_amount_sdl;
                                        $sum_total_employer_deducted_amount_sdl += $total_employer_deducted_amount_sdl;
                                        $sum_total_employee_deducted_amount_heslb += $total_employee_deducted_amount_heslb;
                                        $sum_total_employer_deducted_amount_heslb += $total_employer_deducted_amount_heslb;
                                        $sum_total_employee_deducted_amount_payee += $total_employee_deducted_amount_payee;
                                        $sum_total_employer_deducted_amount_payee += $total_employer_deducted_amount_payee;
                                        $sum_total_loan_balance += $total_loan_balance;
                                        $sum_total_current_loan += $total_current_loan;
                                        $sum_total_loan_deduction += $total_loan_deduction;
                                        $sum_total_taxable += $total_taxable;
                                        $sum_total_adjustment += $total_adjustment;
                                        $sum_total_net += $total_net;
                                    @endphp
                                    <tr>
                                        <th></th>
                                        <th>{{$payroll_type->name}}'s Total</th>
                                        <th class="text-right">{{number_format($total_basic_salary)}}</th>
                                        <th class="text-right">{{number_format($total_allowance)}}</th>
                                        <th class="text-right">{{number_format($total_gross_pay)}}</th>
                                        <th class="text-right">{{number_format($total_employer_deducted_amount_pension)}}</th>
                                        <th class="text-right">{{number_format($total_employee_deducted_amount_pension)}}</th>
                                        <th class="text-right">{{number_format($total_taxable)}}</th>
                                        <th class="text-right">{{number_format($total_employee_deducted_amount_payee)}}</th>
                                        <th class="text-right">{{number_format($total_employer_deducted_amount_wcf)}}</th>
                                        <th class="text-right">{{number_format($total_employer_deducted_amount_sdl)}}</th>
                                        <th class="text-right">{{number_format($total_employee_deducted_amount_heslb)}}</th>
                                        <th class="text-right">{{number_format($total_employer_deducted_amount_health)}}</th>
                                        <th class="text-right">{{number_format($total_employee_deducted_amount_health)}}</th>
                                        <th class="text-right">{{number_format($total_advance_salary)}}</th>
                                        <th class="text-right">{{number_format($total_current_loan)}}</th>
                                        <th class="text-right">{{number_format($total_loan_deduction)}}</th>
                                        <th class="text-right">{{number_format($total_loan_balance)}}</th>
                                        <th class="text-right">{{number_format($total_adjustment)}}</th>
                                        <th class="text-right">{{number_format($total_net)}}</th>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th></th>
                                    <th>All Total</th>
                                    <th class="text-right">{{number_format($sum_total_basic_salary)}}</th>
                                    <th class="text-right">{{number_format($sum_total_allowance)}}</th>
                                    <th class="text-right">{{number_format($sum_total_gross_pay)}}</th>
                                    <th class="text-right">{{number_format($sum_total_employer_deducted_amount_pension)}}</th>
                                    <th class="text-right">{{number_format($sum_total_employee_deducted_amount_pension)}}</th>
                                    <th class="text-right">{{number_format($sum_total_taxable)}}</th>
                                    <th class="text-right">{{number_format($sum_total_employee_deducted_amount_payee)}}</th>
                                    <th class="text-right">{{number_format($sum_total_employer_deducted_amount_wcf)}}</th>
                                    <th class="text-right">{{number_format($sum_total_employer_deducted_amount_sdl)}}</th>
                                    <th class="text-right">{{number_format($sum_total_employee_deducted_amount_heslb)}}</th>
                                    <th class="text-right">{{number_format($sum_total_employer_deducted_amount_health)}}</th>
                                    <th class="text-right">{{number_format($sum_total_employee_deducted_amount_health)}}</th>
                                    <th class="text-right">{{number_format($sum_total_advance_salary)}}</th>
                                    <th class="text-right">{{number_format($sum_total_current_loan)}}</th>
                                    <th class="text-right">{{number_format($sum_total_loan_deduction)}}</th>
                                    <th class="text-right">{{number_format($sum_total_loan_balance)}}</th>
                                    <th class="text-right">{{number_format($sum_total_adjustment)}}</th>
                                    <th class="text-right">{{number_format($sum_total_net)}}</th>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Approvals Section -->
            <div class="approvals-section">
                <style>
                    .approvals-section {
                        background-color: #fff;
                        border-radius: 10px;
                        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
                        margin-bottom: 30px;
                        overflow: hidden;
                        border: 1px solid rgba(0, 0, 0, 0.05);
                    }

                    .section-header {
                        background-color: #f8f9fa;
                        padding: 15px 25px;
                        border-bottom: 1px solid #e9ecef;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    }

                    .section-title {
                        margin: 0;
                        color: #0066cc;
                        font-weight: 600;
                        font-size: 18px;
                        display: flex;
                        align-items: center;
                    }

                    .section-title i {
                        margin-right: 10px;
                        color: #0066cc;
                    }

                    .section-body {
                        padding: 25px;
                    }

                    .approval-steps {
                        position: relative;
                        margin-bottom: 20px;
                    }

                    .approval-timeline {
                        position: absolute;
                        top: 0;
                        bottom: 0;
                        left: 20px;
                        width: 2px;
                        background-color: #dee2e6;
                        z-index: 1;
                    }

                    .approval-submit-container {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        background-color: #f8f9fa;
                        padding: 15px 25px;
                        border-radius: 8px;
                        margin-top: 20px;
                    }

                    .submit-message {
                        color: #6c757d;
                        font-size: 15px;
                    }

                    .btn-submit {
                        background-color: #4CAF50;
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        border-radius: 6px;
                        font-weight: 600;
                        transition: all 0.3s ease;
                        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                    }

                    .btn-submit:hover {
                        background-color: #3e8e41;
                        transform: translateY(-2px);
                        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
                    }

                    /* Status indicators */
                    .status-indicator {
                        display: inline-block;
                        width: 12px;
                        height: 12px;
                        border-radius: 50%;
                        margin-right: 8px;
                    }

                    .status-pending {
                        background-color: #ffc107;
                    }

                    .status-approved {
                        background-color: #4CAF50;
                    }

                    .status-rejected {
                        background-color: #dc3545;
                    }

                    .status-waiting {
                        background-color: #6c757d;
                    }
                </style>

                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-tasks"></i> Approval Flow
                    </h2>
                    <div class="flow-status">
                        <span class="badge bg-info">In Progress</span>
                    </div>
                </div>

                <div class="section-body">
                    <!-- Approval Component -->
                    <x-ringlesoft-approval-actions :model="$payroll"/>
                </div>
            </div>
            <div>
                @if($payroll->status == 'APPROVED')
                    <div class="col-md-10">
                        <button type="button"
                                onclick="loadFormModal('net_form', {className: 'Payroll', id:{{$payroll_id}}}, 'NET Salary for {{date('F',strtotime($payroll->year.'-'.$payroll->month.'-'.'01')).' '.$payroll->year}}', 'modal-xl');"
                                class="btn btn-rounded btn-outline-primary mb-10"><i class="si si-list">&nbsp;</i>NETS
                        </button>
                        <button type="button"
                                onclick="loadFormModal('paye_form', {className: 'Payroll', id:{{$payroll_id}}}, 'PAYE for {{date('F',strtotime($payroll->year.'-'.$payroll->month.'-'.'01')).' '.$payroll->year}}', 'modal-xl');"
                                class="btn btn-rounded btn-outline-primary mb-10"><i class="si si-list">&nbsp;</i>PAYE
                        </button>
                        <button type="button"
                                onclick="loadFormModal('sdl_form', {className: 'Payroll', id:{{$payroll_id}}}, 'SDL for {{date('F',strtotime($payroll->year.'-'.$payroll->month.'-'.'01')).' '.$payroll->year}}', 'modal-xl');"
                                class="btn btn-rounded btn-outline-primary mb-10"><i class="si si-list">&nbsp;</i>SDL
                        </button>
                        <button type="button"
                                onclick="loadFormModal('nssf_form', {className: 'Payroll', id:{{$payroll_id}}}, 'NSSF for {{date('F',strtotime($payroll->year.'-'.$payroll->month.'-'.'01')).' '.$payroll->year}}', 'modal-xl');"
                                class="btn btn-rounded btn-outline-primary mb-10"><i class="si si-list">&nbsp;</i>NSSF
                        </button>
                        <button type="button"
                                onclick="loadFormModal('nhif_form', {className: 'Payroll', id:{{$payroll_id}}}, 'NHIF for {{date('F',strtotime($payroll->year.'-'.$payroll->month.'-'.'01')).' '.$payroll->year}}', 'modal-xl');"
                                class="btn btn-rounded btn-outline-primary mb-10"><i class="si si-list">&nbsp;</i>NHIF
                        </button>
                        <button type="button"
                                onclick="loadFormModal('wcf_form', {className: 'Payroll', id:{{$payroll_id}}}, 'WCF for {{date('F',strtotime($payroll->year.'-'.$payroll->month.'-'.'01')).' '.$payroll->year}}', 'modal-xl');"
                                class="btn btn-rounded btn-outline-primary mb-10"><i class="si si-list">&nbsp;</i>WCF
                        </button>
                        <button type="button"
                                onclick="loadFormModal('heslb_form', {className: 'Payroll', id:{{$payroll_id}}}, 'HESLB for {{date('F',strtotime($payroll->year.'-'.$payroll->month.'-'.'01')).' '.$payroll->year}}', 'modal-xl');"
                                class="btn btn-rounded btn-outline-primary mb-10"><i class="si si-list">&nbsp;</i>HESLB
                        </button>
                    </div>
                    <div class="col-md-2">
                        <span class='text-primary'><i
                                class='fa fa-check '>&nbsp;&nbsp;&nbsp;</i> Payrolls Approved</span>
                    </div>
                @endif
            </div>

        </div>

    </div>

@endsection


<script>
    function showComments(comment) {
        swal.fire({
            title: "Comments",
            text: comment,
            // type: "input"
        });
    }

    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>




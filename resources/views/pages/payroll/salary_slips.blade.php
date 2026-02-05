@extends('layouts.backend')

@section('content')
    <?php

    use Illuminate\Support\Facades\DB;

    $start_date = $_POST['start_date'] ?? date('Y-m-01');
    $end_date = $_POST['end_date'] ?? date('Y-m-t');

    ?>
    <?php
    ?>
    <style>
        :root {
            --primary: #343a40;
            --secondary: #555555;
            --accent: #007bff;
            --light-bg: #f8f9fa;
            --border: #dee2e6;
            --text: #212529;
            --muted: #6c757d;
        }

        .payslip-premium {
            font-family: 'Segoe UI', Arial, sans-serif;
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 0.25rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            overflow: hidden;
            background-color: white;
            margin-bottom: 30px;
        }

        .payslip-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 2px solid var(--border);
            background: linear-gradient(to right, var(--light-bg), white);
        }

        .company-branding {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .logo-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            padding: 5px;
            background-color: white;
            border: 1px solid var(--border);
        }

        .logo-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .company-info {
            line-height: 1.4;
        }

        .company-info p {
            margin: 0;
            padding: 0;
            color: var(--secondary);
        }

        .company-info .company-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .payslip-label {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent);
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .employee-details {
            background-color: white;
            border-bottom: 1px solid var(--border);
        }

        .employee-details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .employee-details-table td {
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
        }

        .detail-label {
            font-weight: 600;
            color: var(--primary);
            background-color: rgba(0, 0, 0, 0.02);
            width: 30%;
        }

        .detail-value {
            color: var(--text);
        }

        .salary-details {
            padding: 1.5rem;
        }

        .salary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .salary-table th,
        .salary-table td {
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            text-align: left;
        }

        .salary-table thead th {
            background-color: var(--light-bg);
            font-weight: 600;
            color: var(--primary);
            text-transform: uppercase;
            font-size: 0.875rem;
        }

        .salary-table tbody th {
            font-weight: 600;
            color: var(--primary);
            background-color: rgba(0, 0, 0, 0.02);
        }

        .section-title {
            background-color: var(--light-bg);
            font-weight: 600;
            color: var(--accent);
        }

        .money {
            font-family: 'Consolas', 'Courier New', monospace;
            white-space: nowrap;
        }

        .text-right {
            text-align: right !important;
        }

        .summary-row th {
            background-color: var(--light-bg);
            border-bottom: 1px solid var(--border);
        }

        .net-salary-row td {
            background-color: var(--primary);
            color: white;
            font-weight: 700;
            padding: 1rem;
            border: 1px solid var(--primary);
        }

        .net-salary-label {
            font-size: 1.1rem;
            text-transform: uppercase;
        }

        .net-salary-value {
            font-size: 1.25rem;
            letter-spacing: 1px;
        }

        .payslip-footer {
            padding: 1rem;
            text-align: center;
            color: var(--muted);
            font-size: 0.875rem;
            border-top: 1px solid var(--border);
            background-color: var(--light-bg);
        }

        /* Button Styles */
        .action-buttons {
            padding: 10px 0;
        }

        .btn {
            display: inline-block;
            font-weight: 400;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border-radius: 0.2rem;
        }

        .btn-primary {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            color: #fff;
            background-color: #0069d9;
            border-color: #0062cc;
        }

        .btn-success {
            color: #fff;
            background-color: #28a745;
            border-color: #28a745;
        }

        .btn-success:hover {
            color: #fff;
            background-color: #218838;
            border-color: #1e7e34;
        }

        .mr-1 {
            margin-right: 0.25rem;
        }

        .mr-2 {
            margin-right: 0.5rem;
        }

        .mb-3 {
            margin-bottom: 1rem;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .payslip-premium {
                box-shadow: none;
                border: 1px solid var(--secondary);
            }

            body {
                padding: 0;
                margin: 0;
            }
        }
    </style>


    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Salary Slip
                @php
                    $this_year = $_POST['year'] ?? date('Y');
                    $this_month = $_POST['month'] ?? date('m');
                    $this_employee = $_POST['staff_id'] ?? 11;
                    $staff_id = $this_employee;
                    $payroll = \App\Models\Payroll::getThisPayrollApproved($this_month,$this_year);
                    $payroll_id = $payroll['id'];
                    $employee = \App\Models\User::find($this_employee);
                    $employee_bank_details = \App\Models\StaffBankDetail::where('staff_id',$this_employee)->get()->first();
                    $basic_salary = \App\Models\Staff::getStaffSalaryPaid($this_employee,$payroll_id);
                    $total_deduction = 0;
                    $gross_salary = \App\Models\Staff::getStaffGrossPayPaid($this_employee,$payroll_id) ?? 0;
                    $net_salary = \App\Models\Staff::getStaffNetPaid($staff_id,$payroll_id) ?? 0;
                    $advance_salary = \App\Models\Staff::getStaffAdvanceSalaryPaid($staff_id,$payroll_id) ?? 0;
                    $loan_balance = \App\Models\Staff::getStaffLoanBalancePaid($staff_id,$payroll_id) ?? 0;
                    $current_loan = \App\Models\Staff::getStaffLoanPaid($staff_id,$payroll_id) ?? 0;
                    $loan_deduction = \App\Models\Staff::getStaffLoanDeductionPaid($staff_id,$payroll_id) ?? 0;
                    $taxable = \App\Models\Staff::getStaffTaxablePaid($staff_id,$payroll_id) ?? 0;




                    $gross_salary_check = 0;
                    $allowances = \App\Models\Allowance::select('allowance_subscriptions.amount as amount','allowances.name as allowance_name','allowances.allowance_type as allowance_type')->join('allowance_subscriptions','allowance_subscriptions.allowance_id','=','allowances.id')->
                                   where('allowance_subscriptions.staff_id',$staff_id)->get();
                    $deductions = \App\Models\Deduction::select('deduction_settings.employee_percentage as employee_deducted_percentage','deductions.id as deduction_id','deductions.name as keyword')->join('deduction_settings','deduction_settings.deduction_id','=','deductions.id')->
                                    join('deduction_subscriptions','deduction_subscriptions.deduction_id','deductions.id')->
                                    where('deductions.id','!=',1)->where('deduction_subscriptions.staff_id',$staff_id)->get();


                    $left_side = [
                        ['name' => 'Basic Salary', 'value' => $basic_salary ]
                    ];

                $right_side = [
                    ['name' => 'Advance Salary', 'value' => $advance_salary ],
                    ['name' => 'Loan', 'value' => $loan_balance ],
                    ['name' => 'Loan Deduction', 'value' => $loan_deduction ],
                    ['name' => 'Loan Balance', 'value' => ($current_loan - $loan_deduction) ],
                ];

                foreach ($allowances as $allowance) {
                    $allowance_type = $allowance->allowance_type;
                    $allowance_amount_first = $allowance->amount;
                    $allowance_amount = \App\Models\Allowance::getAllowanceAmountPerType($allowance_type,$allowance_amount_first,$this_month);
                    if ($allowance_amount > 0) {
                        $gross_salary_check += $allowance_amount;
                        array_push($left_side, ['name' => strtoupper($allowance->allowance_name), 'value' => $allowance_amount]);
                    }
                }

                // Add PAYEE at the top first
                $employee_deducted_amount_payee = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id, 1, 'employee_deduction_amount') ?? 0;

                // Add PAYEE to the total deduction
                if ($employee_deducted_amount_payee > 0) {
                    $total_deduction += $employee_deducted_amount_payee;
                    array_push($right_side, ['name' => 'PAYEE', 'value' => $employee_deducted_amount_payee]);
                }

                // Then continue with your loop for other deductions
                foreach ($deductions as $deduction) {
                    $deduction_id = $deduction['deduction_id'];
                    $deducted_amount = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id, $deduction_id, 'employee_deduction_amount') ?? 0;
                    $total_deduction += $deducted_amount;
                    $percentage = $deduction['employee_deducted_percentage'] > 0 ? "({$deduction['employee_deducted_percentage']}%)" : '';
                    $deduction_title = $deduction['keyword'];

                    // Include all deductions, but we don't need to add PAYEE twice
                    if ($deducted_amount > 0 && $deduction_id != 1) {
                        array_push($right_side, ['name' => strtoupper($deduction_title) . $percentage, 'value' => $deducted_amount]);
                    }
                }

                @endphp

                <div class="float-right">

                </div>
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <div class="row" style="border-bottom: 3px solid gray">
                                        <div class="col-md-3 text-right">
                                            <img class="" src="{{ asset('media/logo/wajenzilogo.png') }}" alt=""
                                                 height="100">
                                        </div>
                                        <div class="col-md-6 text-center">
                                            <span
                                                class="text-center font-size-h3">{{settings('ORGANIZATION_NAME')}}</span><br/>
                                            <span
                                                class="text-center font-size-h5">{{settings('COMPANY_ADDRESS_LINE_1')}}</span><br/>
                                            <span
                                                class="text-center font-size-h5">{{settings('COMPANY_ADDRESS_LINE_2')}}</span><br/>
                                            <span
                                                class="text-center font-size-h5">{{settings('COMPANY_PHONE_NUMBER')}}</span><br/>
                                            <span
                                                class="text-center font-size-h5">{{settings('TAX_IDENTIFICATION_NUMBER')}}</span><br/>
                                        </div>
                                        <div class="col-md-3 text-right">
                                            {{--                                            <a href="{{route('reports')}}"   type="button" class="btn btn-sm btn-danger"><i class="fa fa arrow-left"></i>Back</a>--}}
                                        </div>
                                    </div>
                                </div>
                                <br/>
                                <div class="class card-box">
                                    <form name="gross_search" action="" id="filter-form" method="post"
                                          autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Month</span>
                                                    </div>
                                                    <select name="month" id="month" class="form-control">
                                                        <option value="1" {{ ($this_month == 1) ? 'selected' : '' }}>
                                                            Jan
                                                        </option>
                                                        <option value="2" {{ ($this_month == 2) ? 'selected' : '' }}>
                                                            Feb
                                                        </option>
                                                        <option value="3" {{ ($this_month == 3) ? 'selected' : '' }}>
                                                            Mar
                                                        </option>
                                                        <option value="4" {{ ($this_month == 4) ? 'selected' : '' }}>
                                                            Apr
                                                        </option>
                                                        <option value="5" {{ ($this_month == 5) ? 'selected' : '' }}>
                                                            May
                                                        </option>
                                                        <option value="6" {{ ($this_month == 6) ? 'selected' : '' }}>
                                                            Jun
                                                        </option>
                                                        <option value="7" {{ ($this_month == 7) ? 'selected' : '' }}>
                                                            Jul
                                                        </option>
                                                        <option value="8" {{ ($this_month == 8) ? 'selected' : '' }}>
                                                            Aug
                                                        </option>
                                                        <option value="9" {{ ($this_month == 9) ? 'selected' : '' }}>
                                                            Sept
                                                        </option>
                                                        <option value="10" {{ ($this_month == 10) ? 'selected' : '' }}>
                                                            Oct
                                                        </option>
                                                        <option value="11" {{ ($this_month == 11) ? 'selected' : '' }}>
                                                            Nov
                                                        </option>
                                                        <option value="12" {{ ($this_month == 12) ? 'selected' : '' }}>
                                                            Dec
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Year</span>
                                                    </div>
                                                    <select name="year" id="year" class="form-control">
                                                        {{--                                                        <option value="2021" {{ ($this_year == 2021) ? 'selected' : '' }}>2021</option>--}}
                                                        {{--                                                        <option value="2022" {{ ($this_year == 2022) ? 'selected' : '' }}>2022</option>--}}
                                                        {{--                                                        <option value="2023" {{ ($this_year == 2023) ? 'selected' : '' }}>2023</option>--}}
                                                        {{--                                                        <option value="2024" {{ ($this_year == 2024) ? 'selected' : '' }}>2024</option>--}}
                                                        <option
                                                            value="2025" {{ ($this_year == 2025) ? 'selected' : '' }}>
                                                            2025
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Employee</span>
                                                    </div>
                                                    <select name="staff_id" id="input-staff-id"
                                                            class="form-control select2" required>
                                                        @foreach($staffs as $staff)
                                                            <option
                                                                value="{{ $staff->id }}"> {{ $staff->name }} </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-2">
                                                <div>
                                                    <button type="submit" name="submit" class="btn btn-sm btn-primary">
                                                        Show
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>


                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full"
                                   id="payroll">


                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @if($payroll)
                <div>
                    <div class="block block-themed">
                        <div class="block-content">
                            <div class="row m-t-10">
                                <div class="class col-md-12">
                                    <div class="class card-box">
                                        <!-- Action Buttons -->
                                        <div class="action-buttons no-print mb-3 text-right">
                                            <button type="button" class="btn btn-primary btn-sm mr-2"
                                                    onclick="printPayslip()">
                                                <i class="fa fa-print mr-1"></i> Print Payslip
                                            </button>
                                            <button type="button" class="btn btn-success btn-sm"
                                                    onclick="exportToPDF()">
                                                <i class="fa fa-file-pdf-o mr-1"></i> Export to PDF
                                            </button>
                                        </div>

                                        <div class="table-responsive" id="payslip-container">
                                            <div class="payslip-premium">
                                                <div class="payslip-header">
                                                    <div class="company-branding">
                                                        <div class="logo-wrapper">
                                                            <img src="{{ asset('media/logo/wajenzilogo.png') }}"
                                                                 alt="Company Logo">
                                                        </div>
                                                        <div class="company-info">
                                                            <p class="company-name">{{settings('ORGANIZATION_NAME')}}</p>
                                                            <p>{{settings('COMPANY_ADDRESS_LINE_1')}}</p>
                                                            <p>{{settings('COMPANY_ADDRESS_LINE_2')}}</p>
                                                            <p>{{settings('COMPANY_PHONE_NUMBER')}}</p>
                                                            <p>{{settings('COMPANY_EMAIL_ADDRESS')}}</p>
                                                        </div>
                                                    </div>
                                                    <div class="payslip-label">Payslip</div>
                                                </div>

                                                <div class="employee-details">
                                                    <table class="employee-details-table">
                                                        <tr>
                                                            <td class="detail-label">Payroll Number:</td>
                                                            <td class="detail-value">{{$payroll['payroll_number']}}</td>
                                                            <td class="detail-label">Payroll Month:</td>
                                                            <td class="detail-value">{{date('F',strtotime($payroll->year.'-'.$payroll->month.'-'.'01')).' - '.$payroll->year}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="detail-label">Employee Number:</td>
                                                            <td class="detail-value">
                                                                HRM/LE/PO-{{$employee->employee_number ?? null}}</td>
                                                            <td class="detail-label">Employee Name:</td>
                                                            <td class="detail-value">{{$employee->name ?? null}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="detail-label">Department:</td>
                                                            <td class="detail-value">Human Resources &amp;
                                                                Administration (HRA)
                                                            </td>
                                                            <td class="detail-label">Designation:</td>
                                                            <td class="detail-value">{{$employee->designation ?? null}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="detail-label">Bank Name:</td>
                                                            <td class="detail-value">{{$employee_bank_details->bank->name ?? null}}</td>
                                                            <td class="detail-label">Account Number:</td>
                                                            <td class="detail-value">{{$employee_bank_details->account_number ?? null}}</td>
                                                        </tr>
                                                    </table>
                                                </div>

                                                <div class="salary-details">
                                                    <table class="salary-table">
                                                        <thead>
                                                        <tr>
                                                            <th>DETAILS</th>
                                                            <th class="text-right">AMOUNT</th>
                                                            <th>DETAILS</th>
                                                            <th class="text-right">AMOUNT</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        <tr>
                                                            <th class="section-title">Employee Income</th>
                                                            <td></td>
                                                            <th class="section-title">Deductions</th>
                                                            <td></td>
                                                        </tr>

                                                        @php
                                                            $max = count($left_side) > count($right_side) ? count($left_side) : count($right_side);
                                                            foreach (range(0, $max -1 ) as $index) {
                                                                echo "<tr>
                                                                        <td>". (isset($left_side[$index]) ? $left_side[$index]['name'] : '') . "</td>
                                                                        <td class='money text-right'>". (isset($left_side[$index]) ? \App\Classes\Utility::money_format($left_side[$index]['value']): ''). "</td>
                                                                        <td>". (isset($right_side[$index]) ? $right_side[$index]['name'] : '') ."</td>
                                                                        <td class='money text-right'>". (isset($right_side[$index]) ? \App\Classes\Utility::money_format($right_side[$index]['value']) : '')."</td>
                                                                    </tr>";
                                                            }
                                                        @endphp

                                                        <tr class="summary-row">
                                                            <th>Gross Salary</th>
                                                            <td class="money text-right">{{number_format($gross_salary)}}</td>
                                                            <th>Total Deductions</th>
                                                            <td class="money text-right">{{number_format($total_deduction+$advance_salary+$loan_deduction)}}</td>
                                                        </tr>
                                                        </tbody>
                                                        <tfoot>
                                                        <tr class="net-salary-row">
                                                            <td colspan="3" class="net-salary-label">NET SALARY</td>
                                                            <td class="money text-right net-salary-value">
                                                                {{number_format($net_salary)}}
                                                            </td>
                                                        </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>

                                                <div class="payslip-footer">
                                                    This is a computer-generated payslip and does not require a
                                                    signature.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            @else
                <div>
                    <div class="block block-themed bg-gray min-height-200 text-center">
                        <div class="block-content">
                            <div class="row no-print m-t-10">
                                <div class="class col-md-12">
                                    <div class="class card-box ">
                                        <div class='jumbotron '>Failed to get salary slip for this month!</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

@endsection

<!-- JavaScript for Print and PDF Export -->
<script>
    // Print functionality
    function printPayslip() {
        window.print();
    }

    // PDF Export functionality
    function exportToPDF() {
        // Check if jsPDF is available
        if (typeof window.jspdf === 'undefined') {
            // Load jsPDF dynamically if it's not already loaded
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
        } else {
            generatePDF();
        }
    }

    function generatePDF() {
        // Use jsPDF and html2canvas to generate PDF
        const {jsPDF} = window.jspdf;

        // Get the payslip element
        var element = document.getElementById('payslip-container');

        // Create a new jsPDF instance
        var doc = new jsPDF('p', 'mm', 'a4');

        // Hide action buttons for PDF generation
        var actionButtons = document.querySelector('.action-buttons');
        if (actionButtons) {
            actionButtons.style.display = 'none';
        }

        // Convert the element to canvas
        html2canvas(element, {
            scale: 2,
            logging: false,
            useCORS: true
        }).then(function (canvas) {
            // Show action buttons again
            if (actionButtons) {
                actionButtons.style.display = 'block';
            }

            // Calculate dimensions
            var imgWidth = 210; // A4 width in mm (210mm)
            var pageHeight = 297; // A4 height in mm (297mm)
            var imgHeight = canvas.height * imgWidth / canvas.width;
            var heightLeft = imgHeight;

            var imgData = canvas.toDataURL('image/png');
            var position = 0;

            // Add image to PDF
            doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;

            // Add new pages if the content is longer than one page
            while (heightLeft >= 0) {
                position = heightLeft - imgHeight;
                doc.addPage();
                doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }

            // Get employee name for filename or use default
            var employeeName = '{{$employee->name ?? "Employee"}}';
            var payrollMonth = '{{date("F_Y", strtotime(($payroll->year ?? date('Y'))."-".($payroll->month ?? date('m'))."-01"))}}';

            // Generate filename
            var filename = 'Payslip_' + employeeName.replace(/\s+/g, '_') + '_' + payrollMonth + '.pdf';

            // Save the PDF
            doc.save(filename);
        });
    }
</script>


<?php
$document_id = \App\Classes\Utility::getLastId('Payroll')+1;
$year = request()->input('year') ?? $possible_year ?? date('Y');
$month = request()->input('month') ?? $possible_month ?? date('m');
$is_payroll_created = \App\Models\Payroll::getIsPayrollOpened($month,$year);
$payroll = \App\Models\Payroll::getThisPayroll($month,$year);
$payroll_id = $payroll['id'] ?? null;
//echo phpinfo();
?>
<div class="block-content">
    <div class="row">
        <div class="col-md-4">Payroll Statement: {{ ($is_payroll_created == 1) ? date('F',strtotime("01-$payroll->month-$payroll->year")).','.$payroll['year'] :  date('F',strtotime("01-$month-$year")).','.$year }}</div>
        <div class="col-md-4">Payroll Number: {{ ($is_payroll_created == 1) ? $payroll->payroll_number  :  0 }}</div>
        <div class="col-md-4"><span title="Status: {{ ($is_payroll_created == 1) ? $payroll->status  :  'OPEN' }}" style="margin: 0 .4em; padding: .5em .8em;" class="pull-right label {{ ($is_payroll_created == 1) ? 'badge-primary'  :  'badge-warning' }}"><i class="{{ ($is_payroll_created == 1) ? 'fa fa-lock'  :  'fa fa-unlock' }}">&nbsp;</i>
                {{ ($is_payroll_created == 1) ? $payroll->status  :  'OPEN' }}            </span><span class="pull-right">Created Date : {{ ($is_payroll_created == 1) ? $payroll->submitted_date  :  '' }} </span></div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-vcenter js-dataTable-full" data-ordering="false" data-sorting="false">
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

                $start_date = date("$year-$month-01") ?? date('Y-m-01');
                $end_date = date("$year-$month-t")  ?? date('Y-m-t');
                $start_date_attend = date("$year-$month-01") ?? date('Y-m-01');
                $end_date_attend = date("$year-$month-t")  ?? date('Y-m-t');


            @endphp
            @if($is_payroll_created == 1)

                @php
                    $start_date = $_POST['start_date'] ?? date('Y-m-01');
                    $end_date = $_POST['end_date'] ?? date('Y-m-t');
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
                @endphp
                @foreach($payroll_types as $payroll_type)
                    @php
                        $payroll_type_id = $payroll_type->id;
                        $all_staffs = \App\Models\Staff::getAllStaffSalaryWithPayrollType($payroll_type_id);
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
                        $sum_total_adjustment = 0;
                    @endphp
                    @foreach($all_staffs as $staff)
                        @php
                            $month = date('m');
                            $staff_id = $staff['id'];
                            $staff_name = \App\Models\Staff::getUserName($staff_id);
                            $basic_salary = \App\Models\Staff::getStaffSalaryPaid($staff_id,$payroll_id);
                            $total_basic_salary += $basic_salary;
                            $staff_salary_id = \App\Models\Staff::getStaffSalaryId($staff_id) ?? 0;
                            $advance_salary = \App\Models\Staff::getStaffAdvanceSalaryPaid($staff_id,$payroll_id) ?? 0;
                            $total_advance_salary += $advance_salary;
                            $allowance = \App\Models\Staff::getStaffAllowancePaid($staff_id,$payroll_id) ?? 0;
                            $total_allowance += $allowance;
                            $adjustment = \App\Models\Staff::getStaffAdjustmentPaid($staff_id,$payroll_id) ?? 0;
                            $total_adjustment += $adjustment;
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
                            if($loan_balance > 0){
                            $loan_deduction = \App\Models\Staff::getStaffLoanDeductionPaid($staff_id,$payroll_id) ?? 0;
                            }else{
                               $loan_deduction = 0;
                            }
                            $total_loan_deduction += $loan_deduction;
                            $taxable = \App\Models\Staff::getStaffTaxablePaid($staff_id,$payroll_id) ?? 0;
                            $total_taxable += $taxable;
                            $net = \App\Models\Staff::getStaffNetPaid($staff_id,$payroll_id) ?? 0;
                            $total_net += $net;
                        @endphp
                        <tr>
                            <td>{{$loop->iteration}}</td>
                            <td>{{$staff_name ?? null}}</td>
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
                        $sum_total_net += $total_net;
                        $sum_total_adjustment += $total_adjustment;
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

            @else
                @php
                    $payroll_types_total_basic_salary_not_yet = 0;
                    $payroll_types_total_advance_salary_not_yet = 0;
                    $payroll_types_total_allowance_not_yet = 0;
                    $payroll_types_total_gross_pay_not_yet = 0;
                    $payroll_types_total_employee_deducted_amount_pension_not_yet = 0;
                    $payroll_types_total_employer_deducted_amount_pension_not_yet = 0;
                    $payroll_types_total_employee_deducted_amount_health_not_yet = 0;
                    $payroll_types_total_employer_deducted_amount_health_not_yet = 0;
                    $payroll_types_total_employee_deducted_amount_wcf_not_yet = 0;
                    $payroll_types_total_employer_deducted_amount_wcf_not_yet = 0;
                    $payroll_types_total_employee_deducted_amount_sdl_not_yet = 0;
                    $payroll_types_total_employer_deducted_amount_sdl_not_yet = 0;
                    $payroll_types_total_employee_deducted_amount_heslb_not_yet = 0;
                    $payroll_types_total_employer_deducted_amount_heslb_not_yet = 0;
                    $payroll_types_total_employee_deducted_amount_payee_not_yet = 0;
                    $payroll_types_total_employer_deducted_amount_payee_not_yet = 0;
                    $payroll_types_total_loan_balance_not_yet = 0;
                    $payroll_types_total_current_loan_not_yet = 0;
                    $payroll_types_total_loan_deduction_not_yet = 0;
                    $payroll_types_total_taxable_not_yet = 0;
                    $payroll_types_total_net_not_yet = 0;
                    $payroll_types_total_adjustment_not_yet = 0;
                @endphp
                @foreach($payroll_types as $payroll_type)
                    @php
                        $payroll_type_id = $payroll_type->id;
                        $payroll_staffs = \App\Models\Staff::getAllStaffSalaryWithPayrollType($payroll_type_id);
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
                        <td colspan="20" class="text-center">{{$payroll_type->name}}'s Payroll</td>
                    </tr>
                    @php
                        $total_basic_salary_not_yet = 0;
                        $total_advance_salary_not_yet = 0;
                        $total_allowance_not_yet = 0;
                        $total_gross_pay_not_yet = 0;
                        $total_employee_deducted_amount_pension_not_yet = 0;
                        $total_employer_deducted_amount_pension_not_yet = 0;
                        $total_employee_deducted_amount_health_not_yet = 0;
                        $total_employer_deducted_amount_health_not_yet = 0;
                        $total_employee_deducted_amount_wcf_not_yet = 0;
                        $total_employer_deducted_amount_wcf_not_yet = 0;
                        $total_employee_deducted_amount_sdl_not_yet = 0;
                        $total_employer_deducted_amount_sdl_not_yet = 0;
                        $total_employee_deducted_amount_heslb_not_yet = 0;
                        $total_employer_deducted_amount_heslb_not_yet = 0;
                        $total_employee_deducted_amount_payee_not_yet = 0;
                        $total_employer_deducted_amount_payee_not_yet = 0;
                        $total_loan_balance_not_yet = 0;
                        $total_current_loan_not_yet = 0;
                        $total_loan_deduction_not_yet = 0;
                        $total_taxable_not_yet = 0;
                        $total_net_not_yet = 0;
                        $total_adjustment_not_yet = 0;
                    @endphp
                @foreach($payroll_staffs as $staff)
                    @php

                        $staff_id = $staff->id;
                        $staff_loan_status = $staff->loan_status;
                        $basic_salary = \App\Models\Staff::getStaffSalary($staff_id);
                        $advance_salary = \App\Models\Staff::getStaffAdvanceSalary($staff_id,$start_date,$end_date);
                        $adjustment = \App\Models\Staff::getStaffAdjustment($staff_id,$start_date,$end_date);
                        $allowance = \App\Models\Staff::getStaffAllowance($staff_id,$month);
                        $gross_pay = \App\Models\Staff::getStaffGrossPay($staff_id,$month);
                        $pension = \App\Models\Staff::getStaffDeduction($staff_id,'NSSF');
                    $health = \App\Models\Staff::getStaffDeduction($staff_id,'NHIF');
                    $paye = \App\Models\Staff::getStaffDeduction($staff_id,'PAYE');
                    $wcf = \App\Models\Staff::getStaffDeduction($staff_id,'WCF');
                    $sdl = \App\Models\Staff::getStaffDeduction($staff_id,'SDL');
                    $heslb = \App\Models\Staff::getStaffDeduction($staff_id,'HESLB');
                    $loan = \App\Models\Staff::getStaffLoan($staff_id);
                    $loan_deduction = \App\Models\Staff::getStaffLoanDeductionForCurrentLoan($staff_id);
                    $check_if_staff_has_loan = \App\Models\Staff::isStaffHasLoan($staff_id);

                    if($check_if_staff_has_loan){
                        $current_loan = $loan;
                        $current_loan_deduction = $loan_deduction;
                    }else{
                        $current_loan = 0;
                        $current_loan_deduction = 0;
                    }
                    $health = \App\Models\DeductionSetting::isStaffDeductionSubscribe(6, $staff_id);
                    if($health){
                        $deduction_range = \App\Models\DeductionSetting::getDeductionRange(6,$gross_pay);
                        $employee_ratio = $deduction_range->employee_percentage ?? 0;
                        $employer_ratio = $deduction_range->employer_percentage ?? 0;
                        $addition = $deduction_range->additional_amount;
                        $employee_deducted_amount_health = (($gross_pay - $deduction_range->minimum_amount) * ($employee_ratio > 0 ? ($employee_ratio / 100) : 0)) + ($employee_ratio > 0 ? $addition : 0);
                        $employer_deducted_amount_health = (($gross_pay - $deduction_range->minimum_amount) * ($employer_ratio > 0 ? ($employer_ratio / 100) : 0)) +  ($employer_ratio > 0 ? $addition : 0);
                    }else{
                        $employee_deducted_amount_health = 0;
                        $employer_deducted_amount_health = 0;
                    }

                    $pension = \App\Models\DeductionSetting::isStaffDeductionSubscribe(2, $staff_id);
                    if($pension){
                        $deduction_range = \App\Models\DeductionSetting::getDeductionRange(2,$gross_pay);
                        $employee_ratio = $deduction_range->employee_percentage ?? 0;
                        $employer_ratio = $deduction_range->employer_percentage ?? 0;
                        $addition = $deduction_range->additional_amount;
                        $employee_deducted_amount_pension = (($gross_pay - $deduction_range->minimum_amount) * ($employee_ratio > 0 ? ($employee_ratio / 100) : 0)) + ($employee_ratio > 0 ? $addition : 0);
                        $employer_deducted_amount_pension = (($gross_pay - $deduction_range->minimum_amount) * ($employer_ratio > 0 ? ($employer_ratio / 100) : 0)) +  ($employer_ratio > 0 ? $addition : 0);
                    }else{
                        $employee_deducted_amount_pension = 0;
                        $employer_deducted_amount_pension = 0;
                    }

                    $wcf = \App\Models\DeductionSetting::isStaffDeductionSubscribe(3, $staff_id);
                    if($wcf){
                        $deduction_range = \App\Models\DeductionSetting::getDeductionRange(3,$gross_pay);
                        $employee_ratio = $deduction_range->employee_percentage ?? 0;
                        $employer_ratio = $deduction_range->employer_percentage ?? 0;
                        $addition = $deduction_range->additional_amount;
                        $employee_deducted_amount_wcf = (($gross_pay - $deduction_range->minimum_amount) * ($employee_ratio > 0 ? ($employee_ratio / 100) : 0)) + ($employee_ratio > 0 ? $addition : 0);
                        $employer_deducted_amount_wcf = (($gross_pay - $deduction_range->minimum_amount) * ($employer_ratio > 0 ? ($employer_ratio / 100) : 0)) +  ($employer_ratio > 0 ? $addition : 0);
                    }else{
                        $employee_deducted_amount_wcf = 0;
                        $employer_deducted_amount_wcf = 0;
                    }

                    $sdl = \App\Models\DeductionSetting::isStaffDeductionSubscribe(5, $staff_id);
                    if($sdl){
                        $deduction_range = \App\Models\DeductionSetting::getDeductionRange(5,$gross_pay);
                        $employee_ratio = $deduction_range->employee_percentage ?? 0;
                        $employer_ratio = $deduction_range->employer_percentage ?? 0;
                        $addition = $deduction_range->additional_amount;
                        $employee_deducted_amount_sdl = (($gross_pay - $deduction_range->minimum_amount) * ($employee_ratio > 0 ? ($employee_ratio / 100) : 0)) + ($employee_ratio > 0 ? $addition : 0);
                        $employer_deducted_amount_sdl = (($gross_pay - $deduction_range->minimum_amount) * ($employer_ratio > 0 ? ($employer_ratio / 100) : 0)) +  ($employer_ratio > 0 ? $addition : 0);
                    }else{
                        $employee_deducted_amount_sdl = 0;
                        $employer_deducted_amount_sdl = 0;
                    }

                    $heslb = \App\Models\DeductionSetting::isStaffDeductionSubscribe(4, $staff_id);
                    if($heslb){
                        $deduction_range = \App\Models\DeductionSetting::getDeductionRange(4,$gross_pay);
                        $employee_ratio = $deduction_range->employee_percentage ?? 0;
                        $employer_ratio = $deduction_range->employer_percentage ?? 0;
                        $addition = $deduction_range->additional_amount;
                        $employee_deducted_amount_heslb = (($gross_pay - $deduction_range->minimum_amount) * ($employee_ratio > 0 ? ($employee_ratio / 100) : 0)) + ($employee_ratio > 0 ? $addition : 0);
                        $employer_deducted_amount_heslb = (($gross_pay - $deduction_range->minimum_amount) * ($employer_ratio > 0 ? ($employer_ratio / 100) : 0)) +  ($employer_ratio > 0 ? $addition : 0);
                    }else{
                        $employee_deducted_amount_heslb = 0;
                        $employer_deducted_amount_heslb = 0;
                    }

                    $taxable = $gross_pay - ($employee_deducted_amount_pension);

                    $payee = \App\Models\DeductionSetting::isStaffDeductionSubscribe(1, $staff_id);
                    if($payee){
                        $deduction_range = \App\Models\DeductionSetting::getDeductionRange(1,$taxable);
                        $employee_ratio = $deduction_range->employee_percentage ?? 0;
                        $employer_ratio = $deduction_range->employer_percentage ?? 0;
                        $addition = $deduction_range->additional_amount;
                        $employee_deducted_amount_payee = (($taxable - $deduction_range->minimum_amount) * ($employee_ratio > 0 ? ($employee_ratio / 100) : 0)) + ($employee_ratio > 0 ? $addition : 0);
                        $employer_deducted_amount_payee = (($taxable - $deduction_range->minimum_amount) * ($employer_ratio > 0 ? ($employer_ratio / 100) : 0)) +  ($employer_ratio > 0 ? $addition : 0);
                    }else{
                        $employee_deducted_amount_payee = 0;
                        $employer_deducted_amount_payee = 0;
                    }
                    $loan_balance = $current_loan - $current_loan_deduction;
                   $loan_balance_current = $current_loan;
                   if ($loan_balance_current > 0 && $staff_loan_status == 'ACTIVE'){
                        $current_loan = $loan;
                        $current_loan_deduction = $loan_deduction;
                    }elseif ($loan_balance_current > 0 && $staff_loan_status == 'INACTIVE'){
                        $current_loan_deduction = 0;
                    } else{
                        $current_loan = 0;
                        $current_loan_deduction = 0;
                    }



                    $net = $adjustment + $taxable - $employee_deducted_amount_health - $employee_deducted_amount_payee - $employee_deducted_amount_heslb - $advance_salary - $current_loan_deduction;

                    $total_basic_salary_not_yet += $basic_salary;

                        $total_advance_salary_not_yet += $advance_salary;
                        $total_adjustment_not_yet += $adjustment;

                        $total_allowance_not_yet += $allowance;

                        $total_gross_pay_not_yet += $gross_pay;

                        $total_employee_deducted_amount_pension_not_yet += $employee_deducted_amount_pension;

                        $total_employer_deducted_amount_pension_not_yet += $employer_deducted_amount_pension;

                        $total_employee_deducted_amount_health_not_yet += $employee_deducted_amount_health;

                        $total_employer_deducted_amount_health_not_yet += $employer_deducted_amount_health;

                        $total_employee_deducted_amount_wcf_not_yet += $employee_deducted_amount_wcf;

                        $total_employer_deducted_amount_wcf_not_yet += $employer_deducted_amount_wcf;

                        $total_employee_deducted_amount_sdl_not_yet += $employee_deducted_amount_sdl;

                        $total_employer_deducted_amount_sdl_not_yet += $employer_deducted_amount_sdl;

                        $total_employee_deducted_amount_heslb_not_yet += $employee_deducted_amount_heslb;

                        $total_employer_deducted_amount_heslb_not_yet += $employer_deducted_amount_heslb;

                        $total_employee_deducted_amount_payee_not_yet += $employee_deducted_amount_payee;

                        $total_employer_deducted_amount_payee_not_yet += $employer_deducted_amount_payee;

                        $total_loan_balance_not_yet += $loan_balance;

                        $total_current_loan_not_yet += $current_loan;

                        $total_loan_deduction_not_yet += $current_loan_deduction;

                        $total_taxable_not_yet += $taxable;

                        $total_net_not_yet += $net;


                    @endphp
                    <tr>
                        <td>{{$loop->iteration}}</td>
                        <td>{{$staff->name}}</td>
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
                        <td class="text-right">{{number_format($current_loan_deduction)}}</td>
                        <td class="text-right">{{number_format($current_loan - $current_loan_deduction)}}</td>
                        <td class="text-right">{{number_format($adjustment)}}</td>
                        <td class="text-right">{{number_format($net)}}</td>
                    </tr>
                @endforeach
                    <tr>
                        <th></th>
                        <th>{{$payroll_type->name}}'s Total</th>
                        <th class="text-right">{{number_format($total_basic_salary_not_yet)}}</th>
                        <th class="text-right">{{number_format($total_allowance_not_yet)}}</th>
                        <th class="text-right">{{number_format($total_gross_pay_not_yet)}}</th>
                        <th class="text-right">{{number_format($total_employer_deducted_amount_pension_not_yet)}}</th>
                        <th class="text-right">{{number_format($total_employee_deducted_amount_pension_not_yet)}}</th>
                        <th class="text-right">{{number_format($total_taxable_not_yet)}}</th>
                        <th class="text-right">{{number_format($total_employee_deducted_amount_payee_not_yet)}}</th>
                        <th class="text-right">{{number_format($total_employer_deducted_amount_wcf_not_yet)}}</th>
                        <th class="text-right">{{number_format($total_employer_deducted_amount_sdl_not_yet)}}</th>
                        <th class="text-right">{{number_format($total_employee_deducted_amount_heslb_not_yet)}}</th>
                        <th class="text-right">{{number_format($total_employer_deducted_amount_health_not_yet)}}</th>
                        <th class="text-right">{{number_format($total_employee_deducted_amount_health_not_yet)}}</th>
                        <th class="text-right">{{number_format($total_advance_salary_not_yet)}}</th>
                        <th class="text-right">{{number_format($total_current_loan_not_yet)}}</th>
                        <th class="text-right">{{number_format($total_loan_deduction_not_yet)}}</th>
                        <th class="text-right">{{number_format($total_loan_balance_not_yet)}}</th>
                        <th class="text-right">{{number_format($total_adjustment_not_yet)}}</th>
                        <th class="text-right">{{number_format($total_net_not_yet)}}</th>
                    </tr>
                    @php
                        $payroll_types_total_basic_salary_not_yet += $total_basic_salary_not_yet;
                        $payroll_types_total_advance_salary_not_yet += $total_advance_salary_not_yet;
                        $payroll_types_total_allowance_not_yet += $total_allowance_not_yet;
                        $payroll_types_total_gross_pay_not_yet += $total_gross_pay_not_yet;
                        $payroll_types_total_employee_deducted_amount_pension_not_yet += $total_employee_deducted_amount_pension_not_yet;
                        $payroll_types_total_employer_deducted_amount_pension_not_yet += $total_employer_deducted_amount_pension_not_yet;
                        $payroll_types_total_employee_deducted_amount_health_not_yet += $total_employee_deducted_amount_health_not_yet;
                        $payroll_types_total_employer_deducted_amount_health_not_yet += $total_employer_deducted_amount_health_not_yet;
                        $payroll_types_total_employee_deducted_amount_wcf_not_yet += $total_employee_deducted_amount_wcf_not_yet;
                        $payroll_types_total_employer_deducted_amount_wcf_not_yet += $total_employer_deducted_amount_wcf_not_yet;
                        $payroll_types_total_employee_deducted_amount_sdl_not_yet += $total_employee_deducted_amount_sdl_not_yet;
                        $payroll_types_total_employer_deducted_amount_sdl_not_yet += $total_employer_deducted_amount_sdl_not_yet;
                        $payroll_types_total_employee_deducted_amount_heslb_not_yet += $total_employee_deducted_amount_heslb_not_yet;
                        $payroll_types_total_employer_deducted_amount_heslb_not_yet += $total_employer_deducted_amount_heslb_not_yet;
                        $payroll_types_total_employee_deducted_amount_payee_not_yet += $total_employee_deducted_amount_payee_not_yet;
                        $payroll_types_total_employer_deducted_amount_payee_not_yet += $total_employer_deducted_amount_payee_not_yet;
                        $payroll_types_total_loan_balance_not_yet += $total_loan_balance_not_yet;
                        $payroll_types_total_current_loan_not_yet += $total_current_loan_not_yet;
                        $payroll_types_total_loan_deduction_not_yet += $total_loan_deduction_not_yet;
                        $payroll_types_total_taxable_not_yet += $total_taxable_not_yet;
                        $payroll_types_total_net_not_yet += $total_net_not_yet;
                        $payroll_types_total_adjustment_not_yet += $total_adjustment_not_yet;
                    @endphp

                @endforeach
                <tr>
                    <th></th>
                    <th>Total</th>
                    <th class="text-right">{{number_format($payroll_types_total_basic_salary_not_yet)}}</th>
                    <th class="text-right">{{number_format($payroll_types_total_allowance_not_yet)}}</th>
                    <th class="text-right">{{number_format($payroll_types_total_gross_pay_not_yet)}}</th>
                    <th class="text-right">{{number_format($payroll_types_total_employer_deducted_amount_pension_not_yet)}}</th>
                    <th class="text-right">{{number_format($payroll_types_total_employee_deducted_amount_pension_not_yet)}}</th>
                    <th class="text-right">{{number_format($payroll_types_total_taxable_not_yet)}}</th>
                    <th class="text-right">{{number_format($payroll_types_total_employee_deducted_amount_payee_not_yet)}}</th>
                    <th class="text-right">{{number_format($payroll_types_total_employer_deducted_amount_wcf_not_yet)}}</th>
                    <th class="text-right">{{number_format($payroll_types_total_employer_deducted_amount_sdl_not_yet)}}</th>
                    <th class="text-right">{{number_format($payroll_types_total_employee_deducted_amount_heslb_not_yet)}}</th>
                    <th class="text-right">{{number_format($payroll_types_total_employer_deducted_amount_health_not_yet)}}</th>
                    <th class="text-right">{{number_format($payroll_types_total_employee_deducted_amount_health_not_yet)}}</th>
                    <th class="text-right">{{number_format($payroll_types_total_advance_salary_not_yet)}}</th>
                    <th class="text-right">{{number_format($payroll_types_total_current_loan_not_yet)}}</th>
                    <th class="text-right">{{number_format($payroll_types_total_loan_deduction_not_yet)}}</th>
                    <th class="text-right">{{number_format($payroll_types_total_loan_balance_not_yet)}}</th>
                    <th class="text-right">{{number_format($payroll_types_total_adjustment_not_yet)}}</th>
                    <th class="text-right">{{number_format($payroll_types_total_net_not_yet)}}</th>
                </tr>

            @endif
            </tbody>

{{--                <tfoot>--}}
{{--                --}}
{{--                </tfoot>--}}
        </table>
    </div>
    @if($is_payroll_created != 1)
    <form method="post" action="{{route('create_payroll')}}" enctype="multipart/form-data" autocomplete="off">
        @csrf
        <input type="hidden" name="year" value="{{$year}}">
        <input type="hidden" name="month" value="{{$month}}">
        <input type="hidden" name="payroll_number" value="{{time()}}">
        <input type="hidden" name="created_by_id" value="{{Auth::user()->id}}">
        <input type="hidden" name="document_number" value="PRL/{{date('Y').'/'.$document_id}}">
        @foreach($staffs as $staff)
            @php

                                $staff_id = $staff->id;
                $staff_loan_status = $staff->loan_status;
                                $basic_salary = \App\Models\Staff::getStaffSalary($staff_id);
                                $staff_salary_id = \App\Models\Staff::getStaffSalaryId($staff_id) ?? 0;
                                $advance_salary = \App\Models\Staff::getStaffAdvanceSalary($staff_id,$start_date,$end_date) ?? 0;
                                $adjustment = \App\Models\Staff::getStaffAdjustment($staff_id,$start_date,$end_date) ?? 0;
                                $allowance = \App\Models\Staff::getStaffAllowance($staff_id,$month) ?? 0;
                                $gross_pay = \App\Models\Staff::getStaffGrossPay($staff_id,$month) ?? 0;
                                $pension = \App\Models\Staff::getStaffDeduction($staff_id,'NSSF') ?? 0;
                            $health = \App\Models\Staff::getStaffDeduction($staff_id,'NHIF') ?? 0;
                            $paye = \App\Models\Staff::getStaffDeduction($staff_id,'PAYE') ?? 0;
                            $wcf = \App\Models\Staff::getStaffDeduction($staff_id,'WCF') ?? 0;
                            $sdl = \App\Models\Staff::getStaffDeduction($staff_id,'SDL') ?? 0;
                            $heslb = \App\Models\Staff::getStaffDeduction($staff_id,'HESLB') ?? 0;
                            $loan = \App\Models\Staff::getStaffLoan($staff_id) ?? 0;
                            $loan_deduction = \App\Models\Staff::getStaffLoanDeductionForCurrentLoan($staff_id) ?? 0;
                            $check_if_staff_has_loan = \App\Models\Staff::isStaffHasLoan($staff_id) ?? 0;

                            if($check_if_staff_has_loan){
                                $current_loan = $loan;
                                $current_loan_deduction = $loan_deduction;
                            }else{
                                $current_loan = 0;
                                $current_loan_deduction = 0;
                            }
                            $health = \App\Models\DeductionSetting::isStaffDeductionSubscribe(6, $staff_id);
                            if($health){
                                $deduction_range = \App\Models\DeductionSetting::getDeductionRange(6,$gross_pay);
                                $employee_ratio = $deduction_range->employee_percentage ?? 0;
                                $employer_ratio = $deduction_range->employer_percentage ?? 0;
                                $addition = $deduction_range->additional_amount;
                                $employee_deducted_amount_health = (($gross_pay - $deduction_range->minimum_amount) * ($employee_ratio > 0 ? ($employee_ratio / 100) : 0)) + ($employee_ratio > 0 ? $addition : 0);
                                $employer_deducted_amount_health = (($gross_pay - $deduction_range->minimum_amount) * ($employer_ratio > 0 ? ($employer_ratio / 100) : 0)) +  ($employer_ratio > 0 ? $addition : 0);
                            }else{
                                $employee_deducted_amount_health = 0;
                                $employer_deducted_amount_health = 0;
                            }

                            $pension = \App\Models\DeductionSetting::isStaffDeductionSubscribe(2, $staff_id);
                            if($pension){
                                $deduction_range = \App\Models\DeductionSetting::getDeductionRange(2,$gross_pay);
                                $employee_ratio = $deduction_range->employee_percentage ?? 0;
                                $employer_ratio = $deduction_range->employer_percentage ?? 0;
                                $addition = $deduction_range->additional_amount;
                                $employee_deducted_amount_pension = (($gross_pay - $deduction_range->minimum_amount) * ($employee_ratio > 0 ? ($employee_ratio / 100) : 0)) + ($employee_ratio > 0 ? $addition : 0);
                                $employer_deducted_amount_pension = (($gross_pay - $deduction_range->minimum_amount) * ($employer_ratio > 0 ? ($employer_ratio / 100) : 0)) +  ($employer_ratio > 0 ? $addition : 0);
                            }else{
                                $employee_deducted_amount_pension = 0;
                                $employer_deducted_amount_pension = 0;
                            }

                            $wcf = \App\Models\DeductionSetting::isStaffDeductionSubscribe(3, $staff_id);
                            if($wcf){
                                $deduction_range = \App\Models\DeductionSetting::getDeductionRange(3,$gross_pay);
                                $employee_ratio = $deduction_range->employee_percentage ?? 0;
                                $employer_ratio = $deduction_range->employer_percentage ?? 0;
                                $addition = $deduction_range->additional_amount;
                                $employee_deducted_amount_wcf = ((($gross_pay - $deduction_range->minimum_amount) * ($employee_ratio > 0 ? ($employee_ratio / 100) : 0)) + ($employee_ratio > 0 ? $addition : 0)) ?? 0;
                                $employer_deducted_amount_wcf = ((($gross_pay - $deduction_range->minimum_amount) * ($employer_ratio > 0 ? ($employer_ratio / 100) : 0)) +  ($employer_ratio > 0 ? $addition : 0)) ?? 0;
                            }else{
                                $employee_deducted_amount_wcf = 0;
                                $employer_deducted_amount_wcf = 0;
                            }

                            $sdl = \App\Models\DeductionSetting::isStaffDeductionSubscribe(5, $staff_id);
                            if($sdl){
                                $deduction_range = \App\Models\DeductionSetting::getDeductionRange(5,$gross_pay);
                                $employee_ratio = $deduction_range->employee_percentage ?? 0;
                                $employer_ratio = $deduction_range->employer_percentage ?? 0;
                                $addition = $deduction_range->additional_amount;
                                $employee_deducted_amount_sdl = (($gross_pay - $deduction_range->minimum_amount) * ($employee_ratio > 0 ? ($employee_ratio / 100) : 0)) + ($employee_ratio > 0 ? $addition : 0);
                                $employer_deducted_amount_sdl = (($gross_pay - $deduction_range->minimum_amount) * ($employer_ratio > 0 ? ($employer_ratio / 100) : 0)) +  ($employer_ratio > 0 ? $addition : 0);
                            }else{
                                $employee_deducted_amount_sdl = 0;
                                $employer_deducted_amount_sdl = 0;
                            }

                            $heslb = \App\Models\DeductionSetting::isStaffDeductionSubscribe(4, $staff_id);
                            if($heslb){
                                $deduction_range = \App\Models\DeductionSetting::getDeductionRange(4,$gross_pay);
                                $employee_ratio = $deduction_range->employee_percentage ?? 0;
                                $employer_ratio = $deduction_range->employer_percentage ?? 0;
                                $addition = $deduction_range->additional_amount;
                                $employee_deducted_amount_heslb = (($gross_pay - $deduction_range->minimum_amount) * ($employee_ratio > 0 ? ($employee_ratio / 100) : 0)) + ($employee_ratio > 0 ? $addition : 0);
                                $employer_deducted_amount_heslb = (($gross_pay - $deduction_range->minimum_amount) * ($employer_ratio > 0 ? ($employer_ratio / 100) : 0)) +  ($employer_ratio > 0 ? $addition : 0);
                            }else{
                                $employee_deducted_amount_heslb = 0;
                                $employer_deducted_amount_heslb = 0;
                            }

                            $taxable = $gross_pay - ($employee_deducted_amount_pension);

                            $payee = \App\Models\DeductionSetting::isStaffDeductionSubscribe(1, $staff_id);
                            if($payee){
                                $deduction_range = \App\Models\DeductionSetting::getDeductionRange(1,$taxable);
                                $employee_ratio = $deduction_range->employee_percentage ?? 0;
                                $employer_ratio = $deduction_range->employer_percentage ?? 0;
                                $addition = $deduction_range->additional_amount;
                                $employee_deducted_amount_payee = (($taxable - $deduction_range->minimum_amount) * ($employee_ratio > 0 ? ($employee_ratio / 100) : 0)) + ($employee_ratio > 0 ? $addition : 0);
                                $employer_deducted_amount_payee = (($taxable - $deduction_range->minimum_amount) * ($employer_ratio > 0 ? ($employer_ratio / 100) : 0)) +  ($employer_ratio > 0 ? $addition : 0);
                            }else{
                                $employee_deducted_amount_payee = 0;
                                $employer_deducted_amount_payee = 0;
                            }

                            $balance = ($current_loan ?? 0 - $current_loan_deduction ?? 0);
                                    if ($balance > 0 && $staff_loan_status == 'ACTIVE'){
                                        $current_loan = $loan;
                                        $current_loan_deduction = $loan_deduction;
                                    }elseif ($balance > 0 && $staff_loan_status == 'INACTIVE'){
                                        $current_loan_deduction = 0;
                                    } else{
                                        $current_loan = 0;
                                        $current_loan_deduction = 0;
                                    }


                            $net =  $adjustment + $taxable - $employee_deducted_amount_health - $employee_deducted_amount_payee - $employee_deducted_amount_heslb - $advance_salary - ($current_loan_deduction ?? 0);

            @endphp



            <input type="hidden" name="staff_id[]" value="{{$staff_id}}">
            <input type="hidden" name="allowance[]" value="{{$allowance}}">
            <input type="hidden" name="staff_salary_id[]" value="{{$staff_salary_id}}">
            <input type="hidden" name="basic_salary[]" value="{{$basic_salary}}">
            <input type="hidden" name="gross_pay[]" value="{{$gross_pay}}">
            <input type="hidden" name="employer_deducted_amount_pension[]" value="{{$employer_deducted_amount_pension}}">
            <input type="hidden" name="employee_deducted_amount_pension[]" value="{{$employee_deducted_amount_pension}}">
            <input type="hidden" name="employer_deducted_amount_health[]" value="{{$employer_deducted_amount_health}}">
            <input type="hidden" name="employee_deducted_amount_health[]" value="{{$employee_deducted_amount_health}}">
            <input type="hidden" name="employee_deducted_amount_payee[]" value="{{$employee_deducted_amount_payee}}">
            <input type="hidden" name="employee_deducted_amount_wcf[]" value="{{$employee_deducted_amount_wcf}}">
            <input type="hidden" name="employer_deducted_amount_wcf[]" value="{{$employer_deducted_amount_wcf}}">
            <input type="hidden" name="employee_deducted_amount_sdl[]" value="{{$employee_deducted_amount_sdl}}">
            <input type="hidden" name="employer_deducted_amount_sdl[]" value="{{$employer_deducted_amount_sdl}}">
            <input type="hidden" name="employee_deducted_amount_heslb[]" value="{{$employee_deducted_amount_heslb}}">
            <input type="hidden" name="employer_deducted_amount_heslb[]" value="{{$employer_deducted_amount_heslb}}">
            <input type="hidden" name="taxable[]" value="{{$taxable}}">
            <input type="hidden" name="advance_salary[]" value="{{$advance_salary}}">
            <input type="hidden" name="current_loan[]" value="{{$current_loan ?? 0}}">
            <input type="hidden" name="current_loan_deduction[]" value="{{$current_loan_deduction ?? 0}}">
            <input type="hidden" name="loan_balance[]" value="{{$current_loan - $current_loan_deduction}}">
            <input type="hidden" name="net[]" value="{{$net}}">
            <input type="hidden" name="adjustment[]" value="{{$adjustment}}">
        @endforeach
        <div class="row">
            <div class="offset-9 col-md-3">
                <div class="form-group  text-right">
                    <button type="submit" class="btn btn-alt-primary col" name="addItem" value="AutoComment">Create Payroll</button>
                </div></div>
        </div>
    </form>
    @endif
</div>

<script>
    $.fn.dataTable.ext.errMode = 'none';
        jQuery('.js-dataTable-full').dataTable({
            columnDefs: [ { orderable: false, targets: [ 4 ] } ],
            pageLength: 150,
            lengthMenu: [[5, 8, 15, 20], [5, 8, 15, 20]],
            autoWidth: false
        });

    $(document).ready(function () {
        $('#input-total_amount').keyup(calculate);
    });

    function calculate(e) {
        if($('#input-purchases_type').val() == '1') {
            $('#input-amount_vat_exc').val($('#input-total_amount').val() * 100 / 118);
            $('#input-vat_amount').val($('#input-amount_vat_exc').val() * 18 / 100);
        }else{
            $('#input-amount_vat_exc').val($('#input-total_amount').val() * 0);
            $('#input-vat_amount').val($('#input-amount_vat_exc').val() * 0);
        }
    }

    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });

    $(".select2").select2({
        theme: "bootstrap",
        placeholder: "Choose",
        width: 'auto',
        dropdownAutoWidth: true,
        allowClear: true,
    });
    $(function() {
        $('#amount_vat_exc').hide();
        $('#vat_amount').hide();
        $('#input-purchases_type').change(function(){
            if($('#input-purchases_type').val() == '1') {
                $('#amount_vat_exc').show();
                $('#vat_amount').show();

            } else {
                $('#amount_vat_exc').hide();
                $('#vat_amount').hide();
            }
        });
    });

</script>

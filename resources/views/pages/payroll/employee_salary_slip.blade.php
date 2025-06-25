@extends('layouts.backend')

@section('content')
    <?php
    use Illuminate\Support\Facades\DB;
    $start_date = $_POST['start_date'] ?? date('Y-m-01');
    $end_date = $_POST['end_date'] ?? date('Y-m-t');
    ?>
    
    <div class="main-container">
        <div class="content">
            @if($payroll)
                @include('components.headed_paper_print', ['title' => 'PAYSLIP', 'subtitle' => date('F Y', strtotime($payroll->year.'-'.$payroll->month.'-01')), 'showPrintButton' => true])
                <div class="print-content">
                    @php


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
                        ['name' => 'Pay As You Earn (PAYE)', 'value' => $employee_deducted_amount_payee ],
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

                    foreach ($deductions as $deduction) {
                    $deduction_id = $deduction['deduction_id'];
                    $deducted_amount = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,$deduction_id,'employee_deduction_amount') ?? 0;
                    $total_deduction += $deducted_amount;
                    $percentage = $deduction['employee_deducted_percentage'] > 0 ? "({$deduction['employee_deducted_percentage']}%)" : '' ;
                    $deduction_title = $deduction['keyword'];
                    if($deducted_amount > 0) {
                    array_push($right_side, ['name' => strtoupper($deduction_title). $percentage, 'value' => $deducted_amount]);
                    }
                }

                    @endphp

                    <!-- Employee Information Card -->
                    <div class="card card-custom shadow-sm mb-4">
                        <div class="card-header" style="background: linear-gradient(90deg, #1BC5BD 0%, #1DC9C0 100%); color: white;">
                            <h5 class="mb-0 font-weight-bold">Employee Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-group mb-3">
                                        <label class="font-weight-bold text-dark-75">Payroll Number:</label>
                                        <span class="ml-2">{{$payroll['payroll_number']}}</span>
                                    </div>
                                    <div class="info-group mb-3">
                                        <label class="font-weight-bold text-dark-75">Employee Number:</label>
                                        <span class="ml-2">HRM/LE/PO-{{$employee->employee_number ?? 'N/A'}}</span>
                                    </div>
                                    <div class="info-group mb-3">
                                        <label class="font-weight-bold text-dark-75">Department:</label>
                                        <span class="ml-2">Human Resources & Administration (HRA)</span>
                                    </div>
                                    <div class="info-group mb-3">
                                        <label class="font-weight-bold text-dark-75">Bank Name:</label>
                                        <span class="ml-2">{{$employee_bank_details->bank->name ?? 'N/A'}}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-group mb-3">
                                        <label class="font-weight-bold text-dark-75">Payroll Month:</label>
                                        <span class="ml-2">{{date('F',strtotime($payroll->year.'-'.$payroll->month.'-01')).' - '.$payroll->year}}</span>
                                    </div>
                                    <div class="info-group mb-3">
                                        <label class="font-weight-bold text-dark-75">Employee Name:</label>
                                        <span class="ml-2">{{$employee->name ?? 'N/A'}}</span>
                                    </div>
                                    <div class="info-group mb-3">
                                        <label class="font-weight-bold text-dark-75">Designation:</label>
                                        <span class="ml-2">{{$employee->designation ?? 'N/A'}}</span>
                                    </div>
                                    <div class="info-group mb-3">
                                        <label class="font-weight-bold text-dark-75">Account Number:</label>
                                        <span class="ml-2">{{$employee_bank_details->account_number ?? 'N/A'}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Salary Breakdown -->
                    <div class="card card-custom shadow-sm">
                        <div class="card-header" style="background: linear-gradient(90deg, #1BC5BD 0%, #1DC9C0 100%); color: white;">
                            <h5 class="mb-0 font-weight-bold">Salary Breakdown</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <style>
                                    .payslip-table {
                                        margin-bottom: 0;
                                    }
                                    .payslip-table th {
                                        background: #f8f9fa;
                                        color: #495057;
                                        font-weight: 600;
                                        border-top: none;
                                    }
                                    .payslip-table .section-header {
                                        background: linear-gradient(90deg, #e9ecef 0%, #f8f9fa 100%);
                                        font-weight: 700;
                                        color: #495057;
                                    }
                                    .payslip-table .total-row {
                                        background: #f1f3f4;
                                        font-weight: 600;
                                    }
                                    .payslip-table .net-salary-row {
                                        background: linear-gradient(90deg, #1BC5BD 0%, #1DC9C0 100%);
                                        color: white;
                                        font-weight: 700;
                                        font-size: 1.1rem;
                                    }
                                    .money {
                                        font-family: 'Courier New', monospace;
                                        font-weight: 600;
                                    }
                                    
                                    @media print {
                                        .payslip-table .section-header,
                                        .payslip-table .total-row,
                                        .payslip-table .net-salary-row {
                                            -webkit-print-color-adjust: exact;
                                            print-color-adjust: exact;
                                        }
                                    }
                                </style>
                                <table class="table table-bordered payslip-table">
                                    <thead>
                                        <tr>
                                            <th width="25%">EARNINGS</th>
                                            <th width="25%" class="text-right">AMOUNT</th>
                                            <th width="25%">DEDUCTIONS</th>
                                            <th width="25%" class="text-right">AMOUNT</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="section-header">
                                            <td class="font-weight-bold">Employee Income</td>
                                            <td></td>
                                            <td class="font-weight-bold">Employee Deductions</td>
                                            <td></td>
                                        </tr>
                                        @php
                                        $max = count($left_side) > count($right_side) ? count($left_side) : count($right_side);
                                        foreach (range(0, $max - 1) as $index) {
                                            $earnings_name = isset($left_side[$index]) ? $left_side[$index]['name'] : '';
                                            $earnings_value = isset($left_side[$index]) ? \App\Classes\Utility::money_format($left_side[$index]['value']) : '';
                                            $deduction_name = isset($right_side[$index]) ? $right_side[$index]['name'] : '';
                                            $deduction_value = isset($right_side[$index]) ? \App\Classes\Utility::money_format($right_side[$index]['value']) : '';
                                            
                                            echo "<tr>
                                                <td class='text-dark-75'>$earnings_name</td>
                                                <td class='money text-right text-dark-75'>$earnings_value</td>
                                                <td class='text-dark-75'>$deduction_name</td>
                                                <td class='money text-right text-dark-75'>$deduction_value</td>
                                            </tr>";
                                        }
                                        @endphp
                                        
                                        <tr class="total-row">
                                            <td class="font-weight-bold">GROSS SALARY</td>
                                            <td class="money text-right font-weight-bold">{{number_format($gross_salary)}}</td>
                                            <td class="font-weight-bold">TOTAL DEDUCTIONS</td>
                                            <td class="money text-right font-weight-bold">{{number_format($total_deduction+$advance_salary+$loan_deduction+$employee_deducted_amount_payee)}}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="net-salary-row">
                                            <td colspan="3" class="font-weight-bold" style="font-size: 1.1rem;">NET SALARY</td>
                                            <td class="money text-right font-weight-bold" style="font-size: 1.2rem;">
                                                {{number_format($net_salary)}}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- Close print-document -->
            @else
                <div class="container-fluid">
                    <div class="card card-custom shadow-sm">
                        <div class="card-body text-center py-5">
                            <div class="my-5">
                                <i class="fas fa-exclamation-triangle fa-4x text-warning mb-4"></i>
                                <h3 class="text-dark mb-3">Salary Slip Not Available</h3>
                                <p class="text-muted mb-4">Failed to get salary slip for this month. Please contact HR department.</p>
                                <a href="javascript:history.back()" class="btn font-weight-bold px-6 py-3" style="background: linear-gradient(90deg, #1BC5BD 0%, #1DC9C0 100%); color: white; border: none; border-radius: 8px;">
                                    <i class="fas fa-arrow-left mr-2"></i>Go Back
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

@endsection

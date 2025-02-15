@extends('layouts.backend')

@section('content')
    <?php
    use Illuminate\Support\Facades\DB;
    $start_date = $_POST['start_date'] ?? date('Y-m-01');
    $end_date = $_POST['end_date'] ?? date('Y-m-t');

    ?>
    <?php
    ?>
    <div class="main-container">
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
                                            <img class="" src="{{ asset('media/logo/wajenzilogo.png') }}" alt="" height="100">
                                        </div>
                                        <div class="col-md-6 text-center">
                                               <span class="text-center font-size-h3">{{settings('ORGANIZATION_NAME')}}</span><br/>
            <span class="text-center font-size-h5">{{settings('COMPANY_ADDRESS_LINE_1')}}</span><br/>
            <span class="text-center font-size-h5">{{settings('COMPANY_ADDRESS_LINE_2')}}</span><br/>
            <span class="text-center font-size-h5">{{settings('COMPANY_PHONE_NUMBER')}}</span><br/>
            <span class="text-center font-size-h5">{{settings('TAX_IDENTIFICATION_NUMBER')}}</span><br/>
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
                                                        <option value="1" {{ ($this_month == 1) ? 'selected' : '' }}>Jan</option>
                                                        <option value="2" {{ ($this_month == 2) ? 'selected' : '' }}>Feb</option>
                                                        <option value="3" {{ ($this_month == 3) ? 'selected' : '' }}>Mar</option>
                                                        <option value="4" {{ ($this_month == 4) ? 'selected' : '' }}>Apr</option>
                                                        <option value="5" {{ ($this_month == 5) ? 'selected' : '' }}>May</option>
                                                        <option value="6" {{ ($this_month == 6) ? 'selected' : '' }}>Jun</option>
                                                        <option value="7" {{ ($this_month == 7) ? 'selected' : '' }}>Jul</option>
                                                        <option value="8" {{ ($this_month == 8) ? 'selected' : '' }}>Aug</option>
                                                        <option value="9" {{ ($this_month == 9) ? 'selected' : '' }}>Sept</option>
                                                        <option value="10" {{ ($this_month == 10) ? 'selected' : '' }}>Oct</option>
                                                        <option value="11" {{ ($this_month == 11) ? 'selected' : '' }}>Nov</option>
                                                        <option value="12" {{ ($this_month == 12) ? 'selected' : '' }}>Dec</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Year</span>
                                                    </div>
                                                    <select name="year" id="year" class="form-control">
                                                        <option value="2021" {{ ($this_year == 2021) ? 'selected' : '' }}>2021</option>
                                                        <option value="2022" {{ ($this_year == 2022) ? 'selected' : '' }}>2022</option>
                                                        <option value="2023" {{ ($this_year == 2023) ? 'selected' : '' }}>2023</option>
                                                        <option value="2024" {{ ($this_year == 2024) ? 'selected' : '' }}>2024</option>
                                                        <option value="2025" {{ ($this_year == 2025) ? 'selected' : '' }}>2025</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Employee</span>
                                                    </div>
                                                    <select name="staff_id" id="input-staff-id" class="form-control select2" required>
                                                        @foreach($staffs as $staff)
                                                            <option value="{{ $staff->id }}"> {{ $staff->name }} </option>
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
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full" id="payroll">


                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @if($payroll)
            <div>
                <div class="block block-themed">
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <div class="table-responsive">

                                        <style>
                                            .strong-border {
                                                /*border-top: unset; border-top-color: unset; border-bottom-color: unset;*/
                                                border: 1px solid #555 !important;
                                            }
                                            .payslip-table {
                                                border-color: #555555;
                                            }
                                            p {
                                                padding: 0px;
                                                margin: 0 0 5px 0;
                                            }
                                        </style>
                                        <table class="table table-bordered payslip-table ">
                                            <thead>
                                            <tr>
                                                <th width="30%" colspan="3" style="border-right: none;">
                                                    <div class="row">
                                                        <div class="col-xs-4 col-md-3 col-lg-2">
                                                            <img class="img img-responsive" src="{{ asset('media/avatars/logo.png') }}" height="100" width="100">
                                                        </div>
                                                        <div class="col-xs-8 col-md-9 col-lg-10" >
                                                            <p>{{settings('ORGANIZATION_NAME')}}</p>
                                                            <p>Morogoro Road, House #49 Pwani Tanzania.</p>
                                                            <p>P. O. Box 16520, KIBAHA - COAST</p>
                                                            <p>Tel: +255 657 798 062</p>
                                                            <!--                        <p>Mob:--><!--</p>-->
                                                        </div>
                                                    </div>
                                                </th>
                                                <td colspan="1">
                                                    <div class="row">
                                                        <div class="col-sm-12"><h2 class="text-right" style="color:#777777;">PAYSLIP</h2></div>
                                                        <div class="col-sm-12">

                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td width="30%">Payroll Number:</td><td>{{$payroll['payroll_number']}}</td>
                                                <td width="30%">Payroll Month:</td><td>{{date('F',strtotime($payroll->year.'-'.$payroll->month.'-'.'01')).' - '.$payroll->year}}</td>
                                            </tr>
                                            <tr>
                                                <td width="30%">Employee Number:</td><td>HRM/LE/PO-{{$employee->employee_number ?? null}}</td>
                                                <td width="30%">Employee Name:</td><td>{{$employee->name  ?? null}}</td>
                                            </tr>
                                            <tr>
                                                <td>Department:</td><td>Human Resources &amp; Administration (HRA)</td>
                                                <td>Designation:</td><td>{{$employee->designation ?? null}}</td>
                                            </tr>
                                            <tr>
                                                <td>Bank Name:</td><td>{{$employee_bank_details->bank->name ?? null}}</td>
                                                <td>Account Number:</td><td>{{$employee_bank_details->account_number ?? null}}</td>
                                            </tr>
                                            <tr>
                                                <th>DETAILS</th><th>AMOUNT</th>
                                                <th>DETAILS</th><th>AMOUNT</th>
                                            </tr>
                                            <tr>
                                                <th>Employee Income</th><th></th>
                                                <th>Deductions</th><th></th>
                                            </tr>
                                            @php
                                            $max = count($left_side) > count($right_side) ? count($left_side) : count($right_side);
                                            foreach (range(0, $max -1 ) as $index) {
                                                echo "<tr>
                                                        <td>". (isset($left_side[$index]) ? $left_side[$index]['name'] : '') . "</td>
                                                        <td class='money text-right'>". (isset($left_side[$index]) ? \App\Classes\Utility::money_format($left_side[$index]['value']): ''). "</td>
                                                        <td>". (isset($right_side[$index]) ? $right_side[$index]['name'] : '') ."</td>
                                                        <td class='money text-right'>". (isset($right_side[$index]) ? \App\Classes\Utility::money_format($right_side[$index]['value']) : '')."</td>
                                                    </tr>
                                                ";
                                            }
                                            @endphp
                                            <tr>
                                                <th>&nbsp;</th><th></th>
                                                <th></th><th></th>
                                            </tr>
                                            <tr>
                                                <th>Gross Salary</th><td class="money text-right">{{number_format($gross_salary)}}</td>
                                                <th>Total Deductions</th><td class="money text-right">{{number_format($total_deduction+$advance_salary+$loan_deduction)}}</td>
                                            </tr>
                                            </tbody>
                                            <tfoot>
                                            <tr>
                                                <td class="strong-border" colspan="3">NET SALARY
                                                    <!--            <div class="pull-right" style="font-weight: unset; font-size: small;"><i>(--><!--)</i></div>-->
                                                </td>
                                                <th class="money sum strong-border text-right" colspan="1">
                                                    {{number_format($net_salary)}}
                                                </th>
                                            </tr>
                                            </tfoot>
                                        </table>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @else
                <div>
                    <div class="block block-themed bg-gray min-height-200 text-center" >
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



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

                <div class="float-right">

                </div>
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-content">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="text-right">
                                    <input name="b_print" type="button" class="ipt" onClick="printdiv('div_print');" value=" Print ">
                                </div>
                            </div>
                        </div>
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <div class="row" style="border-bottom: 3px solid gray">
                                        <div class="col-md-3 text-right">
                                            <img class="" src="{{ asset('media/avatars/logo.png') }}" alt="" height="100">
                                        </div>
                                        <div class="col-md-6 text-center">
                                            <span class="text-center font-size-h3">LERUMA ENTERPRISES</span><br/>
                                            <span class="text-center font-size-h5">BOX 30133, KIBAHA - COAST, Mobile 0657 798 062</span><br/>
                                            <span class="text-center font-size-h5">TIN 113 - 882 - 384</span>
                                        </div>
                                        <div class="col-md-3 text-right">
{{--                                            <a href="{{route('reports')}}"   type="button" class="btn btn-sm btn-danger"><i class="fa fa arrow-left"></i>Back</a>--}}
                                        </div>
                                    </div>
                                </div>
                                <br/>
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
                        <div id="div_print">
                            <div class="row m-t-10">

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
                                                                    <p>LERUMA ENTERPRISES</p>
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
                                                        <th>Total Deductions</th><td class="money text-right">{{number_format($total_deduction+$advance_salary+$loan_deduction+$employee_deducted_amount_payee)}}</td>
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


<script language="javascript">
    function printdiv(printpage) {
        var headstr = "<html><head><title></title></head><body>";
        var footstr = "</body>";
        var newstr = document.all.item(printpage).innerHTML;
        var oldstr = document.body.innerHTML;
        document.body.innerHTML = headstr + newstr + footstr;
        window.print();
        document.body.innerHTML = oldstr;
        return false;
    }
</script>

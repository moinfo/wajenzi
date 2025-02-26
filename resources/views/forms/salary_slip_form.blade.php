@php
    $payroll_id = $object->id;
    $staffs = \App\Models\Staff::onlyStaffs();
@endphp
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
                                        <img class="img img-responsive" src="{{ asset('media/logo/wajenzilogo.png') }}" height="100" width="100">
                                    </div>
                                    <div class="col-xs-8 col-md-9 col-lg-10" >
                                        <p>{{settings('ORGANIZATION_NAME')}}</p>
                                        <p>{{settings('COMPANY_ADDRESS_LINE_1')}}</p>
                                        <p>{{settings('COMPANY_ADDRESS_LINE_2')}}</p>
                                        <p>{{settings('COMPANY_PHONE_NUMBER')}}</p>
                                        <p>{{settings('COMPANY_EMAIL_ADDRESS')}}</p>
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



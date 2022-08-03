@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Reports
            </div>
            <div>
                <div class="block block-themed">

                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                @include('components.headed_paper')
                                <br/>
                                <div class="class card-box">
                                    <form  name="collection_search"  id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Start Date</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon2">End Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-2">
                                                <div>
                                                    <button type="submit" name="submit" value="0" class="btn btn-sm btn-primary">Show</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="block-header text-center">
                                    <h3 class="block-title">Transaction Movement Report</h3>
                                </div>
                            </div>
                        </div>
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <tr>
                                <th class="text-center" colspan="6">BANK WITHDRAW REPORT</th>
                            </tr>
                            <tr>
                                <th class="text-center" >#</th>
                                <th>Bank Name</th>
                                <th>Amount</th>
                            </tr>
                            <?php
                            $withdraw_total = 0;
                            ?>
                            @foreach($withdraws as $withdraw)
                                <?php
                                $withdraw_total += $withdraw->amount;
                                ?>
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td>{{$withdraw->name}}</td>
                                    <td class="text-right">{{number_format($withdraw->amount)}}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <th colspan="2" class="text-right">Total Withdraw</th>
                                <th class="text-right"><b>{{number_format($withdraw_total)}}</b></th>
                            </tr>

                            <tr>
                                <th class="text-center" colspan="6">TRANSACTION REPORT</th>
                            </tr>
                            <tr>
                                <th class="text-center" >#</th>
                                <th>Supervisor Name</th>
                                <th>Amount</th>
                            </tr>
                            <?php
                            $transaction_total = $transaction_muhidini+$transaction_kassim+$transaction_leruma;
                            ?>

                            <tr>
                                <td>1</td>
                                <td>MAILIMOJA SHOP</td>
                                <td class="text-right">{{number_format($transaction_muhidini)}}</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>LOLIONDO SHOP</td>
                                <td class="text-right">{{number_format($transaction_kassim)}}</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>MAGARI</td>
                                <td class="text-right">{{number_format($transaction_leruma)}}</td>
                            </tr>
                            <tr>
                                <th colspan="2" class="text-right">Total Transactions</th>
                                <th class="text-right"><b>{{number_format($transaction_total)}}</b></th>
                            </tr>
                            <tr>
                                <th colspan="2" class="text-right">Total Transactions + Total Withdraw</th>
                                <th class="text-right"><b>{{number_format($transaction_total+$withdraw_total)}}</b></th>
                            </tr>
                            <tr>
                                <th class="text-center" colspan="6">BANK DEPOSIT REPORT</th>
                            </tr>
                            <tr>
                                <th class="text-center" >#</th>
                                <th>Bank Name</th>
                                <th>Amount</th>
                            </tr>
                            <?php
                            $deposit_total = 0;
                            ?>
                            @foreach($deposits as $deposit)
                                <?php
                                $deposit_total += $deposit->amount;
                                ?>
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td>{{$deposit->name}}</td>
                                    <td class="text-right">{{number_format($deposit->amount)}}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <th colspan="2" class="text-right">Total Deposit</th>
                                <th class="text-right"><b>{{number_format($deposit_total)}}</b></th>
                            </tr>
                            <tr>
                                <th class="text-center" colspan="6">LOAN REPORT</th>
                            </tr>
                            <tr>
                                <th class="text-center" >#</th>
                                <th>Staff Name</th>
                                <th>Amount</th>
                            </tr>
                            <?php
                            $loan_total = 0;
                            ?>
                            @foreach($loans as $loan)
                                <?php
                                $loan_total += $loan->amount;
                                ?>
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td>{{$loan->name}}</td>
                                    <td class="text-right">{{number_format($loan->amount)}}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <th colspan="2" class="text-right">Total Loan</th>
                                <th class="text-right"><b>{{number_format($loan_total)}}</b></th>
                            </tr>
                            <tr>
                                <th class="text-center" colspan="6">ADVANCE SALARY REPORT</th>
                            </tr>
                            <tr>
                                <th class="text-center" >#</th>
                                <th>staff Name</th>
                                <th>Amount</th>
                            </tr>
                            <?php
                            $advance_salary_total = 0;
                            ?>
                            @foreach($advance_salaries as $advance_salary)
                                <?php
                                $advance_salary_total += $advance_salary->amount;
                                ?>
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td>{{$advance_salary->name}}</td>
                                    <td class="text-right">{{number_format($advance_salary->amount)}}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <th colspan="2" class="text-right">Total Advance Salary</th>
                                <th class="text-right"><b>{{number_format($advance_salary_total)}}</b></th>
                            </tr>
                            <tr>
                                <th class="text-center" colspan="6">NET SALARY REPORT</th>
                            </tr>
                            <tr>
                                <th class="text-center" >#</th>
                                <th>Staff Name</th>
                                <th>Amount</th>
                            </tr>
                            <?php
                            $payroll_total = 0;
                            ?>
                            @foreach($payrolls as $payroll)
                                <?php
                                $payroll_total += $payroll->amount;
                                ?>
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td>{{$payroll->name}}</td>
                                    <td class="text-right">{{number_format($payroll->amount)}}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <th colspan="2" class="text-right">Total Net Salary</th>
                                <th class="text-right"><b>{{number_format($payroll_total)}}</b></th>
                            </tr>
                            <tr>
                                <th class="text-center" colspan="6">ALLOWANCE REPORT</th>
                            </tr>
                            <?php
                            $allowance_total = 0;
                            ?>
                            @foreach($allowances as $allowance)
                                <?php
                                $allowance_total += $allowance->amount;
                                ?>
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td>{{$allowance->name}}</td>
                                    <td class="text-right">{{number_format($allowance->amount)}}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <th colspan="2" class="text-right">Total Allowances</th>
                                <th class="text-right"><b>{{number_format($allowance_total)}}</b></th>
                            </tr>
                            <tr>
                                <th class="text-center" colspan="6">PAYMENT REPORT</th>
                            </tr>
                            <tr>
                                <th class="text-center" >#</th>
                                <th>Supplier Name</th>
                                <th>Amount</th>
                            </tr>
                            <?php
                            $payment_total = 0;
                            ?>
                            @foreach($bonge_payments as $payment)
                                <?php
                                $payment_total += $payment->dr;
                                ?>
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td>{{$payment->first_name_client .' '. $payment->last_name_client}}</td>
                                    <td class="text-right">{{number_format($payment->dr  ?? 0)}}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <th colspan="2" class="text-right">Total Payments</th>
                                <th class="text-right"><b>{{number_format($payment_total  ?? 0)}}</b></th>
                            </tr>
                            <tr>
                                <th colspan="2" class="text-right">Total Payments + Total Deposit + Total Loan + Total Advance + Total Net</th>
                                <th class="text-right"><b>{{number_format(($payment_total+$deposit_total+$loan_total+$advance_salary_total+$payroll_total+$allowance_total)  ?? 0)}}</b></th>
                            </tr>
                            <tr>
                                <th colspan="2" class="text-right">Total All Transactions - Total All Payments</th>
                                <th class="text-right"><b>{{number_format(( ($transaction_total+$withdraw_total)  ?? 0 )-(($payment_total+$deposit_total+$loan_total+$advance_salary_total+$payroll_total+$allowance_total)  ?? 0))}}</b></th>
                            </tr>
                            <tr>
                                <th colspan="2" class="text-right">Balance Increments</th>
                                <th class="text-right"><b>{{number_format(($transaction_muhidini_all_time+$transaction_kassim_all_time+$transaction_leruma_all_time+$withdraws_all_time)-($bonge_payment_all_time+$deposits_all_time+$loans_all_time+$advance_salaries_all_time+$payrolls_all_time+$allowances_all_time))}}</b></th>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

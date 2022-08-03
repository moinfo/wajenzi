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
{{--                                @include('components.headed_paper')--}}
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
                                <th class="text-center" colspan="6">TRANSACTION REPORT</th>
                            </tr>
                            <tr>
                                <th class="text-center" >#</th>
                                <th>Supervisor Name</th>
                                <th>Amount</th>
                            </tr>
                            <?php
                            $transaction_total = $transaction_muhidini+$transaction_kassim+$transaction_leruma+$transaction_whitestar;
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
                                <td>3</td>
                                <td>WHITESTAR</td>
                                <td class="text-right">{{number_format($transaction_whitestar)}}</td>
                            </tr>
                            <tr>
                                <th colspan="2" class="text-right">Total Transactions</th>
                                <th class="text-right"><b>{{number_format($transaction_total)}}</b></th>
                            </tr>



                            <tr>
                                <th class="text-center" colspan="6">PAYMENT REPORT</th>
                            </tr>
                            <tr>
                                <th class="text-center" >#</th>
                                <th>Bonge Supplier Name</th>
                                <th>Amount</th>
                            </tr>
                            <?php
                            $payment_total_bonge = 0;
                            ?>
                            @foreach($bonge_payments as $payment)
                                <?php
                                $payment_total_bonge += $payment->dr;
                                ?>
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td>{{$payment->first_name_client .' '. $payment->last_name_client}}</td>
                                    <td class="text-right">{{number_format($payment->dr  ?? 0)}}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <th colspan="2" class="text-right">Total Payments</th>
                                <th class="text-right"><b>{{number_format(($payment_total_bonge)  ?? 0)}}</b></th>
                            </tr>

                            <tr>
                                <th class="text-center" >#</th>
                                <th>Whitestar Supplier Name</th>
                                <th>Amount</th>
                            </tr>
                            <?php
                            $payment_total_whitestar = 0;
                            ?>
                            @foreach($whitestar_payments as $payment)
                                <?php
                                $payment_total_whitestar += $payment->dr;
                                ?>
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td>{{$payment->first_name_client .' '. $payment->last_name_client}}</td>
                                    <td class="text-right">{{number_format($payment->dr  ?? 0)}}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <th colspan="2" class="text-right">Total Payments</th>
                                <th class="text-right"><b>{{number_format(($payment_total_whitestar)  ?? 0)}}</b></th>
                            </tr>
                            <tr>
                                <th colspan="2" class="text-right">Total All Transactions - Total All Payments</th>
                                <th class="text-right"><b>{{number_format(( ($transaction_total)  ?? 0 )-(($payment_total_bonge+$payment_total_whitestar)  ?? 0))}}</b></th>
                            </tr>
                            <tr>
                                <th colspan="2" class="text-right">Balance Increments</th>
                                <th class="text-right"><b>{{number_format(($transaction_muhidini_all_time+$transaction_kassim_all_time+$transaction_leruma_all_time)-($bonge_payment_all_time+$white_payment_all_time))}}</b></th>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

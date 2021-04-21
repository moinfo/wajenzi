@extends('layouts.backend')
@section('css_before')
<!-- Page JS Plugins CSS -->
<link rel="stylesheet" href="{{ asset('js/plugins/datatables/dataTables.bootstrap4.css') }}">
@endsection

@section('js_after')
<!-- Page JS Plugins -->
<script src="{{ asset('js/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/plugins/datatables/dataTables.bootstrap4.min.js') }}"></script>

<!-- Page JS Code -->
<script src="{{ asset('js/pages/tables_datatables.js') }}"></script>

<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
@endsection
@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading">Reports
        </div>
        <div>
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Transaction Movement Report</h3>
                </div>
                <div class="block-content">
                    <div class="row no-print m-t-10">
                        <div class="class col-md-12">
                            <div class="class card-box">
                                <form  name="collection_search" action="{{route('transaction_movement_report_search')}}" id="filter-form" method="post" autocomplete="off">
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
                        </div>
                    </div>
                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <tr>
                                <th class="text-center" colspan="6">TRANSACTION REPORT</th>
                            </tr>
                        <tr>
                            <th class="text-center" style="width: 100px;">#</th>
                            <th>Supervisor Name</th>
                            <th>Amount</th>
                        </tr>
                            <?php
                        $transaction_total = 0;
                        ?>
                          @foreach($transactions as $transaction)
                              <?php
                            $transaction_total += $transaction->amount;
                              ?>
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{$transaction->name}}</td>
                                <td class="text-right">{{number_format($transaction->amount)}}</td>
                            </tr>
                        @endforeach
                            <tr>
                                <th colspan="2" class="text-right">Total Transactions</th>
                                <th class="text-right"><b>{{number_format($transaction_total)}}</b></th>
                            </tr>
                        <tr>
                            <th class="text-center" colspan="6">PAYMENT REPORT</th>
                        </tr>
                        <tr>
                            <th class="text-center" style="width: 100px;">#</th>
                            <th>Supplier Name</th>
                            <th>Amount</th>
                        </tr>
                        <?php
                        $payment_total = 0;
                        ?>
                        @foreach($payments as $payment)
                            <?php
                            $payment_total += $payment->amount;
                            ?>
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{$payment->name}}</td>
                                <td class="text-right">{{number_format($payment->amount  ?? 0)}}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <th colspan="2" class="text-right">Total Payments</th>
                            <th class="text-right"><b>{{number_format($payment_total  ?? 0)}}</b></th>
                        </tr>
                        <tr>
                            <th colspan="2" class="text-right">Total Transactions - Total Payments</th>
                            <th class="text-right"><b>{{number_format(($transaction_total  ?? 0 )-($payment_total  ?? 0))}}</b></th>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

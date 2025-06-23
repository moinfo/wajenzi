@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Bank Reconciliation
                <div class="float-right">
                        <a href="{{route('bank_reconciliation')}}" type="button" title="Back"  class="btn btn-rounded btn-default min-width-100 mb-10"><i class="si si-arrow-left">&nbsp;</i>Back</a>

                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Bank Reconciliation Statement</h3>
                    </div>
                    <div class="block-content">
                        @can('Date Bank Reconciliation Statement')
                            <div class="row no-print m-t-10">
                                <div class="class col-md-12">
                                    <div class="class card-box">
                                        <form  name="collection_search" action="{{route('bank_reconciliation_bank_reconciliation_statement')}}" id="filter-form" method="post" autocomplete="off">
                                            @csrf
                                            <div class="row">
                                                <div class="class col-md-3">
                                                    <div class="input-group mb-3">
                                                        <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-d')}}">

                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text" id="basic-addon1">Date</span>
                                                        </div>
                                                        <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">

                                                    </div>

                                                </div>
                                                <div class="class col-md-3">
                                                    <div class="input-group mb-3">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text" id="basic-addon3">EFD</span>
                                                        </div>
                                                        <select name="efd_id" id="input-efd-id" class="form-control" aria-describedby="basic-addon3">
                                                            <option value="">All EFD</option>
                                                            @foreach ($efds as $efd)
                                                                <option value="{{ $efd->id }}"> {{ $efd->name }} </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="class col-md-2">
                                                    <div class="input-group mb-3">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text" id="basic-addon3">Type</span>
                                                        </div>
                                                        <select name="payment_type" id="payment_type" class="form-control">
                                                            <option value="">ALL</option>

                                                            @foreach ($bank_reconciliation_payment_types as $bank_reconciliation_payment_type)
                                                                <option value="{{$bank_reconciliation_payment_type['name']}}"> {{ $bank_reconciliation_payment_type['name'] }} </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="class col-md-1">
                                                    <div>
                                                        <button type="submit" name="submit"  class="btn btn-sm btn-primary">Show</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endcan
                        <div class="table-responsive">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full"  data-ordering="false">
                                            <thead>
                                            <tr>
                                                <th width="40">#</th>
                                                @foreach($efdTransactions as $index => $efd)
                                                    <th>{{$efd->name}}</th>
                                                @endforeach
                                                <th>Total</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $sale = new \App\Models\Sale();
                                            $start_date = $_POST['start_date'] ?? date('Y-m-d');
                                            $end_date = $_POST['end_date'] ?? date('Y-m-d');

                                            ?>
                                            <tr>
                                                <td>Receiving</td>
                                                @foreach($efds as $efd)
                                                    @php
                                                        $efd_id = $efd->id;
                                                        $receiving = \App\Models\Report::getTotalDaysSalesBonge($start_date,$end_date,$efd->bonge_customer_id);
                                                    @endphp
                                                    <td class="text-right">{{number_format($receiving,2)}}</td>
                                                @endforeach
                                                @php
                                                    $total_receiving = \App\Models\Receiving::getTotalReceivingPerDayPerSupervisor($start_date,$end_date,null);
                                                @endphp
                                                <td class="text-right">{{number_format($total_receiving,2)}}</td>
                                            </tr>
                                            <tr>
                                                <td>Balance</td>
                                                @foreach($efds as $efd)
                                                    @php
                                                        $efd_id = $efd->id;
                                                        $receiving = \App\Models\Receiving::getTotalReceivingPerDayPerSupervisor($start_date,$end_date,$efd_id);
                                                        $deposit = \App\Models\BankReconciliation::getTotalDepositPerDayPerSupervisor($start_date,$end_date,$efd_id);
                                                    @endphp
                                                    <td class="text-right">{{number_format($receiving-$deposit,2)}}</td>
                                                @endforeach
                                                @php
                                                    $total_receiving = \App\Models\Receiving::getTotalReceivingPerDayPerSupervisor($start_date,$end_date,null);
                                                    $total_deposit = \App\Models\BankReconciliation::getTotalDepositPerDayPerSupervisor($start_date,$end_date,null);

                                                @endphp
                                                <td class="text-right">{{number_format($total_receiving-$total_deposit,2)}}</td>
                                            </tr>
                                            <tr>
                                                <td>Deposits</td>
                                                @foreach($efds as $efd)

                                                    <td class="text-right"></td>
                                                @endforeach
                                                <td class="text-right"></td>
                                            </tr>
                                            @php {{ $totalSum = 0; }} @endphp
                                            @foreach(range(0, 30) as $rowIndex => $rowIndex)
                                                @php {{ $rowSum = 0; }} @endphp
                                                <tr>
                                                    <td>{{$loop->index + 1}}</td>
                                                    @foreach($efdTransactions as $columnIndex => $efd)
                                                        @php {{
                                                    $val = isset($efd->transactions[$rowIndex]) ? $efd->transactions[$rowIndex]->debit : '';
                                                    $rowSum += is_numeric($val) ? $val : 0;
                                                }}
                                                        @endphp
                                                        <td class="text-right">
                                                            {{ $val }}
                                                        </td>
                                                    @endforeach
                                                    <td class="text-right">{{ number_format($rowSum) }}</td>
                                                </tr>

                                                @php {{ $totalSum += $rowSum; }} @endphp
                                            @endforeach
                                            <tr>
                                                <th></th>
                                                @foreach($efds as $efd)

                                                    <th class="">{{$efd->name}}</th>
                                                @endforeach
                                                <th class="text-right"></th>
                                            </tr>
                                            <tr>
                                                <th>All Total</th>
                                                @foreach($efdTransactions as $footerIndex => $efd)
                                                    <td class="text-right">
                                                        {{ array_sum(array_column($efd->transactions->toArray(), 'debit')) }}
                                                    </td>
                                                @endforeach
                                                <th class="text-right">{{ number_format($totalSum,2) }}</th>
                                            </tr>
                                            <tr>
                                                <th>Unspent Total</th>
                                                @foreach($efdTransactions_2 as $footerIndex => $efd)
                                                    <td class="text-right">
                                                        {{ array_sum(array_column($efd->transactions->toArray(), 'debit')) }}
                                                    </td>
                                                @endforeach
                                                <th class="text-right"></th>
                                            </tr>
                                            <tr>
                                                <th>Actual Total</th>
                                                @foreach($efdTransactions_3 as $footerIndex => $efd)
                                                    <td class="text-right">
                                                        {{ array_sum(array_column($efd->transactions->toArray(), 'debit')) }}
                                                    </td>
                                                @endforeach
                                                <th class="text-right"></th>
                                            </tr>
                                            </tbody>
                                        </table>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection



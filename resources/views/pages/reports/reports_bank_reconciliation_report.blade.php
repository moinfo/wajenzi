@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Reports
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-gd-dusk">
                        <h3 class="block-title">Bank Reconciliation Report</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form  name="expense_search" action="" id="filter-form" method="post" autocomplete="off">
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
                                                    <button type="submit" name="submit"  class="btn btn-sm btn-primary">Show</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
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
                                            $receiving = \App\Models\Receiving::getTotalReceivingPerDayPerSupervisor($start_date,$end_date,$efd_id);
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
                                @foreach(range(0, $maxTransactions - 1) as $rowIndex => $rowIndex)
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
                                    <th>Total</th>
                                    @foreach($efdTransactions as $footerIndex => $efd)
                                        <td class="text-right">
                                            {{ array_sum(array_column($efd->transactions->toArray(), 'debit')) }}
                                        </td>
                                    @endforeach
                                    <th class="text-right">{{ number_format($totalSum,2) }}</th>
                                </tr>
                                </tbody>
                            </table>

                    </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full"  data-ordering="false">
                                <thead>
                                <tr>
                                    <th>EFDs/SUPPLIERS</th>
                                    @foreach($supplier_with_deposits as $supplier)
                                    <th>{{$supplier->name}}</th>
                                    @endforeach
                                    <th>Total</th>

                                </tr>
                                </thead>
                                <tbody>
                                @php
                                @endphp
                                @foreach($efds as $efd)
                                    @php
                                        $efd_id = $efd->id;
                                    @endphp
                                    <tr>
                                        <td>{{$efd->name}}</td>
                                        @foreach($supplier_with_deposits as $supplier)
                                            @php {{
                                            $supplier_id = $supplier->supplier_id;
                                            $deposit = \App\Models\BankReconciliation::getTotalDepositPerDayPerSupplier($start_date,$end_date,$efd_id,$supplier_id);

                                            }} @endphp
                                            <td class="text-right">{{number_format($deposit,2)}}</td>
                                        @endforeach
                                        @php {{
                                            $total_deposit = \App\Models\BankReconciliation::getTotalDepositPerDayPerSupplier($start_date,$end_date,$efd_id,null);

                                            }} @endphp
                                        <td class="text-right">{{number_format($total_deposit,2)}}</td>
                                    </tr>
                                @endforeach

                                </tbody>
                                <tfoot>
                                <tr>
                                    <td>Total</td>
                                    @foreach($supplier_with_deposits as $supplier)
                                        @php {{
                                            $supplier_id = $supplier->supplier_id;
                                            $deposit = \App\Models\BankReconciliation::getTotalDepositPerDayPerSupplier($start_date,$end_date,null,$supplier_id);

                                            }} @endphp
                                        <td class="text-right">{{number_format($deposit,2)}}</td>
                                    @endforeach
                                    @php {{
                                            $total_deposit = \App\Models\BankReconciliation::getTotalDepositPerDayPerSupplier($start_date,$end_date,null,null);

                                            }} @endphp
                                    <td class="text-right">{{number_format($total_deposit,2)}}</td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection




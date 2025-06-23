@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Bank Reconciliation
                <div class="float-right">

                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Bank Deposited</h3>
                    </div>
                    <div class="block-content">
                        @can('Date Bank Deposit View')
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form  name="collection_search" action="" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-8">
                                                <div class="input-group mb-3">
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-d')}}">

                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">

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
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Actual</th>
                                                <th>System Bank</th>
                                                <th>Difference</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @php
                                                $start_date = $_POST['start_date'] ?? date('Y-m-d');
                                                $end_date = $_POST['end_date'] ?? date('Y-m-d');
                                                $deposit_sum = 0;
                                                $deposit_excluded_total = 0;
                                                $balance_total = 0;
                                                $bank_deposited_total = 0;
                                                $total_difference = 0;

                                            @endphp
                                            @foreach($systems as $system)
                                                @php
                                                    $deposit = \App\Models\BankReconciliation::getTotalDepositPerDayPerSystem($start_date,$end_date,$system->id);

                                                    $deposit_sum += $deposit;

                                                @endphp
                                                <tr>
                                                    <td>{{$loop->iteration}}</td>
                                                    <td>{{$system->name}}</td>
                                                    <td class="text-right">{{number_format($deposit,2)}}</td>

                                                        @if($system->id == 1)
                                                            @php
                                                                $bank_deposited = \App\Models\Report::getSupplierBankDepositedLeruma($start_date,$end_date);
                                                                $bank_deposited_total += $bank_deposited;
                                                                $difference = $deposit - $bank_deposited;
                                                                $total_difference += $difference;
                                                                @endphp
                                                        <td class="text-right">{{number_format($bank_deposited)}}</td>
                                                        <td class="text-right">{{number_format($difference)}}</td>
                                                        @elseif($system->id == 2)
                                                        @php
                                                                $bank_deposited = \App\Models\Report::getSupplierBankDepositedMuhidini($start_date,$end_date);
                                                                $bank_deposited_total += $bank_deposited;
                                                                $difference = $deposit - $bank_deposited;
                                                                $total_difference += $difference;
                                                        @endphp
                                                        <td class="text-right">{{number_format($bank_deposited)}}</td>
                                                        <td class="text-right">{{number_format($difference)}}</td>
                                                    @elseif($system->id == 3)
                                                        @php
                                                                $bank_deposited = \App\Models\Report::getSupplierBankDepositedKassim($start_date,$end_date);
                                                                $bank_deposited_total += $bank_deposited;
                                                                $difference = $deposit - $bank_deposited;
                                                                $total_difference += $difference;
                                                        @endphp
                                                        <td class="text-right">{{number_format($bank_deposited)}}</td>
                                                        <td class="text-right">{{number_format($difference)}}</td>
                                                    @else
                                                        @php
                                                                $bank_deposited = 0;
                                                                $bank_deposited_total += $bank_deposited;
                                                                $difference = $deposit - $bank_deposited;
                                                                $total_difference += $difference;
                                                        @endphp
                                                        <td class="text-right">{{number_format($bank_deposited)}}</td>
                                                        <td class="text-right">{{number_format($difference)}}</td>
                                                    @endif

                                                </tr>
                                            @endforeach
                                            @php
                                                $white = \App\Models\BankReconciliation::getTotalDepositWhitestar($start_date,$end_date,16) - \App\Models\BankReconciliation::getTotalDepositWhitestarAuto($start_date,$end_date,16) - \App\Models\BankReconciliation::getTotalDepositPerDayPerSystemNotTransfered($start_date,$end_date);
                                                $bank_deposited_whitestar = \App\Models\Report::getSupplierBankDepositedWhiteStar($start_date,$end_date);
                                                $difference_white = $white - $bank_deposited_whitestar;
                                            @endphp
                                            <tr>
                                                <td>5</td>
                                                <td>WHITESTAR</td>
                                                <td class="text-right">{{number_format($white)}}</td>
                                                <td class="text-right">{{number_format($bank_deposited_whitestar)}}</td>
                                                <td class="text-right">{{number_format($white-$bank_deposited_whitestar)}}</td>
                                            </tr>
                                            </tbody>
                                            <tfoot>
                                            <tr>
                                                <th colspan="2" class="text-right">Total</th>
                                                <th class="text-right">{{number_format(($deposit_sum+$white),2)}}</th>
                                                <th class="text-right">{{number_format($bank_deposited_total+$bank_deposited_whitestar)}}</th>
                                                <th class="text-right">{{number_format(($deposit_sum+$white)-($bank_deposited_total+$bank_deposited_whitestar))}}</th>
                                            </tr>

                                            </tfoot>

                                        </table>
                                    </div>
                                </div>
                                <div class="col-sm-2"></div>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection



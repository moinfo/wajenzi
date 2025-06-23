@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">BR
                <div class="float-right">
                    @can('Sales Bank Deposited')
                        <a href="{{route('bank_reconciliation_sales_bank_deposited')}}" title="Sales Bank Deposited"
                           class="btn btn-rounded btn-outline-primary min-width-100 mb-10"><i
                                class="si si-graph">&nbsp;</i>SBD</a>
                    @endcan
                    @can('Unrepresented Slip')
                        <a href="{{route('unrepresented_slip')}}" title="Unrepresented Slip"
                           class="btn btn-rounded btn-outline-primary min-width-100 mb-10"><i
                                class="si si-graph">&nbsp;</i>UPS <span class="text-danger">{{$total_unrepresented_slip}}</span></a>
                    @endcan
                    @can('Supplier Statement')
                        <a href="{{route('bank_reconciliation_suppliers_statement')}}" title="Supplier Statement"
                           class="btn btn-rounded btn-outline-primary min-width-100 mb-10"><i
                                class="si si-graph">&nbsp;</i>SS</a>
                    @endcan
                    @can('Bank Reconciliation Statement')
                        <a href="{{route('bank_reconciliation_bank_reconciliation_statement')}}"
                           title="Bank Reconciliation Statement"
                           class="btn btn-rounded btn-outline-primary min-width-100 mb-10"><i
                                class="si si-graph">&nbsp;</i>BRS</a>
                    @endcan
                    @can('Slip Review Report')
                        <a href="{{route('slip_review_report')}}" title="Slip Review Report"
                           class="btn btn-rounded btn-outline-primary min-width-100 mb-10"><i
                                class="si si-graph">&nbsp;</i>SRR</a>
                    @endcan
                    @can('Bank deposit View')
                        <a href="{{route('bank_deposit_report')}}" title="Bank deposit View"
                           class="btn btn-rounded btn-outline-danger min-width-100 mb-10"><i
                                class="si si-graph">&nbsp;</i>BDV</a>
                    @endcan
                    @can('Supplier Target')
                        <a href="{{route('supplier_targets')}}" title="Supplier Target"
                           class="btn btn-rounded btn-outline-warning min-width-100 mb-10"><i
                                class="si si-graph">&nbsp;</i>ST</a>
                    @endcan
                    @can('Supplier Target Report')
                        <a href="{{route('supplier_targets_report')}}" title="Supplier Target Report"
                           class="btn btn-rounded btn-outline-warning min-width-100 mb-10"><i
                                class="si si-graph">&nbsp;</i>STR</a>
                    @endcan
                    @can('Supplier Target Preparation')
                        <a href="{{route('supplier_target_preparation')}}" title="Supplier Target Preparation"
                           class="btn btn-rounded btn-outline-warning min-width-100 mb-10"><i
                                class="si si-graph">&nbsp;</i>STP</a>
                    @endcan
                    @can('Transfer Report')
                        <a href="{{route('transfer_reports')}}" title="Transfer Report"
                           class="btn btn-rounded btn-outline-success min-width-100 mb-10"><i
                                class="si si-graph">&nbsp;</i>TR</a>
                    @endcan
                    @can('Withdraw Office Report')
                        <a href="{{route('bank_withdraw_reports')}}" title="Withdraw Office Report"
                           class="btn btn-rounded btn-outline-primary min-width-100 mb-10"><i
                                class="si si-graph">&nbsp;</i>WOR</a>
                    @endcan
                    @can('Deposit Office Report')
                        <a href="{{route('bank_deposit_reports')}}" title="Deposit Office Report"
                           class="btn btn-rounded btn-outline-danger min-width-100 mb-10"><i
                                class="si si-graph">&nbsp;</i>DOR</a>
                    @endcan
                    @can('Deposit')
                        <a href="{{route('bank_reconciliation_deposits')}}" type="button" title="Deposit"
                           class="btn btn-rounded btn-outline-primary min-width-100 mb-10"><i
                                class="si si-graph">&nbsp;</i>D</a>
                    @endcan
                    @can('Withdraw')
                        <a href="{{route('bank_reconciliation_withdraws')}}" type="button" title="Withdraw"
                           class="btn btn-rounded btn-outline-primary min-width-100 mb-10"><i
                                class="si si-graph">&nbsp;</i>W</a>
                    @endcan
                    @can('Transfer')
                        <a href="{{route('bank_reconciliation_transfers')}}" type="button" title="Transfer"
                           class="btn btn-rounded btn-outline-danger min-width-100 mb-10"><i
                                class="si si-graph">&nbsp;</i>T</a>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Bank Reconciliation</h3>
                    </div>
                    <div class="block-content">
                        @can('Date Bank Reconciliation')
                            <div class="row no-print m-t-10">
                                <div class="class col-md-12">
                                    <div class="class card-box">
                                        <form name="collection_search" action="" id="filter-form" method="post"
                                              autocomplete="off">
                                            @csrf
                                            <div class="row">
                                                <div class="class col-md-3">
                                                    <div class="input-group mb-3">
                                                        <input type="text" name="start_date" id="start_date"
                                                               class="form-control datepicker-index-form datepicker"
                                                               aria-describedby="basic-addon1"
                                                               value="{{date('Y-m-d')}}">

                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text" id="basic-addon1">Date</span>
                                                        </div>
                                                        <input type="text" name="end_date" id="end_date"
                                                               class="form-control datepicker-index-form datepicker"
                                                               aria-describedby="basic-addon2"
                                                               value="{{date('Y-m-d')}}">

                                                    </div>

                                                </div>
                                                <div class="class col-md-2">
                                                    <div class="input-group mb-3">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text" id="basic-addon3">Type</span>
                                                        </div>
                                                        <select name="payment_type" id="payment_type"
                                                                class="form-control">
                                                            <option value="">ALL</option>

                                                            @foreach ($bank_reconciliation_payment_types as $bank_reconciliation_payment_type)
                                                                <option
                                                                    value="{{$bank_reconciliation_payment_type['name']}}"> {{ $bank_reconciliation_payment_type['name'] }} </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="class col-md-1">
                                                    <div>
                                                        <button type="submit" name="submit"
                                                                class="btn btn-sm btn-primary">Show
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endcan
                        <div class="row">
                            <div class="col-sm-2"></div>
                            <div class="col-sm-8">
                                <h4>Actual Deposit Summary</h4>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full"
                                           data-ordering="false">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Efd</th>
                                            <th>Actual</th>
                                            <th>Supplier Excluded</th>
                                            <th>Balance</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @php
                                            $start_date = $_POST['start_date'] ?? date('Y-m-d');
                                            $end_date = $_POST['end_date'] ?? date('Y-m-d');
                                            $deposit_sum = 0;
                                            $deposit_excluded_total = 0;
                                            $balance_total = 0;

                                        @endphp
                                        @foreach($systems as $system)
                                            @php
                                                $deposit = \App\Models\BankReconciliation::getTotalDepositPerDayPerSystem($start_date,$end_date,$system->id);
                                                $deposit_excluded = \App\Models\BankReconciliation::getTotalDepositPerDayPerSystemExcluded($start_date,$end_date,$system->id);
                                                $balance = $deposit - $deposit_excluded;

                                                $deposit_excluded_total += $deposit_excluded;
                                                $balance_total += $balance;
                                                $deposit_sum += $deposit;
                                            @endphp
                                            <tr>
                                                <td>{{$loop->iteration}}</td>
                                                <td>{{$system->name}}</td>
                                                <td class="text-right">{{number_format($deposit,2)}}</td>
                                                <td class="text-right">{{number_format($balance,2)}}</td>
                                                <td class="text-right">{{number_format($deposit_excluded,2)}}</td>
                                            </tr>
                                        @endforeach
                                        @php
                                            $white = \App\Models\BankReconciliation::getTotalDepositWhitestar($start_date,$end_date,16) - \App\Models\BankReconciliation::getTotalDepositWhitestarAuto($start_date,$end_date,16);
                                        @endphp
                                        <tr>
                                            <td>5</td>
                                            <td>WHITESTAR</td>
                                            <td class="text-right">{{number_format($white)}}</td>
                                            <td class="text-right"></td>
                                            <td class="text-right"></td>
                                        </tr>
                                        </tbody>
                                        <tfoot>
                                        <tr>
                                            <th colspan="2" class="text-right">Total</th>
                                            <th class="text-right">{{number_format($deposit_sum,2)}}</th>
                                            <th class="text-right">{{number_format($balance_total,2)}}</th>
                                            <th class="text-right">{{number_format($deposit_excluded_total,2)}}</th>
                                        </tr>
                                        @php

                                            $deposit_out = \App\Models\BankReconciliation::getTotalDepositPerDayPerSystem($start_date,$end_date,5) ?? 0;
                                            $deposit_out_only = \App\Models\BankReconciliation::getTotalDepositPerDayPerSystemOnly($start_date,$end_date,5) ?? 0;
                                             $deposit_excluded_out = \App\Models\BankReconciliation::getTotalDepositPerDayPerSystemExcluded($start_date,$end_date,5) ?? 0;
                                             $deposit_excluded_out_only = \App\Models\BankReconciliation::getTotalDepositPerDayPerSystemExcludedOnly($start_date,$end_date,5) ?? 0;
                                        @endphp
                                        <tr>
                                            <td colspan="2" class="text-right">KIWANDANI</td>
                                            <td class="text-right">{{number_format($deposit_out,2)}}</td>
                                            <td class="text-right">{{number_format(($deposit_out)-($deposit_excluded_out),2)}}</td>
                                            <td class="text-right">{{number_format(($deposit_excluded_out),2)}}</td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="col-sm-2"></div>
                        </div>
                        <br>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection



@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Supplier Target Report
                <div class="float-right">

                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Supplier Targets Report</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            @can('Date Supplier Targets Report')

                            <div class="class col-md-9">
                                <div class="class card-box">
                                    <form  name="collection_search" action="" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-5">
                                                <div class="input-group mb-3">
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-d')}}">

                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">

                                                </div>

                                            </div>
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon3">Supplier</span>
                                                    </div>
                                                    <select name="supplier_id" id="input-supplier-id" class="form-control" aria-describedby="basic-addon3">
                                                        <option value="">All Suppliers</option>
                                                        @foreach ($suppliers as $supplier)
                                                            <option value="{{ $supplier->id }}"> {{ $supplier->name }} </option>
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
                            @endcan
                            <div class="class col-md-3">
                                <div>
                                    <a href="{{route('reports_commission_vs_deposit_report')}}" class="btn btn-rounded btn-outline-warning min-width-125 mb-10"><i class="si si-graph">&nbsp;</i>Supplier Commissions Report</a>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full"  data-ordering="false">
                                            <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Supplier</th>
                                                <th>Beneficiary</th>
                                                <th>Target</th>
                                                <th>Deposited</th>
                                                <th>Transfers</th>
                                                <th>Difference</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @php
                                                $total_target = 0;
                                                $total_difference = 0;
                                                $total_deposited = 0;
                                                $total_transfers = 0;
                                                $efd_id = null;
                                            @endphp
                                            @foreach($supplier_targets_reports as $supplier_targets_report)
                                            @php
                                                $total_target += $supplier_targets_report->total_target;
                                                $deposited = \App\Models\BankReconciliation::getTotalDepositBySupplier($supplier_targets_report->target_date, $supplier_targets_report->target_date, $supplier_targets_report->supplier_id);
                                                 $total_deposited += $deposited;
                                                 $transfers = \App\Models\BankReconciliation::getOnlyTransferedBySupplierSingle($supplier_targets_report->target_date, $supplier_targets_report->target_date,$supplier_targets_report->supplier_id)->debit_amount ?? 0;
                                                 $total_transfers += $transfers;
                                                 $difference =  $supplier_targets_report->total_target + $transfers  - $deposited;
                                                $total_difference += $difference;
                                            @endphp
                                                <tr id="supplier_targets_report-tr-{{$supplier_targets_report->id}}">
                                                    <td class="text-center">
                                                        {{$loop->iteration}}
                                                    </td>
                                                    <td class="font-w600">{{ $supplier_targets_report->supplier_name }}</td>
                                                    <td class="font-w600">{{ $supplier_targets_report->beneficiary_name }}</td>
                                                    <td class="text-right">{{ number_format($supplier_targets_report->total_target, 2) }}</td>
                                                    <td class="text-right">{{ number_format($deposited, 2) }}</td>
                                                    <td class="text-right">{{ number_format(abs($transfers))}}</td>
                                                    <td class="text-right">{{ number_format($difference, 2) }}</td>
                                                </tr>
                                            @endforeach

                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th class="text-right" colspan="3">TOTAL</th>
                                                    <th class="text-right">{{number_format($total_target)}}</th>
                                                    <th class="text-right">{{number_format($total_deposited)}}</th>
                                                    <th class="text-right">{{number_format(abs($total_transfers))}}</th>
                                                    <th class="text-right">{{number_format($total_difference)}}</th>
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



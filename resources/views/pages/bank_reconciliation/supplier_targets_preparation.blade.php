@php use Illuminate\Support\Facades\DB; @endphp
@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Supplier Target Preparation
                <div class="float-right">

                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Supplier Targets Preparation</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Date Supplier Targets Report"))

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
                            @endif
                            <div class="class col-md-3">
                                <div>
                                    @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Add Supplier Target Preparation"))
                                        <button type="button" onclick="loadFormModal('supplier_target_preparation_form', {className: 'SupplierTargetPreparation'}, 'Create New Target Preparation', 'modal-lg');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Target Preparation</button>
                                    @endif
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
                                            @foreach($supplier_target_preparations as $supplier_targets_report)
                                            @php
                                                $total_target += $supplier_targets_report->total_target;
                                            @endphp
                                                <tr id="supplier_targets_report-tr-{{$supplier_targets_report->id}}">
                                                    <td class="text-center">
                                                        {{$loop->iteration}}
                                                    </td>
                                                    <td class="font-w600">{{ $supplier_targets_report->supplier_name }}</td>
                                                    <td class="font-w600">{{ $supplier_targets_report->beneficiary_name }}</td>
                                                    <td class="text-right">{{ number_format($supplier_targets_report->total_target, 2) }}</td>
                                                </tr>
                                            @endforeach

                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th class="text-right" colspan="3">TOTAL</th>
                                                    <th class="text-right">{{number_format($total_target)}}</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-sm-2"></div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-vcenter">
                                            <thead>
                                            @php
                                                // Get only EFDs with bonge sales
                                                $efdsWithSales = [];
                                                $totalBongeSales = 0;
                                                foreach($efds as $efd) {
                                                    $bongeSales = \App\Models\Report::getTotalDaysSalesBonge($start_date, $end_date, $efd->bonge_customer_id);
                                                    if($bongeSales > 0) {
                                                        $efdsWithSales[] = [
                                                            'efd' => $efd,
                                                            'bonge_sales' => $bongeSales
                                                        ];
                                                        $totalBongeSales += $bongeSales;
                                                    }
                                                }
                                            @endphp
                                            <tr>
                                                <th>EFD NAME</th>
                                                @foreach($efdsWithSales as $efdData)
                                                    <th class="text-right">{{ $efdData['efd']->name }}</th>
                                                @endforeach
                                                <th class="text-right bg-light">TOTAL</th>
                                            </tr>
                                            <tr>
                                                <th>BONGE SALES</th>
                                                @foreach($efdsWithSales as $efdData)
                                                    <th class="text-right">{{ number_format($efdData['bonge_sales'], 2) }}</th>
                                                @endforeach
                                                <th class="text-right bg-light">{{ number_format($totalBongeSales, 2) }}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @php
                                                $beneficiariesWithAmount = DB::table('beneficiaries as b')
                                                    ->select('b.*')
                                                    ->join('supplier_targets as st', 'st.beneficiary_id', '=', 'b.id')
                                                    ->join('supplier_target_preparations as stp', 'stp.supplier_target_id', '=', 'st.id')
                                                    ->where('st.type', 'TARGET')
                                                    ->whereBetween('stp.date', [$start_date, $end_date])
                                                    ->groupBy('b.id', 'b.name')
                                                    ->having(DB::raw('SUM(stp.amount)'), '>', 0)
                                                    ->get();
                                            @endphp

                                            @foreach($beneficiariesWithAmount as $beneficiary)
                                                <tr>
                                                    <td>{{ $beneficiary->name }}</td>
                                                    @php $rowTotal = 0; @endphp
                                                    @foreach($efdsWithSales as $efdData)
                                                        @php
                                                            $amount = DB::table('supplier_target_preparations as stp')
                                                                ->join('supplier_targets as st', 'st.id', '=', 'stp.supplier_target_id')
                                                                ->where('st.beneficiary_id', $beneficiary->id)
                                                                ->where('stp.efd_id', $efdData['efd']->id)
                                                                ->where('st.type', 'TARGET')
                                                                ->whereBetween('stp.date', [$start_date, $end_date])
                                                                ->sum('stp.amount');
                                                            $rowTotal += $amount;
                                                        @endphp
                                                        <td class="text-right">{{ $amount > 0 ? number_format($amount, 2) : '' }}</td>
                                                    @endforeach
                                                    <td class="text-right bg-light">{{ $rowTotal > 0 ? number_format($rowTotal, 2) : '' }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                            <tfoot>
                                            <tr class="bg-light">
                                                <th>TOTAL</th>
                                                @php $grandTotal = 0; @endphp
                                                @foreach($efdsWithSales as $efdData)
                                                    @php
                                                        $totalAmount = DB::table('supplier_target_preparations as stp')
                                                            ->join('supplier_targets as st', 'st.id', '=', 'stp.supplier_target_id')
                                                            ->where('stp.efd_id', $efdData['efd']->id)
                                                            ->where('st.type', 'TARGET')
                                                            ->whereBetween('stp.date', [$start_date, $end_date])
                                                            ->sum('stp.amount');
                                                        $grandTotal += $totalAmount;
                                                    @endphp
                                                    <th class="text-right">{{ number_format($totalAmount, 2) }}</th>
                                                @endforeach
                                                <th class="text-right">{{ number_format($grandTotal, 2) }}</th>
                                            </tr>
                                            <tr class="bg-light-subtle">
                                                <th>BALANCE</th>
                                                @php $totalBalance = 0; @endphp
                                                @foreach($efdsWithSales as $efdData)
                                                    @php
                                                        $totalAmount = DB::table('supplier_target_preparations as stp')
                                                            ->join('supplier_targets as st', 'st.id', '=', 'stp.supplier_target_id')
                                                            ->where('stp.efd_id', $efdData['efd']->id)
                                                            ->where('st.type', 'TARGET')
                                                            ->whereBetween('stp.date', [$start_date, $end_date])
                                                            ->sum('stp.amount');

                                                        $balance = $efdData['bonge_sales'] - $totalAmount;
                                                        $totalBalance += $balance;
                                                    @endphp
                                                    <th class="text-right {{ $balance > 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ number_format($balance, 2) }}
                                                    </th>
                                                @endforeach
                                                <th class="text-right {{ $totalBalance > 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ number_format($totalBalance, 2) }}
                                                </th>
                                            </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Date</th>
                                    <th>Supplier Target Preparation</th>
                                    <th>Efd</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($supplier_target_preparation_lists as $supplier_target_preparation)

                                    <tr id="collection-tr-{{$supplier_target_preparation->id}}">
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$supplier_target_preparation->date}}</td>
                                        <td>{{$supplier_target_preparation->supplierTarget->supplier->name ?? null}} {{ number_format($supplier_target_preparation->supplierTarget->amount)}}</td>
                                        <td>{{$supplier_target_preparation->efd->name ?? null}}</td>
                                        <td>{{$supplier_target_preparation->description}}</td>
                                        <td class="text-right">{{number_format($supplier_target_preparation->amount)}}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Delete Supplier Target Preparation"))
                                                    <button type="button"
                                                            onclick="deleteModelItem('SupplierTargetPreparation', {{$supplier_target_preparation->id}}, 'collection-tr-{{$supplier_target_preparation->id}}');"
                                                            class="btn btn-sm btn-danger js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Delete"
                                                            data-original-title="Delete">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>

                                @endforeach
                                </tbody>

                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection



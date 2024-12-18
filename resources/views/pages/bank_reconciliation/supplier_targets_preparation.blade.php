<style>
    /* Card Styles */
    .report-section {
        margin-bottom: 2rem;
    }

    .report-card {
        background: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        margin-bottom: 1.5rem;
    }

    .report-card-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #edf2f7;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Filter Section */
    .filter-section {
        background: #f8fafc;
        padding: 1.5rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
    }

    /* Table Improvements */
    .table-section {
        background: #fff;
        padding: 1rem;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }

    .summary-table th {
        background: #f8fafc;
    }

    .table-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 1rem;
    }

    /* Stats and Totals */
    .stats-row {
        background: #edf2f7;
        font-weight: 600;
    }

    /* Action Buttons */
    .action-btn {
        padding: 0.4rem 1rem;
        border-radius: 0.375rem;
        font-size: 0.875rem;
    }
</style>
<!-- Additional styles for tables -->
<style>
    .table thead th {
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
    }

    .table-hover tbody tr:hover {
        background-color: #f7fafc;
    }

    .text-success { color: #0f766e !important; }
    .text-danger { color: #dc2626 !important; }

    .bg-light { background-color: #f8fafc !important; }
    .bg-light-subtle { background-color: #f1f5f9 !important; }
</style>
@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <!-- Header Section -->
            <div class="report-section">
                <div class="report-card-header">
                    <h4 class="mb-0">Supplier Target Preparation</h4>
                    @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Add Supplier Target Preparation"))
                        <button type="button"
                                onclick="loadFormModal('supplier_target_preparation_form', {className: 'SupplierTargetPreparation'}, 'Create New Target Preparation', 'modal-lg');"
                                class="btn btn-primary action-btn">
                            <i class="si si-plus mr-1"></i>New Target Preparation
                        </button>
                    @endif
                </div>
            </div>

            <!-- Filter Section -->
            @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Date Supplier Targets Report"))
                <div class="filter-section">
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

            <!-- Summary Tables Section -->
            <div class="row">
                <!-- Target Summary -->
                <div class="col-12 table-section">
                    <h5 class="table-title">Target Summary</h5>
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
                </div>

                <!-- EFD Analysis -->
                <div class="col-12 table-section">
                    <h5 class="table-title">EFD Analysis</h5>
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
                </div>

                <!-- Detailed List -->
                <div class="col-12 table-section">
                    <h5 class="table-title">Preparation Details</h5>
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
@endsection

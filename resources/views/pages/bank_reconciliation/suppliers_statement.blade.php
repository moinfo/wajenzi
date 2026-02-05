@extends('layouts.backend')

@section('content')

    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Bank Reconciliation
                <div class="float-right">
                        <a href="{{route('bank_reconciliation')}}" type="button" title="Back"  class="btn btn-rounded btn-default min-width-100 mb-10"><i class="si si-arrow-left">&nbsp;</i>Back</a>

                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Supplier Statement</h3>
                    </div>
                    <div class="block-content">
                        @can('Date Supplier Statement')
                            <div class="row no-print m-t-10">
                                <div class="class col-md-12">
                                    <div class="class card-box">
                                        <form  name="collection_search" action="{{route('bank_reconciliation_suppliers_statement')}}" id="filter-form" method="post" autocomplete="off">
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
                                                <th>EFDs/SUPPLIERS</th>
                                                <th>MASHINENI</th>
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
                                                    @php
                                                        $deposit_whitestar = \App\Models\BankReconciliation::getTotalDepositPerDayPerSupplierInWhitestar($start_date,$end_date,$efd_id);
                                                    @endphp
                                                    <td class="text-right">{{number_format($deposit_whitestar)}}</td>
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
                                                @php
                                                    $total_deposit_whitestar = \App\Models\BankReconciliation::getTotalDepositPerDayPerSupplierInWhitestar($start_date,$end_date,null);
                                                @endphp
                                                <td class="text-right">{{number_format($total_deposit_whitestar)}}</td>
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
            </div>
        </div>
    </div>

@endsection



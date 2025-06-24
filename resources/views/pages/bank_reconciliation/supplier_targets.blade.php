@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Supplier Target
                <div class="float-right">
                    <a href="{{route('supplier_commissions')}}" class="btn btn-rounded btn-outline-warning min-width-125 mb-10"><i class="si si-graph">&nbsp;</i>Supplier Commissions</a>
                @can('Add Supplier Target')
                        <button type="button" onclick="loadFormModal('supplier_target_form', {className: 'SupplierTarget'}, 'Create New Target', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Target</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Supplier Target</h3>
                    </div>
                    <div class="block-content">
                        @can('Date Supplier Target')
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form  name="collection_search" action="" id="filter-form" method="post" autocomplete="off">
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
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th>Supplier</th>
                                                <th>Type</th>
                                                <th>Amount</th>
                                                <th>Beneficiary Name</th>
                                                <th>Action</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @php
                                                $sum = 0;
                                            @endphp
                                            @foreach($supplier_targets as $supplier_target)
                                                <?php
                                                $sum += $supplier_target->amount;
                                                ?>
                                                <tr id="supplier_target-tr-{{$supplier_target->id}}">
                                                    <td class="text-center">
                                                        {{$loop->iteration}}
                                                    </td>
                                                    <td class="font-w600">{{ $supplier_target->date }}</td>
                                                    <td class="font-w600">{{ $supplier_target->description }}</td>
                                                    <td class="font-w600">{{ $supplier_target->supplier->name }}</td>
                                                    <td class="font-w600">{{ $supplier_target->type }}</td>
                                                    <td class="text-right">{{ number_format($supplier_target->amount, 2) }}</td>
                                                    <td class="font-w600">{{ $supplier_target->beneficiary->name }}</td>
                                                    <td class="text-center">
                                                        <div class="btn-group">
                                                            @can('Edit Supplier Target')
                                                                <button type="button"
                                                                        onclick="loadFormModal('supplier_target_form', {className: 'SupplierTarget', id: {{$supplier_target->id}}}, 'Edit SupplierTargets', 'modal-md');"
                                                                        class="btn btn-sm btn-primary js-tooltip-enabled"
                                                                        data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                                    <i class="fa fa-pencil"></i>
                                                                </button>
                                                            @endcan

                                                            @can('Delete Supplier Target')
                                                                <button type="button"
                                                                        onclick="deleteModelItem('SupplierTarget', {{$supplier_target->id}}, 'supplier_target-tr-{{$supplier_target->id}}');"
                                                                        class="btn btn-sm btn-danger js-tooltip-enabled"
                                                                        data-toggle="tooltip" title="Delete"
                                                                        data-original-title="Delete">
                                                                    <i class="fa fa-times"></i>
                                                                </button>
                                                            @endcan

                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach

                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th class="text-right" colspan="5">TOTAL</th>
                                                    <th class="text-right">{{number_format($sum)}}</th>
                                                    <th></th>
                                                    <th></th>
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



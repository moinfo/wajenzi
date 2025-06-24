@extends('layouts.backend')

@section('content')


    <div class="main-container">
        <div class="content">
            <div class="content-heading">Supplier Receiving
                <div class="float-right">
                    @can('Add Supplier Receiving')
                        <button type="button" onclick="loadFormModal('supplier_receiving_form', {className: 'SupplierReceiving'}, 'Create New Supplier Receiving', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Supplier Receiving</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-gd-lake">
                        <h3 class="block-title">All Supplier Receivings</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form  name="supplier_receiving_search" action="{{route('supplier_receiving_search')}}" id="filter-form" method="post" autocomplete="off">
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
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon3">Supplier</span>
                                                    </div>
                                                    <select name="supervisor_id" id="input-supervisor-id" class="form-control" aria-describedby="basic-addon3">
                                                        <option value="">All Supplier</option>
                                                        @foreach ($suppliers as $supplier)
                                                            <option value="{{ $supplier->id }}"> {{ $supplier->name }} </option>
                                                        @endforeach
                                                    </select>
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
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Date</th>
                                    <th>Supplier Name</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Payment Type</th>
                                    <th>Attachment</th>
                                    <th scope="col">Status</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $sum = 0;
                                ?>
                                @foreach($supplier_receivings as $supplier_receiving)
                                    <?php
                                    $payment_type = $supplier_receiving->payment_type_id == '1' ? 'System' : 'Office';
                                    $sum += $supplier_receiving->amount;
                                    ?>

                                    <tr id="supplier_receiving-tr-{{$supplier_receiving->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td>{{ $supplier_receiving->date }}</td>
                                        <td>{{ $supplier_receiving->supplier->name ?? $supplier_receiving->supplier_name}}</td>
                                        <td class="font-w600">{{ $supplier_receiving->description }}</td>
                                        <td class="text-right">{{ number_format($supplier_receiving->amount, 2) }}</td>
                                        <td>{{$payment_type}}</td>
                                        <td class="text-center">
                                            @if($supplier_receiving->file != null)
                                                <a href="{{ url("$supplier_receiving->file") }}">Attachment</a>
                                            @else
                                                No File
                                            @endif
                                        </td>
                                        <td>
                                            @if($supplier_receiving->status == 'PENDING')
                                                <div class="badge badge-warning">{{ $supplier_receiving->status}}</div>
                                            @elseif($supplier_receiving->status == 'APPROVED')
                                                <div class="badge badge-primary">{{ $supplier_receiving->status}}</div>
                                            @elseif($supplier_receiving->status == 'REJECTED')
                                                <div class="badge badge-danger">{{ $supplier_receiving->status}}</div>
                                            @elseif($supplier_receiving->status == 'PAID')
                                                <div class="badge badge-primary">{{ $supplier_receiving->status}}</div>
                                            @elseif($supplier_receiving->status == 'COMPLETED')
                                                <div class="badge badge-success">{{ $supplier_receiving->status}}</div>
                                            @else
                                                <div class="badge badge-secondary">{{ $supplier_receiving->status}}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success js-tooltip-enabled" href="{{route('supplier_receivings',['id' => $supplier_receiving->id,'document_type_id'=>10])}}"><i class="fa fa-eye"></i></a>
                                            @can('Edit Supplier Receiving')
                                                    <button type="button"
                                                            onclick="loadFormModal('supplier_receiving_form', {className: 'SupplierReceiving', id: {{$supplier_receiving->id}}}, 'Edit {{$supplier_receiving->supplier->name ?? $supplier_receiving->supplier_name}}', 'modal-md');"
                                                            class="btn btn-sm btn-primary js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan
                                                    @can('Delete Supplier Receiving')
                                                        <button type="button"
                                                                onclick="deleteModelItem('SupplierReceiving', {{$supplier_receiving->id}}, 'supplier_receiving-tr-{{$supplier_receiving->id}}');"
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
                                    <td class="text-right text-dark" colspan="5"><b>{{number_format($sum,2)}}</b></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
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



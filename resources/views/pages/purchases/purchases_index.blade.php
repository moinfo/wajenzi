@extends('layouts.backend')


@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Purchases
                <div class="float-right">
                    @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Add Purchases"))
                        <button type="button" onclick="loadFormModal('purchase_form', {className: 'Purchase'}, 'Create New Purchase', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Purchase</button>
                    @endif
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Purchases</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form  name="supplier_receiving_search" action="" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Start Date</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon2">End Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
{{--                                            <div class="class col-md-4">--}}
{{--                                                <div class="input-group mb-3">--}}
{{--                                                    <div class="input-group-prepend">--}}
{{--                                                        <span class="input-group-text" id="basic-addon3">Supplier</span>--}}
{{--                                                    </div>--}}
{{--                                                    <select name="supervisor_id" id="input-supervisor-id" class="form-control" aria-describedby="basic-addon3">--}}
{{--                                                        <option value="">All Supplier</option>--}}
{{--                                                        @foreach ($suppliers as $supplier)--}}
{{--                                                            <option value="{{ $supplier->id }}"> {{ $supplier->name }} </option>--}}
{{--                                                        @endforeach--}}
{{--                                                    </select>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
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
                                    <th>Supplier VRN</th>
                                    <th>Tax Invoice</th>
                                    <th>Invoice Date</th>
                                    <th>Goods</th>
                                    <th>Total Amount</th>
                                    <th>Amount VAT EXC</th>
                                    <th>VAT Amount</th>
                                    <th>Is Expenses</th>
                                    <th>Attachment</th>
                                    <th scope="col">Status</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($purchases as $purchase)
                                    <tr id="purchase-tr-{{$purchase->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td class="font-w600">{{ $purchase->date }}</td>
                                        <td class="font-w600">{{ $purchase->supplier->name }}</td>
                                        <td class="font-w600">{{ $purchase->supplier->vrn }}</td>
                                        <td class="font-w600">{{ $purchase->tax_invoice }}</td>
                                        <td class="font-w600">{{ $purchase->invoice_date }}</td>
                                        <td class="font-w600">{{ $purchase->item->name }}</td>
                                        <td class="font-w600">{{ number_format($purchase->total_amount, 2) }}</td>
                                        <td class="font-w600">{{ number_format($purchase->amount_vat_exc,2) }}</td>
                                        <td class="font-w600">{{ number_format($purchase->vat_amount, 2) }}</td>
                                        <td class="font-w600">{{ $purchase->is_expense }}</td>
                                        <td class="text-center">
                                            @if($purchase->file != null)
                                                <a href="{{ url("$purchase->file") }}">Attachment</a>
                                            @else
                                                No File
                                            @endif
                                        </td>
                                        <td>
                                            @if($purchase->status == 'PENDING')
                                                <div class="badge badge-warning">{{ $purchase->status}}</div>
                                            @elseif($purchase->status == 'APPROVED')
                                                <div class="badge badge-primary">{{ $purchase->status}}</div>
                                            @elseif($purchase->status == 'REJECTED')
                                                <div class="badge badge-danger">{{ $purchase->status}}</div>
                                            @elseif($purchase->status == 'PAID')
                                                <div class="badge badge-primary">{{ $purchase->status}}</div>
                                            @elseif($purchase->status == 'COMPLETED')
                                                <div class="badge badge-success">{{ $purchase->status}}</div>
                                            @else
                                                <div class="badge badge-secondary">{{ $purchase->status}}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success js-tooltip-enabled" href="{{route('purchase',['id' => $purchase->id,'document_type_id'=>3])}}"><i class="fa fa-eye"></i></a>
                                            @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Edit Purchases"))
                                                    <button type="button"
                                                            onclick="loadFormModal('purchase_form', {className: 'Purchase', id: {{$purchase->id}}}, 'Edit {{$purchase->name}}', 'modal-md');"
                                                            class="btn btn-sm btn-primary js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endif

                                                    @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Delete Purchases"))
                                                        <button type="button"
                                                                onclick="deleteModelItem('Purchase', {{$purchase->id}}, 'purchase-tr-{{$purchase->id}}');"
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


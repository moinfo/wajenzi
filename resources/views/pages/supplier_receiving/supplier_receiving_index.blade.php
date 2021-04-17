@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Supplier Receiving
                <div class="float-right">
                    <button type="button" onclick="loadFormModal('supplier_receiving_form', {className: 'SupplierReceiving'}, 'Create New Supplier Receiving', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Supplier Receiving</button>
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Supplier Receivings</h3>
                    </div>
                    <div class="block-content">
                        <p>This is a list containing all Supplier Receivings</p>
                        <div class="table-responsive">
                            <table class="table table-striped table-vcenter">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Date</th>
                                    <th>Supplier Name</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($supplier_receivings as $supplier_receiving)
                                    <tr id="supplier_receiving-tr-{{$supplier_receiving->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td>{{ $supplier_receiving->date }}</td>
                                        <td>{{ $supplier_receiving->supplier->name }}</td>
                                        <td class="font-w600">{{ $supplier_receiving->description }}</td>
                                        <td class="text-right">{{ number_format($supplier_receiving->amount, 2) }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button"
                                                        onclick="loadFormModal('supplier_receiving_form', {className: 'SupplierReceiving', id: {{$supplier_receiving->id}}}, 'Edit {{$supplier_receiving->name}}', 'modal-md');"
                                                        class="btn btn-sm btn-primary js-tooltip-enabled"
                                                        data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                                <button type="button"
                                                        onclick="deleteModelItem('SupplierReceiving', {{$supplier_receiving->id}}, 'supplier_receiving-tr-{{$supplier_receiving->id}}');"
                                                        class="btn btn-sm btn-danger js-tooltip-enabled"
                                                        data-toggle="tooltip" title="Delete"
                                                        data-original-title="Delete">
                                                    <i class="fa fa-times"></i>
                                                </button>
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



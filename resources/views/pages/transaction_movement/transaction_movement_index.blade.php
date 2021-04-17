@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Transaction Movement
                <div class="float-right">
                    <button type="button" onclick="loadFormModal('transaction_movement_form', {className: 'TransactionMovement'}, 'Create New TransactionMovement', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New TransactionMovement</button>
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Transaction Movements</h3>
                    </div>
                    <div class="block-content">
                        <p>This is a list containing all Transaction Movements</p>
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
                                @foreach($transaction_movements as $transaction_movement)
                                    <tr id="transaction_movement-tr-{{$transaction_movement->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td>{{ $transaction_movement->date }}</td>
                                        <td>{{ $transaction_movement->supplier->name }}</td>
                                        <td class="font-w600">{{ $transaction_movement->description }}</td>
                                        <td class="text-right">{{ number_format($transaction_movement->amount, 2) }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button"
                                                        onclick="loadFormModal('transaction_movement_form', {className: 'TransactionMovement', id: {{$transaction_movement->id}}}, 'Edit {{$transaction_movement->name}}', 'modal-md');"
                                                        class="btn btn-sm btn-primary js-tooltip-enabled"
                                                        data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                                <button type="button"
                                                        onclick="deleteModelItem('TransactionMovement', {{$transaction_movement->id}}, 'transaction_movement-tr-{{$transaction_movement->id}}');"
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



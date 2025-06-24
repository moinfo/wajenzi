@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Transaction Movement
                <div class="float-right">
                    @can('Add Transaction Movement')
                        <button type="button" onclick="loadFormModal('transaction_movement_form', {className: 'TransactionMovement'}, 'Create New TransactionMovement', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Transaction Movement</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-gd-lake">
                        <h3 class="block-title">All Transaction Movements</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form  name="collection_search" action="{{route('transaction_movement_search')}}" id="filter-form" method="post" autocomplete="off">
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
                                                    <select name="supplier_id" id="input-supplier-id" class="form-control" aria-describedby="basic-addon3">
                                                        <option value="0">All Supplier</option>
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
                                @foreach($transaction_movements as $transaction_movement)
                                    <?php
                                    $payment_type = $transaction_movement->payment_type_id == '1' ? 'System' : 'Office';
                                    $sum += $transaction_movement->amount;
                                    ?>

                                    <tr id="transaction_movement-tr-{{$transaction_movement->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td>{{ $transaction_movement->date }}</td>
                                        <td>{{ $transaction_movement->supplier->name ?? $transaction_movement->supplier_name }}</td>
                                        <td class="font-w600">{{ $transaction_movement->description }}</td>
                                        <td class="text-right">{{ number_format($transaction_movement->amount, 2) }}</td>
                                        <td>{{$payment_type}}</td>
                                        <td class="text-center">
                                            @if($transaction_movement->file != null)
                                                <a href="{{ url("$transaction_movement->file") }}">Attachment</a>
                                            @else
                                                No File
                                            @endif
                                        </td>
                                        <td>
                                            @if($transaction_movement->status == 'PENDING')
                                                <div class="badge badge-warning">{{ $transaction_movement->status}}</div>
                                            @elseif($transaction_movement->status == 'APPROVED')
                                                <div class="badge badge-primary">{{ $transaction_movement->status}}</div>
                                            @elseif($transaction_movement->status == 'REJECTED')
                                                <div class="badge badge-danger">{{ $transaction_movement->status}}</div>
                                            @elseif($transaction_movement->status == 'PAID')
                                                <div class="badge badge-primary">{{ $transaction_movement->status}}</div>
                                            @elseif($transaction_movement->status == 'COMPLETED')
                                                <div class="badge badge-success">{{ $transaction_movement->status}}</div>
                                            @else
                                                <div class="badge badge-secondary">{{ $transaction_movement->status}}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success js-tooltip-enabled" href="{{route('transaction_movements',['id' => $transaction_movement->id,'document_type_id'=>9])}}"><i class="fa fa-eye"></i></a>
                                            @can('Edit Transaction Movement')
                                                    <button type="button"
                                                            onclick="loadFormModal('transaction_movement_form', {className: 'TransactionMovement', id: {{$transaction_movement->id}}}, 'Edit {{$transaction_movement->supplier->name ?? $transaction_movement->supplier_name}} Transcaction Movement', 'modal-md');"
                                                            class="btn btn-sm btn-primary js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan

                                                    @can('Delete Transaction Movement')
                                                        <button type="button"
                                                                onclick="deleteModelItem('TransactionMovement', {{$transaction_movement->id}}, 'transaction_movement-tr-{{$transaction_movement->id}}');"
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



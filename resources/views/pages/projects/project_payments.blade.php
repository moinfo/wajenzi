{{-- project_payments.blade.php --}}
@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Project Payments
                <div class="float-right">
                    @can('Add Payment')
                        <button type="button" onclick="loadFormModal('project_payment_form', {className: 'ProjectPayment'}, 'Create New Payment', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Payment</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Payments</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form name="payment_search" action="" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Start Date</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">End Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Status</span>
                                                    </div>
                                                    <select name="status" id="input-status" class="form-control">
                                                        <option value="">All Status</option>
                                                        <option value="pending">Pending</option>
                                                        <option value="completed">Completed</option>
                                                        <option value="failed">Failed</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-2">
                                                <div>
                                                    <button type="submit" name="submit" class="btn btn-sm btn-primary">Show</button>
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
                                    <th>Invoice</th>
                                    <th class="text-right">Amount</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($payments as $payment)
                                    <tr id="payment-tr-{{$payment->id}}">
                                        <td class="text-center">{{$loop->index + 1}}</td>
                                        <td>{{ $payment->invoice->invoice_number }}</td>
                                        <td class="text-right">{{ number_format($payment->amount, 2) }}</td>
                                        <td>{{ ucfirst($payment->payment_method) }}</td>
                                        <td>{{ $payment->reference_number }}</td>
                                        <td>
                                            @if($payment->status == 'pending')
                                                <div class="badge badge-warning">{{ $payment->status}}</div>
                                            @elseif($payment->status == 'completed')
                                                <div class="badge badge-success">{{ $payment->status}}</div>
                                            @elseif($payment->status == 'failed')
                                                <div class="badge badge-danger">{{ $payment->status}}</div>
                                            @else
                                                <div class="badge badge-secondary">{{ $payment->status}}</div>
                                            @endif
                                        </td>
                                        <td>{{ $payment->created_at }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                @can('Edit Payment')
                                                    <button type="button"
                                                            onclick="loadFormModal('project_payment_form', {className: 'ProjectPayment', id: {{$payment->id}}}, 'Edit Payment', 'modal-md');"
                                                            class="btn btn-sm btn-primary">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan
                                                @can('Delete Payment')
                                                    <button type="button"
                                                            onclick="deleteModelItem('ProjectPayment', {{$payment->id}}, 'payment-tr-{{$payment->id}}');"
                                                            class="btn btn-sm btn-danger">
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
                                    <td colspan="2" class="text-right"><strong>Total:</strong></td>
                                    <td class="text-right"><strong>{{ number_format($payments->sum('amount'), 2) }}</strong></td>
                                    <td colspan="5"></td>
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

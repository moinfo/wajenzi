@extends('layouts.backend')

@section('css')
<style>
    .summary-stats {
        display: inline-flex !important;
        border: 1px solid #e9ecef;
        border-radius: 5px;
        overflow: hidden;
    }
    .summary-stats .badge {
        border-radius: 0;
        margin: 0;
        font-size: 0.8rem;
        padding: 0.3rem 0.5rem;
    }
    .summary-stats .badge:hover {
        filter: brightness(0.9);
    }
    .approval-badge {
        padding: 5px 10px;
        border-radius: 15px;
        margin-right: 5px;
        font-size: 0.85em;
        white-space: nowrap;
        display: inline-block;
    }
    .approval-badge i {
        margin-right: 5px;
    }
    .approval-badge.approved {
        background-color: #28a745;
        color: white;
    }
    .approval-badge.pending {
        background-color: #ffc107;
        color: #212529;
    }
    .approval-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }
</style>
@endsection

@section('content')

    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Purchases
                <div class="float-right">
                    @can('Add Purchases')
                        <a href="{{route('expense_adjustable')}}" class="btn btn-rounded btn-outline-danger min-width-100 mb-10"><i class="si si-graph">&nbsp;</i>Expense Adjustable</a>
                        <button type="button" onclick="loadFormModal('purchase_form', {className: 'Purchase'}, 'Create New Purchase', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Purchase</button>
                    @endcan
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
                                    <th scope="col">Approvals</th>
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
                                        <td class="text-center">
                                            <!-- Approval status summary component -->
                                            <x-ringlesoft-approval-status-summary :model="$purchase" />
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $approvalStatus = $purchase->approvalStatus?->status ?? $purchase->status;
                                                $statusClass = [
                                                    'PENDING' => 'warning',
                                                    'SUBMITTED' => 'info',
                                                    'APPROVED' => 'success',
                                                    'REJECTED' => 'danger',
                                                    'PAID' => 'primary',
                                                    'COMPLETED' => 'success',
                                                    'DISCARDED' => 'danger',
                                                ][$approvalStatus] ?? 'secondary';

                                                $statusIcon = [
                                                    'PENDING' => '<i class="fas fa-clock me-1"></i>',
                                                    'SUBMITTED' => '<i class="fas fa-paper-plane me-1"></i>',
                                                    'APPROVED' => '<i class="fas fa-check me-1"></i>',
                                                    'REJECTED' => '<i class="fas fa-times me-1"></i>',
                                                    'PAID' => '<i class="fas fa-money-bill me-1"></i>',
                                                    'COMPLETED' => '<i class="fas fa-check-circle me-1"></i>',
                                                    'DISCARDED' => '<i class="fas fa-trash me-1"></i>',
                                                ][$approvalStatus] ?? '<i class="fas fa-question-circle me-1"></i>';
                                            @endphp
                                            <span class="badge badge-{{ $statusClass }} badge-pill" style="font-size: 0.9em; padding: 6px 10px;">
                                                {!! $statusIcon !!} {{ $approvalStatus }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success js-tooltip-enabled" href="{{route('purchase',['id' => $purchase->id,'document_type_id'=>3])}}"><i class="fas fa-eye"></i></a>
                                            @can('Edit Purchases')
                                                    <button type="button"
                                                            onclick="loadFormModal('purchase_form', {className: 'Purchase', id: {{$purchase->id}}}, 'Edit Purchase', 'modal-md');"
                                                            class="btn btn-sm btn-primary js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </button>
                                                @endcan

                                                    @can('Delete Purchases')
                                                        <button type="button"
                                                                onclick="deleteModelItem('Purchase', {{$purchase->id}}, 'purchase-tr-{{$purchase->id}}');"
                                                                class="btn btn-sm btn-danger js-tooltip-enabled"
                                                                data-toggle="tooltip" title="Delete"
                                                                data-original-title="Delete">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    @endcan

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


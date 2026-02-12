@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">Purchase Orders
            <div class="float-right">
                <a href="{{ route('procurement_dashboard') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="si si-graph"></i> Dashboard
                </a>
            </div>
        </div>

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">All Purchase Orders</h3>
            </div>
            <div class="block-content">
                <form method="post" id="filter-form" autocomplete="off">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text">Start Date</span>
                                <input type="text" name="start_date" class="form-control datepicker"
                                    value="{{ $start_date ?? date('Y-m-01') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text">End Date</span>
                                <input type="text" name="end_date" class="form-control datepicker"
                                    value="{{ $end_date ?? date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>PO Number</th>
                                <th>Project</th>
                                <th>Supplier</th>
                                <th>Material Request</th>
                                <th class="text-center">Items</th>
                                <th class="text-right">Subtotal</th>
                                <th class="text-right">VAT</th>
                                <th class="text-right">Total</th>
                                <th class="text-center">Approvals</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchaseOrders as $po)
                                <tr id="purchase-tr-{{ $po->id }}">
                                    <td class="text-center">{{ $loop->index + 1 }}</td>
                                    <td>
                                        <a href="{{ route('purchase_order', ['id' => $po->id, 'document_type_id' => 0]) }}">
                                            <strong>{{ $po->document_number ?? 'PO-' . $po->id }}</strong>
                                        </a>
                                    </td>
                                    <td>{{ $po->project?->name ?? 'N/A' }}</td>
                                    <td>{{ $po->supplier?->name ?? 'N/A' }}</td>
                                    <td>
                                        @if($po->materialRequest)
                                            <a href="{{ route('supplier_quotations.by_request', $po->material_request_id) }}">
                                                {{ $po->materialRequest->request_number }}
                                            </a>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info">{{ $po->purchaseItems->count() }}</span>
                                    </td>
                                    <td class="text-right">{{ number_format($po->amount_vat_exc, 2) }}</td>
                                    <td class="text-right">{{ number_format($po->vat_amount, 2) }}</td>
                                    <td class="text-right">
                                        <strong>{{ number_format($po->total_amount, 2) }}</strong>
                                    </td>
                                    <td class="text-center">
                                        <x-ringlesoft-approval-status-summary :model="$po" />
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $status = strtoupper($po->approvalStatus?->status ?? $po->status ?? 'pending');
                                            $statusClass = match($status) {
                                                'APPROVED' => 'success',
                                                'PENDING', 'SUBMITTED' => 'warning',
                                                'REJECTED' => 'danger',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge badge-{{ $statusClass }}">{{ $status }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="{{ route('purchase_order', ['id' => $po->id, 'document_type_id' => 0]) }}"
                                                class="btn btn-sm btn-success" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            @if(strtoupper($status) === 'APPROVED' && $po->material_request_id && $po->purchaseItems->contains(fn($i) => !$i->isFullyReceived()))
                                                <a href="{{ route('purchase_order.record_delivery', $po->id) }}"
                                                    class="btn btn-sm btn-info" title="Record Delivery">
                                                    <i class="fa fa-truck"></i>
                                                </a>
                                            @endif
                                            @if(strtoupper($po->status) !== 'APPROVED')
                                                <button type="button"
                                                    onclick="deleteModelItem('Purchase', {{ $po->id }}, 'purchase-tr-{{ $po->id }}');"
                                                    class="btn btn-sm btn-danger" title="Delete">
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

@section('js')
<script>
    $(document).ready(function() {
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });
    });
</script>
@endsection

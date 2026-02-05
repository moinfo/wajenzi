@extends('layouts.backend')

@section('css')
<style>
    .status-badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.85em;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">Supplier Quotations
            <div class="float-right">
                <a href="{{ route('procurement_dashboard') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="si si-graph"></i> Dashboard
                </a>
                <button type="button" onclick="loadFormModal('supplier_quotation_form', {className: 'SupplierQuotation'}, 'Add Quotation', 'modal-lg');"
                    class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                    <i class="si si-plus"></i> New Quotation
                </button>
            </div>
        </div>

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">All Quotations</h3>
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
                                <th>Quotation #</th>
                                <th>Material Request</th>
                                <th>Supplier</th>
                                <th>Date</th>
                                <th class="text-right">Quantity</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Total</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($quotations as $quotation)
                                <tr id="quotation-tr-{{ $quotation->id }}">
                                    <td class="text-center">{{ $loop->index + 1 }}</td>
                                    <td>
                                        <strong>{{ $quotation->quotation_number }}</strong>
                                    </td>
                                    <td>
                                        <a href="{{ route('supplier_quotations.by_request', $quotation->material_request_id) }}">
                                            {{ $quotation->materialRequest?->request_number ?? 'N/A' }}
                                        </a>
                                        <br>
                                        <small class="text-muted">{{ $quotation->materialRequest?->project?->name ?? '' }}</small>
                                    </td>
                                    <td>{{ $quotation->supplier?->name ?? 'N/A' }}</td>
                                    <td>{{ $quotation->quotation_date?->format('Y-m-d') }}</td>
                                    <td class="text-right">{{ number_format($quotation->quantity, 2) }}</td>
                                    <td class="text-right">{{ number_format($quotation->unit_price, 2) }}</td>
                                    <td class="text-right">
                                        <strong>{{ number_format($quotation->grand_total, 2) }}</strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $quotation->status_badge_class }}">
                                            {{ ucfirst($quotation->status) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            @if($quotation->file)
                                                <a href="{{ $quotation->file }}" target="_blank" class="btn btn-sm btn-info" title="View File">
                                                    <i class="fa fa-file"></i>
                                                </a>
                                            @endif
                                            <button type="button"
                                                onclick="loadFormModal('supplier_quotation_form', {className: 'SupplierQuotation', id: {{ $quotation->id }}}, 'Edit Quotation', 'modal-lg');"
                                                class="btn btn-sm btn-primary">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button type="button"
                                                onclick="deleteModelItem('SupplierQuotation', {{ $quotation->id }}, 'quotation-tr-{{ $quotation->id }}');"
                                                class="btn btn-sm btn-danger">
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

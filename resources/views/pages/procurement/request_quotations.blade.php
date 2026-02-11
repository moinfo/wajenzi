@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            Quotations for {{ $materialRequest->request_number }}
            <div class="float-right">
                <a href="{{ route('supplier_quotations') }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
                @if($canCreateComparison)
                    <a href="{{ route('quotation_comparison.create', $materialRequest->id) }}"
                        class="btn btn-rounded btn-success min-width-125 mb-10">
                        <i class="fa fa-balance-scale"></i> Create Comparison
                    </a>
                @endif
                <button type="button" onclick="loadFormModal('supplier_quotation_form', {className: 'SupplierQuotation', metadata: {material_request_id: {{ $materialRequest->id }}}}, 'Add Quotation', 'modal-lg');"
                    class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                    <i class="si si-plus"></i> Add Quotation
                </button>
            </div>
        </div>

        <!-- Request Details Card -->
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Material Request Details</h3>
            </div>
            <div class="block-content">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Request Number:</strong><br>
                        {{ $materialRequest->request_number }}
                    </div>
                    <div class="col-md-3">
                        <strong>Project:</strong><br>
                        {{ $materialRequest->project?->name ?? 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Items:</strong><br>
                        {{ $materialRequest->items->count() }} item(s)
                    </div>
                    <div class="col-md-3">
                        <strong>Required Date:</strong><br>
                        {{ $materialRequest->required_date?->format('Y-m-d') ?? 'N/A' }}
                    </div>
                </div>
                @if($materialRequest->items->count() > 0)
                <div class="row mt-3">
                    <div class="col-md-12">
                        <strong>BOQ Items:</strong><br>
                        @foreach($materialRequest->items as $mrItem)
                            {{ $mrItem->boqItem->item_code ?? '' }} - {{ $mrItem->boqItem->description ?? $mrItem->description ?? '' }}
                            ({{ number_format($mrItem->quantity_requested, 2) }} {{ $mrItem->unit }})@if(!$loop->last), @endif
                        @endforeach
                    </div>
                </div>
                @endif
                @if($materialRequest->purpose)
                <div class="row mt-3">
                    <div class="col-md-12">
                        <strong>Purpose:</strong><br>
                        {{ $materialRequest->purpose }}
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Quotation Progress -->
        <div class="block">
            <div class="block-header block-header-default {{ $quotationCount >= $minimumRequired ? 'bg-success' : 'bg-warning' }}">
                <h3 class="block-title text-white">
                    Quotation Progress: {{ $quotationCount }} / {{ $minimumRequired }} minimum required
                </h3>
            </div>
            @if($quotationCount < $minimumRequired)
            <div class="block-content bg-light">
                <div class="alert alert-warning mb-0">
                    <i class="fa fa-exclamation-triangle"></i>
                    You need at least <strong>{{ $minimumRequired - $quotationCount }}</strong> more quotation(s) before creating a comparison.
                </div>
            </div>
            @endif
        </div>

        <!-- Quotations List -->
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Quotations ({{ $quotationCount }})</h3>
            </div>
            <div class="block-content">
                @if($quotations->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>Quotation #</th>
                                <th>Supplier</th>
                                <th>Date</th>
                                <th>Valid Until</th>
                                <th class="text-right">Quantity</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Grand Total</th>
                                <th>Delivery</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($quotations as $index => $quotation)
                                <tr id="quotation-tr-{{ $quotation->id }}" class="{{ $index === 0 ? 'table-success' : '' }}">
                                    <td class="text-center">
                                        {{ $index + 1 }}
                                        @if($index === 0)
                                            <br><span class="badge badge-success">Lowest</span>
                                        @endif
                                    </td>
                                    <td><strong>{{ $quotation->quotation_number }}</strong></td>
                                    <td>{{ $quotation->supplier?->name ?? 'N/A' }}</td>
                                    <td>{{ $quotation->quotation_date?->format('Y-m-d') }}</td>
                                    <td>
                                        {{ $quotation->valid_until?->format('Y-m-d') ?? 'N/A' }}
                                        @if($quotation->isExpired())
                                            <br><span class="badge badge-danger">Expired</span>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ number_format($quotation->quantity, 2) }}</td>
                                    <td class="text-right">{{ number_format($quotation->unit_price, 2) }}</td>
                                    <td class="text-right">{{ number_format($quotation->total_amount, 2) }}</td>
                                    <td class="text-right"><strong>{{ number_format($quotation->grand_total, 2) }}</strong></td>
                                    <td>{{ $quotation->delivery_time_days ?? 'N/A' }} days</td>
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
                        @if($quotations->count() > 1)
                        <tfoot>
                            <tr class="bg-light">
                                <td colspan="8" class="text-right"><strong>Price Range:</strong></td>
                                <td class="text-right">
                                    <strong>{{ number_format($quotations->min('grand_total'), 2) }}</strong>
                                    -
                                    <strong>{{ number_format($quotations->max('grand_total'), 2) }}</strong>
                                </td>
                                <td colspan="3">
                                    Variance: <strong>{{ number_format($quotations->max('grand_total') - $quotations->min('grand_total'), 2) }}</strong>
                                </td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
                @else
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> No quotations have been added yet.
                    Click "Add Quotation" to start collecting supplier quotes.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

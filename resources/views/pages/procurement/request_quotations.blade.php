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
                @php $hasSelected = $quotations->contains('status', 'selected'); @endphp
                @if($hasSelected && ($approvedComparison ?? null))
                    @if(!$approvedComparison->purchases()->exists())
                        <a href="{{ route('quotation_comparison.create_purchase', $approvedComparison->id) }}"
                            class="btn btn-rounded btn-primary min-width-125 mb-10"
                            onclick="return confirm('Create purchase order from the approved comparison?')">
                            <i class="fa fa-shopping-cart"></i> Create Purchase Order
                        </a>
                    @else
                        <span class="badge badge-success" style="font-size: 13px; padding: 8px 15px;">
                            <i class="fa fa-check"></i> Purchase Order Created
                        </span>
                    @endif
                @else
                    @if($canCreateComparison)
                        <a href="{{ route('quotation_comparison.create', $materialRequest->id) }}"
                            class="btn btn-rounded btn-success min-width-125 mb-10">
                            <i class="fa fa-balance-scale"></i> Create Comparison
                        </a>
                    @endif
                    <button type="button" onclick="openQuotationForm()"
                        class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                        <i class="si si-plus"></i> Add Quotation
                    </button>
                @endif
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
                                <th class="text-right">Subtotal</th>
                                <th class="text-right">VAT</th>
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
                                    <td class="text-right">{{ number_format($quotation->total_amount, 2) }}</td>
                                    <td class="text-right">{{ number_format($quotation->vat_amount, 2) }}</td>
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
                                            @if($quotation->status === 'received')
                                                <button type="button"
                                                    onclick="openQuotationForm({{ $quotation->id }})"
                                                    class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                                <button type="button"
                                                    onclick="deleteModelItem('SupplierQuotation', {{ $quotation->id }}, 'quotation-tr-{{ $quotation->id }}');"
                                                    class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        @if($quotations->count() > 1)
                        <tfoot>
                            <tr class="bg-light">
                                <td colspan="7" class="text-right"><strong>Price Range:</strong></td>
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

<!-- Quotation Form Modal -->
<div class="modal fade" id="quotation-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="quotation-modal-title">Add Quotation</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="quotation-form" method="POST" enctype="multipart/form-data" action="{{ route('supplier_quotations.store') }}">
                @csrf
                <input type="hidden" name="material_request_id" value="{{ $materialRequest->id }}">
                <input type="hidden" id="form-quotation-id" name="quotation_id" value="">
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <!-- Supplier & Dates -->
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="control-label required">Supplier</label>
                                <select name="supplier_id" id="input-supplier" class="form-control" required>
                                    <option value="">Select Supplier</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label class="control-label required">Quotation Date</label>
                                <input type="text" class="form-control datepicker" id="input-quotation-date"
                                    name="quotation_date" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label class="control-label">Valid Until</label>
                                <input type="text" class="form-control datepicker" id="input-valid-until"
                                    name="valid_until" value="{{ date('Y-m-d', strtotime('+30 days')) }}">
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label class="control-label">Delivery (Days)</label>
                                <input type="number" class="form-control" id="input-delivery-time"
                                    name="delivery_time_days" value="7" min="1">
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="table-responsive mt-2">
                        <table class="table table-sm table-bordered mb-0" style="font-size: 13px;">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th style="padding: 8px; width: 35px;">#</th>
                                    <th style="padding: 8px;">Item Code</th>
                                    <th style="padding: 8px;">Description</th>
                                    <th class="text-right" style="padding: 8px; width: 80px;">Qty</th>
                                    <th style="padding: 8px; width: 60px;">Unit</th>
                                    <th class="text-right" style="padding: 8px; width: 130px;">Unit Price</th>
                                    <th class="text-right" style="padding: 8px; width: 120px;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($materialRequest->items as $index => $mrItem)
                                    <tr>
                                        <td style="padding: 6px 8px;" class="text-center text-muted">{{ $index + 1 }}</td>
                                        <td style="padding: 6px 8px;" class="font-w600">{{ $mrItem->boqItem->item_code ?? '-' }}</td>
                                        <td style="padding: 6px 8px;">{{ $mrItem->boqItem->description ?? $mrItem->description ?? '-' }}</td>
                                        <td style="padding: 6px 8px;" class="text-right">
                                            {{ number_format($mrItem->quantity_approved ?? $mrItem->quantity_requested, 2) }}
                                            <input type="hidden" name="items[{{ $index }}][material_request_item_id]" value="{{ $mrItem->id }}">
                                            <input type="hidden" name="items[{{ $index }}][boq_item_id]" value="{{ $mrItem->boq_item_id }}">
                                            <input type="hidden" name="items[{{ $index }}][description]" value="{{ $mrItem->boqItem->description ?? $mrItem->description ?? '' }}">
                                            <input type="hidden" name="items[{{ $index }}][quantity]" value="{{ $mrItem->quantity_approved ?? $mrItem->quantity_requested }}">
                                            <input type="hidden" name="items[{{ $index }}][unit]" value="{{ $mrItem->unit }}">
                                        </td>
                                        <td style="padding: 6px 8px;">{{ $mrItem->unit }}</td>
                                        <td style="padding: 4px 6px;">
                                            <input type="number" step="0.01" min="0"
                                                name="items[{{ $index }}][unit_price]"
                                                class="form-control form-control-sm text-right item-unit-price"
                                                data-index="{{ $index }}"
                                                data-qty="{{ $mrItem->quantity_approved ?? $mrItem->quantity_requested }}"
                                                placeholder="0.00" required>
                                        </td>
                                        <td style="padding: 6px 8px;" class="text-right font-w600">
                                            <span class="item-line-total" id="line-total-{{ $index }}">0.00</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr style="background: #f8f9fa;">
                                    <td colspan="6" class="text-right" style="padding: 8px;"><strong>Subtotal</strong></td>
                                    <td class="text-right" style="padding: 8px;"><strong id="subtotal-display">0.00</strong></td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-right" style="padding: 6px 8px;">VAT (18%)</td>
                                    <td class="text-right" style="padding: 8px;">
                                        <span id="vat-display">0.00</span>
                                        <input type="hidden" name="vat_amount" id="input-vat" value="0">
                                    </td>
                                </tr>
                                <tr style="background: #e8f5e9;">
                                    <td colspan="6" class="text-right" style="padding: 8px;"><strong>Grand Total</strong></td>
                                    <td class="text-right" style="padding: 8px;"><strong id="grand-total-display" style="font-size: 15px;">0.00</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Additional Details -->
                    <div class="row mt-3">
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="control-label">Payment Terms</label>
                                <input type="text" class="form-control" id="input-payment-terms"
                                    name="payment_terms" placeholder="e.g., Net 30">
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="control-label">Quotation Document</label>
                                <input type="file" class="form-control" name="file"
                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="control-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="1" placeholder="Optional notes"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="quotation-submit-btn">
                        <i class="si si-check"></i> Add Quotation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@php
    $quotationsJson = $quotations->keyBy('id')->map(function($q) {
        return [
            'id' => $q->id,
            'supplier_id' => $q->supplier_id,
            'quotation_date' => $q->quotation_date?->format('Y-m-d'),
            'valid_until' => $q->valid_until?->format('Y-m-d'),
            'delivery_time_days' => $q->delivery_time_days,
            'payment_terms' => $q->payment_terms,
            'vat_amount' => $q->vat_amount,
            'notes' => $q->notes,
            'items' => $q->items->keyBy('material_request_item_id')->map(function($item) {
                return ['unit_price' => $item->unit_price];
            }),
        ];
    });
@endphp
@section('js_after')
<script>
    // Existing quotation data for editing
    var quotationsData = @json($quotationsJson);

    function openQuotationForm(quotationId) {
        var form = $('#quotation-form');
        var isEdit = !!quotationId;

        // Reset form
        form[0].reset();
        form.find('.item-unit-price').val('');
        form.find('.item-line-total').text('0.00');
        $('#subtotal-display').text('0.00');
        $('#grand-total-display').text('0.00');
        $('#input-quotation-date').val('{{ date("Y-m-d") }}');
        $('#input-valid-until').val('{{ date("Y-m-d", strtotime("+30 days")) }}');
        $('#input-delivery-time').val(7);
        $('#input-vat').val(0);

        if (isEdit && quotationsData[quotationId]) {
            var data = quotationsData[quotationId];
            $('#quotation-modal-title').text('Edit Quotation');
            $('#quotation-submit-btn').html('<i class="si si-check"></i> Update Quotation');
            form.attr('action', '/supplier_quotations/update/' + quotationId);
            $('#form-quotation-id').val(quotationId);

            $('#input-supplier').val(data.supplier_id);
            $('#input-quotation-date').val(data.quotation_date);
            $('#input-valid-until').val(data.valid_until || '');
            $('#input-delivery-time').val(data.delivery_time_days || 7);
            $('#input-payment-terms').val(data.payment_terms || '');
            $('#input-vat').val(data.vat_amount || 0);
            form.find('textarea[name="notes"]').val(data.notes || '');

            // Fill item prices
            form.find('.item-unit-price').each(function() {
                var mrItemId = $(this).closest('tr').find('input[name$="[material_request_item_id]"]').val();
                if (data.items[mrItemId]) {
                    $(this).val(parseFloat(data.items[mrItemId].unit_price).toFixed(2));
                    $(this).trigger('input');
                }
            });
        } else {
            $('#quotation-modal-title').text('Add Quotation');
            $('#quotation-submit-btn').html('<i class="si si-plus"></i> Add Quotation');
            form.attr('action', '{{ route("supplier_quotations.store") }}');
            $('#form-quotation-id').val('');
        }

        $('#quotation-modal').modal('show');
    }

    $(document).ready(function() {
        // Datepickers
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });

        // Calculate line totals and grand total
        function recalculate() {
            var subtotal = 0;
            $('.item-unit-price').each(function() {
                var price = parseFloat($(this).val()) || 0;
                var qty = parseFloat($(this).data('qty')) || 0;
                var lineTotal = price * qty;
                var index = $(this).data('index');
                $('#line-total-' + index).text(lineTotal.toFixed(2));
                subtotal += lineTotal;
            });
            $('#subtotal-display').text(subtotal.toFixed(2));
            var vat = subtotal * 0.18;
            $('#input-vat').val(vat.toFixed(2));
            $('#vat-display').text(vat.toFixed(2));
            $('#grand-total-display').text((subtotal + vat).toFixed(2));
        }

        $(document).on('input', '.item-unit-price', recalculate);
    });
</script>
@endsection

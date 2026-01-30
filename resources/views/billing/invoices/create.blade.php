@extends('layouts.backend')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<style>
    .note-editor.note-frame { border: 1px solid #ddd; border-radius: 4px; }
    .note-toolbar { background: #f8f9fa; border-bottom: 1px solid #ddd; padding: 5px; }
</style>
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>Create Invoice</h1>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('billing.invoices.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Invoices
                    </a>
                </div>
            </div>
        </div>

        <form id="invoiceForm" method="POST" action="{{ route('billing.invoices.store') }}">
            @csrf
            @if($parentDocument)
                <input type="hidden" name="parent_document_id" value="{{ $parentDocument->id }}">
            @endif

            @if(isset($lead) && $lead)
                <input type="hidden" name="lead_id" value="{{ $lead->id }}">
                <div class="alert alert-info">
                    <i class="fa fa-link mr-2"></i>
                    Creating invoice for lead: <strong>{{ $lead->lead_number ?? $lead->name }}</strong>
                    @if($lead->client)
                        (Client: {{ $lead->client->first_name }} {{ $lead->client->last_name }})
                    @endif
                    <a href="{{ route('leads.show', $lead->id) }}" class="ml-2"><i class="fa fa-external-link-alt"></i> View Lead</a>
                </div>
            @endif

            <!-- Line Items - Full Width -->
            <div class="row mt-3">
                <div class="col-12">
                    <!-- Line Items -->
                    <div class="block block-themed">
                        <div class="block-header">
                            <h3 class="block-title">Line Items</h3>
                            <button type="button" class="btn btn-sm btn-success" onclick="addLineItem()">
                                <i class="fa fa-plus"></i> Add Item
                            </button>
                        </div>
                        <div class="block-content">
                            <div class="table-responsive">
                                <table class="table table-sm" id="itemsTable">
                                    <thead>
                                    <tr>
                                        <th width="20%">Product/Service</th>
                                        <th width="20%">Item/Description</th>
                                        <th width="8%">Qty</th>
                                        <th width="8%">Unit</th>
                                        <th width="12%">Unit Price</th>
                                        <th width="8%">Tax %</th>
                                        <th width="12%">Amount</th>
                                        <th width="5%">Action</th>
                                    </tr>
                                    </thead>
                                    <tbody id="itemsTableBody">
                                    @if($parentDocument && $parentDocument->items->count() > 0)
                                        @foreach($parentDocument->items as $index => $item)
                                            <tr data-index="{{ $index }}">
                                                <td>
                                                    <select name="items[{{ $index }}][product_service_id]"
                                                            class="form-control form-control-sm product-selector"
                                                            onchange="selectProduct(this, {{ $index }})">
                                                        <option value="">Select Product/Service</option>
                                                        @foreach($products as $product)
                                                            <option value="{{ $product->id }}"
                                                                    data-name="{{ $product->name }}"
                                                                    data-description="{{ $product->description }}"
                                                                    data-unit-price="{{ $product->unit_price }}"
                                                                    data-unit="{{ $product->unit_of_measure }}"
                                                                    data-tax-rate="{{ $product->taxRate ? $product->taxRate->rate : 0 }}"
                                                                {{ $item->product_service_id == $product->id ? 'selected' : '' }}>
                                                                [{{ $product->code }}] {{ $product->name }} - {{ ucfirst($product->type) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-muted">Or leave empty for custom item</small>
                                                </td>
                                                <td>
                                                    <input type="text" name="items[{{ $index }}][item_name]"
                                                           class="form-control form-control-sm"
                                                           value="{{ $item->item_name }}"
                                                           placeholder="Item name" required>
                                                    <textarea name="items[{{ $index }}][description]"
                                                              class="form-control form-control-sm mt-1"
                                                              rows="2" placeholder="Description">{{ $item->description }}</textarea>
                                                </td>
                                                <td>
                                                    <input type="number" name="items[{{ $index }}][quantity]"
                                                           class="form-control form-control-sm quantity"
                                                           value="{{ $item->quantity }}" step="0.01" min="0.01" required
                                                           onchange="calculateLineTotal(this)">
                                                </td>
                                                <td>
                                                    <input type="text" name="items[{{ $index }}][unit_of_measure]"
                                                           class="form-control form-control-sm"
                                                           value="{{ $item->unit_of_measure }}" placeholder="Unit">
                                                </td>
                                                <td>
                                                    <input type="number" name="items[{{ $index }}][unit_price]"
                                                           class="form-control form-control-sm unit-price"
                                                           value="{{ $item->unit_price }}" step="0.01" min="0" required
                                                           onchange="calculateLineTotal(this)">
                                                </td>
                                                <td>
                                                    <input type="number" name="items[{{ $index }}][tax_percentage]"
                                                           class="form-control form-control-sm tax-percentage"
                                                           value="{{ $item->tax_percentage }}" step="0.01" min="0" max="100"
                                                           onchange="calculateLineTotal(this)">
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm line-total"
                                                           value="{{ $item->line_total }}" readonly>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeLineItem(this)">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr data-index="0">
                                            <td>
                                                <select name="items[0][product_service_id]"
                                                        class="form-control form-control-sm product-selector"
                                                        onchange="selectProduct(this, 0)">
                                                    <option value="">Select Product/Service</option>
                                                    @foreach($products as $product)
                                                        <option value="{{ $product->id }}"
                                                                data-name="{{ $product->name }}"
                                                                data-description="{{ $product->description }}"
                                                                data-unit-price="{{ $product->unit_price }}"
                                                                data-unit="{{ $product->unit_of_measure }}"
                                                                data-tax-rate="{{ $product->taxRate ? $product->taxRate->rate : 0 }}">
                                                            [{{ $product->code }}] {{ $product->name }} - {{ ucfirst($product->type) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted">Or leave empty for custom item</small>
                                            </td>
                                            <td>
                                                <input type="text" name="items[0][item_name]"
                                                       class="form-control form-control-sm"
                                                       placeholder="Item name" required>
                                                <textarea name="items[0][description]"
                                                          class="form-control form-control-sm mt-1"
                                                          rows="2" placeholder="Description"></textarea>
                                            </td>
                                            <td>
                                                <input type="number" name="items[0][quantity]"
                                                       class="form-control form-control-sm quantity"
                                                       value="1" step="0.01" min="0.01" required
                                                       onchange="calculateLineTotal(this)">
                                            </td>
                                            <td>
                                                <input type="text" name="items[0][unit_of_measure]"
                                                       class="form-control form-control-sm" placeholder="Unit">
                                            </td>
                                            <td>
                                                <input type="number" name="items[0][unit_price]"
                                                       class="form-control form-control-sm unit-price"
                                                       value="0" step="0.01" min="0" required
                                                       onchange="calculateLineTotal(this)">
                                            </td>
                                            <td>
                                                <input type="number" name="items[0][tax_percentage]"
                                                       class="form-control form-control-sm tax-percentage"
                                                       value="18" step="0.01" min="0" max="100"
                                                       onchange="calculateLineTotal(this)">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm line-total"
                                                       value="0" readonly>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="removeLineItem(this)">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Left Column -->
                <div class="col-md-8">
                    <div class="block block-themed">
                        <div class="block-header">
                            <h3 class="block-title">Invoice Details</h3>
                        </div>
                        <div class="block-content">

                            <!-- Client & Basic Info -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Client <span class="text-danger">*</span></label>
                                        <select name="client_id" class="form-control" required>
                                            <option value="">Select Client</option>
                                            @foreach($clients as $client)
                                                <option value="{{ $client->id }}"
                                                        {{ (old('client_id') == $client->id || ($parentDocument && $parentDocument->client_id == $client->id) || (isset($lead) && $lead && $lead->client_id == $client->id)) ? 'selected' : '' }}>
                                                    {{ $client->first_name }} {{ $client->last_name }}
                                                    @if($client->email) - {{ $client->email }} @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('client_id')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Reference Number</label>
                                        <input type="text" name="reference_number" class="form-control"
                                               value="{{ old('reference_number', $parentDocument->reference_number ?? '') }}"
                                               placeholder="External reference">
                                    </div>
                                </div>
                            </div>

                            <!-- Dates -->
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Issue Date <span class="text-danger">*</span></label>
                                        <input type="text" name="issue_date" class="form-control datepicker"
                                               value="{{ old('issue_date', date('Y-m-d')) }}" required>
                                        @error('issue_date')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Payment Terms</label>
                                        <select name="payment_terms" class="form-control">
                                            <option value="immediate">Due Immediately</option>
                                            <option value="net_7">Net 7 days</option>
                                            <option value="net_15">Net 15 days</option>
                                            <option value="net_30" selected>Net 30 days</option>
                                            <option value="net_45">Net 45 days</option>
                                            <option value="net_60">Net 60 days</option>
                                            <option value="net_90">Net 90 days</option>
                                            <option value="custom">Custom</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Due Date</label>
                                        <input type="text" name="due_date" class="form-control datepicker"
                                               value="{{ old('due_date') }}">
                                    </div>
                                </div>
                            </div>

                            <!-- PO Number & Sales Person -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>PO Number</label>
                                        <input type="text" name="po_number" class="form-control"
                                               value="{{ old('po_number', $parentDocument->po_number ?? '') }}">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Sales Person</label>
                                        <input type="text" name="sales_person" class="form-control"
                                               value="{{ old('sales_person', auth()->user()->name) }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column - Summary -->
                <div class="col-md-4">
                    <div class="block block-themed">
                        <div class="block-header">
                            <h3 class="block-title">Summary</h3>
                        </div>
                        <div class="block-content">
                            <table class="table table-sm">
                                <tr>
                                    <td>Subtotal:</td>
                                    <td class="text-right" id="subtotal">{{ $settings['default_currency'] ?? 'TZS' }} 0.00</td>
                                </tr>
                                <tr>
                                    <td>Tax:</td>
                                    <td class="text-right" id="tax-amount">{{ $settings['default_currency'] ?? 'TZS' }} 0.00</td>
                                </tr>
                                <tr>
                                    <td><strong>Total:</strong></td>
                                    <td class="text-right"><strong id="total-amount">{{ $settings['default_currency'] ?? 'TZS' }} 0.00</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Currency & Discount -->
                    <div class="block block-themed">
                        <div class="block-header">
                            <h3 class="block-title">Additional Settings</h3>
                        </div>
                        <div class="block-content">
                            <div class="form-group">
                                <label>Currency</label>
                                <select name="currency_code" class="form-control">
                                    <option value="TZS" {{ old('currency_code', $settings['default_currency'] ?? 'TZS') == 'TZS' ? 'selected' : '' }}>TZS - Tanzanian Shilling</option>
                                    <option value="USD" {{ old('currency_code', $settings['default_currency'] ?? 'TZS') == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                    <option value="EUR" {{ old('currency_code', $settings['default_currency'] ?? 'TZS') == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                    <option value="GBP" {{ old('currency_code', $settings['default_currency'] ?? 'TZS') == 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Shipping Amount</label>
                                <input type="number" name="shipping_amount" class="form-control"
                                       value="{{ old('shipping_amount', 0) }}" step="0.01" min="0" onchange="calculateTotals()">
                            </div>

                            <div class="form-group">
                                <label>Discount Type</label>
                                <select name="discount_type" class="form-control" onchange="calculateTotals()">
                                    <option value="">No Discount</option>
                                    <option value="percentage" {{ old('discount_type') == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                    <option value="fixed" {{ old('discount_type') == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Discount Value</label>
                                <input type="number" name="discount_value" class="form-control"
                                       value="{{ old('discount_value', 0) }}" step="0.01" min="0" onchange="calculateTotals()">
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="block block-themed">
                        <div class="block-header">
                            <h3 class="block-title">Notes & Terms</h3>
                        </div>
                        <div class="block-content">
                            <div class="form-group">
                                <label>Internal Notes</label>
                                <textarea name="notes" id="notes-editor" class="form-control">{{ old('notes') }}</textarea>
                            </div>

                            <div class="form-group">
                                <label>Terms & Conditions</label>
                                <textarea name="terms_conditions" class="form-control" rows="3">{{ old('terms_conditions', $settings['invoice_terms'] ?? '') }}</textarea>
                            </div>

                            <div class="form-group">
                                <label>Footer Text</label>
                                <textarea name="footer_text" class="form-control" rows="2">{{ old('footer_text', $settings['invoice_footer'] ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Action Buttons -->
            <div class="row">
                <div class="col-12">
                    <div class="text-center">
                        <button type="submit" name="save_as_draft" value="1" class="btn btn-secondary">
                            <i class="fa fa-save"></i> Save as Draft
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check"></i> Create Invoice
                        </button>
                        <a href="{{ route('billing.invoices.index') }}" class="btn btn-light">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let itemIndex = 1;

function addLineItem() {
    const tbody = document.getElementById('itemsTableBody');
    const newRow = `
        <tr data-index="${itemIndex}">
            <td style="vertical-align: top;">
                <select name="items[${itemIndex}][product_service_id]" class="form-control form-control-sm product-selector"
                        onchange="selectProduct(this, ${itemIndex})">
                    <option value="">Select Product/Service</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}"
                                data-name="{{ $product->name }}"
                                data-price="{{ $product->unit_price }}"
                                data-unit="{{ $product->unit_of_measure }}"
                                data-tax="{{ $product->taxRate ? $product->taxRate->rate : 0 }}">
                            [{{ $product->code }}] {{ $product->name }} - TZS {{ number_format($product->unit_price, 2) }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">Or leave empty for custom item</small>
            </td>
            <td style="vertical-align: top;">
                <input type="text" name="items[${itemIndex}][item_name]"
                       class="form-control form-control-sm"
                       placeholder="Or enter custom item name" required>
                <textarea name="items[${itemIndex}][description]"
                          class="form-control form-control-sm mt-1"
                          rows="2" placeholder="Description"></textarea>
            </td>
            <td style="vertical-align: top;">
                <input type="number" name="items[${itemIndex}][quantity]"
                       class="form-control form-control-sm quantity"
                       value="1" step="0.01" min="0.01" required
                       onchange="calculateLineTotal(this)">
            </td>
            <td style="vertical-align: top;">
                <input type="text" name="items[${itemIndex}][unit_of_measure]"
                       class="form-control form-control-sm" placeholder="Unit">
            </td>
            <td style="vertical-align: top;">
                <input type="number" name="items[${itemIndex}][unit_price]"
                       class="form-control form-control-sm unit-price"
                       value="0" step="0.01" min="0" required
                       onchange="calculateLineTotal(this)">
            </td>
            <td style="vertical-align: top;">
                <input type="number" name="items[${itemIndex}][tax_percentage]"
                       class="form-control form-control-sm tax-percentage"
                       value="18" step="0.01" min="0" max="100"
                       onchange="calculateLineTotal(this)">
            </td>
            <td style="vertical-align: top;">
                <input type="number" class="form-control form-control-sm line-total"
                       value="0" readonly>
            </td>
            <td style="vertical-align: top;">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeLineItem(this)">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
    tbody.insertAdjacentHTML('beforeend', newRow);
    itemIndex++;
}

function removeLineItem(button) {
    if (document.querySelectorAll('#itemsTableBody tr').length > 1) {
        button.closest('tr').remove();
        calculateTotals();
    }
}

function calculateLineTotal(element) {
    const row = element.closest('tr');
    const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
    const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
    const taxPercentage = parseFloat(row.querySelector('.tax-percentage').value) || 0;

    const subtotal = quantity * unitPrice;
    const taxAmount = subtotal * (taxPercentage / 100);
    const total = subtotal + taxAmount;

    row.querySelector('.line-total').value = total.toFixed(2);
    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0;
    let taxAmount = 0;

    document.querySelectorAll('#itemsTableBody tr').forEach(row => {
        const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
        const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
        const taxPercentage = parseFloat(row.querySelector('.tax-percentage').value) || 0;

        const lineSubtotal = quantity * unitPrice;
        const lineTaxAmount = lineSubtotal * (taxPercentage / 100);

        subtotal += lineSubtotal;
        taxAmount += lineTaxAmount;
    });

    const shippingAmount = parseFloat(document.querySelector('[name="shipping_amount"]')?.value) || 0;
    const discountType = document.querySelector('[name="discount_type"]')?.value;
    const discountValue = parseFloat(document.querySelector('[name="discount_value"]')?.value) || 0;

    let discountAmount = 0;
    if (discountType === 'percentage') {
        discountAmount = subtotal * (discountValue / 100);
    } else if (discountType === 'fixed') {
        discountAmount = discountValue;
    }

    const total = subtotal + taxAmount + shippingAmount - discountAmount;

    if (document.getElementById('subtotal')) document.getElementById('subtotal').textContent = 'TZS ' + subtotal.toFixed(2);
    if (document.getElementById('tax-amount')) document.getElementById('tax-amount').textContent = 'TZS ' + taxAmount.toFixed(2);
    if (document.getElementById('total-amount')) document.getElementById('total-amount').textContent = 'TZS ' + total.toFixed(2);
}

function selectProduct(selectElement, index) {
    const option = selectElement.selectedOptions[0];

    if (option.value) {
        const row = selectElement.closest('tr');

        // Fill in the product details
        row.querySelector(`input[name="items[${index}][item_name]"]`).value = option.dataset.name || '';
        row.querySelector(`textarea[name="items[${index}][description]"]`).value = option.dataset.description || '';
        row.querySelector(`input[name="items[${index}][unit_price]"]`).value = parseFloat(option.dataset.unitPrice || 0).toFixed(2);
        row.querySelector(`input[name="items[${index}][unit_of_measure]"]`).value = option.dataset.unit || '';
        row.querySelector(`input[name="items[${index}][tax_percentage]"]`).value = parseFloat(option.dataset.taxRate || 0).toFixed(2);

        // Recalculate the line total
        calculateLineTotal(row.querySelector('.quantity'));
    }
}

document.addEventListener('DOMContentLoaded', function() {
    calculateTotals();
});
</script>

@endsection

@section('js_after')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
$(document).ready(function() {
    $('#notes-editor').summernote({
        height: 200,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ],
        placeholder: 'Enter internal notes here...'
    });
});
</script>
@endsection

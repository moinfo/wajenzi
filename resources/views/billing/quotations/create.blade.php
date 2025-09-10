@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>Create Quotation</h1>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('billing.quotations.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Quotations
                    </a>
                </div>
            </div>
        </div>

        <form id="quotationForm" method="POST" action="{{ route('billing.quotations.store') }}">
            @csrf

            <div class="row">
                <!-- Left Column -->
                <div class="col-md-8">
                    <div class="block block-themed">
                        <div class="block-header">
                            <h3 class="block-title">Quotation Details</h3>
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
                                                        {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                                    {{ $client->company_name }}
                                                    @if($client->contact_person) - {{ $client->contact_person }} @endif
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
                                               value="{{ old('reference_number') }}"
                                               placeholder="External reference">
                                    </div>
                                </div>
                            </div>

                            <!-- Dates -->
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Issue Date <span class="text-danger">*</span></label>
                                        <input type="date" name="issue_date" class="form-control"
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
                                            <option value="immediate" {{ old('payment_terms', $settings['default_payment_terms'] ?? 'net_30') == 'immediate' ? 'selected' : '' }}>Due Immediately</option>
                                            <option value="net_7" {{ old('payment_terms', $settings['default_payment_terms'] ?? 'net_30') == 'net_7' ? 'selected' : '' }}>Net 7 days</option>
                                            <option value="net_15" {{ old('payment_terms', $settings['default_payment_terms'] ?? 'net_30') == 'net_15' ? 'selected' : '' }}>Net 15 days</option>
                                            <option value="net_30" {{ old('payment_terms', $settings['default_payment_terms'] ?? 'net_30') == 'net_30' ? 'selected' : '' }}>Net 30 days</option>
                                            <option value="net_45" {{ old('payment_terms', $settings['default_payment_terms'] ?? 'net_30') == 'net_45' ? 'selected' : '' }}>Net 45 days</option>
                                            <option value="net_60" {{ old('payment_terms', $settings['default_payment_terms'] ?? 'net_30') == 'net_60' ? 'selected' : '' }}>Net 60 days</option>
                                            <option value="net_90" {{ old('payment_terms', $settings['default_payment_terms'] ?? 'net_30') == 'net_90' ? 'selected' : '' }}>Net 90 days</option>
                                            <option value="custom">Custom</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Valid Until</label>
                                        <input type="date" name="valid_until_date" class="form-control"
                                               value="{{ old('valid_until_date') }}">
                                    </div>
                                </div>
                            </div>

                            <!-- PO Number & Sales Person -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>PO Number</label>
                                        <input type="text" name="po_number" class="form-control"
                                               value="{{ old('po_number') }}">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Sales Person</label>
                                        <input type="text" name="sales_person" class="form-control"
                                               value="{{ old('sales_person', auth()->user()->name ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                            <th width="25%">Item/Description</th>
                                            <th width="10%">Qty</th>
                                            <th width="10%">Unit</th>
                                            <th width="15%">Unit Price</th>
                                            <th width="10%">Tax %</th>
                                            <th width="15%">Amount</th>
                                            <th width="5%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsTableBody">
                                        <tr data-index="0">
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
                                                       value="{{ $settings['default_tax_rate'] ?? 18 }}" step="0.01" min="0" max="100"
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
                                    </tbody>
                                </table>
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
                                <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
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
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-check"></i> Create Quotation
                        </button>
                        <a href="{{ route('billing.quotations.index') }}" class="btn btn-light">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{{--@push('scripts')--}}
<script>
let itemIndex = 1;

function addLineItem() {
    const tbody = document.getElementById('itemsTableBody');
    const newRow = `
        <tr data-index="${itemIndex}">
            <td>
                <input type="text" name="items[${itemIndex}][item_name]"
                       class="form-control form-control-sm" placeholder="Item name" required>
                <textarea name="items[${itemIndex}][description]"
                          class="form-control form-control-sm mt-1" rows="2" placeholder="Description"></textarea>
            </td>
            <td>
                <input type="number" name="items[${itemIndex}][quantity]"
                       class="form-control form-control-sm quantity"
                       value="1" step="0.01" min="0.01" required onchange="calculateLineTotal(this)">
            </td>
            <td>
                <input type="text" name="items[${itemIndex}][unit_of_measure]"
                       class="form-control form-control-sm" placeholder="Unit">
            </td>
            <td>
                <input type="number" name="items[${itemIndex}][unit_price]"
                       class="form-control form-control-sm unit-price"
                       value="0" step="0.01" min="0" required onchange="calculateLineTotal(this)">
            </td>
            <td>
                <input type="number" name="items[${itemIndex}][tax_percentage]"
                       class="form-control form-control-sm tax-percentage"
                       value="{{ $settings['default_tax_rate'] ?? 18 }}" step="0.01" min="0" max="100" onchange="calculateLineTotal(this)">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm line-total" value="0" readonly>
            </td>
            <td>
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

    const shippingAmount = parseFloat(document.querySelector('[name="shipping_amount"]').value) || 0;
    const discountType = document.querySelector('[name="discount_type"]').value;
    const discountValue = parseFloat(document.querySelector('[name="discount_value"]').value) || 0;

    let discountAmount = 0;
    if (discountType === 'percentage') {
        discountAmount = subtotal * (discountValue / 100);
    } else if (discountType === 'fixed') {
        discountAmount = discountValue;
    }

    const total = subtotal + taxAmount + shippingAmount - discountAmount;
    const currency = document.querySelector('[name="currency_code"]').value;

    document.getElementById('subtotal').textContent = currency + ' ' + subtotal.toFixed(2);
    document.getElementById('tax-amount').textContent = currency + ' ' + taxAmount.toFixed(2);
    document.getElementById('total-amount').textContent = currency + ' ' + total.toFixed(2);
}

// Calculate valid until date from payment terms
document.querySelector('[name="payment_terms"]').addEventListener('change', function() {
    const issueDate = new Date(document.querySelector('[name="issue_date"]').value);
    const paymentTerms = this.value;
    let validUntilDate = new Date(issueDate);

    switch(paymentTerms) {
        case 'net_7': validUntilDate.setDate(validUntilDate.getDate() + 7); break;
        case 'net_15': validUntilDate.setDate(validUntilDate.getDate() + 15); break;
        case 'net_30': validUntilDate.setDate(validUntilDate.getDate() + 30); break;
        case 'net_45': validUntilDate.setDate(validUntilDate.getDate() + 45); break;
        case 'net_60': validUntilDate.setDate(validUntilDate.getDate() + 60); break;
        case 'net_90': validUntilDate.setDate(validUntilDate.getDate() + 90); break;
        default: return;
    }

    document.querySelector('[name="valid_until_date"]').value = validUntilDate.toISOString().split('T')[0];
});

// Update currency in totals when currency changes
document.querySelector('[name="currency_code"]').addEventListener('change', function() {
    calculateTotals();
});

// Initialize calculations
document.addEventListener('DOMContentLoaded', function() {
    calculateTotals();
});
</script>
{{--@endpush--}}

@endsection

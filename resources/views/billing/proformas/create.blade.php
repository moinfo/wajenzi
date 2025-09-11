@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>Create Proforma Invoice</h1>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('billing.proformas.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Proformas
                    </a>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('billing.proformas.store') }}">
            @csrf
            <input type="hidden" name="document_type" value="proforma">
            <!-- Line Items - Full Width -->
            <div class="row mt-3">
                <div class="col-12">
                    <!-- Line Items -->
                    <div class="block block-themed">
                        <div class="block-header">
                            <h3 class="block-title">Line Items</h3>
                            <div class="block-options">
                                <button type="button" class="btn btn-sm btn-primary" onclick="addItem()">
                                    <i class="fa fa-plus"></i> Add Item
                                </button>
                            </div>
                        </div>
                        <div class="block-content">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped" id="items-table" style="min-width: 1000px;">
                                    <thead>
                                    <tr>
                                        <th width="20%" style="vertical-align: top;">Product/Service</th>
                                        <th width="20%" style="vertical-align: top;">Item/Description</th>
                                        <th width="8%" style="vertical-align: top;">Qty</th>
                                        <th width="8%" style="vertical-align: top;">Unit</th>
                                        <th width="12%" style="vertical-align: top;">Unit Price</th>
                                        <th width="8%" style="vertical-align: top;">Tax %</th>
                                        <th width="12%" style="vertical-align: top;">Amount</th>
                                        <th width="5%" style="vertical-align: top;">Action</th>
                                    </tr>
                                    </thead>
                                    <tbody id="items-tbody">
                                    <!-- Items will be added here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="block block-themed">
                        <div class="block-header">
                            <h3 class="block-title">Additional Information</h3>
                        </div>
                        <div class="block-content">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="notes">Notes</label>
                                        <textarea name="notes" id="notes" rows="3"
                                                  class="form-control @error('notes') is-invalid @enderror"
                                                  placeholder="Additional notes or comments...">{{ old('notes') }}</textarea>
                                        @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="terms_conditions">Terms & Conditions</label>
                                        <textarea name="terms_conditions" id="terms_conditions" rows="3"
                                                  class="form-control @error('terms_conditions') is-invalid @enderror"
                                                  placeholder="Terms and conditions...">{{ old('terms_conditions', $settings['default_terms'] ?? '') }}</textarea>
                                        @error('terms_conditions')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <div class="block block-themed">
                        <div class="block-header">
                            <h3 class="block-title">Proforma Details</h3>
                        </div>
                        <div class="block-content">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="client_id">Client <span class="text-danger">*</span></label>
                                        <select name="client_id" id="client_id" class="form-control @error('client_id') is-invalid @enderror" required>
                                            <option value="">Select a client...</option>
                                            @foreach($clients as $client)
                                                <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                                    {{ $client->company_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('client_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        @if($clients->count() === 0)
                                            <small class="text-warning">No clients available. <a href="{{ route('billing.clients.create') }}">Create a client first</a>.</small>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="issue_date">Issue Date <span class="text-danger">*</span></label>
                                        <input type="text" name="issue_date" id="issue_date"
                                               class="form-control datepicker @error('issue_date') is-invalid @enderror"
                                               value="{{ old('issue_date', date('Y-m-d')) }}" required>
                                        @error('issue_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="valid_until_date">Valid Until</label>
                                        <input type="text" name="valid_until_date" id="valid_until_date"
                                               class="form-control datepicker @error('valid_until_date') is-invalid @enderror"
                                               value="{{ old('valid_until_date', date('Y-m-d', strtotime('+30 days'))) }}">
                                        @error('valid_until_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="reference_number">Reference Number</label>
                                        <input type="text" name="reference_number" id="reference_number"
                                               class="form-control @error('reference_number') is-invalid @enderror"
                                               value="{{ old('reference_number') }}" placeholder="Your reference">
                                        @error('reference_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="currency_code">Currency</label>
                                        <select name="currency_code" id="currency_code" class="form-control @error('currency_code') is-invalid @enderror">
                                            <option value="TZS" {{ old('currency_code', 'TZS') == 'TZS' ? 'selected' : '' }}>TZS - Tanzanian Shilling</option>
                                            <option value="USD" {{ old('currency_code') == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                            <option value="EUR" {{ old('currency_code') == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                        </select>
                                        @error('currency_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="sales_person">Sales Person</label>
                                        <input type="text" name="sales_person" id="sales_person"
                                               class="form-control @error('sales_person') is-invalid @enderror"
                                               value="{{ old('sales_person') }}" placeholder="Sales person name">
                                        @error('sales_person')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Summary Sidebar -->
                <div class="col-md-4">
                    <div class="block block-themed">
                        <div class="block-header">
                            <h3 class="block-title">Summary</h3>
                        </div>
                        <div class="block-content">
                            <table class="table table-sm">
                                <tr>
                                    <td>Subtotal:</td>
                                    <td class="text-right" id="subtotal">TZS 0.00</td>
                                </tr>
                                <tr>
                                    <td>Tax:</td>
                                    <td class="text-right" id="tax-amount">TZS 0.00</td>
                                </tr>
                                <tr class="table-active">
                                    <td><strong>Total:</strong></td>
                                    <td class="text-right"><strong id="total">TZS 0.00</strong></td>
                                </tr>
                            </table>

                            @if($errors->any())
                                <div class="alert alert-danger mb-3">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fa fa-check"></i> Create Proforma
                                </button>
                            </div>

                            <div class="form-group">
                                <a href="{{ route('billing.proformas.index') }}" class="btn btn-secondary btn-block">
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </form>
    </div>
</div>

<script>
let itemCount = 0;

function addItem() {
    itemCount++;
    const tbody = document.getElementById('items-tbody');
    const row = document.createElement('tr');
    row.id = 'item-' + itemCount;

    row.innerHTML = `
        <td style="vertical-align: top;">
            <select name="items[${itemCount}][product_service_id]" class="form-control form-control-sm"
                    onchange="selectProduct(this, ${itemCount})">
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
        <td style="vertical-align: top;">
            <input type="text" name="items[${itemCount}][item_name]" class="form-control form-control-sm" placeholder="Item name" required>
            <input type="text" name="items[${itemCount}][description]" class="form-control form-control-sm mt-1" placeholder="Description (optional)">
        </td>
        <td style="vertical-align: top;">
            <input type="number" name="items[${itemCount}][quantity]" class="form-control form-control-sm" placeholder="Qty" min="0.01" step="0.01" value="1" onchange="calculateRow(${itemCount})" required>
        </td>
        <td style="vertical-align: top;">
            <input type="text" name="items[${itemCount}][unit_of_measure]" class="form-control form-control-sm" placeholder="Unit">
        </td>
        <td style="vertical-align: top;">
            <input type="number" name="items[${itemCount}][unit_price]" class="form-control form-control-sm" placeholder="Price" min="0" step="0.01" onchange="calculateRow(${itemCount})" required>
        </td>
        <td style="vertical-align: top;">
            <input type="number" name="items[${itemCount}][tax_percentage]" class="form-control form-control-sm" placeholder="Tax %" min="0" max="100" step="0.01" value="0" onchange="calculateRow(${itemCount})">
        </td>
        <td style="vertical-align: top;">
            <input type="number" name="items[${itemCount}][line_total]" class="form-control form-control-sm" readonly>
        </td>
        <td style="vertical-align: top;">
            <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${itemCount})">
                <i class="fa fa-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(row);
}

function removeItem(itemId) {
    document.getElementById('item-' + itemId).remove();
    calculateTotals();
}

function calculateRow(itemId) {
    const quantity = parseFloat(document.querySelector(`input[name="items[${itemId}][quantity]"]`).value) || 0;
    const unitPrice = parseFloat(document.querySelector(`input[name="items[${itemId}][unit_price]"]`).value) || 0;
    const taxPercentage = parseFloat(document.querySelector(`input[name="items[${itemId}][tax_percentage]"]`).value) || 0;

    const subtotal = quantity * unitPrice;
    const taxAmount = subtotal * (taxPercentage / 100);
    const total = subtotal + taxAmount;

    document.querySelector(`input[name="items[${itemId}][line_total]"]`).value = total.toFixed(2);

    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0;
    let taxAmount = 0;

    document.querySelectorAll('input[name$="[line_total]"]').forEach(input => {
        subtotal += parseFloat(input.value) || 0;
    });

    document.querySelectorAll('input[name$="[tax_percentage]"]').forEach((input, index) => {
        const quantity = parseFloat(document.querySelectorAll('input[name$="[quantity]"]')[index].value) || 0;
        const unitPrice = parseFloat(document.querySelectorAll('input[name$="[unit_price]"]')[index].value) || 0;
        const taxPercentage = parseFloat(input.value) || 0;

        const itemSubtotal = quantity * unitPrice;
        taxAmount += itemSubtotal * (taxPercentage / 100);
    });

    const currency = document.getElementById('currency_code').value;

    document.getElementById('subtotal').textContent = currency + ' ' + (subtotal - taxAmount).toFixed(2);
    document.getElementById('tax-amount').textContent = currency + ' ' + taxAmount.toFixed(2);
    document.getElementById('total').textContent = currency + ' ' + subtotal.toFixed(2);
}

document.getElementById('currency_code').addEventListener('change', calculateTotals);

// Add first item on load
document.addEventListener('DOMContentLoaded', function() {
    addItem();
});

// Product selection function
function selectProduct(selectElement, itemId) {
    const option = selectElement.selectedOptions[0];

    if (option.value) {
        // Get the row
        const row = selectElement.closest('tr');

        // Populate the fields
        row.querySelector(`input[name="items[${itemId}][item_name]"]`).value = option.dataset.name || '';
        row.querySelector(`input[name="items[${itemId}][description]"]`).value = option.dataset.description || '';
        row.querySelector(`input[name="items[${itemId}][unit_price]"]`).value = parseFloat(option.dataset.unitPrice || 0).toFixed(2);
        row.querySelector(`input[name="items[${itemId}][tax_percentage]"]`).value = parseFloat(option.dataset.taxRate || 0).toFixed(2);

        // Calculate totals
        calculateRow(itemId);
    }
}
</script>
@endsection

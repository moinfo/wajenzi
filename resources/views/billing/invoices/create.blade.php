@extends('layouts.backend')

@section('content')
<div class="main-container">
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
                                                        {{ (old('client_id') == $client->id || ($parentDocument && $parentDocument->client_id == $client->id)) ? 'selected' : '' }}>
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
                                        <input type="date" name="due_date" class="form-control"
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
                                        @if($parentDocument && $parentDocument->items->count() > 0)
                                            @foreach($parentDocument->items as $index => $item)
                                                <tr data-index="{{ $index }}">
                                                    <td>
                                                        <input type="text" name="items[{{ $index }}][item_name]"
                                                               class="form-control form-control-sm"
                                                               value="{{ $item->item_name }}" required>
                                                        <input type="hidden" name="items[{{ $index }}][product_service_id]"
                                                               value="{{ $item->product_service_id }}">
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
                                    <td class="text-right" id="subtotal">TZS 0.00</td>
                                </tr>
                                <tr>
                                    <td>Tax:</td>
                                    <td class="text-right" id="tax-amount">TZS 0.00</td>
                                </tr>
                                <tr>
                                    <td><strong>Total:</strong></td>
                                    <td class="text-right"><strong id="total-amount">TZS 0.00</strong></td>
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
                                    <option value="TZS">TZS - Tanzanian Shilling</option>
                                    <option value="USD">USD - US Dollar</option>
                                    <option value="EUR">EUR - Euro</option>
                                    <option value="GBP">GBP - British Pound</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Shipping Amount</label>
                                <input type="number" name="shipping_amount" class="form-control"
                                       value="0" step="0.01" min="0" onchange="calculateTotals()">
                            </div>

                            <div class="form-group">
                                <label>Discount Type</label>
                                <select name="discount_type" class="form-control" onchange="calculateTotals()">
                                    <option value="">No Discount</option>
                                    <option value="percentage">Percentage</option>
                                    <option value="fixed">Fixed Amount</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Discount Value</label>
                                <input type="number" name="discount_value" class="form-control"
                                       value="0" step="0.01" min="0" onchange="calculateTotals()">
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
                                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $parentDocument->notes ?? '') }}</textarea>
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

{{--@push('scripts')--}}
<script>
let itemIndex = {{ $parentDocument && $parentDocument->items->count() > 0 ? $parentDocument->items->count() : 1 }};

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
                       value="18" step="0.01" min="0" max="100" onchange="calculateLineTotal(this)">
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

    document.getElementById('subtotal').textContent = 'TZS ' + subtotal.toFixed(2);
    document.getElementById('tax-amount').textContent = 'TZS ' + taxAmount.toFixed(2);
    document.getElementById('total-amount').textContent = 'TZS ' + total.toFixed(2);
}

// Calculate payment terms due date
document.querySelector('[name="payment_terms"]').addEventListener('change', function() {
    const issueDate = new Date(document.querySelector('[name="issue_date"]').value);
    const paymentTerms = this.value;
    let dueDate = new Date(issueDate);

    switch(paymentTerms) {
        case 'net_7': dueDate.setDate(dueDate.getDate() + 7); break;
        case 'net_15': dueDate.setDate(dueDate.getDate() + 15); break;
        case 'net_30': dueDate.setDate(dueDate.getDate() + 30); break;
        case 'net_45': dueDate.setDate(dueDate.getDate() + 45); break;
        case 'net_60': dueDate.setDate(dueDate.getDate() + 60); break;
        case 'net_90': dueDate.setDate(dueDate.getDate() + 90); break;
        default: return;
    }

    document.querySelector('[name="due_date"]').value = dueDate.toISOString().split('T')[0];
});

// Initialize calculations
document.addEventListener('DOMContentLoaded', function() {
    calculateTotals();
});
</script>
{{--@endpush--}}

@endsection

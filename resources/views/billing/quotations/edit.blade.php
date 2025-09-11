@extends('layouts.app')

@section('title', 'Edit Quotation #' . $quotation->document_number)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Edit Quotation #{{ $quotation->document_number }}</h4>
                    <a href="{{ route('billing.quotations.show', $quotation) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Quotation
                    </a>
                </div>
                
                <form action="{{ route('billing.quotations.update', $quotation) }}" method="POST" id="quotationForm">
                    @csrf
                    @method('PUT')
                    
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">
                            <!-- Client Information -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="client_id">Client <span class="text-danger">*</span></label>
                                    <select name="client_id" id="client_id" class="form-control" required>
                                        <option value="">Select Client</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}" {{ old('client_id', $quotation->client_id) == $client->id ? 'selected' : '' }}>
                                                {{ $client->company_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="issue_date">Issue Date <span class="text-danger">*</span></label>
                                    <input type="text" name="issue_date" id="issue_date" class="form-control datepicker" 
                                           value="{{ old('issue_date', $quotation->issue_date->format('Y-m-d')) }}" required>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="valid_until_date">Valid Until</label>
                                    <input type="text" name="valid_until_date" id="valid_until_date" class="form-control datepicker" 
                                           value="{{ old('valid_until_date', $quotation->valid_until_date ? $quotation->valid_until_date->format('Y-m-d') : '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="payment_terms">Payment Terms</label>
                                    <select name="payment_terms" id="payment_terms" class="form-control">
                                        <option value="immediate" {{ old('payment_terms', $quotation->payment_terms) == 'immediate' ? 'selected' : '' }}>Immediate</option>
                                        <option value="net_7" {{ old('payment_terms', $quotation->payment_terms) == 'net_7' ? 'selected' : '' }}>Net 7</option>
                                        <option value="net_15" {{ old('payment_terms', $quotation->payment_terms) == 'net_15' ? 'selected' : '' }}>Net 15</option>
                                        <option value="net_30" {{ old('payment_terms', $quotation->payment_terms) == 'net_30' ? 'selected' : '' }}>Net 30</option>
                                        <option value="net_45" {{ old('payment_terms', $quotation->payment_terms) == 'net_45' ? 'selected' : '' }}>Net 45</option>
                                        <option value="net_60" {{ old('payment_terms', $quotation->payment_terms) == 'net_60' ? 'selected' : '' }}>Net 60</option>
                                        <option value="net_90" {{ old('payment_terms', $quotation->payment_terms) == 'net_90' ? 'selected' : '' }}>Net 90</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="currency_code">Currency</label>
                                    <select name="currency_code" id="currency_code" class="form-control">
                                        <option value="TZS" {{ old('currency_code', $quotation->currency_code) == 'TZS' ? 'selected' : '' }}>TZS</option>
                                        <option value="USD" {{ old('currency_code', $quotation->currency_code) == 'USD' ? 'selected' : '' }}>USD</option>
                                        <option value="EUR" {{ old('currency_code', $quotation->currency_code) == 'EUR' ? 'selected' : '' }}>EUR</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="po_number">PO Number</label>
                                    <input type="text" name="po_number" id="po_number" class="form-control" 
                                           value="{{ old('po_number', $quotation->po_number) }}">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="sales_person">Sales Person</label>
                                    <input type="text" name="sales_person" id="sales_person" class="form-control" 
                                           value="{{ old('sales_person', $quotation->sales_person) }}">
                                </div>
                            </div>
                        </div>

                        <!-- Line Items -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5>Line Items</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="itemsTable">
                                        <thead>
                                            <tr>
                                                <th width="25%">Product/Service</th>
                                                <th width="25%">Item</th>
                                                <th width="12%">Quantity</th>
                                                <th width="12%">Unit Price</th>
                                                <th width="8%">Tax %</th>
                                                <th width="12%">Amount</th>
                                                <th width="5%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemsTableBody">
                                            @foreach($quotation->items as $index => $item)
                                                <tr class="item-row">
                                                    <td>
                                                        <select name="items[{{ $index }}][product_service_id]" class="form-control form-control-sm" 
                                                                onchange="selectProduct(this, {{ $index }})">
                                                            <option value="">Select Product/Service</option>
                                                            @foreach($products as $product)
                                                                <option value="{{ $product->id }}" 
                                                                        {{ old('items.' . $index . '.product_service_id', $item->product_service_id) == $product->id ? 'selected' : '' }}
                                                                        data-name="{{ $product->name }}"
                                                                        data-description="{{ $product->description }}"
                                                                        data-unit-price="{{ $product->unit_price }}"
                                                                        data-unit="{{ $product->unit_of_measure }}"
                                                                        data-tax-rate="{{ $product->taxRate ? $product->taxRate->rate : 0 }}">
                                                                    [{{ $product->code }}] {{ $product->name }} - {{ ucfirst($product->type) }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Or leave empty to enter custom item</small>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="items[{{ $index }}][item_name]" class="form-control item-name" 
                                                               value="{{ old('items.' . $index . '.item_name', $item->item_name) }}" required>
                                                        <textarea name="items[{{ $index }}][description]" class="form-control mt-1 item-description" rows="2" 
                                                                  placeholder="Description (optional)">{{ old('items.' . $index . '.description', $item->description) }}</textarea>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="items[{{ $index }}][quantity]" class="form-control item-quantity" 
                                                               step="0.01" min="0.01" value="{{ old('items.' . $index . '.quantity', $item->quantity) }}" required>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="items[{{ $index }}][unit_price]" class="form-control item-price" 
                                                               step="0.01" min="0" value="{{ old('items.' . $index . '.unit_price', $item->unit_price) }}" required>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="items[{{ $index }}][tax_percentage]" class="form-control item-tax" 
                                                               step="0.01" min="0" max="100" value="{{ old('items.' . $index . '.tax_percentage', $item->tax_percentage) }}">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control item-amount" readonly>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-danger remove-item">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <button type="button" class="btn btn-sm btn-primary" id="addItem">
                                        <i class="fas fa-plus"></i> Add Item
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Totals -->
                        <div class="row mt-4">
                            <div class="col-md-8"></div>
                            <div class="col-md-4">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Subtotal:</strong></td>
                                        <td class="text-right"><span id="subtotal">0.00</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tax:</strong></td>
                                        <td class="text-right"><span id="tax-amount">0.00</span></td>
                                    </tr>
                                    <tr class="table-primary">
                                        <td><strong>Total:</strong></td>
                                        <td class="text-right"><strong><span id="total">0.00</span></strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes', $quotation->notes) }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="terms_conditions">Terms & Conditions</label>
                                    <textarea name="terms_conditions" id="terms_conditions" class="form-control" rows="3">{{ old('terms_conditions', $quotation->terms_conditions) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="footer_text">Footer Text</label>
                                    <textarea name="footer_text" id="footer_text" class="form-control" rows="2">{{ old('footer_text', $quotation->footer_text) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6"></div>
                            <div class="col-md-6 text-right">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Quotation
                                </button>
                                <a href="{{ route('billing.quotations.show', $quotation) }}" class="btn btn-secondary ml-2">Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let itemIndex = {{ $quotation->items->count() }};
    
    // Add new item row
    document.getElementById('addItem').addEventListener('click', function() {
        const tbody = document.getElementById('itemsTableBody');
        const row = document.createElement('tr');
        row.className = 'item-row';
        row.innerHTML = `
            <td>
                <select name="items[${itemIndex}][product_service_id]" class="form-control form-control-sm" 
                        onchange="selectProduct(this, ${itemIndex})">
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
                <small class="text-muted">Or leave empty to enter custom item</small>
            </td>
            <td>
                <input type="text" name="items[${itemIndex}][item_name]" class="form-control item-name" placeholder="Item name" required>
                <textarea name="items[${itemIndex}][description]" class="form-control mt-1 item-description" rows="2" placeholder="Description (optional)"></textarea>
            </td>
            <td>
                <input type="number" name="items[${itemIndex}][quantity]" class="form-control item-quantity" step="0.01" min="0.01" value="1" required>
            </td>
            <td>
                <input type="number" name="items[${itemIndex}][unit_price]" class="form-control item-price" step="0.01" min="0" required>
            </td>
            <td>
                <input type="number" name="items[${itemIndex}][tax_percentage]" class="form-control item-tax" step="0.01" min="0" max="100" value="{{ $settings['default_tax_rate'] ?? 18 }}">
            </td>
            <td>
                <input type="text" class="form-control item-amount" readonly>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger remove-item">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
        
        // Add event listeners to new row
        addRowEventListeners(row);
        updateRemoveButtons();
        itemIndex++;
    });
    
    // Remove item row
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            e.target.closest('tr').remove();
            updateRemoveButtons();
            calculateTotals();
        }
    });
    
    // Add event listeners to existing rows
    document.querySelectorAll('.item-row').forEach(addRowEventListeners);
    
    function addRowEventListeners(row) {
        const quantity = row.querySelector('.item-quantity');
        const price = row.querySelector('.item-price');
        const tax = row.querySelector('.item-tax');
        
        [quantity, price, tax].forEach(input => {
            input.addEventListener('input', calculateRowTotal);
        });
    }
    
    function calculateRowTotal(e) {
        const row = e.target.closest('tr');
        const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        const taxRate = parseFloat(row.querySelector('.item-tax').value) || 0;
        
        const subtotal = quantity * price;
        const taxAmount = subtotal * (taxRate / 100);
        const total = subtotal + taxAmount;
        
        row.querySelector('.item-amount').value = total.toFixed(2);
        calculateTotals();
    }
    
    function calculateTotals() {
        let subtotal = 0;
        let taxTotal = 0;
        
        document.querySelectorAll('.item-row').forEach(row => {
            const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const taxRate = parseFloat(row.querySelector('.item-tax').value) || 0;
            
            const lineSubtotal = quantity * price;
            const lineTax = lineSubtotal * (taxRate / 100);
            
            subtotal += lineSubtotal;
            taxTotal += lineTax;
        });
        
        const total = subtotal + taxTotal;
        
        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('tax-amount').textContent = taxTotal.toFixed(2);
        document.getElementById('total').textContent = total.toFixed(2);
    }
    
    function updateRemoveButtons() {
        const rows = document.querySelectorAll('.item-row');
        rows.forEach((row, index) => {
            const removeBtn = row.querySelector('.remove-item');
            removeBtn.disabled = rows.length <= 1;
        });
    }
    
    // Initial calculation
    calculateTotals();
    updateRemoveButtons();
});

// Product selection function
function selectProduct(selectElement, index) {
    const option = selectElement.selectedOptions[0];
    
    if (option.value) {
        // Get the row
        const row = selectElement.closest('tr');
        
        // Populate the fields
        row.querySelector(`input[name="items[${index}][item_name]"]`).value = option.dataset.name || '';
        row.querySelector(`textarea[name="items[${index}][description]"]`).value = option.dataset.description || '';
        row.querySelector(`input[name="items[${index}][unit_price]"]`).value = parseFloat(option.dataset.unitPrice || 0).toFixed(2);
        row.querySelector(`input[name="items[${index}][tax_percentage]"]`).value = parseFloat(option.dataset.taxRate || 0).toFixed(2);
        
        // Calculate totals
        calculateRowTotal({ target: row.querySelector('.item-quantity') });
    }
}
</script>
@endsection
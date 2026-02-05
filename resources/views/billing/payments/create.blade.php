@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>Record Payment</h1>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('billing.payments.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Payments
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Payment Details</h3>
                    </div>
                    <div class="block-content">
                        <form method="POST" action="{{ route('billing.payments.store') }}">
                            @csrf

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="document_id">Select Invoice <span class="text-danger">*</span></label>
                                        <select name="document_id" id="document_id" class="form-control @error('document_id') is-invalid @enderror" required>
                                            <option value="">Choose an outstanding invoice...</option>
                                            @foreach($outstandingDocuments as $doc)
                                                <option value="{{ $doc->id }}"
                                                        data-client="{{ $doc->client->company_name }}"
                                                        data-amount="{{ $doc->total_amount }}"
                                                        data-balance="{{ $doc->balance_amount }}"
                                                        data-currency="{{ $doc->currency_code }}"
                                                        {{ old('document_id', $document?->id) == $doc->id ? 'selected' : '' }}>
                                                    {{ $doc->document_number }} - {{ $doc->client->company_name }}
                                                    (Balance: {{ $doc->currency_code }} {{ number_format($doc->balance_amount, 2) }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('document_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_date">Payment Date <span class="text-danger">*</span></label>
                                        <input type="text" name="payment_date" id="payment_date"
                                               class="form-control datepicker @error('payment_date') is-invalid @enderror"
                                               value="{{ old('payment_date', date('Y-m-d')) }}" required>
                                        @error('payment_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="amount">Payment Amount <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text" id="currency-code">TZS</span>
                                            </div>
                                            <input type="number" name="amount" id="amount" step="0.01" min="0.01"
                                                   class="form-control @error('amount') is-invalid @enderror"
                                                   value="{{ old('amount') }}" required>
                                        </div>
                                        <small class="form-text text-muted">Maximum amount: <span id="max-amount">0.00</span></small>
                                        @error('amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_method">Payment Method <span class="text-danger">*</span></label>
                                        <select name="payment_method" id="payment_method"
                                                class="form-control @error('payment_method') is-invalid @enderror" required>
                                            <option value="">Select payment method...</option>
                                            <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                            <option value="cheque" {{ old('payment_method') == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                            <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                            <option value="credit_card" {{ old('payment_method') == 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                                            <option value="mobile_payment" {{ old('payment_method') == 'mobile_payment' ? 'selected' : '' }}>Mobile Payment</option>
                                            <option value="other" {{ old('payment_method') == 'other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                        @error('payment_method')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="reference_number">Reference Number</label>
                                        <input type="text" name="reference_number" id="reference_number"
                                               class="form-control @error('reference_number') is-invalid @enderror"
                                               value="{{ old('reference_number') }}"
                                               placeholder="Transaction ID, Cheque number, etc.">
                                        @error('reference_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="notes">Notes</label>
                                        <textarea name="notes" id="notes" rows="3"
                                                  class="form-control @error('notes') is-invalid @enderror"
                                                  placeholder="Additional payment notes...">{{ old('notes') }}</textarea>
                                        @error('notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-success">
                                    <i class="fa fa-check"></i> Record Payment
                                </button>
                                <a href="{{ route('billing.payments.index') }}" class="btn btn-secondary">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Invoice Details Card -->
                <div class="block block-themed" id="invoice-details" style="display: none;">
                    <div class="block-header">
                        <h3 class="block-title">Invoice Details</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Client:</strong></td>
                                <td id="invoice-client">-</td>
                            </tr>
                            <tr>
                                <td><strong>Total Amount:</strong></td>
                                <td id="invoice-total">-</td>
                            </tr>
                            <tr>
                                <td><strong>Outstanding Balance:</strong></td>
                                <td id="invoice-balance" class="text-danger">-</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Payment Methods Info -->
                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Payment Methods</h3>
                    </div>
                    <div class="block-content">
                        <small class="text-muted">
                            <ul class="list-unstyled">
                                <li><strong>Cash:</strong> Direct cash payment</li>
                                <li><strong>Cheque:</strong> Bank cheque payment</li>
                                <li><strong>Bank Transfer:</strong> Direct bank transfer</li>
                                <li><strong>Credit Card:</strong> Card payment</li>
                                <li><strong>Mobile Payment:</strong> M-Pesa, etc.</li>
                                <li><strong>Other:</strong> Alternative methods</li>
                            </ul>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const documentSelect = document.getElementById('document_id');
    const amountInput = document.getElementById('amount');
    const currencyCode = document.getElementById('currency-code');
    const maxAmount = document.getElementById('max-amount');
    const invoiceDetails = document.getElementById('invoice-details');
    const invoiceClient = document.getElementById('invoice-client');
    const invoiceTotal = document.getElementById('invoice-total');
    const invoiceBalance = document.getElementById('invoice-balance');

    documentSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];

        if (this.value) {
            const client = selectedOption.dataset.client;
            const amount = parseFloat(selectedOption.dataset.amount);
            const balance = parseFloat(selectedOption.dataset.balance);
            const currency = selectedOption.dataset.currency || 'TZS';

            // Update currency
            currencyCode.textContent = currency;

            // Update max amount
            maxAmount.textContent = balance.toFixed(2);
            amountInput.max = balance;
            amountInput.value = balance.toFixed(2);

            // Show invoice details
            invoiceClient.textContent = client;
            invoiceTotal.textContent = currency + ' ' + amount.toFixed(2);
            invoiceBalance.textContent = currency + ' ' + balance.toFixed(2);
            invoiceDetails.style.display = 'block';
        } else {
            // Hide invoice details
            invoiceDetails.style.display = 'none';
            amountInput.value = '';
            amountInput.max = '';
            currencyCode.textContent = 'TZS';
            maxAmount.textContent = '0.00';
        }
    });

    // Trigger change event if document is pre-selected
    if (documentSelect.value) {
        documentSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endsection

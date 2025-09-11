@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>Edit Payment {{ $payment->payment_number }}</h1>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('billing.payments.show', $payment) }}" class="btn btn-info">
                        <i class="fa fa-eye"></i> View Payment
                    </a>
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
                        <form method="POST" action="{{ route('billing.payments.update', $payment) }}">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_number">Payment Number</label>
                                        <input type="text" id="payment_number" class="form-control" 
                                               value="{{ $payment->payment_number }}" readonly>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_date">Payment Date <span class="text-danger">*</span></label>
                                        <input type="text" name="payment_date" id="payment_date" 
                                               class="form-control datepicker @error('payment_date') is-invalid @enderror" 
                                               value="{{ old('payment_date', $payment->payment_date->format('Y-m-d')) }}" required>
                                        @error('payment_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="document">Invoice</label>
                                        <input type="text" id="document" class="form-control" 
                                               value="{{ $payment->document->document_number }} - {{ $payment->document->client->company_name }}" readonly>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="amount">Payment Amount <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">{{ $payment->document->currency_code ?? 'TZS' }}</span>
                                            </div>
                                            <input type="number" name="amount" id="amount" step="0.01" min="0.01"
                                                   class="form-control @error('amount') is-invalid @enderror" 
                                                   value="{{ old('amount', number_format($payment->amount, 2, '.', '')) }}" required>
                                        </div>
                                        <small class="form-text text-muted">
                                            Current balance: {{ $payment->document->currency_code ?? 'TZS' }} {{ number_format($payment->document->balance_amount + $payment->amount, 2) }}
                                        </small>
                                        @error('amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_method">Payment Method <span class="text-danger">*</span></label>
                                        <select name="payment_method" id="payment_method" 
                                                class="form-control @error('payment_method') is-invalid @enderror" required>
                                            <option value="">Select payment method...</option>
                                            <option value="cash" {{ old('payment_method', $payment->payment_method) == 'cash' ? 'selected' : '' }}>Cash</option>
                                            <option value="cheque" {{ old('payment_method', $payment->payment_method) == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                            <option value="bank_transfer" {{ old('payment_method', $payment->payment_method) == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                            <option value="credit_card" {{ old('payment_method', $payment->payment_method) == 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                                            <option value="mobile_payment" {{ old('payment_method', $payment->payment_method) == 'mobile_payment' ? 'selected' : '' }}>Mobile Payment</option>
                                            <option value="other" {{ old('payment_method', $payment->payment_method) == 'other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                        @error('payment_method')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="reference_number">Reference Number</label>
                                        <input type="text" name="reference_number" id="reference_number" 
                                               class="form-control @error('reference_number') is-invalid @enderror" 
                                               value="{{ old('reference_number', $payment->reference_number) }}" 
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
                                                  placeholder="Additional payment notes...">{{ old('notes', $payment->notes) }}</textarea>
                                        @error('notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> Update Payment
                                </button>
                                <a href="{{ route('billing.payments.show', $payment) }}" class="btn btn-secondary">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Payment Info -->
                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Payment Information</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge badge-{{ $payment->status == 'completed' ? 'success' : 'warning' }}">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Created:</strong></td>
                                <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @if($payment->updated_at != $payment->created_at)
                                <tr>
                                    <td><strong>Last Modified:</strong></td>
                                    <td>{{ $payment->updated_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td><strong>Received By:</strong></td>
                                <td>{{ $payment->receiver->name ?? 'System' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Invoice Details -->
                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Invoice Details</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Invoice #:</strong></td>
                                <td>
                                    <a href="{{ route('billing.invoices.show', $payment->document) }}">
                                        {{ $payment->document->document_number }}
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Client:</strong></td>
                                <td>{{ $payment->document->client->company_name }}</td>
                            </tr>
                            <tr>
                                <td><strong>Invoice Total:</strong></td>
                                <td>{{ $payment->document->currency_code ?? 'TZS' }} {{ number_format($payment->document->total_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Amount Paid:</strong></td>
                                <td>{{ $payment->document->currency_code ?? 'TZS' }} {{ number_format($payment->document->paid_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Balance:</strong></td>
                                <td class="text-{{ $payment->document->balance_amount > 0 ? 'danger' : 'success' }}">
                                    {{ $payment->document->currency_code ?? 'TZS' }} {{ number_format($payment->document->balance_amount, 2) }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Warning -->
                <div class="alert alert-warning">
                    <i class="fa fa-warning"></i>
                    <strong>Warning:</strong> Editing this payment will recalculate the invoice balance and status.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
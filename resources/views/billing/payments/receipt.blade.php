@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>Payment Receipt - {{ $payment->payment_number }}</h1>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('billing.payments.receipt.pdf', $payment) }}" class="btn btn-success" target="_blank">
                        <i class="fa fa-download"></i> Download PDF
                    </a>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fa fa-print"></i> Print Receipt
                    </button>
                    <a href="{{ route('billing.payments.show', $payment) }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Payment
                    </a>
                </div>
            </div>
        </div>

        <div class="block block-themed">
            <div class="block-content">
                @include('components.headed_paper')

            <div class="receipt-header text-center">
                <h2 class="mb-0">PAYMENT RECEIPT</h2>
                <p class="mb-0">Receipt No: {{ $payment->payment_number }}</p>
            </div>

            <!-- Payment Details -->
            <div class="row">
                <div class="col-md-6">
                    <div class="receipt-details">
                        <h5>Received From:</h5>
                        <p class="mb-1"><strong>{{ $payment->client->company_name }}</strong></p>
                        @if($payment->client->contact_person)
                            <p class="mb-1">{{ $payment->client->contact_person }}</p>
                        @endif
                        <p class="mb-1">{{ $payment->client->full_billing_address }}</p>
                        @if($payment->client->phone)
                            <p class="mb-1">Phone: {{ $payment->client->phone }}</p>
                        @endif
                        @if($payment->client->email)
                            <p class="mb-0">Email: {{ $payment->client->email }}</p>
                        @endif
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="receipt-details">
                        <h5>Payment Details:</h5>
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td><strong>Receipt No:</strong></td>
                                <td>{{ $payment->payment_number }}</td>
                            </tr>
                            <tr>
                                <td><strong>Date:</strong></td>
                                <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Time:</strong></td>
                                <td>{{ $payment->created_at->format('H:i:s') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Method:</strong></td>
                                <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                            </tr>
                            @if($payment->reference_number)
                                <tr>
                                    <td><strong>Reference:</strong></td>
                                    <td>{{ $payment->reference_number }}</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            <!-- Invoice Information -->
            <div class="row">
                <div class="col-12">
                    <h5>Payment For:</h5>
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice Number</th>
                                <th>Invoice Date</th>
                                <th>Invoice Amount</th>
                                <th>Previous Paid</th>
                                <th>This Payment</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $payment->document->document_number }}</td>
                                <td>{{ $payment->document->issue_date->format('d/m/Y') }}</td>
                                <td class="text-end">{{ $payment->document->currency_code ?? 'TZS' }} {{ number_format($payment->document->total_amount, 2) }}</td>
                                <td class="text-end">{{ $payment->document->currency_code ?? 'TZS' }} {{ number_format($payment->document->paid_amount - $payment->amount, 2) }}</td>
                                <td class="text-end amount-paid">{{ $payment->document->currency_code ?? 'TZS' }} {{ number_format($payment->amount, 2) }}</td>
                                <td class="text-end">{{ $payment->document->currency_code ?? 'TZS' }} {{ number_format($payment->document->balance_amount, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Amount in Words -->
            <div class="row">
                <div class="col-12">
                    <div class="receipt-details">
                        <h6>Amount in Words:</h6>
                        <p class="mb-0"><em>{{ ucfirst(\Illuminate\Support\Str::title(number_format($payment->amount, 2))) }} {{ $payment->document->currency_code ?? 'TZS' }} Only</em></p>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            @if($payment->notes)
                <div class="row">
                    <div class="col-12">
                        <h6>Notes:</h6>
                        <p>{{ $payment->notes }}</p>
                    </div>
                </div>
            @endif

            <!-- Payment Status -->
            <div class="row">
                <div class="col-12 text-center">
                    <div class="alert alert-{{ $payment->status == 'completed' ? 'success' : 'warning' }}">
                        <strong>Payment Status: {{ ucfirst($payment->status) }}</strong>
                        @if($payment->status == 'completed')
                            <br><small>This payment has been successfully processed and recorded.</small>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Signatures -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="text-center">
                        <div style="border-top: 1px solid #333; width: 200px; margin: 0 auto;">
                            <p class="mt-2 mb-0"><strong>Customer Signature</strong></p>
                            <small class="text-muted">{{ $payment->client->contact_person ?? $payment->client->company_name }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="text-center">
                        <div style="border-top: 1px solid #333; width: 200px; margin: 0 auto;">
                            <p class="mt-2 mb-0"><strong>Received By</strong></p>
                            <small class="text-muted">{{ $payment->receiver->name ?? 'System' }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="receipt-footer">
                <p class="mb-1"><strong>Thank you for your payment!</strong></p>
                <p class="mb-0">Receipt generated on {{ now()->format('d/m/Y H:i:s') }}</p>
                @if($payment->document->balance_amount > 0)
                    <p class="text-danger mb-0">
                        <strong>Outstanding Balance: {{ $payment->document->currency_code ?? 'TZS' }} {{ number_format($payment->document->balance_amount, 2) }}</strong>
                    </p>
                @else
                    <p class="text-success mb-0">
                        <strong>Invoice Fully Paid</strong>
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
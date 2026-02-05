@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>Payment {{ $payment->payment_number }}</h1>
                    <span class="badge badge-{{ $payment->status === 'completed' ? 'success' : ($payment->status === 'voided' ? 'danger' : 'warning') }} badge-lg">
                        {{ ucfirst(str_replace('_', ' ', $payment->status)) }}
                    </span>
                </div>
                <div class="col-md-6 text-right">
                    @if($payment->status === 'completed')
                        <a href="{{ route('billing.payments.edit', $payment) }}" class="btn btn-primary">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        
                        <a href="{{ route('billing.payments.receipt.pdf', $payment) }}" class="btn btn-secondary" target="_blank">
                            <i class="fa fa-download"></i> Receipt PDF
                        </a>
                    @endif

                    <a href="{{ route('billing.payments.index') }}" class="btn btn-info">
                        <i class="fa fa-arrow-left"></i> Back to Payments
                    </a>

                    @if($payment->status === 'completed')
                        <div class="btn-group">
                            <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown">
                                More Actions
                            </button>
                            <div class="dropdown-menu">
                                <form action="{{ route('billing.payments.void', $payment) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Are you sure you want to void this payment?')">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fa fa-ban"></i> Void Payment
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Payment Information -->
            <div class="col-md-6">
                <div class="block block-rounded">
                    <div class="block-header">
                        <h3 class="block-title">
                            <i class="fa fa-credit-card"></i>
                            Payment Information
                        </h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-vcenter">
                            <tr>
                                <td width="35%"><strong>Payment Number:</strong></td>
                                <td>{{ $payment->payment_number }}</td>
                            </tr>
                            <tr>
                                <td><strong>Payment Date:</strong></td>
                                <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Amount:</strong></td>
                                <td>
                                    <strong class="text-success">
                                        {{ $payment->document->currency_code ?? 'TZS' }} {{ number_format($payment->amount, 2) }}
                                    </strong>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Payment Method:</strong></td>
                                <td>
                                    <span class="badge badge-info">
                                        {{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}
                                    </span>
                                </td>
                            </tr>
                            @if($payment->reference_number)
                                <tr>
                                    <td><strong>Reference Number:</strong></td>
                                    <td>{{ $payment->reference_number }}</td>
                                </tr>
                            @endif
                            @if($payment->bank_name)
                                <tr>
                                    <td><strong>Bank Name:</strong></td>
                                    <td>{{ $payment->bank_name }}</td>
                                </tr>
                            @endif
                            @if($payment->cheque_number)
                                <tr>
                                    <td><strong>Cheque Number:</strong></td>
                                    <td>{{ $payment->cheque_number }}</td>
                                </tr>
                            @endif
                            @if($payment->transaction_id)
                                <tr>
                                    <td><strong>Transaction ID:</strong></td>
                                    <td>{{ $payment->transaction_id }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td><strong>Received By:</strong></td>
                                <td>{{ $payment->receiver->name ?? 'System' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge badge-{{ $payment->status === 'completed' ? 'success' : ($payment->status === 'voided' ? 'danger' : 'warning') }}">
                                        {{ ucfirst(str_replace('_', ' ', $payment->status)) }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Document & Client Information -->
            <div class="col-md-6">
                <div class="block block-rounded">
                    <div class="block-header">
                        <h3 class="block-title">
                            <i class="fa fa-file-invoice"></i>
                            Related Document
                        </h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-vcenter">
                            <tr>
                                <td width="35%"><strong>Document Type:</strong></td>
                                <td>
                                    <span class="badge badge-primary">
                                        {{ ucfirst($payment->document->document_type) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Document Number:</strong></td>
                                <td>
                                    <a href="{{ route('billing.' . $payment->document->document_type . 's.show', $payment->document) }}" 
                                       class="font-weight-bold text-primary">
                                        {{ $payment->document->document_number }}
                                        <i class="fa fa-external-link-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Document Date:</strong></td>
                                <td>{{ $payment->document->issue_date->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Total Amount:</strong></td>
                                <td>{{ $payment->document->currency_code ?? 'TZS' }} {{ number_format($payment->document->total_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Paid Amount:</strong></td>
                                <td>{{ $payment->document->currency_code ?? 'TZS' }} {{ number_format($payment->document->paid_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Balance:</strong></td>
                                <td>
                                    @if($payment->document->balance_amount > 0)
                                        <span class="text-warning font-weight-bold">
                                            {{ $payment->document->currency_code ?? 'TZS' }} {{ number_format($payment->document->balance_amount, 2) }}
                                        </span>
                                    @else
                                        <span class="text-success font-weight-bold">Fully Paid</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Client Information -->
                <div class="block block-rounded">
                    <div class="block-header">
                        <h3 class="block-title">
                            <i class="fa fa-user"></i>
                            Client Information
                        </h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-vcenter">
                            <tr>
                                <td width="35%"><strong>Client:</strong></td>
                                <td>{{ $payment->document->client->first_name }} {{ $payment->document->client->last_name }}</td>
                            </tr>
                            @if($payment->document->client->email)
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>{{ $payment->document->client->email }}</td>
                                </tr>
                            @endif
                            @if($payment->document->client->phone_number)
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td>{{ $payment->document->client->phone_number }}</td>
                                </tr>
                            @endif
                            @if($payment->document->client->email)
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>{{ $payment->document->client->email }}</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes -->
        @if($payment->notes)
            <div class="row">
                <div class="col-12">
                    <div class="block block-rounded">
                        <div class="block-header">
                            <h3 class="block-title">
                                <i class="fa fa-sticky-note"></i>
                                Notes
                            </h3>
                        </div>
                        <div class="block-content">
                            <div class="alert alert-info">
                                {{ $payment->notes }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Payment History for this Document -->
        @if($payment->document->payments->count() > 1)
            <div class="row">
                <div class="col-12">
                    <div class="block block-rounded">
                        <div class="block-header">
                            <h3 class="block-title">
                                <i class="fa fa-history"></i>
                                Payment History for {{ $payment->document->document_number }}
                            </h3>
                        </div>
                        <div class="block-content">
                            <div class="table-responsive">
                                <table class="table table-vcenter">
                                    <thead>
                                        <tr>
                                            <th>Payment #</th>
                                            <th>Date</th>
                                            <th>Method</th>
                                            <th class="text-right">Amount</th>
                                            <th>Status</th>
                                            <th>Received By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($payment->document->payments->sortBy('payment_date') as $documentPayment)
                                            <tr class="{{ $documentPayment->id === $payment->id ? 'table-warning' : '' }}">
                                                <td>
                                                    <a href="{{ route('billing.payments.show', $documentPayment) }}" 
                                                       class="{{ $documentPayment->id === $payment->id ? 'font-weight-bold' : '' }}">
                                                        {{ $documentPayment->payment_number }}
                                                        @if($documentPayment->id === $payment->id)
                                                            <span class="badge badge-sm badge-info">Current</span>
                                                        @endif
                                                    </a>
                                                </td>
                                                <td>{{ $documentPayment->payment_date->format('d/m/Y') }}</td>
                                                <td>{{ ucwords(str_replace('_', ' ', $documentPayment->payment_method)) }}</td>
                                                <td class="text-right">{{ $payment->document->currency_code ?? 'TZS' }} {{ number_format($documentPayment->amount, 2) }}</td>
                                                <td>
                                                    <span class="badge badge-{{ $documentPayment->status === 'completed' ? 'success' : ($documentPayment->status === 'voided' ? 'danger' : 'warning') }}">
                                                        {{ ucfirst($documentPayment->status) }}
                                                    </span>
                                                </td>
                                                <td>{{ $documentPayment->receiver->name ?? 'System' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-active">
                                            <th colspan="3">Total Payments:</th>
                                            <th class="text-right">{{ $payment->document->currency_code ?? 'TZS' }} {{ number_format($payment->document->payments->where('status', 'completed')->sum('amount'), 2) }}</th>
                                            <th colspan="2"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
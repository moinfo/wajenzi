@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>Payments</h1>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('billing.payments.create') }}" class="btn btn-success">
                        <i class="fa fa-plus"></i> Record Payment
                    </a>
                </div>
            </div>
        </div>

        <div class="block block-themed">
            <div class="block-content">

                <!-- Filters -->
                <div class="row no-print m-t-10">
                    <div class="col-md-12">
                        <div class="card-box">
                            <form method="GET" action="{{ route('billing.payments.index') }}" class="row">
                                <div class="col-md-3">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Status</span>
                                        </div>
                                        <select name="status" class="form-control">
                                            <option value="">All Statuses</option>
                                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="voided" {{ request('status') == 'voided' ? 'selected' : '' }}>Voided</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Client</span>
                                        </div>
                                        <select name="client_id" class="form-control">
                                            <option value="">All Clients</option>
                                            @foreach($clients as $client)
                                                <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                                    {{ $client->company_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">From</span>
                                        </div>
                                        <input type="text" name="from_date" class="form-control datepicker" value="{{ request('from_date') }}">
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">To</span>
                                        </div>
                                        <input type="text" name="to_date" class="form-control datepicker" value="{{ request('to_date') }}">
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="{{ route('billing.payments.index') }}" class="btn btn-secondary">Clear</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Payments Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Payment #</th>
                                <th>Document</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Received By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                <tr>
                                    <td>
                                        <a href="{{ route('billing.payments.show', $payment) }}" class="text-primary font-weight-bold">
                                            {{ $payment->payment_number }}
                                        </a>
                                    </td>
                                    <td>
                                        @if($payment->document)
                                            <a href="{{ route('billing.invoices.show', $payment->document) }}">
                                                {{ $payment->document->document_number }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $payment->client->company_name }}</strong>
                                        @if($payment->client->contact_person)
                                            <br><small class="text-muted">{{ $payment->client->contact_person }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                    <td class="text-right">
                                        <strong>{{ $payment->document->currency_code ?? 'TZS' }} {{ number_format($payment->amount, 2) }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            {{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $statusClass = match($payment->status) {
                                                'completed' => 'badge-success',
                                                'pending' => 'badge-warning',
                                                'voided' => 'badge-danger',
                                                default => 'badge-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">
                                            {{ ucfirst($payment->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $payment->receiver->name ?? 'System' }}
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('billing.payments.show', $payment) }}"
                                               class="btn btn-sm btn-outline-primary" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>

                                            @if($payment->status !== 'voided')
                                                <a href="{{ route('billing.payments.edit', $payment) }}"
                                                   class="btn btn-sm btn-outline-secondary" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                            @endif

                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split"
                                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <span class="sr-only">Toggle Dropdown</span>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <a class="dropdown-item" href="{{ route('billing.payments.receipt', $payment) }}" target="_blank">
                                                        <i class="fa fa-eye"></i> View Receipt
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('billing.payments.receipt.pdf', $payment) }}" target="_blank">
                                                        <i class="fa fa-file-pdf"></i> Download PDF
                                                    </a>

                                                    @if($payment->status === 'completed')
                                                        <div class="dropdown-divider"></div>
                                                        <form action="{{ route('billing.payments.void', $payment->id) }}"
                                                              method="POST" class="d-inline"
                                                              onsubmit="return confirm('Are you sure you want to void this payment?')">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="dropdown-item text-warning">
                                                                <i class="fa fa-ban"></i> Void Payment
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fa fa-money fa-3x mb-3"></i>
                                            <h5>No payments found</h5>
                                            <p>Record your first payment to get started</p>
                                            <a href="{{ route('billing.payments.create') }}" class="btn btn-success">
                                                <i class="fa fa-plus"></i> Record Payment
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($payments->hasPages())
                    <div class="text-center">
                        {{ $payments->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

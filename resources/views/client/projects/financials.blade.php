@extends('layouts.client')

@section('title', 'Financials - ' . $project->project_name)

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <a href="{{ route('client.dashboard') }}" class="text-muted text-decoration-none" style="font-size: 0.8125rem;">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
            <h4 class="fw-bold mt-2 mb-0">{{ $project->project_name }}</h4>
        </div>
    </div>

    @include('client.partials.project_tabs')

    <!-- Financial Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label mb-1">Contract Value</div>
                <div class="stat-value" style="color: #2563EB;">TZS {{ number_format($summary['contract_value'], 2) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label mb-1">Total Invoiced</div>
                <div class="stat-value" style="color: #D97706;">TZS {{ number_format($summary['total_invoiced'], 2) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label mb-1">Total Paid</div>
                <div class="stat-value" style="color: #16A34A;">TZS {{ number_format($summary['total_paid'], 2) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label mb-1">Balance Due</div>
                <div class="stat-value" style="color: {{ $summary['balance_due'] > 0 ? '#DC2626' : '#16A34A' }};">
                    TZS {{ number_format($summary['balance_due'], 2) }}
                </div>
            </div>
        </div>
    </div>

    <!-- Invoices -->
    <div class="portal-card mb-3">
        <div class="portal-card-header">
            <h5><i class="fas fa-file-invoice me-2"></i>Invoices</h5>
        </div>
        @if($invoices->count())
            <div class="portal-card-body p-0">
                <div class="table-responsive">
                    <table class="portal-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Invoice Number</th>
                                <th>Due Date</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                                @php
                                    $paid = $invoice->payments->sum('amount');
                                    $balance = $invoice->amount - $paid;
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">{{ $invoice->invoice_number }}</td>
                                    <td>{{ $invoice->due_date?->format('M d, Y') ?? '-' }}</td>
                                    <td class="text-end">{{ number_format($invoice->amount ?? 0, 2) }}</td>
                                    <td class="text-end" style="color: #16A34A;">{{ number_format($paid, 2) }}</td>
                                    <td class="text-end fw-semibold" style="color: {{ $balance > 0 ? '#DC2626' : '#16A34A' }};">
                                        {{ number_format($balance, 2) }}
                                    </td>
                                    <td>
                                        @php
                                            $invStatusMap = ['paid' => 'success', 'partial' => 'warning', 'unpaid' => 'danger', 'overdue' => 'danger', 'sent' => 'info'];
                                        @endphp
                                        <span class="status-badge {{ $invStatusMap[$invoice->status] ?? 'secondary' }}">
                                            {{ ucfirst($invoice->status ?? 'N/A') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background: var(--wajenzi-gray-50);">
                                <td colspan="3" class="fw-bold text-end">Totals</td>
                                <td class="text-end fw-bold">TZS {{ number_format($invoices->sum('amount'), 2) }}</td>
                                <td class="text-end fw-bold" style="color: #16A34A;">TZS {{ number_format($summary['total_paid'], 2) }}</td>
                                <td class="text-end fw-bold" style="color: {{ $summary['balance_due'] > 0 ? '#DC2626' : '#16A34A' }};">
                                    TZS {{ number_format($summary['balance_due'], 2) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @else
            <div class="portal-card-body text-center py-4 text-muted">
                <i class="fas fa-file-invoice fa-2x mb-2"></i>
                <p class="mb-0">No invoices have been created yet.</p>
            </div>
        @endif
    </div>

    <!-- Payments -->
    @php
        $allPayments = $invoices->flatMap(fn($inv) => $inv->payments->map(fn($p) => $p->setAttribute('invoice_number', $inv->invoice_number)));
    @endphp
    @if($allPayments->count())
        <div class="portal-card">
            <div class="portal-card-header">
                <h5><i class="fas fa-money-check-alt me-2"></i>Payments</h5>
            </div>
            <div class="portal-card-body p-0">
                <div class="table-responsive">
                    <table class="portal-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Invoice</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allPayments->sortByDesc('payment_date') as $payment)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">{{ $payment->invoice_number }}</td>
                                    <td>{{ $payment->payment_date?->format('M d, Y') ?? '-' }}</td>
                                    <td>{{ ucfirst($payment->payment_method ?? '-') }}</td>
                                    <td>{{ $payment->reference_number ?? '-' }}</td>
                                    <td class="text-end fw-semibold" style="color: #16A34A;">TZS {{ number_format($payment->amount ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection

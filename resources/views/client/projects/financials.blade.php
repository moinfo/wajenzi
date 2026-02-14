@extends('layouts.client')

@section('title', 'Financials - ' . $project->project_name)

@section('content')
    <div style="margin-bottom: var(--m-md);">
        <a href="{{ route('client.dashboard') }}" class="m-dimmed m-text-xs" style="text-decoration: none;">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
        <h1 class="m-title m-title-3" style="margin-top: var(--m-sm); margin-bottom: 0;">{{ $project->project_name }}</h1>
    </div>

    @include('client.partials.project_tabs')

    <!-- Financial Summary -->
    <div class="m-stat-grid" style="margin-bottom: var(--m-xl);">
        <div class="m-stat">
            <div class="m-stat-top">
                <span class="m-stat-title">Contract Value</span>
                <i class="fas fa-file-contract m-stat-icon"></i>
            </div>
            <div class="m-stat-value" style="font-size: 1.125rem;">TZS {{ number_format($summary['contract_value'], 2) }}</div>
        </div>
        <div class="m-stat">
            <div class="m-stat-top">
                <span class="m-stat-title">Total Invoiced</span>
                <i class="fas fa-file-invoice m-stat-icon"></i>
            </div>
            <div class="m-stat-value" style="font-size: 1.125rem;">TZS {{ number_format($summary['total_invoiced'], 2) }}</div>
        </div>
        <div class="m-stat">
            <div class="m-stat-top">
                <span class="m-stat-title">Total Paid</span>
                <i class="fas fa-check-circle m-stat-icon"></i>
            </div>
            <div class="m-stat-value" style="font-size: 1.125rem; color: var(--m-teal-6);">TZS {{ number_format($summary['total_paid'], 2) }}</div>
        </div>
        <div class="m-stat">
            <div class="m-stat-top">
                <span class="m-stat-title">Balance Due</span>
                <i class="fas fa-exclamation-circle m-stat-icon"></i>
            </div>
            <div class="m-stat-value" style="font-size: 1.125rem; color: {{ $summary['balance_due'] > 0 ? 'var(--m-red-6)' : 'var(--m-teal-6)' }};">
                TZS {{ number_format($summary['balance_due'], 2) }}
            </div>
        </div>
    </div>

    <!-- Invoices -->
    <div class="m-paper mb-3">
        <div class="m-paper-header">
            <h5><i class="fas fa-file-invoice me-2" style="color: var(--m-blue-6);"></i>Invoices</h5>
        </div>
        @if($invoices->count())
            <div style="padding: 0;">
                <div class="table-responsive">
                    <table class="m-table">
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
                                    <td class="m-fw-500">{{ $invoice->invoice_number }}</td>
                                    <td>{{ $invoice->due_date?->format('M d, Y') ?? '-' }}</td>
                                    <td class="text-end">{{ number_format($invoice->amount ?? 0, 2) }}</td>
                                    <td class="text-end" style="color: var(--m-teal-6);">{{ number_format($paid, 2) }}</td>
                                    <td class="text-end m-fw-600" style="color: {{ $balance > 0 ? 'var(--m-red-6)' : 'var(--m-teal-6)' }};">
                                        {{ number_format($balance, 2) }}
                                    </td>
                                    <td>
                                        @php
                                            $invMap = ['paid' => 'teal', 'partial' => 'yellow', 'unpaid' => 'red', 'overdue' => 'red', 'sent' => 'blue'];
                                        @endphp
                                        <span class="m-badge m-badge-{{ $invMap[$invoice->status] ?? 'gray' }}">
                                            {{ ucfirst($invoice->status ?? 'N/A') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background: var(--m-gray-0);">
                                <td colspan="3" class="m-fw-700 text-end">Totals</td>
                                <td class="text-end m-fw-700">TZS {{ number_format($invoices->sum('amount'), 2) }}</td>
                                <td class="text-end m-fw-700" style="color: var(--m-teal-6);">TZS {{ number_format($summary['total_paid'], 2) }}</td>
                                <td class="text-end m-fw-700" style="color: {{ $summary['balance_due'] > 0 ? 'var(--m-red-6)' : 'var(--m-teal-6)' }};">
                                    TZS {{ number_format($summary['balance_due'], 2) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @else
            <div class="m-paper-body" style="text-align: center; padding: 2rem;">
                <i class="fas fa-file-invoice" style="font-size: 2rem; color: var(--m-gray-3); margin-bottom: 0.5rem;"></i>
                <p class="m-text-sm m-dimmed" style="margin: 0;">No invoices have been created yet.</p>
            </div>
        @endif
    </div>

    <!-- Payments -->
    @php
        $allPayments = $invoices->flatMap(fn($inv) => $inv->payments->map(fn($p) => $p->setAttribute('invoice_number', $inv->invoice_number)));
    @endphp
    @if($allPayments->count())
        <div class="m-paper">
            <div class="m-paper-header">
                <h5><i class="fas fa-money-check-alt me-2" style="color: var(--m-teal-6);"></i>Payments</h5>
            </div>
            <div style="padding: 0;">
                <div class="table-responsive">
                    <table class="m-table">
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
                                    <td class="m-fw-500">{{ $payment->invoice_number }}</td>
                                    <td>{{ $payment->payment_date?->format('M d, Y') ?? '-' }}</td>
                                    <td>{{ ucfirst($payment->payment_method ?? '-') }}</td>
                                    <td>{{ $payment->reference_number ?? '-' }}</td>
                                    <td class="text-end m-fw-600" style="color: var(--m-teal-6);">TZS {{ number_format($payment->amount ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection

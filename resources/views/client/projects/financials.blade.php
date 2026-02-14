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

    {{-- ── Billing Invoices (modern billing module) ── --}}
    @if($billingInvoices->count())
        <div class="m-paper mb-3">
            <div class="m-paper-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h5><i class="fas fa-file-invoice me-2" style="color: var(--m-blue-6);"></i>Invoices</h5>
                <span class="m-badge m-badge-blue">{{ $billingInvoices->count() }}</span>
            </div>
            <div style="padding: 0;">
                <div class="table-responsive">
                    <table class="m-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Invoice No.</th>
                                <th>Date</th>
                                <th>Due Date</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Balance</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($billingInvoices as $inv)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="m-fw-500">{{ $inv->document_number }}</td>
                                    <td>{{ $inv->issue_date?->format('M d, Y') ?? '-' }}</td>
                                    <td>{{ $inv->due_date?->format('M d, Y') ?? '-' }}</td>
                                    <td class="text-end">{{ number_format($inv->total_amount ?? 0, 2) }}</td>
                                    <td class="text-end" style="color: var(--m-teal-6);">{{ number_format($inv->paid_amount ?? 0, 2) }}</td>
                                    <td class="text-end m-fw-600" style="color: {{ ($inv->balance_amount ?? 0) > 0 ? 'var(--m-red-6)' : 'var(--m-teal-6)' }};">
                                        {{ number_format($inv->balance_amount ?? 0, 2) }}
                                    </td>
                                    <td>
                                        @php
                                            $statusMap = [
                                                'paid' => 'teal', 'partial_paid' => 'yellow', 'overdue' => 'red',
                                                'sent' => 'blue', 'viewed' => 'blue', 'pending' => 'yellow',
                                                'accepted' => 'teal', 'rejected' => 'red',
                                            ];
                                        @endphp
                                        <span class="m-badge m-badge-{{ $statusMap[$inv->status] ?? 'gray' }}">
                                            {{ ucfirst(str_replace('_', ' ', $inv->status ?? 'N/A')) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('client.project.billing_pdf', [$project->id, $inv->id]) }}"
                                           class="m-btn m-btn-light m-btn-sm" style="height: 1.5rem; padding: 0 0.5rem; font-size: 0.6875rem;"
                                           title="Download PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background: var(--m-gray-0);">
                                <td colspan="4" class="m-fw-700 text-end">Totals</td>
                                <td class="text-end m-fw-700">TZS {{ number_format($billingInvoices->sum('total_amount'), 2) }}</td>
                                <td class="text-end m-fw-700" style="color: var(--m-teal-6);">TZS {{ number_format($billingInvoices->sum('paid_amount'), 2) }}</td>
                                <td class="text-end m-fw-700" style="color: {{ $billingInvoices->sum('balance_amount') > 0 ? 'var(--m-red-6)' : 'var(--m-teal-6)' }};">
                                    TZS {{ number_format($billingInvoices->sum('balance_amount'), 2) }}
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Quotations ── --}}
    @if($billingQuotes->count())
        <div class="m-paper mb-3">
            <div class="m-paper-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h5><i class="fas fa-file-alt me-2" style="color: var(--m-violet-6);"></i>Quotations</h5>
                <span class="m-badge m-badge-violet">{{ $billingQuotes->count() }}</span>
            </div>
            <div style="padding: 0;">
                <div class="table-responsive">
                    <table class="m-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Quote No.</th>
                                <th>Date</th>
                                <th>Valid Until</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($billingQuotes as $quote)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="m-fw-500">{{ $quote->document_number }}</td>
                                    <td>{{ $quote->issue_date?->format('M d, Y') ?? '-' }}</td>
                                    <td>{{ $quote->valid_until_date?->format('M d, Y') ?? '-' }}</td>
                                    <td class="text-end m-fw-600">{{ number_format($quote->total_amount ?? 0, 2) }}</td>
                                    <td>
                                        @php
                                            $qMap = ['sent' => 'blue', 'viewed' => 'blue', 'accepted' => 'teal', 'rejected' => 'red', 'pending' => 'yellow'];
                                        @endphp
                                        <span class="m-badge m-badge-{{ $qMap[$quote->status] ?? 'gray' }}">
                                            {{ ucfirst($quote->status ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('client.project.billing_pdf', [$project->id, $quote->id]) }}"
                                           class="m-btn m-btn-light m-btn-sm" style="height: 1.5rem; padding: 0 0.5rem; font-size: 0.6875rem;"
                                           title="Download PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Proforma Invoices ── --}}
    @if($billingProformas->count())
        <div class="m-paper mb-3">
            <div class="m-paper-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h5><i class="fas fa-file-invoice-dollar me-2" style="color: var(--m-orange-6);"></i>Proforma Invoices</h5>
                <span class="m-badge m-badge-orange">{{ $billingProformas->count() }}</span>
            </div>
            <div style="padding: 0;">
                <div class="table-responsive">
                    <table class="m-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Proforma No.</th>
                                <th>Date</th>
                                <th>Valid Until</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($billingProformas as $proforma)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="m-fw-500">{{ $proforma->document_number }}</td>
                                    <td>{{ $proforma->issue_date?->format('M d, Y') ?? '-' }}</td>
                                    <td>{{ $proforma->valid_until_date?->format('M d, Y') ?? '-' }}</td>
                                    <td class="text-end m-fw-600">{{ number_format($proforma->total_amount ?? 0, 2) }}</td>
                                    <td>
                                        @php
                                            $pMap = ['sent' => 'blue', 'viewed' => 'blue', 'accepted' => 'teal', 'rejected' => 'red', 'pending' => 'yellow'];
                                        @endphp
                                        <span class="m-badge m-badge-{{ $pMap[$proforma->status] ?? 'gray' }}">
                                            {{ ucfirst($proforma->status ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('client.project.billing_pdf', [$project->id, $proforma->id]) }}"
                                           class="m-btn m-btn-light m-btn-sm" style="height: 1.5rem; padding: 0 0.5rem; font-size: 0.6875rem;"
                                           title="Download PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Payments (from billing invoices) ── --}}
    @php
        $billingPayments = $billingInvoices->flatMap(fn($inv) => $inv->payments->map(fn($p) => $p->setAttribute('_doc_number', $inv->document_number)));
    @endphp
    @if($billingPayments->count())
        <div class="m-paper mb-3">
            <div class="m-paper-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h5><i class="fas fa-money-check-alt me-2" style="color: var(--m-teal-6);"></i>Payments</h5>
                <span class="m-badge m-badge-teal">{{ $billingPayments->count() }}</span>
            </div>
            <div style="padding: 0;">
                <div class="table-responsive">
                    <table class="m-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Payment No.</th>
                                <th>Invoice</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($billingPayments->sortByDesc('payment_date') as $payment)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="m-fw-500">{{ $payment->payment_number ?? '-' }}</td>
                                    <td>{{ $payment->_doc_number }}</td>
                                    <td>{{ $payment->payment_date?->format('M d, Y') ?? '-' }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method ?? '-')) }}</td>
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

    {{-- ── Legacy Project Invoices (shown only if no billing invoices exist, or as fallback) ── --}}
    @if($invoices->count() && !$billingInvoices->count())
        <div class="m-paper mb-3">
            <div class="m-paper-header">
                <h5><i class="fas fa-file-invoice me-2" style="color: var(--m-blue-6);"></i>Invoices</h5>
            </div>
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
                                <td class="text-end m-fw-700" style="color: var(--m-teal-6);">TZS {{ number_format($invoices->sum(fn($i) => $i->payments->sum('amount')), 2) }}</td>
                                <td class="text-end m-fw-700">
                                    TZS {{ number_format($invoices->sum('amount') - $invoices->sum(fn($i) => $i->payments->sum('amount')), 2) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Legacy Payments --}}
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
    @endif

    {{-- Empty state when nothing exists at all --}}
    @if(!$billingInvoices->count() && !$billingQuotes->count() && !$billingProformas->count() && !$invoices->count())
        <div class="m-paper">
            <div class="m-paper-body" style="text-align: center; padding: 3rem;">
                <i class="fas fa-file-invoice" style="font-size: 2.5rem; color: var(--m-gray-3); margin-bottom: 0.75rem;"></i>
                <p class="m-text-sm m-dimmed" style="margin: 0;">No billing documents have been created yet.</p>
            </div>
        </div>
    @endif
@endsection

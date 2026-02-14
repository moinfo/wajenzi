@extends('layouts.client')

@section('title', 'Billing')

@section('content')
    <div style="margin-bottom: var(--m-md);">
        <a href="{{ route('client.dashboard') }}" class="m-dimmed m-text-xs" style="text-decoration: none;">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
        <h1 class="m-title m-title-3" style="margin-top: var(--m-sm); margin-bottom: 0;">Billing & Invoices</h1>
        <p class="m-text-sm m-dimmed" style="margin-top: 0.25rem;">All your invoices, quotations, and payments across all projects.</p>
    </div>

    <!-- Summary Stats -->
    <div class="m-stat-grid" style="margin-bottom: var(--m-xl);">
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
        <div class="m-stat">
            <div class="m-stat-top">
                <span class="m-stat-title">Overdue</span>
                <i class="fas fa-clock m-stat-icon"></i>
            </div>
            <div class="m-stat-value" style="font-size: 1.125rem; color: {{ $summary['overdue_count'] > 0 ? 'var(--m-red-6)' : 'var(--m-teal-6)' }};">
                {{ $summary['overdue_count'] }}
            </div>
            <div class="m-stat-desc">invoice{{ $summary['overdue_count'] !== 1 ? 's' : '' }}</div>
        </div>
    </div>

    {{-- ── Invoices ── --}}
    @if($invoices->count())
        <div class="m-paper mb-3">
            <div class="m-paper-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h5><i class="fas fa-file-invoice me-2" style="color: var(--m-blue-6);"></i>Invoices</h5>
                <span class="m-badge m-badge-blue">{{ $invoices->count() }}</span>
            </div>
            <div style="padding: 0;">
                <div class="table-responsive">
                    <table class="m-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Invoice No.</th>
                                <th>Project</th>
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
                            @foreach($invoices as $inv)
                                @php
                                    $statusMap = [
                                        'paid' => 'teal', 'partial_paid' => 'yellow', 'overdue' => 'red',
                                        'sent' => 'blue', 'viewed' => 'blue', 'pending' => 'yellow',
                                        'accepted' => 'teal', 'rejected' => 'red',
                                    ];
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="m-fw-500">{{ $inv->document_number }}</td>
                                    <td>
                                        @if($inv->project)
                                            <a href="{{ route('client.project.financials', $inv->project_id) }}" class="m-text-sm" style="color: var(--m-blue-6); text-decoration: none;">
                                                {{ $inv->project->project_name }}
                                            </a>
                                        @else
                                            <span class="m-text-sm m-dimmed">General</span>
                                        @endif
                                    </td>
                                    <td>{{ $inv->issue_date?->format('M d, Y') ?? '-' }}</td>
                                    <td>
                                        {{ $inv->due_date?->format('M d, Y') ?? '-' }}
                                        @if($inv->is_overdue)
                                            <i class="fas fa-exclamation-triangle ms-1" style="color: var(--m-red-6); font-size: 0.7rem;" title="Overdue"></i>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($inv->total_amount ?? 0, 2) }}</td>
                                    <td class="text-end" style="color: var(--m-teal-6);">{{ number_format($inv->paid_amount ?? 0, 2) }}</td>
                                    <td class="text-end m-fw-600" style="color: {{ ($inv->balance_amount ?? 0) > 0 ? 'var(--m-red-6)' : 'var(--m-teal-6)' }};">
                                        {{ number_format($inv->balance_amount ?? 0, 2) }}
                                    </td>
                                    <td>
                                        <span class="m-badge m-badge-{{ $statusMap[$inv->status] ?? 'gray' }}">
                                            {{ ucfirst(str_replace('_', ' ', $inv->status ?? 'N/A')) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('client.billing.pdf', $inv->id) }}"
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
                                <td colspan="5" class="m-fw-700 text-end">Totals</td>
                                <td class="text-end m-fw-700">TZS {{ number_format($invoices->sum('total_amount'), 2) }}</td>
                                <td class="text-end m-fw-700" style="color: var(--m-teal-6);">TZS {{ number_format($invoices->sum('paid_amount'), 2) }}</td>
                                <td class="text-end m-fw-700" style="color: {{ $invoices->sum('balance_amount') > 0 ? 'var(--m-red-6)' : 'var(--m-teal-6)' }};">
                                    TZS {{ number_format($invoices->sum('balance_amount'), 2) }}
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
    @if($quotes->count())
        <div class="m-paper mb-3">
            <div class="m-paper-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h5><i class="fas fa-file-alt me-2" style="color: var(--m-violet-6);"></i>Quotations</h5>
                <span class="m-badge m-badge-violet">{{ $quotes->count() }}</span>
            </div>
            <div style="padding: 0;">
                <div class="table-responsive">
                    <table class="m-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Quote No.</th>
                                <th>Project</th>
                                <th>Date</th>
                                <th>Valid Until</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($quotes as $quote)
                                @php
                                    $qMap = ['sent' => 'blue', 'viewed' => 'blue', 'accepted' => 'teal', 'rejected' => 'red', 'pending' => 'yellow'];
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="m-fw-500">{{ $quote->document_number }}</td>
                                    <td>
                                        @if($quote->project)
                                            <span class="m-text-sm">{{ $quote->project->project_name }}</span>
                                        @else
                                            <span class="m-text-sm m-dimmed">General</span>
                                        @endif
                                    </td>
                                    <td>{{ $quote->issue_date?->format('M d, Y') ?? '-' }}</td>
                                    <td>{{ $quote->valid_until_date?->format('M d, Y') ?? '-' }}</td>
                                    <td class="text-end m-fw-600">{{ number_format($quote->total_amount ?? 0, 2) }}</td>
                                    <td>
                                        <span class="m-badge m-badge-{{ $qMap[$quote->status] ?? 'gray' }}">
                                            {{ ucfirst($quote->status ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('client.billing.pdf', $quote->id) }}"
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
    @if($proformas->count())
        <div class="m-paper mb-3">
            <div class="m-paper-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h5><i class="fas fa-file-invoice-dollar me-2" style="color: var(--m-orange-6);"></i>Proforma Invoices</h5>
                <span class="m-badge m-badge-orange">{{ $proformas->count() }}</span>
            </div>
            <div style="padding: 0;">
                <div class="table-responsive">
                    <table class="m-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Proforma No.</th>
                                <th>Project</th>
                                <th>Date</th>
                                <th>Valid Until</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($proformas as $proforma)
                                @php
                                    $pMap = ['sent' => 'blue', 'viewed' => 'blue', 'accepted' => 'teal', 'rejected' => 'red', 'pending' => 'yellow'];
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="m-fw-500">{{ $proforma->document_number }}</td>
                                    <td>
                                        @if($proforma->project)
                                            <span class="m-text-sm">{{ $proforma->project->project_name }}</span>
                                        @else
                                            <span class="m-text-sm m-dimmed">General</span>
                                        @endif
                                    </td>
                                    <td>{{ $proforma->issue_date?->format('M d, Y') ?? '-' }}</td>
                                    <td>{{ $proforma->valid_until_date?->format('M d, Y') ?? '-' }}</td>
                                    <td class="text-end m-fw-600">{{ number_format($proforma->total_amount ?? 0, 2) }}</td>
                                    <td>
                                        <span class="m-badge m-badge-{{ $pMap[$proforma->status] ?? 'gray' }}">
                                            {{ ucfirst($proforma->status ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('client.billing.pdf', $proforma->id) }}"
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

    {{-- ── Credit Notes ── --}}
    @if($creditNotes->count())
        <div class="m-paper mb-3">
            <div class="m-paper-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h5><i class="fas fa-receipt me-2" style="color: var(--m-red-6);"></i>Credit Notes</h5>
                <span class="m-badge m-badge-red">{{ $creditNotes->count() }}</span>
            </div>
            <div style="padding: 0;">
                <div class="table-responsive">
                    <table class="m-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Credit Note No.</th>
                                <th>Project</th>
                                <th>Date</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($creditNotes as $cn)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="m-fw-500">{{ $cn->document_number }}</td>
                                    <td>
                                        @if($cn->project)
                                            <span class="m-text-sm">{{ $cn->project->project_name }}</span>
                                        @else
                                            <span class="m-text-sm m-dimmed">General</span>
                                        @endif
                                    </td>
                                    <td>{{ $cn->issue_date?->format('M d, Y') ?? '-' }}</td>
                                    <td class="text-end m-fw-600" style="color: var(--m-red-6);">-{{ number_format($cn->total_amount ?? 0, 2) }}</td>
                                    <td>
                                        <span class="m-badge m-badge-{{ ($cn->status === 'paid' || $cn->status === 'accepted') ? 'teal' : 'gray' }}">
                                            {{ ucfirst(str_replace('_', ' ', $cn->status ?? 'N/A')) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Payments ── --}}
    @php
        $allPayments = $invoices->flatMap(fn($inv) => $inv->payments->map(fn($p) => $p->setAttribute('_doc_number', $inv->document_number)->setAttribute('_project_name', $inv->project?->project_name)));
    @endphp
    @if($allPayments->count())
        <div class="m-paper mb-3">
            <div class="m-paper-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h5><i class="fas fa-money-check-alt me-2" style="color: var(--m-teal-6);"></i>Payments</h5>
                <span class="m-badge m-badge-teal">{{ $allPayments->count() }}</span>
            </div>
            <div style="padding: 0;">
                <div class="table-responsive">
                    <table class="m-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Payment No.</th>
                                <th>Invoice</th>
                                <th>Project</th>
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
                                    <td class="m-fw-500">{{ $payment->payment_number ?? '-' }}</td>
                                    <td>{{ $payment->_doc_number }}</td>
                                    <td>
                                        @if($payment->_project_name)
                                            <span class="m-text-sm">{{ $payment->_project_name }}</span>
                                        @else
                                            <span class="m-text-sm m-dimmed">General</span>
                                        @endif
                                    </td>
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

    {{-- Empty state --}}
    @if(!$invoices->count() && !$quotes->count() && !$proformas->count() && !$creditNotes->count())
        <div class="m-paper">
            <div class="m-paper-body" style="text-align: center; padding: 3rem;">
                <i class="fas fa-file-invoice" style="font-size: 2.5rem; color: var(--m-gray-3); margin-bottom: 0.75rem;"></i>
                <p class="m-text-sm m-dimmed" style="margin: 0;">No billing documents yet.</p>
            </div>
        </div>
    @endif
@endsection

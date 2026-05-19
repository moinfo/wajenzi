@extends('layouts.backend')

@section('content')
<style>
.inv-stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px 22px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 1px 4px rgba(0,0,0,.07);
    border: 1px solid #f0f0f0;
    height: 100%;
}
.inv-stat-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; flex-shrink: 0;
}
.inv-stat-label { font-size: 12px; color: #8a92a6; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
.inv-stat-value { font-size: 20px; font-weight: 700; color: #1a2332; line-height: 1.2; margin-top: 2px; }
.inv-stat-sub   { font-size: 11px; color: #8a92a6; margin-top: 2px; }

.inv-filter-card {
    background: #fff;
    border-radius: 12px;
    padding: 18px 22px;
    box-shadow: 0 1px 4px rgba(0,0,0,.07);
    border: 1px solid #f0f0f0;
    margin-bottom: 20px;
}
.inv-filter-card label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #8a92a6; margin-bottom: 5px; display: block; }
.inv-filter-card .form-control, .inv-filter-card .form-select {
    border: 1.5px solid #e8eaed; border-radius: 8px; font-size: 13px;
    height: 38px; padding: 0 12px; color: #1a2332;
}
.inv-filter-card .form-control:focus, .inv-filter-card .form-select:focus {
    border-color: #1BC5BD; box-shadow: 0 0 0 3px rgba(27,197,189,.12);
}

.inv-table-wrap {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,.07);
    border: 1px solid #f0f0f0;
    overflow: hidden;
}
.inv-table { width: 100%; border-collapse: collapse; }
.inv-table thead th {
    background: #1a2332; color: #fff;
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px;
    padding: 13px 14px; white-space: nowrap; border: none;
}
.inv-table tbody tr { border-bottom: 1px solid #f5f6f8; transition: background .15s; }
.inv-table tbody tr:last-child { border-bottom: none; }
.inv-table tbody tr:hover { background: #f8faff; }
.inv-table td { padding: 13px 14px; font-size: 13px; color: #2d3748; vertical-align: middle; }

.inv-num-link { font-weight: 700; color: #1BC5BD; text-decoration: none; font-size: 13px; }
.inv-num-link:hover { color: #159e97; text-decoration: underline; }
.inv-ref { font-size: 11px; color: #aab0bc; margin-top: 2px; }

.inv-client-name { font-weight: 600; color: #1a2332; }
.inv-client-email { font-size: 11px; color: #aab0bc; margin-top: 1px; }

.inv-amount { font-weight: 600; color: #1a2332; font-variant-numeric: tabular-nums; }
.inv-currency { font-size: 10px; color: #aab0bc; font-weight: 500; }

.inv-date-overdue { font-size: 11px; color: #e53e3e; font-weight: 600; margin-top: 2px; }

.badge-status {
    display: inline-block; padding: 4px 10px; border-radius: 20px;
    font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; white-space: nowrap;
}
.badge-paid       { background: #d4edda; color: #1a7a35; }
.badge-pending    { background: #fff3cd; color: #856404; }
.badge-overdue    { background: #fde8e8; color: #c53030; }
.badge-draft      { background: #e9ecef; color: #495057; }
.badge-sent       { background: #cce5ff; color: #004085; }
.badge-viewed     { background: #d6d8f7; color: #383d8b; }
.badge-partial_paid { background: #d1ecf1; color: #0c5460; }
.badge-cancelled  { background: #fde8e8; color: #c53030; }
.badge-refunded   { background: #e2d9f3; color: #5a3e85; }
.badge-void       { background: #e9ecef; color: #6c757d; }

.inv-action-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 8px; border: 1.5px solid transparent;
    font-size: 13px; cursor: pointer; text-decoration: none; transition: all .15s;
}
.inv-btn-view    { background:#e8f8f7; color:#1BC5BD; border-color:#c5edeb; }
.inv-btn-view:hover { background:#1BC5BD; color:#fff; }
.inv-btn-edit    { background:#e8f0fe; color:#4285f4; border-color:#c5d8fc; }
.inv-btn-edit:hover { background:#4285f4; color:#fff; }
.inv-btn-pdf     { background:#f3f4f6; color:#6b7280; border-color:#e0e2e7; }
.inv-btn-pdf:hover { background:#6b7280; color:#fff; }
.inv-btn-pay     { background:#e6f9f0; color:#22c55e; border-color:#bbf0d4; }
.inv-btn-pay:hover { background:#22c55e; color:#fff; }
.inv-btn-more    { background:#f3f4f6; color:#6b7280; border-color:#e0e2e7; }
.inv-btn-more:hover { background:#6b7280; color:#fff; }

.inv-empty { text-align:center; padding: 60px 20px; color: #aab0bc; }
.inv-empty i { font-size: 48px; display: block; margin-bottom: 12px; }
.inv-empty p { font-size: 14px; margin: 0; }
</style>

<div class="container-fluid" style="padding: 24px 28px;">

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 style="font-size:22px; font-weight:800; color:#1a2332; margin:0;">Invoices</h2>
            <p style="margin:4px 0 0; font-size:13px; color:#8a92a6;">
                Manage and track all client invoices
            </p>
        </div>
        <div class="d-flex gap-2" style="gap:10px;">
            <div class="btn-group">
                <button type="button" class="btn btn-sm dropdown-toggle"
                        style="background:#f0faf9; border:1.5px solid #1BC5BD; color:#1BC5BD; font-weight:600; border-radius:8px; padding:7px 14px; font-size:13px;"
                        data-toggle="dropdown">
                    <i class="fa fa-filter mr-1"></i> Filter by Status
                </button>
                <div class="dropdown-menu dropdown-menu-right" style="border-radius:10px; border:1px solid #e8eaed; box-shadow:0 4px 20px rgba(0,0,0,.12); min-width:180px; padding:6px;">
                    <a class="dropdown-item" style="border-radius:6px; font-size:13px; padding:8px 12px;"
                       href="{{ route('billing.invoices.index') }}"><i class="fa fa-list mr-2 text-muted"></i>All Invoices</a>
                    <div class="dropdown-divider my-1"></div>
                    <a class="dropdown-item" style="border-radius:6px; font-size:13px; padding:8px 12px;"
                       href="{{ route('billing.invoices.paid') }}"><i class="fa fa-check-circle mr-2 text-success"></i>Paid</a>
                    <a class="dropdown-item" style="border-radius:6px; font-size:13px; padding:8px 12px;"
                       href="{{ route('billing.invoices.unpaid') }}"><i class="fa fa-clock mr-2 text-warning"></i>Unpaid</a>
                    <a class="dropdown-item" style="border-radius:6px; font-size:13px; padding:8px 12px;"
                       href="{{ route('billing.invoices.overdue') }}"><i class="fa fa-exclamation-triangle mr-2 text-danger"></i>Overdue</a>
                    <a class="dropdown-item" style="border-radius:6px; font-size:13px; padding:8px 12px;"
                       href="{{ route('billing.invoices.draft') }}"><i class="fa fa-edit mr-2 text-secondary"></i>Draft</a>
                    <a class="dropdown-item" style="border-radius:6px; font-size:13px; padding:8px 12px;"
                       href="{{ route('billing.invoices.cancelled') }}"><i class="fa fa-times mr-2 text-danger"></i>Cancelled</a>
                    <a class="dropdown-item" style="border-radius:6px; font-size:13px; padding:8px 12px;"
                       href="{{ route('billing.invoices.refunded') }}"><i class="fa fa-undo mr-2 text-primary"></i>Refunded</a>
                </div>
            </div>
            <a href="{{ route('billing.invoices.create') }}"
               style="background:#1BC5BD; color:#fff; font-weight:700; border-radius:8px; padding:7px 18px; font-size:13px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                <i class="fa fa-plus"></i> New Invoice
            </a>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="row mb-4" style="row-gap:14px;">
        <div class="col-6 col-md-3">
            <div class="inv-stat-card">
                <div class="inv-stat-icon" style="background:#e8f8f7;">
                    <i class="fa fa-file-text" style="color:#1BC5BD;"></i>
                </div>
                <div>
                    <div class="inv-stat-label">Total Invoices</div>
                    <div class="inv-stat-value">{{ number_format($stats->total_count) }}</div>
                    @if($stats->overdue_count > 0)
                        <div class="inv-stat-sub" style="color:#e53e3e;">{{ $stats->overdue_count }} overdue</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="inv-stat-card">
                <div class="inv-stat-icon" style="background:#e8f0fe;">
                    <i class="fa fa-money" style="color:#4285f4;"></i>
                </div>
                <div>
                    <div class="inv-stat-label">Total Billed</div>
                    <div class="inv-stat-value" style="font-size:16px;">TZS {{ number_format($stats->total_amount ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="inv-stat-card">
                <div class="inv-stat-icon" style="background:#e6f9f0;">
                    <i class="fa fa-check-circle" style="color:#22c55e;"></i>
                </div>
                <div>
                    <div class="inv-stat-label">Amount Collected</div>
                    <div class="inv-stat-value" style="font-size:16px; color:#22c55e;">TZS {{ number_format($stats->paid_amount ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="inv-stat-card">
                <div class="inv-stat-icon" style="background:#fff3cd;">
                    <i class="fa fa-hourglass-half" style="color:#f59e0b;"></i>
                </div>
                <div>
                    <div class="inv-stat-label">Outstanding</div>
                    <div class="inv-stat-value" style="font-size:16px; color:#f59e0b;">TZS {{ number_format($stats->balance_amount ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="inv-filter-card no-print">
        <form method="GET" action="{{ route('billing.invoices.index') }}">
            <div class="row" style="row-gap:12px; align-items:flex-end;">
                <div class="col-md-2 col-6">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="draft"        {{ request('status')=='draft'        ?'selected':'' }}>Draft</option>
                        <option value="pending"      {{ request('status')=='pending'      ?'selected':'' }}>Pending</option>
                        <option value="sent"         {{ request('status')=='sent'         ?'selected':'' }}>Sent</option>
                        <option value="viewed"       {{ request('status')=='viewed'       ?'selected':'' }}>Viewed</option>
                        <option value="partial_paid" {{ request('status')=='partial_paid' ?'selected':'' }}>Partial Paid</option>
                        <option value="paid"         {{ request('status')=='paid'         ?'selected':'' }}>Paid</option>
                        <option value="overdue"      {{ request('status')=='overdue'      ?'selected':'' }}>Overdue</option>
                    </select>
                </div>
                <div class="col-md-3 col-6">
                    <label>Client</label>
                    <select name="client_id" class="form-control">
                        <option value="">All Clients</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ request('client_id')==$client->id?'selected':'' }}>
                                {{ $client->first_name }} {{ $client->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label>Type</label>
                    <select name="invoice_type" class="form-control">
                        <option value="">All Types</option>
                        <option value="Site Visit"  {{ request('invoice_type')=='Site Visit'  ?'selected':'' }}>Site Visit</option>
                        <option value="Design"      {{ request('invoice_type')=='Design'      ?'selected':'' }}>Design</option>
                        <option value="BOQ"         {{ request('invoice_type')=='BOQ'         ?'selected':'' }}>BOQ</option>
                        <option value="Supervision" {{ request('invoice_type')=='Supervision' ?'selected':'' }}>Supervision</option>
                        <option value="Topography"  {{ request('invoice_type')=='Topography'  ?'selected':'' }}>Topography</option>
                        <option value="Other"       {{ request('invoice_type')=='Other'       ?'selected':'' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label>From Date</label>
                    <input type="text" name="from_date" class="form-control datepicker" value="{{ request('from_date') }}" placeholder="yyyy-mm-dd" autocomplete="off">
                </div>
                <div class="col-md-2 col-6">
                    <label>To Date</label>
                    <input type="text" name="to_date" class="form-control datepicker" value="{{ request('to_date') }}" placeholder="yyyy-mm-dd" autocomplete="off">
                </div>
                <div class="col-md-1 col-6 d-flex" style="gap:8px;">
                    <button type="submit" class="btn btn-sm"
                            style="background:#1BC5BD; color:#fff; border-radius:8px; font-weight:600; font-size:13px; height:38px; padding:0 16px; white-space:nowrap;">
                        <i class="fa fa-search"></i>
                    </button>
                    <a href="{{ route('billing.invoices.index') }}"
                       class="btn btn-sm"
                       style="background:#f3f4f6; color:#6b7280; border-radius:8px; font-weight:600; font-size:13px; height:38px; padding:0 12px; white-space:nowrap;">
                        <i class="fa fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="inv-table-wrap">
        <div class="table-responsive">
            <table class="inv-table">
                <thead>
                    <tr>
                        <th style="width:150px;">Invoice #</th>
                        <th style="width:120px;">Type</th>
                        <th>Client</th>
                        <th style="width:110px;">Issue Date</th>
                        <th style="width:120px;">Due Date</th>
                        <th style="width:150px;">Amount</th>
                        <th style="width:130px;">Paid</th>
                        <th style="width:150px;">Balance</th>
                        <th style="width:100px;">Status</th>
                        <th style="width:130px; text-align:right; padding-right:18px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>
                                <a href="{{ route('billing.invoices.show', $invoice) }}" class="inv-num-link">
                                    {{ $invoice->document_number }}
                                </a>
                                @if($invoice->reference_number)
                                    <div class="inv-ref">Ref: {{ $invoice->reference_number }}</div>
                                @endif
                            </td>
                            <td class="inv-type-cell"
                                data-id="{{ $invoice->id }}"
                                data-url="{{ route('billing.invoices.update-type', $invoice) }}"
                                data-type="{{ $invoice->invoice_type }}">
                                @php
                                    $typeConfig = [
                                        'Site Visit'  => ['bg'=>'#e0f7f6','color'=>'#0d9488','border'=>'#99e6e3'],
                                        'Design'      => ['bg'=>'#dbeafe','color'=>'#1d4ed8','border'=>'#93c5fd'],
                                        'BOQ'         => ['bg'=>'#fef9c3','color'=>'#854d0e','border'=>'#fde047'],
                                        'Supervision' => ['bg'=>'#dcfce7','color'=>'#166534','border'=>'#86efac'],
                                        'Topography'  => ['bg'=>'#f3f4f6','color'=>'#374151','border'=>'#d1d5db'],
                                        'Other'       => ['bg'=>'#f3f4f6','color'=>'#6b7280','border'=>'#e5e7eb'],
                                    ];
                                    $tc = $typeConfig[$invoice->invoice_type] ?? null;
                                @endphp
                                @if($invoice->invoice_type && $tc)
                                    <span class="inv-type-badge"
                                          style="display:inline-block; background:{{ $tc['bg'] }}; color:{{ $tc['color'] }}; border:1px solid {{ $tc['border'] }}; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:700; cursor:pointer; white-space:nowrap;"
                                          title="Click to change">{{ $invoice->invoice_type }}</span>
                                @else
                                    <span class="inv-type-badge text-muted"
                                          style="cursor:pointer; font-size:13px; color:#cbd5e0;" title="Click to set type">—</span>
                                @endif
                                <select class="inv-type-select form-control form-control-sm"
                                        style="display:none; width:130px; font-size:12px; border-radius:8px;">
                                    <option value="">— None —</option>
                                    <option value="Site Visit">Site Visit</option>
                                    <option value="Design">Design</option>
                                    <option value="BOQ">BOQ</option>
                                    <option value="Supervision">Supervision</option>
                                    <option value="Topography">Topography</option>
                                    <option value="Other">Other</option>
                                </select>
                            </td>
                            <td>
                                <div class="inv-client-name">{{ $invoice->client->first_name }} {{ $invoice->client->last_name }}</div>
                                @if($invoice->client->email)
                                    <div class="inv-client-email">{{ $invoice->client->email }}</div>
                                @endif
                            </td>
                            <td style="font-size:13px; color:#4a5568;">{{ $invoice->issue_date->format('d/m/Y') }}</td>
                            <td>
                                @if($invoice->due_date)
                                    <div style="font-size:13px; color:#4a5568;">{{ $invoice->due_date->format('d/m/Y') }}</div>
                                    @if($invoice->is_overdue)
                                        <div class="inv-date-overdue">{{ $invoice->due_date->diffForHumans() }}</div>
                                    @endif
                                @else
                                    <span style="color:#cbd5e0;">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="inv-amount">{{ number_format($invoice->total_amount, 2) }}</div>
                                <div class="inv-currency">{{ $invoice->currency_code }}</div>
                            </td>
                            <td>
                                <div class="inv-amount" style="color:#22c55e;">{{ number_format($invoice->paid_amount, 2) }}</div>
                                <div class="inv-currency">{{ $invoice->currency_code }}</div>
                            </td>
                            <td>
                                @php $bal = $invoice->balance_amount; @endphp
                                <div class="inv-amount" style="color:{{ $bal > 0 ? '#f59e0b' : '#22c55e' }};">
                                    {{ number_format($bal, 2) }}
                                </div>
                                <div class="inv-currency">{{ $invoice->currency_code }}</div>
                            </td>
                            <td>
                                <span class="badge-status badge-{{ $invoice->status }}">
                                    {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                                </span>
                            </td>
                            <td style="text-align:right; padding-right:14px;">
                                <div style="display:inline-flex; gap:5px; align-items:center;">
                                    <a href="{{ route('billing.invoices.show', $invoice) }}"
                                       class="inv-action-btn inv-btn-view" title="View">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    @if($invoice->is_editable)
                                        <a href="{{ route('billing.invoices.edit', $invoice) }}"
                                           class="inv-action-btn inv-btn-edit" title="Edit">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('billing.invoices.pdf', $invoice) }}"
                                       class="inv-action-btn inv-btn-pdf" title="Download PDF" target="_blank">
                                        <i class="fa fa-download"></i>
                                    </a>
                                    @if(!$invoice->is_paid && $invoice->balance_amount > 0)
                                        <button type="button" class="inv-action-btn inv-btn-pay"
                                                onclick="recordPayment({{ $invoice->id }})" title="Record Payment"
                                                style="border:none;">
                                            <i class="fa fa-credit-card"></i>
                                        </button>
                                    @endif
                                    <div class="btn-group">
                                        <button type="button"
                                                class="inv-action-btn inv-btn-more dropdown-toggle"
                                                data-toggle="dropdown" data-boundary="viewport"
                                                style="border:none;">
                                            <i class="fa fa-ellipsis-h"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right"
                                             style="border-radius:10px; border:1px solid #e8eaed; box-shadow:0 4px 20px rgba(0,0,0,.12); min-width:160px; padding:6px; z-index:1050;">
                                            <a class="dropdown-item" style="border-radius:6px; font-size:13px; padding:8px 12px;"
                                               href="{{ route('billing.invoices.duplicate', $invoice) }}">
                                                <i class="fa fa-copy mr-2 text-muted"></i> Duplicate
                                            </a>
                                            @if($invoice->status !== 'void')
                                                <a class="dropdown-item" style="border-radius:6px; font-size:13px; padding:8px 12px; color:#d97706;"
                                                   href="{{ route('billing.invoices.void', $invoice) }}"
                                                   onclick="return confirm('Void this invoice?')">
                                                    <i class="fa fa-ban mr-2"></i> Void
                                                </a>
                                            @endif
                                            @if($invoice->is_editable)
                                                <div class="dropdown-divider my-1"></div>
                                                <a class="dropdown-item" style="border-radius:6px; font-size:13px; padding:8px 12px; color:#e53e3e;"
                                                   href="{{ route('billing.invoices.destroy', $invoice) }}"
                                                   onclick="return confirm('Delete this invoice?')">
                                                    <i class="fa fa-trash mr-2"></i> Delete
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="inv-empty">
                                    <i class="fa fa-file-text-o"></i>
                                    <p>No invoices found</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
            <div style="padding:16px 20px; border-top:1px solid #f0f0f0; background:#fafbfc;">
                {{ $invoices->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:14px; border:none; box-shadow:0 8px 40px rgba(0,0,0,.15);">
            <form id="paymentForm" method="POST">
                @csrf
                <div class="modal-header" style="border-bottom:1px solid #f0f0f0; padding:20px 24px;">
                    <h5 class="modal-title" style="font-weight:700; font-size:16px; color:#1a2332;">Record Payment</h5>
                    <button type="button" class="close" data-dismiss="modal" style="font-size:20px;">&times;</button>
                </div>
                <div class="modal-body" style="padding:20px 24px;">
                    <div class="form-group">
                        <label style="font-size:12px; font-weight:700; color:#8a92a6; text-transform:uppercase; letter-spacing:.5px;">Amount</label>
                        <input type="number" name="amount" class="form-control" step="0.01" required
                               style="border-radius:8px; border:1.5px solid #e8eaed; height:40px;">
                    </div>
                    <div class="form-group">
                        <label style="font-size:12px; font-weight:700; color:#8a92a6; text-transform:uppercase; letter-spacing:.5px;">Payment Date</label>
                        <input type="text" name="payment_date" class="form-control datepicker" value="{{ date('Y-m-d') }}" required
                               style="border-radius:8px; border:1.5px solid #e8eaed; height:40px;">
                    </div>
                    <div class="form-group">
                        <label style="font-size:12px; font-weight:700; color:#8a92a6; text-transform:uppercase; letter-spacing:.5px;">Payment Method</label>
                        <select name="payment_method" class="form-control" required
                                style="border-radius:8px; border:1.5px solid #e8eaed; height:40px;">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="online">Online Payment</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="font-size:12px; font-weight:700; color:#8a92a6; text-transform:uppercase; letter-spacing:.5px;">Reference Number</label>
                        <input type="text" name="reference_number" class="form-control"
                               style="border-radius:8px; border:1.5px solid #e8eaed; height:40px;">
                    </div>
                    <div class="form-group">
                        <label style="font-size:12px; font-weight:700; color:#8a92a6; text-transform:uppercase; letter-spacing:.5px;">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"
                                  style="border-radius:8px; border:1.5px solid #e8eaed;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f0f0f0; padding:16px 24px; gap:10px;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"
                            style="border-radius:8px; font-weight:600; font-size:13px;">Cancel</button>
                    <button type="submit" class="btn"
                            style="background:#1BC5BD; color:#fff; border-radius:8px; font-weight:700; font-size:13px; padding:8px 22px;">
                        Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function recordPayment(invoiceId) {
    $('#paymentForm').attr('action', `/billing/invoices/${invoiceId}/payment`);
    $('#paymentModal').modal('show');
}

// Inline invoice type editing
(function () {
    const typeConfig = {
        'Site Visit':  { bg:'#e0f7f6', color:'#0d9488', border:'#99e6e3' },
        'Design':      { bg:'#dbeafe', color:'#1d4ed8', border:'#93c5fd' },
        'BOQ':         { bg:'#fef9c3', color:'#854d0e', border:'#fde047' },
        'Supervision': { bg:'#dcfce7', color:'#166534', border:'#86efac' },
        'Topography':  { bg:'#f3f4f6', color:'#374151', border:'#d1d5db' },
        'Other':       { bg:'#f3f4f6', color:'#6b7280', border:'#e5e7eb' },
    };

    function applyBadge($badge, type) {
        if (type && typeConfig[type]) {
            const s = typeConfig[type];
            $badge.text(type)
                .attr('style',
                    `display:inline-block; background:${s.bg}; color:${s.color}; border:1px solid ${s.border};` +
                    `padding:3px 9px; border-radius:20px; font-size:11px; font-weight:700; cursor:pointer; white-space:nowrap;`)
                .removeClass('text-muted')
                .attr('title', 'Click to change');
        } else {
            $badge.text('—')
                .attr('style', 'cursor:pointer; font-size:13px; color:#cbd5e0;')
                .addClass('text-muted')
                .attr('title', 'Click to set type');
        }
    }

    $(document).on('click', '.inv-type-badge', function () {
        const $cell   = $(this).closest('.inv-type-cell');
        const $select = $cell.find('.inv-type-select');
        $select.val($cell.data('type') || '');
        $(this).hide();
        $select.show().focus();
    });

    $(document).on('change', '.inv-type-select', function () {
        const $cell  = $(this).closest('.inv-type-cell');
        const $badge = $cell.find('.inv-type-badge');
        const $sel   = $(this);
        const type   = $(this).val();

        $.ajax({
            url:  $cell.data('url'),
            type: 'POST',
            data: { _method: 'PATCH', _token: '{{ csrf_token() }}', invoice_type: type },
            success: function (res) {
                if (res.success) {
                    $cell.data('type', res.invoice_type || '');
                    applyBadge($badge, res.invoice_type);
                }
            },
            error: function () { alert('Failed to update type. Please try again.'); },
            complete: function () { $sel.hide(); $badge.show(); }
        });
    });

    $(document).on('blur', '.inv-type-select', function () {
        const $cell  = $(this).closest('.inv-type-cell');
        const $badge = $cell.find('.inv-type-badge');
        setTimeout(function () {
            $cell.find('.inv-type-select').hide();
            $badge.show();
        }, 200);
    });

    $(document).on('keydown', '.inv-type-select', function (e) {
        if (e.key === 'Escape') {
            const $cell = $(this).closest('.inv-type-cell');
            $(this).hide();
            $cell.find('.inv-type-badge').show();
        }
    });
}());
</script>
@endpush

@endsection

@extends('layouts.backend')

@section('content')
<style>
.inv-stat-card {
    background: #fff;
    border-radius: 10px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 1px 4px rgba(0,0,0,.07);
    height: 100%;
}
.inv-stat-icon {
    width: 44px; height: 44px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: 18px;
}
.inv-stat-label { font-size: 10px; text-transform: uppercase; letter-spacing: .7px; color: #9ca3af; font-weight: 700; }
.inv-stat-value { font-size: 17px; font-weight: 800; margin-top: 2px; line-height: 1.2; }
.inv-action-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 7px 14px; border-radius: 7px; font-size: 12px;
    font-weight: 600; border: none; cursor: pointer; white-space: nowrap;
    transition: opacity .15s;
}
.inv-action-btn:hover { opacity: .85; text-decoration: none; }
.inv-action-btn i { font-size: 12px; }
.inv-doc-section { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.07); overflow: hidden; }
.inv-table thead th {
    background: #f8fafc; font-size: 10px; text-transform: uppercase;
    letter-spacing: .6px; color: #6b7280; font-weight: 700;
    padding: 10px 12px; border: none; border-bottom: 2px solid #e5e7eb;
}
.inv-table tbody td { padding: 13px 12px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
.inv-table tbody tr:last-child td { border-bottom: none; }
.sidebar-label {
    font-size: 10px; text-transform: uppercase; letter-spacing: .7px;
    color: #9ca3af; font-weight: 700; margin-bottom: 14px;
}
.sidebar-row { display: flex; justify-content: space-between; align-items: flex-start; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 12px; gap: 8px; }
.sidebar-row:last-child { border-bottom: none; }
.sidebar-row .s-key { color: #6b7280; flex-shrink: 0; }
.sidebar-row .s-val { font-weight: 600; color: #111; text-align: right; }
.pay-hist-item { padding: 10px 0; border-bottom: 1px solid #f3f4f6; }
.pay-hist-item:last-child { border-bottom: none; }
.tc-section { font-size: 12px; line-height: 1.7; color: #555; }
.tc-section h1, .tc-section h2, .tc-section h3 { font-size: 13px; font-weight: 700; color: #111; margin: 14px 0 4px; }
.tc-section p { margin: 0 0 6px; }
.tc-section ul, .tc-section ol { padding-left: 18px; margin: 4px 0 8px; }
.tc-section li { margin-bottom: 3px; }
</style>

<div class="container-fluid">
<div class="content">

{{-- ── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap" style="gap:10px;">
    {{-- Left: back + title --}}
    <div class="d-flex align-items-center" style="gap:10px;">
        <a href="{{ $invoice->lead_id ? route('leads.show', $invoice->lead_id) : route('billing.invoices.index') }}"
           style="width:32px;height:32px;border-radius:8px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;color:#374151;text-decoration:none;flex-shrink:0;">
            <i class="fa fa-arrow-left" style="font-size:13px;"></i>
        </a>
        <div>
            <div style="font-size:18px;font-weight:800;color:#111;line-height:1.1;">{{ $invoice->document_number }}</div>
            <div style="margin-top:3px;display:flex;align-items:center;gap:6px;">
                <span class="badge badge-{{ $invoice->status_color }}" style="font-size:10px;padding:3px 9px;">
                    {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                </span>
                @if($invoice->approved_at)
                    <span style="font-size:10px;background:#dcfce7;color:#15803d;padding:3px 9px;border-radius:20px;font-weight:600;">
                        <i class="fa fa-check-circle"></i> Approved
                    </span>
                @endif
                @if($invoice->is_overdue)
                    <span style="font-size:10px;background:#fee2e2;color:#dc2626;padding:3px 9px;border-radius:20px;font-weight:600;">
                        Overdue
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Right: Actions --}}
    <div class="d-flex flex-wrap align-items-center" style="gap:6px;">
        @if($invoice->is_editable)
            <a href="{{ route('billing.invoices.edit', $invoice) }}" class="inv-action-btn" style="background:#3b82f6;color:#fff;">
                <i class="fa fa-edit"></i> Edit
            </a>
        @endif
        <a href="{{ route('billing.invoices.pdf', $invoice) }}" class="inv-action-btn" style="background:#1f2937;color:#fff;" target="_blank">
            <i class="fa fa-file-pdf-o"></i> PDF
        </a>
        @if($invoice->is_paid)
            <a href="{{ route('billing.invoices.receipt', $invoice) }}" class="inv-action-btn" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;" target="_blank">
                <i class="fa fa-file-text"></i> Receipt
            </a>
        @endif
        <button class="inv-action-btn" style="background:#0ea5e9;color:#fff;" onclick="sendEmailModal()">
            <i class="fa fa-envelope"></i> Email
        </button>
        @if($invoice->recipient_phone)
            <button class="inv-action-btn" style="background:#128c7e;color:#fff;" onclick="sendWhatsAppModal()">
                <i class="fa fa-whatsapp"></i> WhatsApp
            </button>
        @endif
        @if(!$invoice->is_paid && $invoice->balance_amount > 0)
            <button class="inv-action-btn" style="background:#f59e0b;color:#fff;" onclick="sendReminderModal()">
                <i class="fa fa-bell"></i> Reminder
            </button>
            <button class="inv-action-btn" style="background:#16a34a;color:#fff;" onclick="recordPaymentModal()">
                <i class="fa fa-money"></i> Record Payment
            </button>
        @endif
        @if(!$invoice->approved_at)
            <form method="POST" action="{{ route('billing.invoices.approve', $invoice) }}" class="d-inline">
                @csrf
                <button type="submit" class="inv-action-btn" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;"
                        onclick="return confirm('Approve this invoice? The official stamp will appear on the PDF.')">
                    <i class="fa fa-check-circle"></i> Approve
                </button>
            </form>
        @endif
        <div class="btn-group">
            <button type="button" class="inv-action-btn dropdown-toggle" style="background:#f3f4f6;color:#374151;" data-toggle="dropdown">
                <i class="fa fa-ellipsis-v"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-right" style="font-size:13px;">
                <a class="dropdown-item" href="{{ route('billing.invoices.duplicate', $invoice) }}">
                    <i class="fa fa-copy mr-2"></i>Duplicate
                </a>
                @if(!$invoice->is_paid && $invoice->balance_amount > 0 && !$invoice->late_fee_applied_at)
                    <a class="dropdown-item" href="javascript:void(0)" onclick="applyLateFeeModal()">
                        <i class="fa fa-plus mr-2"></i>Apply Late Fee
                    </a>
                @endif
                @if($invoice->status !== 'void')
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-warning" href="{{ route('billing.invoices.void', $invoice) }}"
                       onclick="return confirm('Void this invoice?')">
                        <i class="fa fa-ban mr-2"></i>Void Invoice
                    </a>
                @endif
                @if($invoice->is_editable)
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="javascript:void(0)"
                       onclick="if(confirm('Delete invoice {{ $invoice->document_number }}? This cannot be undone.')) document.getElementById('deleteInvoiceForm').submit()">
                        <i class="fa fa-trash mr-2"></i>Delete
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Stat Cards ───────────────────────────────────────────────────────────── --}}
<div class="row mb-4" style="row-gap:12px;">
    <div class="col-6 col-md-3">
        <div class="inv-stat-card">
            <div class="inv-stat-icon" style="background:#eff6ff;">
                <i class="fa fa-file-text-o" style="color:#3b82f6;"></i>
            </div>
            <div>
                <div class="inv-stat-label">Total</div>
                <div class="inv-stat-value" style="color:#111;">
                    <span style="font-size:11px;font-weight:600;color:#6b7280;">{{ $invoice->currency_code }}</span>
                    {{ number_format($invoice->total_amount, 2) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="inv-stat-card">
            <div class="inv-stat-icon" style="background:#f0fdf4;">
                <i class="fa fa-check" style="color:#16a34a;"></i>
            </div>
            <div>
                <div class="inv-stat-label">Paid</div>
                <div class="inv-stat-value" style="color:#16a34a;">
                    <span style="font-size:11px;font-weight:600;color:#6b7280;">{{ $invoice->currency_code }}</span>
                    {{ number_format($invoice->paid_amount, 2) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="inv-stat-card">
            <div class="inv-stat-icon" style="background:{{ $invoice->balance_amount > 0 ? '#fffbeb' : '#f0fdf4' }};">
                <i class="fa fa-hourglass-half" style="color:{{ $invoice->balance_amount > 0 ? '#d97706' : '#16a34a' }};"></i>
            </div>
            <div>
                <div class="inv-stat-label">Balance</div>
                <div class="inv-stat-value" style="color:{{ $invoice->balance_amount > 0 ? '#d97706' : '#16a34a' }};">
                    <span style="font-size:11px;font-weight:600;color:#6b7280;">{{ $invoice->currency_code }}</span>
                    {{ number_format($invoice->balance_amount, 2) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="inv-stat-card">
            <div class="inv-stat-icon" style="background:{{ $invoice->is_overdue ? '#fef2f2' : '#f8fafc' }};">
                <i class="fa fa-calendar{{ $invoice->is_overdue ? '-times' : '' }}" style="color:{{ $invoice->is_overdue ? '#dc2626' : '#6b7280' }};"></i>
            </div>
            <div>
                <div class="inv-stat-label">Due Date</div>
                <div class="inv-stat-value" style="font-size:13px; color:{{ $invoice->is_overdue ? '#dc2626' : '#111' }};">
                    @if($invoice->due_date)
                        {{ $invoice->due_date->format('d M Y') }}
                    @else
                        <span style="color:#9ca3af;">—</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Main Layout ──────────────────────────────────────────────────────────── --}}
<div class="row" style="row-gap:20px;">

    {{-- ── Document Column ───────────────────────────────────────────────────── --}}
    <div class="col-lg-8">
        <div class="inv-doc-section">

            {{-- Letterhead (white, above the dark title strip) --}}
            @include('components.headed_paper', [
                'backUrl'  => null,
                'backText' => null
            ])

            {{-- Dark title strip --}}
            <div style="background:#1f2937; padding:20px 28px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:12px;">
                <div>
                    <div style="font-size:30px; font-weight:900; letter-spacing:2px; color:#fff; line-height:1;">INVOICE</div>
                    <div style="color:#6b7280; font-size:12px; margin-top:4px;">{{ $invoice->document_number }}</div>
                </div>
                <table style="font-size:12px; border-collapse:collapse;">
                    @if($invoice->reference_number)
                        <tr>
                            <td style="color:#6b7280; padding:2px 14px 2px 0;">Reference</td>
                            <td style="color:#fff; font-weight:600;">{{ $invoice->reference_number }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="color:#6b7280; padding:2px 14px 2px 0;">Issue Date</td>
                        <td style="color:#fff; font-weight:600;">{{ $invoice->issue_date->format('d M Y') }}</td>
                    </tr>
                    @if($invoice->due_date)
                        <tr>
                            <td style="color:#6b7280; padding:2px 14px 2px 0;">Due Date</td>
                            <td style="color:{{ $invoice->is_overdue ? '#f87171' : '#fff' }}; font-weight:600;">
                                {{ $invoice->due_date->format('d M Y') }}
                            </td>
                        </tr>
                    @endif
                    @if($invoice->po_number)
                        <tr>
                            <td style="color:#6b7280; padding:2px 14px 2px 0;">PO Number</td>
                            <td style="color:#fff; font-weight:600;">{{ $invoice->po_number }}</td>
                        </tr>
                    @endif
                </table>
            </div>

            {{-- Document body --}}
            <div style="padding:28px;">

                {{-- Bill To + Invoice For --}}
                <div class="row mb-4" style="row-gap:16px;">
                    <div class="col-sm-5">
                        <div style="font-size:9px; text-transform:uppercase; letter-spacing:1px; color:#9ca3af; font-weight:700; margin-bottom:6px;">Bill To @if(!$invoice->client && $invoice->lead)<span style="color:#9ca3af;">(Lead)</span>@endif</div>
                        <div style="font-size:14px; font-weight:800; color:#111; margin-bottom:4px;">
                            {{ $invoice->recipient_name }}
                        </div>
                        @if($invoice->recipient_address)
                            <div style="font-size:12px; color:#6b7280; margin-bottom:2px;">{{ $invoice->recipient_address }}</div>
                        @endif
                        @if($invoice->recipient_phone)
                            <div style="font-size:12px; color:#6b7280; margin-bottom:2px;">
                                <i class="fa fa-phone mr-1" style="color:#d1d5db;font-size:10px;"></i>{{ $invoice->recipient_phone }}
                            </div>
                        @endif
                        @if($invoice->recipient_email)
                            <div style="font-size:12px; color:#6b7280;">
                                <i class="fa fa-envelope mr-1" style="color:#d1d5db;font-size:10px;"></i>{{ $invoice->recipient_email }}
                            </div>
                        @endif
                    </div>
                    @if($invoice->title || $invoice->service_description)
                        <div class="col-sm-7">
                            <div style="font-size:9px; text-transform:uppercase; letter-spacing:1px; color:#9ca3af; font-weight:700; margin-bottom:6px;">Invoice For</div>
                            @if($invoice->title)
                                <div style="font-size:14px; font-weight:800; color:#111; margin-bottom:6px;">{{ $invoice->title }}</div>
                            @endif
                            @if($invoice->service_description)
                                <div style="font-size:12px; color:#555; line-height:1.7;">
                                    {!! str_replace(['&nbsp;', "\xC2\xA0"], ' ', $invoice->service_description) !!}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Divider --}}
                <div style="height:1px; background:#e5e7eb; margin: 0 0 20px;"></div>

                {{-- Line Items --}}
                <div class="table-responsive">
                    <table class="table inv-table mb-0">
                        <thead>
                            <tr>
                                <th>Item / Description</th>
                                <th class="text-center">Qty</th>
                                <th class="text-center">Unit</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Tax</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $item)
                                <tr>
                                    <td>
                                        <div style="font-weight:600; color:#111; font-size:13px;">{{ $item->item_name }}</div>
                                        @if($item->description)
                                            <div style="font-size:11px; color:#9ca3af; margin-top:2px;">{{ $item->description }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center" style="font-size:13px; color:#374151;">{{ number_format($item->quantity, 2) }}</td>
                                    <td class="text-center" style="font-size:12px; color:#9ca3af;">{{ $item->unit_of_measure ?? '—' }}</td>
                                    <td class="text-right" style="font-size:13px; color:#374151; white-space:nowrap;">
                                        {{ $invoice->currency_code }} {{ number_format($item->unit_price, 2) }}
                                    </td>
                                    <td class="text-right" style="font-size:12px; color:#6b7280;">
                                        @if($item->tax_percentage > 0)
                                            {{ $item->tax_percentage }}%<br>
                                            <span style="font-size:10px;">{{ number_format($item->tax_amount, 2) }}</span>
                                        @else
                                            <span style="color:#d1d5db;">—</span>
                                        @endif
                                    </td>
                                    <td class="text-right" style="font-size:13px; font-weight:700; color:#111; white-space:nowrap;">
                                        {{ $invoice->currency_code }} {{ number_format($item->line_total, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Totals --}}
                <div class="d-flex justify-content-between flex-wrap mt-3" style="gap:20px;">
                    <div style="flex:1; max-width:320px;">
                        @if($invoice->notes)
                            <div style="font-size:9px; text-transform:uppercase; letter-spacing:1px; color:#9ca3af; font-weight:700; margin-bottom:6px;">Notes</div>
                            <p style="font-size:12px; color:#555; line-height:1.6; margin:0;">{{ $invoice->notes }}</p>
                        @endif
                    </div>
                    <div style="min-width:230px;">
                        <table style="width:100%; font-size:13px; border-collapse:collapse;">
                            <tr>
                                <td style="padding:5px 0; color:#6b7280;">Subtotal</td>
                                <td style="padding:5px 0; text-align:right;">{{ $invoice->currency_code }} {{ number_format($invoice->subtotal_amount, 2) }}</td>
                            </tr>
                            @if($invoice->discount_amount > 0)
                                <tr>
                                    <td style="padding:5px 0; color:#6b7280;">Discount</td>
                                    <td style="padding:5px 0; text-align:right; color:#16a34a;">−{{ $invoice->currency_code }} {{ number_format($invoice->discount_amount, 2) }}</td>
                                </tr>
                            @endif
                            @if($invoice->tax_amount > 0)
                                <tr>
                                    <td style="padding:5px 0; color:#6b7280;">Tax</td>
                                    <td style="padding:5px 0; text-align:right;">{{ $invoice->currency_code }} {{ number_format($invoice->tax_amount, 2) }}</td>
                                </tr>
                            @endif
                            @if($invoice->shipping_amount > 0)
                                <tr>
                                    <td style="padding:5px 0; color:#6b7280;">Shipping</td>
                                    <td style="padding:5px 0; text-align:right;">{{ $invoice->currency_code }} {{ number_format($invoice->shipping_amount, 2) }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="2" style="padding:0;"><div style="height:2px;background:#1f2937;margin:6px 0;"></div></td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0; font-weight:800; font-size:16px; color:#111;">TOTAL</td>
                                <td style="padding:4px 0; text-align:right; font-weight:800; font-size:16px; color:#111; white-space:nowrap;">
                                    {{ $invoice->currency_code }} {{ number_format($invoice->total_amount, 2) }}
                                </td>
                            </tr>
                            @if($invoice->paid_amount > 0)
                                <tr>
                                    <td style="padding:4px 0; color:#16a34a; font-weight:600; font-size:12px;">Paid</td>
                                    <td style="padding:4px 0; text-align:right; color:#16a34a; font-weight:600; font-size:12px;">
                                        −{{ $invoice->currency_code }} {{ number_format($invoice->paid_amount, 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="padding:0;">
                                        <div style="background:#f0fdf4; border-radius:6px; padding:8px 10px; margin-top:4px; display:flex; justify-content:space-between;">
                                            <span style="font-weight:700; color:#15803d; font-size:13px;">Balance Due</span>
                                            <span style="font-weight:700; color:#15803d; font-size:13px; white-space:nowrap;">
                                                {{ $invoice->currency_code }} {{ number_format($invoice->balance_amount, 2) }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>

                {{-- Payment Info --}}
                <div style="margin-top:24px; border-radius:8px; border:1px solid #fde68a; background:#fefce8; padding:16px 20px;">
                    <div style="font-size:10px; text-transform:uppercase; letter-spacing:.8px; color:#92400e; font-weight:700; margin-bottom:10px;">
                        <i class="fa fa-bank mr-1"></i> Payment Information
                    </div>
                    <div class="row" style="font-size:12px; color:#374151;">
                        <div class="col-md-6" style="line-height:1.9;">
                            <strong>Bank:</strong> CRDB Bank<br>
                            <strong>Account No:</strong> 0150884401500<br>
                            <strong>Account Name:</strong> WAJENZI PROFESSIONAL COMPANY LTD
                        </div>
                        <div class="col-md-6" style="line-height:1.9;">
                            <strong>Currency:</strong> {{ $invoice->currency_code }}<br>
                            <strong>Reference:</strong> {{ $invoice->document_number }}
                        </div>
                    </div>
                </div>

                {{-- Terms & Conditions --}}
                @if($invoice->terms_conditions)
                    <div style="margin-top:20px;">
                        <button type="button"
                                onclick="var el=document.getElementById('tcBody');var ic=document.getElementById('tcIcon');el.style.display=el.style.display==='none'?'block':'none';ic.className=el.style.display==='none'?'fa fa-chevron-down':'fa fa-chevron-up';"
                                style="width:100%;display:flex;justify-content:space-between;align-items:center;background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:12px 16px;cursor:pointer;font-size:11px;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;font-weight:700;">
                            <span><i class="fa fa-file-text-o mr-2"></i>Terms &amp; Conditions</span>
                            <i class="fa fa-chevron-down" id="tcIcon"></i>
                        </button>
                        <div id="tcBody" style="display:none; border:1px solid #e5e7eb; border-top:none; border-radius:0 0 8px 8px; padding:18px 20px;">
                            <div class="tc-section">
                                {!! $invoice->terms_conditions !!}
                            </div>
                        </div>
                    </div>
                @endif

            </div>{{-- /document body --}}
        </div>{{-- /inv-doc-section --}}
    </div>

    {{-- ── Sidebar ───────────────────────────────────────────────────────────── --}}
    <div class="col-lg-4">
        <div style="display:flex;flex-direction:column;gap:16px;">

            {{-- Details --}}
            <div style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,.07);">
                <div class="sidebar-label">Details</div>
                <div class="sidebar-row">
                    <span class="s-key">Created by</span>
                    <span class="s-val">{{ $invoice->creator->name ?? 'System' }}</span>
                </div>
                <div class="sidebar-row">
                    <span class="s-key">Created</span>
                    <span class="s-val">{{ $invoice->created_at->format('d M Y, H:i') }}</span>
                </div>
                @if($invoice->sales_person)
                    <div class="sidebar-row">
                        <span class="s-key">Sales Person</span>
                        <span class="s-val">{{ $invoice->sales_person }}</span>
                    </div>
                @endif
                @if($invoice->sent_at)
                    <div class="sidebar-row">
                        <span class="s-key">Sent</span>
                        <span class="s-val">{{ $invoice->sent_at->format('d M Y, H:i') }}</span>
                    </div>
                @endif
                @if($invoice->viewed_at)
                    <div class="sidebar-row">
                        <span class="s-key">Viewed</span>
                        <span class="s-val">{{ $invoice->viewed_at->format('d M Y, H:i') }}</span>
                    </div>
                @endif
                @if($invoice->approved_at)
                    <div class="sidebar-row">
                        <span class="s-key">Approved</span>
                        <span class="s-val" style="color:#15803d;">{{ $invoice->approved_at->format('d M Y') }}</span>
                    </div>
                @endif
                @if($invoice->paid_at)
                    <div class="sidebar-row">
                        <span class="s-key">Paid</span>
                        <span class="s-val" style="color:#15803d;">{{ $invoice->paid_at->format('d M Y, H:i') }}</span>
                    </div>
                @endif
            </div>

            {{-- Payment History --}}
            @if($invoice->payments->count() > 0)
                <div style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,.07);">
                    <div class="sidebar-label" style="display:flex;justify-content:space-between;align-items:center;">
                        <span>Payment History</span>
                        <span style="background:#dcfce7;color:#15803d;border-radius:20px;padding:2px 8px;font-size:10px;font-weight:700;">
                            {{ $invoice->payments->count() }} payment{{ $invoice->payments->count() > 1 ? 's' : '' }}
                        </span>
                    </div>
                    @foreach($invoice->payments as $payment)
                        <div class="pay-hist-item">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                                <div>
                                    <div style="font-size:13px;font-weight:700;color:#111;">
                                        {{ $invoice->currency_code }} {{ number_format($payment->amount, 2) }}
                                    </div>
                                    <div style="font-size:11px;color:#9ca3af;margin-top:2px;">
                                        {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                                        @if($payment->reference_number)
                                            · {{ $payment->reference_number }}
                                        @endif
                                    </div>
                                </div>
                                <div style="font-size:11px;color:#9ca3af;white-space:nowrap;">
                                    {{ $payment->payment_date->format('d M Y') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Linked Lead --}}
            @if($invoice->lead_id && $invoice->lead)
                <div style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,.07);">
                    <div class="sidebar-label">Linked Lead</div>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                        <div style="width:38px;height:38px;border-radius:10px;background:#eff6ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fa fa-user" style="color:#3b82f6;"></i>
                        </div>
                        <div>
                            <a href="{{ route('leads.show', $invoice->lead->id) }}" style="font-weight:700;font-size:13px;color:#111;">
                                {{ $invoice->lead->lead_number ?? $invoice->lead->name }}
                            </a>
                            @if($invoice->lead->phone)
                                <div style="font-size:11px;color:#9ca3af;">{{ $invoice->lead->phone }}</div>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('leads.show', $invoice->lead->id) }}"
                       style="display:block;text-align:center;padding:7px;background:#f3f4f6;border-radius:7px;font-size:12px;font-weight:600;color:#374151;text-decoration:none;">
                        <i class="fa fa-arrow-left mr-1"></i>Back to Lead
                    </a>
                </div>
            @endif

            {{-- Related Documents --}}
            @if($invoice->parentDocument || $invoice->childDocuments->count() > 0)
                <div style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,.07);">
                    <div class="sidebar-label">Related Documents</div>
                    @if($invoice->parentDocument)
                        <div class="sidebar-row">
                            <span class="s-key">Converted from</span>
                            <a href="{{ route('billing.invoices.show', $invoice->parentDocument) }}" style="font-weight:600;font-size:12px;">
                                {{ ucfirst($invoice->parentDocument->document_type) }}
                                {{ $invoice->parentDocument->document_number }}
                            </a>
                        </div>
                    @endif
                    @foreach($invoice->childDocuments as $child)
                        <div class="sidebar-row">
                            <span class="s-key">{{ ucfirst($child->document_type) }}</span>
                            <a href="{{ route('billing.invoices.show', $child) }}" style="font-weight:600;font-size:12px;">
                                {{ $child->document_number }}
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>{{-- /sidebar --}}

</div>{{-- /row --}}
</div>{{-- /content --}}
</div>{{-- /container --}}

{{-- ── Modals ───────────────────────────────────────────────────────────────── --}}
<div class="modal fade" id="emailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('billing.invoices.send-email', $invoice) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-envelope mr-2"></i>Send Invoice via Email</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>To</label>
                        <input type="email" name="email" class="form-control" value="{{ $invoice->recipient_email }}" required>
                    </div>
                    <div class="form-group">
                        <label>CC <small class="text-muted">(comma-separated)</small></label>
                        <input type="text" name="cc" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" class="form-control" value="Invoice {{ $invoice->document_number }}" required>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" class="form-control" rows="6" required>Dear {{ $invoice->recipient_name }},

Please find attached invoice {{ $invoice->document_number }} for {{ $invoice->currency_code }} {{ number_format($invoice->total_amount, 2) }}.
@if($invoice->due_date)
Payment is due by {{ $invoice->due_date->format('d/m/Y') }}.
@endif

Thank you for choosing us

Best regards</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-send mr-1"></i>Send</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('billing.invoices.payment', $invoice) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-money mr-2"></i>Record Payment</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Amount <small class="text-muted">max {{ $invoice->currency_code }} {{ number_format($invoice->balance_amount, 2) }}</small></label>
                        <input type="number" name="amount" class="form-control" max="{{ $invoice->balance_amount }}" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Date</label>
                        <input type="text" name="payment_date" class="form-control datepicker" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Method</label>
                        <select name="payment_method" class="form-control" required>
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
                        <label>Reference <small class="text-muted">(optional)</small></label>
                        <input type="text" name="reference_number" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Notes <small class="text-muted">(optional)</small></label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fa fa-check mr-1"></i>Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="reminderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('billing.invoices.send-reminder', $invoice) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-bell mr-2"></i>Send Payment Reminder</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>To</label>
                        <input type="email" name="email" class="form-control" value="{{ $invoice->recipient_email }}" required>
                    </div>
                    <div class="form-group">
                        <label>CC <small class="text-muted">(comma-separated)</small></label>
                        <input type="text" name="cc" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="reminder_type" class="form-control" id="reminderType" onchange="updateReminderSubject()" required>
                            <option value="manual">Manual</option>
                            <option value="before_due">Before Due Date</option>
                            <option value="overdue">Overdue</option>
                            <option value="late_fee">Late Fee Applied</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" class="form-control" id="reminderSubject"
                               value="Payment Reminder - Invoice {{ $invoice->document_number }}" required>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" class="form-control" rows="6" id="reminderMessage" required>Dear {{ $invoice->recipient_name }},

This is a friendly reminder regarding your outstanding invoice payment.

Invoice Number: {{ $invoice->document_number }}
Due Date: {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A' }}
Outstanding Amount: {{ $invoice->currency_code ?? 'TZS' }} {{ number_format($invoice->balance_amount, 2) }}

Please arrange payment at your earliest convenience.

Best regards,
{{ config('app.name') }} Team</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fa fa-bell mr-1"></i>Send Reminder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="lateFeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('billing.invoices.apply-late-fee', $invoice) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Apply Late Fee</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle mr-1"></i> This cannot be undone.
                    </div>
                    <div class="form-group">
                        <label>Late Fee Percentage</label>
                        <div class="input-group">
                            <input type="number" name="late_fee_percentage" class="form-control"
                                   value="10" min="0" max="100" step="0.01" id="lateFeePercentage"
                                   onchange="calculateLateFee()" required>
                            <div class="input-group-append"><span class="input-group-text">%</span></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Fee Amount</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text">{{ $invoice->currency_code ?? 'TZS' }}</span></div>
                            <input type="text" class="form-control" id="lateFeeAmount" readonly>
                        </div>
                        <small class="text-muted">On: {{ $invoice->currency_code }} {{ number_format($invoice->total_amount - $invoice->late_fee_amount, 2) }}</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fa fa-plus mr-1"></i>Apply</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="whatsappModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#128c7e;color:#fff;">
                <h5 class="modal-title"><i class="fa fa-whatsapp mr-2"></i>Send via WhatsApp</h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="shareModeBadge" class="alert alert-info mb-3" style="display:none;">
                    <i class="fa fa-mobile mr-1"></i><strong>Mobile:</strong> PDF will be attached directly
                </div>
                <div id="shareModeDesktop" class="alert alert-secondary mb-3" style="display:none;">
                    <i class="fa fa-desktop mr-1"></i><strong>Desktop:</strong> PDF download link included
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" id="whatsappPhone" class="form-control" value="{{ $invoice->recipient_phone }}">
                    <small class="text-muted">Country code + number (no +, spaces or dashes)</small>
                </div>
                <div class="form-group">
                    <label>Message</label>
                    @php
                        $shareToken   = \App\Http\Controllers\Billing\InvoiceController::generateShareToken($invoice);
                        $publicPdfUrl = route('invoice.public', ['token' => $shareToken]);
                    @endphp
                    <textarea id="whatsappMessage" class="form-control" rows="8">Hello {{ $invoice->recipient_name }},

Please find your invoice details below:

📄 Invoice #: {{ $invoice->document_number }}
📅 Issue Date: {{ $invoice->issue_date->format('d/m/Y') }}
@if($invoice->due_date)💳 Due Date: {{ $invoice->due_date->format('d/m/Y') }}
@endif💰 Total Amount: {{ $invoice->currency_code }} {{ number_format($invoice->total_amount, 2) }}
@if($invoice->balance_amount > 0)⚠️ Balance Due: {{ $invoice->currency_code }} {{ number_format($invoice->balance_amount, 2) }}
@endif

📥 Download PDF: {{ $publicPdfUrl }}

Thank you for choosing us
Wajenzi Professional Co. Ltd</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="sendWhatsApp()" style="background:#128c7e;border-color:#128c7e;">
                    <i class="fa fa-whatsapp mr-1"></i>Open WhatsApp
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function sendEmailModal()    { $('#emailModal').modal('show'); }
function sendReminderModal() { $('#reminderModal').modal('show'); }
function recordPaymentModal(){ $('#paymentModal').modal('show'); }

function sendWhatsAppModal() {
    let ok = false;
    try { ok = navigator.canShare && navigator.canShare({ files: [new File([''], 'x.pdf', {type:'application/pdf'})] }); } catch(e){}
    document.getElementById('shareModeBadge').style.display   = ok ? 'block' : 'none';
    document.getElementById('shareModeDesktop').style.display = ok ? 'none'  : 'block';
    $('#whatsappModal').modal('show');
}

async function sendWhatsApp() {
    let phone   = document.getElementById('whatsappPhone').value.replace(/[\s\-\+\(\)]/g,'');
    let message = document.getElementById('whatsappMessage').value;
    const btn   = document.querySelector('#whatsappModal .btn-success');
    if (phone.startsWith('0')) phone = '255' + phone.substring(1);
    let ok = false;
    try { ok = navigator.canShare && navigator.canShare({ files: [new File([''], 'x.pdf', {type:'application/pdf'})] }); } catch(e){}
    if (ok) {
        try {
            btn.disabled = true;
            btn.querySelector('i').className = 'fa fa-spinner fa-spin';
            const blob = await (await fetch('{{ $publicPdfUrl }}')).blob();
            await navigator.share({ title:'Invoice {{ $invoice->document_number }}', text: message, files:[new File([blob],'Invoice-{{ $invoice->document_number }}.pdf',{type:'application/pdf'})] });
            $('#whatsappModal').modal('hide');
        } catch(e) {
            if (e.name !== 'AbortError') fallbackWhatsAppShare(phone, message);
        } finally {
            btn.disabled = false;
            btn.querySelector('i').className = 'fa fa-whatsapp';
        }
    } else {
        fallbackWhatsAppShare(phone, message);
    }
}

function fallbackWhatsAppShare(phone, message) {
    window.open(`https://wa.me/${phone}?text=${encodeURIComponent(message)}`, '_blank');
    $('#whatsappModal').modal('hide');
}

function applyLateFeeModal() { $('#lateFeeModal').modal('show'); calculateLateFee(); }

function updateReminderSubject() {
    const m = { before_due:`Payment Reminder - Invoice {{ $invoice->document_number }} (Due Soon)`, overdue:`Overdue Payment - Invoice {{ $invoice->document_number }}`, late_fee:`Late Fee Applied - Invoice {{ $invoice->document_number }}`, manual:`Payment Reminder - Invoice {{ $invoice->document_number }}` };
    document.getElementById('reminderSubject').value = m[document.getElementById('reminderType').value] || m.manual;
}

function calculateLateFee() {
    document.getElementById('lateFeeAmount').value = ({{ $invoice->total_amount - $invoice->late_fee_amount }} * (parseFloat(document.getElementById('lateFeePercentage').value)||0) / 100).toFixed(2);
}
</script>

@if($invoice->is_editable)
<form id="deleteInvoiceForm" method="POST" action="{{ route('billing.invoices.destroy', $invoice) }}" style="display:none">
    @csrf @method('DELETE')
</form>
@endif
@endsection

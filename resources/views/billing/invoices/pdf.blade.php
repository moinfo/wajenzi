<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $invoice->document_number }}</title>
    <style>
        @page { margin: 0 0 20px 0; }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.45;
            margin: 0; padding: 0;
            color: #1a2332;
        }

        /* ── Header ─────────────────────── */
        .hdr-accent   { background: #1BC5BD; height: 5px; width: 100%; font-size: 0; }
        .hdr-wrap     { padding: 14px 30px 12px; border-bottom: 1px solid #e0e0e0; }
        .hdr-table    { width: 100%; border-collapse: collapse; }
        .hdr-logo-td  { width: 80px; vertical-align: middle; }
        .hdr-mid-td   { text-align: center; vertical-align: middle; padding: 0 10px; }
        .hdr-co-name  { font-size: 18px; font-weight: 900; color: #1a2332; letter-spacing: .5px; line-height: 1.15; }
        .hdr-co-sub   { font-size: 9px; color: #6b7280; margin-top: 3px; line-height: 1.6; }
        .hdr-contact-td { width: 180px; text-align: right; vertical-align: middle; font-size: 9px; color: #6b7280; line-height: 1.7; }

        /* ── Invoice title band ─────────── */
        .title-band   { background: #1a2332; padding: 16px 30px; }
        .title-band table { width: 100%; border-collapse: collapse; }
        .title-main   { font-size: 32px; font-weight: 900; color: #ffffff; letter-spacing: 2px; line-height: 1; }
        .title-num    { font-size: 12px; color: #9ca3af; margin-top: 4px; }
        .title-meta td { padding: 2px 0; font-size: 10px; }
        .title-meta .lbl { color: #9ca3af; padding-right: 12px; text-align: right; }
        .title-meta .val { color: #ffffff; font-weight: 700; }
        .title-meta .val-red { color: #f87171; font-weight: 700; }

        /* ── Status badge ───────────────── */
        .badge        { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 9px; font-weight: 700; text-transform: uppercase; margin-top: 6px; }
        .badge-paid   { background: #dcfce7; color: #15803d; }
        .badge-sent   { background: #cffafe; color: #0e7490; }
        .badge-viewed { background: #dbeafe; color: #1d4ed8; }
        .badge-pending{ background: #fef9c3; color: #854d0e; }
        .badge-overdue{ background: #fee2e2; color: #991b1b; }
        .badge-draft  { background: #f3f4f6; color: #374151; }
        .badge-void   { background: #f3f4f6; color: #6b7280; }

        /* ── Body content ───────────────── */
        .body-pad { padding: 20px 30px; }

        /* ── Bill To / Invoice For ──────── */
        .section-label { font-size: 8.5px; text-transform: uppercase; letter-spacing: .9px; color: #9ca3af; font-weight: 700; margin-bottom: 5px; }
        .bill-to-box { background: #f8f9fa; border-left: 3px solid #1BC5BD; padding: 10px 12px; }
        .client-name { font-size: 13px; font-weight: 800; color: #1a2332; }
        .client-detail { font-size: 10px; color: #555; margin-top: 2px; line-height: 1.6; }
        .inv-for-title { font-size: 12px; font-weight: 800; color: #1a2332; margin-bottom: 4px; }

        /* ── Service description ─────────── */
        .service-desc { font-size: 10px; color: #374151; line-height: 1.5; }
        .service-desc ol, .service-desc ul { margin: 2px 0; padding-left: 16px; }
        .service-desc ol { list-style-type: decimal; }
        .service-desc ul { list-style-type: disc; }
        .service-desc li { margin-bottom: 1px; padding-left: 2px; font-size: 10px; }
        .service-desc li ol, .service-desc li ul { margin: 1px 0; padding-left: 14px; }
        .service-desc p { margin: 1px 0; }

        /* ── Divider ────────────────────── */
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 14px 0; }

        /* ── Items table ────────────────── */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .items-table thead tr { background: #1a2332; }
        .items-table th {
            color: #ffffff; padding: 9px 8px;
            font-size: 9.5px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .5px;
        }
        .items-table tbody tr:nth-child(even) { background: #f8f9fa; }
        .items-table td { padding: 9px 8px; border-bottom: 1px solid #e5e7eb; font-size: 10px; vertical-align: top; }
        .items-table tbody tr:last-child td { border-bottom: 2px solid #1a2332; }
        .item-name { font-weight: 700; color: #1a2332; font-size: 11px; }
        .item-desc { color: #9ca3af; font-size: 9px; margin-top: 2px; }
        .tr { text-align: right; }
        .tc { text-align: center; }

        /* ── Totals ─────────────────────── */
        .totals-outer { width: 100%; border-collapse: collapse; margin-top: 0; }
        .totals-outer .notes-td { vertical-align: top; padding: 12px 0; }
        .totals-outer .totals-td { vertical-align: top; padding: 12px 0; width: 260px; }
        .totals-inner { width: 100%; border-collapse: collapse; }
        .totals-inner td { padding: 4px 8px; font-size: 11px; }
        .totals-inner .t-lbl { color: #6b7280; }
        .totals-inner .t-val { text-align: right; font-weight: 600; color: #1a2332; }
        .total-main { background: #1a2332; }
        .total-main td { padding: 10px 8px; font-size: 14px; font-weight: 900; color: #ffffff; }
        .paid-row td { background: #f0fdf4; color: #15803d; font-weight: 700; padding: 5px 8px; font-size: 11px; }
        .balance-row td { background: #1BC5BD; color: #ffffff; font-weight: 700; padding: 7px 8px; font-size: 12px; }

        /* ── Payment box ────────────────── */
        .payment-box { border: 1px solid #fde68a; background: #fefce8; border-radius: 5px; padding: 12px 15px; }
        .payment-box-title { font-size: 10px; text-transform: uppercase; letter-spacing: .8px; color: #92400e; font-weight: 700; margin-bottom: 8px; }
        .payment-box table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .payment-box td { padding: 2px 0; vertical-align: top; color: #374151; line-height: 1.7; }

        /* ── Stamp ──────────────────────── */
        .stamp-wrap { text-align: right; margin: 8px 0 4px; }

        /* ── Footer ─────────────────────── */
        .footer-band { background: #1a2332; margin-top: 16px; padding: 10px 30px; }
        .footer-band table { width: 100%; border-collapse: collapse; }
        .footer-band td { color: #9ca3af; font-size: 9px; vertical-align: middle; line-height: 1.7; }

        /* ── Page 2 ─────────────────────── */
        .page-break { page-break-before: always; }
        .tc-hdr-band { background: #1a2332; padding: 10px 30px; }
        .tc-hdr-band table { width: 100%; border-collapse: collapse; }
        .tc-hdr-band td { vertical-align: middle; }
        .tc-hdr-co { font-size: 13px; font-weight: 900; color: #ffffff; }
        .tc-hdr-sub { font-size: 9px; color: #9ca3af; margin-top: 1px; }
        .tc-title-band { background: #f8f9fa; border-bottom: 2px solid #1BC5BD; padding: 12px 30px; text-align: center; }
        .tc-title { font-size: 15px; font-weight: 900; color: #1a2332; text-transform: uppercase; letter-spacing: 1px; }
        .tc-body { padding: 16px 30px; font-size: 10px; line-height: 1.6; color: #374151; }
        .tc-body h1, .tc-body h2 { font-size: 11px; font-weight: 700; color: #1a2332; margin: 12px 0 4px; }
        .tc-body h3 { font-size: 10.5px; font-weight: 700; color: #374151; margin: 8px 0 3px; }
        .tc-body p { margin: 2px 0 6px; text-align: justify; }
        .tc-body ul, .tc-body ol { padding-left: 16px; margin: 3px 0 7px; }
        .tc-body li { margin-bottom: 3px; }
        .tc-body strong { color: #1a2332; }
        .tc-clause { border-left: 2px solid #1BC5BD; padding-left: 8px; margin-bottom: 10px; }
        .tc-footer { border-top: 1px solid #e5e7eb; padding: 8px 30px; margin-top: 10px; }
        .tc-footer table { width: 100%; border-collapse: collapse; font-size: 8.5px; color: #9ca3af; }
    </style>
</head>
<body>

{{-- ==================== PAGE 1 ==================== --}}

{{-- Teal accent bar --}}
<div class="hdr-accent"></div>

{{-- Company header --}}
<div class="hdr-wrap">
    <table class="hdr-table">
        <tr>
            <td class="hdr-logo-td">
                <img src="{{ public_path('media/logo/wajenzilogo.png') }}" alt="Logo" style="height: 60px;">
            </td>
            <td class="hdr-mid-td">
                <div class="hdr-co-name">WAJENZI PROFESSIONAL CO. LTD</div>
                <div class="hdr-co-sub">
                    PSSSF COMMERCIAL COMPLEX, SAM NUJOMA ROAD, DAR ES SALAAM, TANZANIA<br>
                    P.O. Box 14492, Dar es Salaam &nbsp;|&nbsp; TIN: 154-867-805
                </div>
            </td>
            <td class="hdr-contact-td">
                +255 793 444 400<br>
                billing@wajenziprofessional.co.tz
            </td>
        </tr>
    </table>
</div>

{{-- Invoice title band --}}
<div class="title-band">
    <table>
        <tr>
            <td style="vertical-align: bottom; width: 55%;">
                <div class="title-main">INVOICE</div>
                <div class="title-num">{{ $invoice->document_number }}</div>
                @php
                    $statusMap = [
                        'paid'    => 'badge-paid',
                        'sent'    => 'badge-sent',
                        'viewed'  => 'badge-viewed',
                        'pending' => 'badge-pending',
                        'overdue' => 'badge-overdue',
                        'draft'   => 'badge-draft',
                        'void'    => 'badge-void',
                    ];
                    $badgeClass = $statusMap[$invoice->status] ?? 'badge-draft';
                @endphp
                <span class="badge {{ $badgeClass }}">{{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</span>
            </td>
            <td style="vertical-align: middle; text-align: right;">
                <table class="title-meta" style="margin-left: auto;">
                    @if($invoice->reference_number)
                        <tr>
                            <td class="lbl">Reference:</td>
                            <td class="val">{{ $invoice->reference_number }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="lbl">Issue Date:</td>
                        <td class="val">{{ $invoice->issue_date->format('d M Y') }}</td>
                    </tr>
                    @if($invoice->due_date)
                        <tr>
                            <td class="lbl">Due Date:</td>
                            <td class="{{ $invoice->is_overdue ? 'val-red' : 'val' }}">
                                {{ $invoice->due_date->format('d M Y') }}
                                @if($invoice->is_overdue) &nbsp;&#9888; OVERDUE @endif
                            </td>
                        </tr>
                    @endif
                    @if($invoice->po_number)
                        <tr>
                            <td class="lbl">PO Number:</td>
                            <td class="val">{{ $invoice->po_number }}</td>
                        </tr>
                    @endif
                    @if($invoice->sales_person)
                        <tr>
                            <td class="lbl">Sales Person:</td>
                            <td class="val">{{ $invoice->sales_person }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>
</div>

<div class="body-pad">

    {{-- Bill To + Invoice For --}}
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 14px;">
        <tr>
            <td style="width: 40%; vertical-align: top; padding-right: 16px;">
                <div class="section-label">Bill To</div>
                <div class="bill-to-box">
                    <div class="client-name">{{ $invoice->client->first_name }} {{ $invoice->client->last_name }}</div>
                    <div class="client-detail">
                        @if($invoice->client->address){{ $invoice->client->address }}<br>@endif
                        @if($invoice->client->phone_number)<strong>Phone:</strong> {{ $invoice->client->phone_number }}<br>@endif
                        @if($invoice->client->email)<strong>Email:</strong> {{ $invoice->client->email }}<br>@endif
                        @if($invoice->client->identification_number)<strong>ID:</strong> {{ $invoice->client->identification_number }}@endif
                    </div>
                </div>
            </td>
            <td style="vertical-align: top; padding-left: 8px;">
                @if($invoice->title || $invoice->service_description)
                    <div class="section-label">Invoice For</div>
                    @if($invoice->title)
                        <div class="inv-for-title">{{ $invoice->title }}</div>
                    @endif
                    @if($invoice->service_description)
                        <div style="margin-top: 4px; font-size: 10px; color: #555;">
                            <strong style="font-size: 10px;">Service Includes:</strong>
                            <div class="service-desc" style="margin-top: 3px;">
                                {!! str_replace(['&nbsp;', "\xC2\xA0"], ' ', $invoice->service_description) !!}
                            </div>
                        </div>
                    @endif
                @endif
            </td>
        </tr>
    </table>

    {{-- Items table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 38%; text-align: left;">Item / Description</th>
                <th style="width: 8%;" class="tc">Qty</th>
                <th style="width: 8%;" class="tc">Unit</th>
                <th style="width: 17%;" class="tr">Unit Price</th>
                <th style="width: 11%;" class="tr">Tax</th>
                <th style="width: 18%;" class="tr">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>
                        <div class="item-name">{{ $item->item_name }}</div>
                        @if($item->description)
                            <div class="item-desc">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td class="tc">{{ number_format($item->quantity, 2) }}</td>
                    <td class="tc" style="color: #9ca3af;">{{ $item->unit_of_measure ?? '—' }}</td>
                    <td class="tr">{{ $invoice->currency_code }} {{ number_format($item->unit_price, 2) }}</td>
                    <td class="tr" style="color: #6b7280;">
                        @if($item->tax_percentage > 0)
                            {{ $item->tax_percentage }}%<br>
                            <span style="font-size: 9px;">{{ number_format($item->tax_amount, 2) }}</span>
                        @else
                            <span style="color: #d1d5db;">—</span>
                        @endif
                    </td>
                    <td class="tr" style="font-weight: 700;">{{ $invoice->currency_code }} {{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <table class="totals-outer">
        <tr>
            <td class="notes-td">
                @if($invoice->notes)
                    <div class="section-label">Notes</div>
                    <div style="font-size: 10px; color: #555; max-width: 300px; line-height: 1.6;">{{ $invoice->notes }}</div>
                @endif
            </td>
            <td class="totals-td">
                <table class="totals-inner">
                    <tr>
                        <td class="t-lbl">Subtotal</td>
                        <td class="t-val">{{ $invoice->currency_code }} {{ number_format($invoice->subtotal_amount, 2) }}</td>
                    </tr>
                    @if($invoice->discount_amount > 0)
                        <tr>
                            <td class="t-lbl">Discount</td>
                            <td class="t-val" style="color: #16a34a;">−{{ $invoice->currency_code }} {{ number_format($invoice->discount_amount, 2) }}</td>
                        </tr>
                    @endif
                    @if($invoice->tax_amount > 0)
                        <tr>
                            <td class="t-lbl">Tax</td>
                            <td class="t-val">{{ $invoice->currency_code }} {{ number_format($invoice->tax_amount, 2) }}</td>
                        </tr>
                    @endif
                    @if($invoice->shipping_amount > 0)
                        <tr>
                            <td class="t-lbl">Shipping</td>
                            <td class="t-val">{{ $invoice->currency_code }} {{ number_format($invoice->shipping_amount, 2) }}</td>
                        </tr>
                    @endif
                </table>
                <table style="width: 100%; border-collapse: collapse; margin-top: 2px;">
                    <tr class="total-main">
                        <td style="font-size: 14px; font-weight: 900; color: #fff; padding: 9px 8px;">TOTAL</td>
                        <td style="font-size: 14px; font-weight: 900; color: #fff; padding: 9px 8px; text-align: right; white-space: nowrap;">
                            {{ $invoice->currency_code }} {{ number_format($invoice->total_amount, 2) }}
                        </td>
                    </tr>
                    @if($invoice->paid_amount > 0)
                        <tr>
                            <td style="background: #f0fdf4; color: #15803d; font-weight: 700; padding: 5px 8px; font-size: 11px;">Paid</td>
                            <td style="background: #f0fdf4; color: #15803d; font-weight: 700; padding: 5px 8px; text-align: right; font-size: 11px; white-space: nowrap;">
                                −{{ $invoice->currency_code }} {{ number_format($invoice->paid_amount, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td style="background: #1BC5BD; color: #fff; font-weight: 900; padding: 8px 8px; font-size: 12px;">BALANCE DUE</td>
                            <td style="background: #1BC5BD; color: #fff; font-weight: 900; padding: 8px 8px; text-align: right; font-size: 12px; white-space: nowrap;">
                                {{ $invoice->currency_code }} {{ number_format($invoice->balance_amount, 2) }}
                            </td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- Payment box (instructions + bank details combined) --}}
    <div style="page-break-inside: avoid;">
        <div class="payment-box" style="margin-top: 14px;">
            <div class="payment-box-title">Payment Information</div>
            <table>
                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        <strong>Bank:</strong> CRDB Bank<br>
                        <strong>Account Number:</strong> 0150884401500<br>
                        <strong>Account Name:</strong> WAJENZI PROFESSIONAL COMPANY LTD
                    </td>
                    <td style="width: 25%; vertical-align: top;">
                        <strong>Currency:</strong> {{ $invoice->currency_code }}<br>
                        <strong>Reference:</strong> {{ $invoice->document_number }}<br>
                        @if($invoice->due_date && $invoice->balance_amount > 0)
                            <strong>Due:</strong> {{ $invoice->due_date->format('d M Y') }}
                        @endif
                    </td>
                    <td style="width: 25%; vertical-align: top; text-align: right; color: #92400e; font-size: 10px;">
                        @if($invoice->balance_amount > 0)
                            <strong>Amount Due</strong><br>
                            <span style="font-size: 13px; font-weight: 900; color: #1a2332;">
                                {{ $invoice->currency_code }} {{ number_format($invoice->balance_amount, 2) }}
                            </span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        {{-- Stamp with date overlay --}}
        <div style="text-align: right; margin: 10px 0 4px;">
            <div style="position: relative; width: 210px; height: 140px; display: inline-block;">
                <img src="{{ public_path('images/invoice-stamp.png') }}"
                     alt="Stamp"
                     style="position: absolute; top: 0; left: 0; width: 210px; height: 140px;">
                <div style="position: absolute; top: 34px; left: 0; width: 210px; text-align: center;">
                    <span style="color: #cc0000; font-size: 16px; font-weight: 900; letter-spacing: 1.5px;">
                        {{ strtoupper($invoice->issue_date->format('d M Y')) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer-band">
            <table>
                <tr>
                    <td style="vertical-align: middle;">
                        <span style="color: #ffffff; font-weight: 700; font-size: 10px;">WAJENZI PROFESSIONAL CO. LTD</span><br>
                        info@wajenziprofessional.co.tz &nbsp;|&nbsp; +255 793 444 400 &nbsp;|&nbsp; Instagram: wajenziprofessionaltz<br>
                        PSSSF Commercial Complex, Ground Floor, Sam Nujoma Road, Dar es Salaam
                    </td>
                    <td style="text-align: right; vertical-align: middle; width: 75px;">
                        @if(file_exists(public_path('media/logo/instagram-qr.png')))
                            <img src="{{ public_path('media/logo/instagram-qr.png') }}" alt="QR" style="width: 55px; height: 55px;">
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

</div>{{-- /body-pad --}}

{{-- ==================== PAGE 2: TERMS & CONDITIONS ==================== --}}
@php $hasTerms = !empty(trim(strip_tags($invoice->terms_conditions ?? ''))); @endphp
@if($hasTerms)
<div class="page-break"></div>

{{-- Teal accent bar --}}
<div class="hdr-accent"></div>

{{-- Compact header for T&C page --}}
<div class="tc-hdr-band">
    <table>
        <tr>
            <td style="width: 50px; vertical-align: middle;">
                <img src="{{ public_path('media/logo/wajenzilogo.png') }}" alt="Logo" style="height: 36px; filter: brightness(0) invert(1); opacity: .9;">
            </td>
            <td style="vertical-align: middle; padding-left: 12px;">
                <div class="tc-hdr-co">WAJENZI PROFESSIONAL CO. LTD</div>
                <div class="tc-hdr-sub">billing@wajenziprofessional.co.tz &nbsp;|&nbsp; +255 793 444 400</div>
            </td>
            <td style="text-align: right; vertical-align: middle; font-size: 9px; color: #6b7280;">
                Ref: {{ $invoice->document_number }}<br>
                {{ $invoice->issue_date->format('d M Y') }}
            </td>
        </tr>
    </table>
</div>

{{-- T&C title --}}
<div class="tc-title-band">
    <div class="tc-title">Terms &amp; Conditions of the Invoice</div>
</div>

<div class="tc-body">
    @if($invoice->terms_conditions)
        {!! $invoice->terms_conditions !!}
    @else
        <div class="tc-clause">
            <strong>1. Payment Terms</strong>
            <p>Payment shall be made according to the schedule outlined in the invoice. A deposit of 60% of the total project cost is required before commencement of work. The remaining 40% balance is due upon completion and delivery of the project. Late payments may attract a penalty of 2% per month on the outstanding balance.</p>
        </div>
        <div class="tc-clause">
            <strong>2. Project Deliverables, Changes &amp; Revisions</strong>
            <p><strong>2D Design Stage:</strong> Initial concept designs will be presented based on the client's brief. Up to two (2) rounds of revisions are included. Additional revisions will be charged at 10% of the design fee per revision.</p>
            <p><strong>3D Design Stage:</strong> 3D visualization commences only after approval of the final 2D design. Up to two (2) rounds of revisions included. Final renders delivered upon full payment.</p>
        </div>
        <div class="tc-clause">
            <strong>3. Validity</strong>
            <p>This invoice is valid for seven (7) days from the date of issue. After this period, prices may be subject to review and adjustment without prior notice.</p>
        </div>
        <div class="tc-clause">
            <strong>4. Taxes &amp; Statutory Deductions</strong>
            <p>All prices quoted are exclusive of applicable taxes unless otherwise stated. VAT at 18% will be applied where applicable. Withholding tax and other statutory deductions shall be borne by the respective party per Tanzanian law.</p>
        </div>
        <div class="tc-clause">
            <strong>5. Ownership of Work</strong>
            <p>All intellectual property rights remain the sole property of Wajenzi Professional Company Ltd until full payment is received. Upon receipt of full payment, ownership of final deliverables is transferred to the client.</p>
        </div>
        <div class="tc-clause">
            <strong>6. Cancellation Policy</strong>
            <p>Cancellation before commencement — 80% refund of deposit. Cancellation after commencement but before 50% completion — 40% refund. Cancellation after 50% completion — no refund. All cancellation requests must be in writing.</p>
        </div>
        <div class="tc-clause">
            <strong>7. Dispute Resolution</strong>
            <p>Both parties shall first attempt to resolve disputes amicably. If unresolved within 14 days, the matter shall be referred to mediation then arbitration per the laws of the United Republic of Tanzania. Venue: Dar es Salaam.</p>
        </div>
        <div class="tc-clause">
            <strong>8. Agreement</strong>
            <p>By making payment or confirming acceptance, the client acknowledges they have read, understood, and agreed to all terms and conditions stated herein.</p>
        </div>
    @endif
</div>

<div class="tc-footer">
    <table>
        <tr>
            <td><strong style="color: #1a2332;">WAJENZI PROFESSIONAL CO. LTD</strong> &nbsp;|&nbsp; billing@wajenziprofessional.co.tz &nbsp;|&nbsp; +255 793 444 400</td>
            <td style="text-align: right; font-size: 8px; color: #9ca3af;">Page 2 of 2</td>
        </tr>
    </table>
</div>

@endif
</body>
</html>

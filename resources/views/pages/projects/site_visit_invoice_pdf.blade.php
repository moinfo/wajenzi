@php
    $client = $visit->project->client ?? $visit->client ?? null;
    $clientName = $client ? trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')) : 'N/A';
    $subject = $visit->project->project_name ?? $clientName;
    $loc = $visit->siteVisitLocation;
    $days = max(1, (int) $visit->visit_days);
    $isPaid = (bool) $visit->payment_confirmed_at;
    $perDay = $loc ? ((float)$loc->preset_travel_tzs + (float)$loc->preset_local_tzs + (float)$loc->preset_allowance_tzs + (float)$loc->preset_food_tzs + (float)$loc->preset_accommodation_tzs) : 0;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $visit->invoice_number }}</title>
    <style>
        @page { margin: 0 0 20px 0; }
        body { font-family: Arial, sans-serif; font-size: 11px; line-height: 1.45; margin: 0; padding: 0; color: #1a2332; }

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

        /* ── Status badge ───────────────── */
        .badge        { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 9px; font-weight: 700; text-transform: uppercase; margin-top: 6px; }
        .badge-paid   { background: #dcfce7; color: #15803d; }
        .badge-pending{ background: #fef9c3; color: #854d0e; }

        /* ── Body ───────────────────────── */
        .body-pad { padding: 20px 30px; }
        .section-label { font-size: 8.5px; text-transform: uppercase; letter-spacing: .9px; color: #9ca3af; font-weight: 700; margin-bottom: 5px; }
        .bill-to-box { background: #f8f9fa; border-left: 3px solid #1BC5BD; padding: 10px 12px; }
        .client-name { font-size: 13px; font-weight: 800; color: #1a2332; }
        .client-detail { font-size: 10px; color: #555; margin-top: 2px; line-height: 1.6; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 14px 0; }

        /* ── Items table ────────────────── */
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table thead tr { background: #1a2332; }
        .items-table th { color: #ffffff; padding: 9px 8px; font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
        .items-table td { padding: 9px 8px; border-bottom: 1px solid #e5e7eb; font-size: 10px; vertical-align: top; }
        .items-table tbody tr:last-child td { border-bottom: 2px solid #1a2332; }
        .item-name { font-weight: 700; color: #1a2332; font-size: 11px; }
        .item-desc { color: #9ca3af; font-size: 9px; margin-top: 2px; }
        .tr { text-align: right; }
        .tc { text-align: center; }

        /* ── Totals ─────────────────────── */
        .totals-outer { width: 100%; border-collapse: collapse; }
        .totals-outer .totals-td { vertical-align: top; padding: 12px 0; width: 260px; }
        .totals-inner { width: 100%; border-collapse: collapse; }
        .totals-inner td { padding: 4px 8px; font-size: 11px; }
        .totals-inner .t-lbl { color: #6b7280; }
        .totals-inner .t-val { text-align: right; font-weight: 600; color: #1a2332; }
        .total-main { background: #1a2332; }
        .total-main td { padding: 10px 8px; font-size: 14px; font-weight: 900; color: #ffffff; }
        .paid-row td { background: #f0fdf4; color: #15803d; font-weight: 700; padding: 5px 8px; font-size: 11px; }

        /* ── Footer ─────────────────────── */
        .footer-band { background: #1a2332; margin-top: 16px; padding: 10px 30px; }
        .footer-band table { width: 100%; border-collapse: collapse; }
        .footer-band td { color: #9ca3af; font-size: 9px; vertical-align: middle; line-height: 1.7; }
        .muted { color: #9ca3af; font-size: 9px; }
    </style>
</head>
<body>

{{-- Teal accent bar --}}
<div class="hdr-accent"></div>

{{-- Company header (same as billing invoices) --}}
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
                <div class="title-num">{{ $visit->invoice_number }}</div>
                <span class="badge {{ $isPaid ? 'badge-paid' : 'badge-pending' }}">{{ $isPaid ? 'Paid' : 'Pending Payment' }}</span>
            </td>
            <td style="vertical-align: middle; text-align: right;">
                <table class="title-meta" style="margin-left: auto;">
                    <tr><td class="lbl">Reference:</td><td class="val">{{ $visit->reference_number }}</td></tr>
                    <tr><td class="lbl">Issue Date:</td><td class="val">{{ optional($visit->created_at)->format('d M Y') }}</td></tr>
                    <tr><td class="lbl">Visit Date:</td><td class="val">{{ optional($visit->visit_date)->format('d M Y') }}</td></tr>
                </table>
            </td>
        </tr>
    </table>
</div>

<div class="body-pad">
    {{-- Bill To --}}
    <div class="section-label">Bill To</div>
    <div class="bill-to-box">
        <div class="client-name">{{ $clientName }}</div>
        <div class="client-detail">
            @if($visit->phone_number)Phone: {{ $visit->phone_number }}<br>@endif
            @if($visit->project)Project: {{ $visit->project->project_name }}<br>@endif
            @if($visit->location)Site: {{ $visit->location }}@endif
        </div>
    </div>

    <hr class="divider">

    {{-- Items --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="text-align:left; width:60%;">Description</th>
                <th class="tc" style="width:15%;">Days</th>
                <th class="tr" style="width:25%;">Amount (TZS)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div class="item-name">Site visit{{ $loc ? ' — ' . $loc->name : '' }}</div>
                    <div class="item-desc">For {{ $subject }}@if($loc)
                        &nbsp;|&nbsp; Base {{ number_format((float) $loc->base_cost_tzs) }}@if($perDay > 0) + {{ number_format($perDay) }}/day × {{ $days }}@endif
                    @endif</div>
                </td>
                <td class="tc">{{ $days }}</td>
                <td class="tr">{{ number_format((float) $visit->invoice_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Totals --}}
    <table class="totals-outer">
        <tr>
            <td style="vertical-align: top;">
                <div class="muted" style="padding-top:12px;">Amounts are VAT exclusive unless stated otherwise.</div>
            </td>
            <td class="totals-td">
                <table class="totals-inner">
                    <tr><td class="t-lbl">Subtotal</td><td class="t-val">{{ number_format((float) $visit->invoice_amount, 2) }}</td></tr>
                    <tr class="total-main"><td>Total</td><td class="tr">{{ number_format((float) $visit->invoice_amount, 2) }} TZS</td></tr>
                    @if($isPaid)
                        <tr class="paid-row"><td>Paid</td><td class="tr">{{ $visit->payment_confirmed_at->format('d M Y') }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>
</div>

{{-- Footer --}}
<div class="footer-band">
    <table>
        <tr>
            <td>
                @if($visit->billedBy)Prepared by: {{ $visit->billedBy->name }} &nbsp;|&nbsp; @endif
                Generated {{ optional($visit->created_at)->format('d M Y') }}
            </td>
            <td style="text-align:right;">WAJENZI PROFESSIONAL CO. LTD &nbsp;|&nbsp; billing@wajenziprofessional.co.tz</td>
        </tr>
    </table>
</div>

</body>
</html>

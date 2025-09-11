<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Proforma Invoice {{ $proforma->document_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .company-details {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .proforma-details {
            width: 100%;
            margin-bottom: 30px;
        }
        .proforma-details td {
            vertical-align: top;
            padding: 5px 0;
        }
        .proforma-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            text-align: right;
        }
        .proforma-number {
            font-size: 16px;
            font-weight: bold;
            color: #666;
            text-align: right;
        }
        .client-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .client-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background-color: #333;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #ddd;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table .text-center {
            text-align: center;
        }
        .item-description {
            color: #666;
            font-size: 11px;
            margin-top: 3px;
        }
        .totals-table {
            width: 300px;
            margin-left: auto;
            margin-bottom: 30px;
        }
        .totals-table td {
            padding: 5px 10px;
            border-bottom: 1px solid #eee;
        }
        .totals-table .total-row {
            background-color: #333;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        .notes {
            margin-bottom: 20px;
        }
        .notes-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .footer {
            text-align: center;
            font-style: italic;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
            margin-top: 30px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-draft { background-color: #6c757d; color: white; }
        .status-pending { background-color: #17a2b8; color: white; }
        .status-sent { background-color: #007bff; color: white; }
        .status-viewed { background-color: #ffc107; color: black; }
        .status-accepted { background-color: #28a745; color: white; }
        .status-rejected { background-color: #dc3545; color: white; }
        .status-cancelled { background-color: #6c757d; color: white; }
        .validity-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <img src="{{ public_path('media/logo/wajenzilogo.png') }}" alt="{{ config('app.name') }}" style="max-height: 60px; margin-bottom: 10px;">
        <div class="company-name">{{ config('app.name') }}</div>
        <div class="company-details">
            PSSSF COMMERCIAL COMPLEX, SAM NUJOMA ROAD, DSM-TANZANIA<br>
            P. O. Box 14492, Dar es Salaam Tanzania<br>
            Phone: +255 793 444 400 | Email: billing@wajenziprofessional.co.tz<br>
            TIN: 154-867-805
        </div>
    </div>

    <!-- Proforma Details -->
    <table class="proforma-details">
        <tr>
            <td width="50%">
                <div class="proforma-title">PROFORMA INVOICE</div>
                <div class="proforma-number">{{ $proforma->document_number }}</div>
                <div style="margin-top: 15px;">
                    <span class="status-badge status-{{ $proforma->status }}">
                        {{ ucfirst(str_replace('_', ' ', $proforma->status)) }}
                    </span>
                </div>
            </td>
            <td width="50%" style="text-align: right;">
                <table style="margin-left: auto;">
                    @if($proforma->reference_number)
                        <tr>
                            <td><strong>Reference:</strong></td>
                            <td style="padding-left: 15px;">{{ $proforma->reference_number }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td><strong>Issue Date:</strong></td>
                        <td style="padding-left: 15px;">{{ $proforma->issue_date->format('d/m/Y') }}</td>
                    </tr>
                    @if($proforma->valid_until_date)
                        <tr>
                            <td><strong>Valid Until:</strong></td>
                            <td style="padding-left: 15px;">
                                {{ $proforma->valid_until_date->format('d/m/Y') }}
                                @if($proforma->valid_until_date->isPast() && !in_array($proforma->status, ['accepted', 'cancelled']))
                                    <span style="color: #ffc107; font-weight: bold;">(EXPIRED)</span>
                                @endif
                            </td>
                        </tr>
                    @endif
                    @if($proforma->sales_person)
                        <tr>
                            <td><strong>Sales Person:</strong></td>
                            <td style="padding-left: 15px;">{{ $proforma->sales_person }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- Client Details -->
    <div class="client-details">
        <div class="client-title">PROFORMA FOR:</div>
        <strong>{{ $proforma->client->company_name }}</strong><br>
        @if($proforma->client->contact_person)
            {{ $proforma->client->contact_person }}<br>
        @endif
        @if($proforma->client->billing_address_line1)
            {{ $proforma->client->billing_address_line1 }}<br>
        @endif
        @if($proforma->client->billing_address_line2)
            {{ $proforma->client->billing_address_line2 }}<br>
        @endif
        @if($proforma->client->billing_city || $proforma->client->billing_postal_code)
            {{ $proforma->client->billing_city }} {{ $proforma->client->billing_postal_code }}<br>
        @endif
        @if($proforma->client->billing_country)
            {{ $proforma->client->billing_country }}<br>
        @endif
        @if($proforma->client->phone)
            <strong>Phone:</strong> {{ $proforma->client->phone }}<br>
        @endif
        @if($proforma->client->email)
            <strong>Email:</strong> {{ $proforma->client->email }}<br>
        @endif
        @if($proforma->client->tax_identification_number)
            <strong>TIN:</strong> {{ $proforma->client->tax_identification_number }}
        @endif
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th width="40%">Item/Description</th>
                <th width="10%" class="text-center">Qty</th>
                <th width="8%" class="text-center">Unit</th>
                <th width="15%" class="text-right">Unit Price</th>
                <th width="12%" class="text-right">Tax</th>
                <th width="15%" class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($proforma->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->item_name }}</strong>
                        @if($item->description)
                            <div class="item-description">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-center">{{ $item->unit_of_measure ?? '-' }}</td>
                    <td class="text-right">{{ $proforma->currency_code }} {{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">
                        @if($item->tax_percentage > 0)
                            {{ $item->tax_percentage }}%<br>
                            <small>{{ $proforma->currency_code }} {{ number_format($item->tax_amount, 2) }}</small>
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">{{ $proforma->currency_code }} {{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <table class="totals-table">
        <tr>
            <td><strong>Subtotal:</strong></td>
            <td class="text-right">{{ $proforma->currency_code }} {{ number_format($proforma->subtotal_amount, 2) }}</td>
        </tr>
        @if($proforma->discount_amount > 0)
            <tr>
                <td><strong>Discount:</strong></td>
                <td class="text-right" style="color: #28a745;">-{{ $proforma->currency_code }} {{ number_format($proforma->discount_amount, 2) }}</td>
            </tr>
        @endif
        @if($proforma->tax_amount > 0)
            <tr>
                <td><strong>Tax:</strong></td>
                <td class="text-right">{{ $proforma->currency_code }} {{ number_format($proforma->tax_amount, 2) }}</td>
            </tr>
        @endif
        @if($proforma->shipping_amount > 0)
            <tr>
                <td><strong>Shipping:</strong></td>
                <td class="text-right">{{ $proforma->currency_code }} {{ number_format($proforma->shipping_amount, 2) }}</td>
            </tr>
        @endif
        <tr class="total-row">
            <td><strong>TOTAL:</strong></td>
            <td class="text-right"><strong>{{ $proforma->currency_code }} {{ number_format($proforma->total_amount, 2) }}</strong></td>
        </tr>
    </table>

    <!-- Validity Notice -->
    @if($proforma->valid_until_date)
        <div class="validity-notice">
            <strong>Validity Notice:</strong><br>
            This proforma invoice is valid until <strong>{{ $proforma->valid_until_date->format('d/m/Y') }}</strong>.<br>
            @if($proforma->valid_until_date->isPast() && !in_array($proforma->status, ['accepted', 'cancelled']))
                <span style="color: #dc3545;"><strong>This proforma has expired.</strong></span>
            @else
                Please confirm your acceptance before the expiry date.
            @endif
        </div>
    @endif

    <!-- Notes -->
    @if($proforma->notes)
        <div class="notes">
            <div class="notes-title">Notes:</div>
            {{ $proforma->notes }}
        </div>
    @endif

    <!-- Terms & Conditions -->
    @if($proforma->terms_conditions)
        <div class="notes">
            <div class="notes-title">Terms & Conditions:</div>
            {{ $proforma->terms_conditions }}
        </div>
    @endif

    <!-- Footer -->
    @if($proforma->footer_text)
        <div class="footer">
            {{ $proforma->footer_text }}
        </div>
    @endif

    <!-- Important Notice -->
    <div style="background-color: #e9ecef; border: 1px solid #dee2e6; padding: 15px; margin-top: 30px; border-radius: 5px; text-align: center;">
        <strong>IMPORTANT:</strong> This is a proforma invoice for your information only.<br>
        No payment is due until this is converted to a formal invoice after your acceptance.
    </div>

    <!-- Generated Info -->
    <div style="text-align: center; margin-top: 30px; font-size: 10px; color: #999;">
        Generated on {{ now()->format('d/m/Y H:i') }} | Page 1 of 1
    </div>
</body>
</html>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $invoice->document_number }}</title>
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
        .invoice-details {
            width: 100%;
            margin-bottom: 30px;
        }
        .invoice-details td {
            vertical-align: top;
            padding: 5px 0;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            text-align: right;
        }
        .invoice-number {
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
        .status-paid { background-color: #28a745; color: white; }
        .status-pending { background-color: #ffc107; color: black; }
        .status-overdue { background-color: #dc3545; color: white; }
        .status-draft { background-color: #6c757d; color: white; }
        .status-sent { background-color: #17a2b8; color: white; }
        .status-viewed { background-color: #007bff; color: white; }
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

    <!-- Invoice Details -->
    <table class="invoice-details">
        <tr>
            <td width="50%">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">{{ $invoice->document_number }}</div>
                <div style="margin-top: 15px;">
                    <span class="status-badge status-{{ $invoice->status }}">
                        {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                    </span>
                </div>
            </td>
            <td width="50%" style="text-align: right;">
                <table style="margin-left: auto;">
                    @if($invoice->reference_number)
                        <tr>
                            <td><strong>Reference:</strong></td>
                            <td style="padding-left: 15px;">{{ $invoice->reference_number }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td><strong>Issue Date:</strong></td>
                        <td style="padding-left: 15px;">{{ $invoice->issue_date->format('d/m/Y') }}</td>
                    </tr>
                    @if($invoice->due_date)
                        <tr>
                            <td><strong>Due Date:</strong></td>
                            <td style="padding-left: 15px;">
                                {{ $invoice->due_date->format('d/m/Y') }}
                                @if($invoice->is_overdue)
                                    <span style="color: #dc3545; font-weight: bold;">(OVERDUE)</span>
                                @endif
                            </td>
                        </tr>
                    @endif
                    @if($invoice->po_number)
                        <tr>
                            <td><strong>PO Number:</strong></td>
                            <td style="padding-left: 15px;">{{ $invoice->po_number }}</td>
                        </tr>
                    @endif
                    @if($invoice->sales_person)
                        <tr>
                            <td><strong>Sales Person:</strong></td>
                            <td style="padding-left: 15px;">{{ $invoice->sales_person }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- Client Details -->
    <div class="client-details">
        <div class="client-title">BILL TO:</div>
        <strong>{{ $invoice->client->first_name }} {{ $invoice->client->last_name }}</strong><br>
        @if($invoice->client->address)
            {{ $invoice->client->address }}<br>
        @endif
        @if($invoice->client->phone_number)
            <strong>Phone:</strong> {{ $invoice->client->phone_number }}<br>
        @endif
        @if($invoice->client->email)
            <strong>Email:</strong> {{ $invoice->client->email }}<br>
        @endif
        @if($invoice->client->identification_number)
            <strong>ID:</strong> {{ $invoice->client->identification_number }}
        @endif
    </div>

    <!-- Invoice Title -->
    @if($invoice->title)
        <div style="text-align: center; margin: 15px 0 10px 0;">
            <strong style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; color: #333;">
                {{ $invoice->title }}
            </strong>
        </div>
    @endif

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
            @foreach($invoice->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->item_name }}</strong>
                        @if($item->description)
                            <div class="item-description">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-center">{{ $item->unit_of_measure ?? '-' }}</td>
                    <td class="text-right">{{ $invoice->currency_code }} {{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">
                        @if($item->tax_percentage > 0)
                            {{ $item->tax_percentage }}%<br>
                            <small>{{ $invoice->currency_code }} {{ number_format($item->tax_amount, 2) }}</small>
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">{{ $invoice->currency_code }} {{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <table class="totals-table">
        <tr>
            <td><strong>Subtotal:</strong></td>
            <td class="text-right">{{ $invoice->currency_code }} {{ number_format($invoice->subtotal_amount, 2) }}</td>
        </tr>
        @if($invoice->discount_amount > 0)
            <tr>
                <td><strong>Discount:</strong></td>
                <td class="text-right" style="color: #28a745;">-{{ $invoice->currency_code }} {{ number_format($invoice->discount_amount, 2) }}</td>
            </tr>
        @endif
        @if($invoice->tax_amount > 0)
            <tr>
                <td><strong>Tax:</strong></td>
                <td class="text-right">{{ $invoice->currency_code }} {{ number_format($invoice->tax_amount, 2) }}</td>
            </tr>
        @endif
        @if($invoice->shipping_amount > 0)
            <tr>
                <td><strong>Shipping:</strong></td>
                <td class="text-right">{{ $invoice->currency_code }} {{ number_format($invoice->shipping_amount, 2) }}</td>
            </tr>
        @endif
        <tr class="total-row">
            <td><strong>TOTAL:</strong></td>
            <td class="text-right"><strong>{{ $invoice->currency_code }} {{ number_format($invoice->total_amount, 2) }}</strong></td>
        </tr>
        @if($invoice->paid_amount > 0)
            <tr style="background-color: #28a745; color: white;">
                <td><strong>PAID:</strong></td>
                <td class="text-right"><strong>{{ $invoice->currency_code }} {{ number_format($invoice->paid_amount, 2) }}</strong></td>
            </tr>
            <tr style="background-color: #17a2b8; color: white;">
                <td><strong>BALANCE:</strong></td>
                <td class="text-right"><strong>{{ $invoice->currency_code }} {{ number_format($invoice->balance_amount, 2) }}</strong></td>
            </tr>
        @endif
    </table>

    <!-- Payment Instructions -->
    @if($invoice->balance_amount > 0)
        <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin-bottom: 30px; border-radius: 5px;">
            <strong>Payment Instructions:</strong><br>
            Amount Due: <strong>{{ $invoice->currency_code }} {{ number_format($invoice->balance_amount, 2) }}</strong><br>
            @if($invoice->due_date)
                Due Date: <strong>{{ $invoice->due_date->format('d/m/Y') }}</strong><br>
            @endif
            Please reference invoice number <strong>{{ $invoice->document_number }}</strong> when making payment.
        </div>
    @endif

    <!-- Notes -->
    @if($invoice->notes)
        <div class="notes">
            <div class="notes-title">Notes:</div>
            {{ $invoice->notes }}
        </div>
    @endif

    <!-- Terms & Conditions -->
    @if($invoice->terms_conditions)
        <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; font-size: 10px; line-height: 1.5;">
            <div style="font-weight: bold; font-size: 12px; margin-bottom: 10px; text-transform: uppercase; border-bottom: 1px solid #dee2e6; padding-bottom: 5px;">
                Terms and Conditions of the Invoice
            </div>
            {!! $invoice->terms_conditions !!}
        </div>
    @endif

    <!-- Payment Information -->
    <div style="margin-top: 15px; padding: 12px; background-color: #f8f9fa; border-radius: 5px; font-size: 10px; line-height: 1.5;">
        <div style="font-weight: bold; font-size: 11px; margin-bottom: 8px;">Payment Information:</div>
        <p style="margin: 4px 0;"><strong>Please arrange payment for the outstanding amount at your earliest convenience. The original invoice is attached for your reference.</strong></p>
        <p style="margin: 4px 0;"><strong>If you have already made this payment, please disregard this reminder and contact us with your payment reference.</strong></p>
    </div>

    <div style="margin-top: 10px; font-size: 10px;">
        <p><strong>If you have any questions regarding this invoice or need to discuss payment arrangements, please don't hesitate to contact us.</strong></p>
    </div>

    <div style="text-align: center; margin-top: 15px; padding-top: 10px; border-top: 2px solid #dee2e6; font-size: 11px;">
        <p><strong>Thank you for your business!</strong></p>
        <p><strong>Best regards,<br>{{ config('app.name') }} Accounts Team</strong></p>
    </div>

    <div style="text-align: center; padding: 8px; background-color: #f1f3f5; border-radius: 5px; font-size: 9px; color: #666; margin-top: 10px;">
        <strong>{{ config('app.name') }}</strong><br>
        <strong>Email:</strong> billing@wajenziprofessional.co.tz | <strong>Phone:</strong> +255 793 444 400
    </div>

    <!-- Signatures -->
    <div style="margin-top: 50px;">
        <table style="width: 100%;">
            <tr>
                @if($invoice->is_signed)
                    <td style="width: 50%; text-align: center; vertical-align: bottom;">
                        @if($invoice->creator_signature && file_exists(public_path($invoice->creator_signature)))
                            <img src="{{ public_path($invoice->creator_signature) }}" 
                                 style="max-height: 60px; max-width: 150px; margin-bottom: 10px;"
                                 alt="Signature">
                        @endif
                        <div style="border-top: 1px solid #333; width: 200px; margin: 10px auto; padding-top: 5px;">
                            <strong>{{ $invoice->creator->name ?? 'System' }}</strong><br>
                            <small>{{ $invoice->creator->designation ?? 'Authorized Signatory' }}</small><br>
                            <small>{{ $invoice->signed_at ? $invoice->signed_at->format('d/m/Y') : '' }}</small>
                        </div>
                    </td>
                @endif
                
                @if($invoice->is_approved_signed)
                    <td style="width: 50%; text-align: center; vertical-align: bottom;">
                        @if($invoice->approver_signature && file_exists(public_path($invoice->approver_signature)))
                            <img src="{{ public_path($invoice->approver_signature) }}" 
                                 style="max-height: 60px; max-width: 150px; margin-bottom: 10px;"
                                 alt="Approver Signature">
                        @endif
                        <div style="border-top: 1px solid #333; width: 200px; margin: 10px auto; padding-top: 5px;">
                            <strong>{{ $invoice->approver->name ?? 'Approver' }}</strong><br>
                            <small>{{ $invoice->approver->designation ?? 'Manager' }}</small><br>
                            <small>{{ $invoice->approved_signed_at ? $invoice->approved_signed_at->format('d/m/Y') : '' }}</small>
                        </div>
                    </td>
                @endif
                
                @if(!$invoice->is_signed && !$invoice->is_approved_signed)
                    <td style="text-align: center; color: #999; font-style: italic;">
                        Document signatures will appear here once signed
                    </td>
                @endif
            </tr>
        </table>
    </div>

    <!-- Generated Info -->
    <div style="text-align: center; margin-top: 30px; font-size: 10px; color: #999;">
        Generated on {{ now()->format('d/m/Y H:i') }} | Page 1 of 1
        @if($invoice->is_signed)
            <br>Digitally signed on {{ $invoice->signed_at->format('d/m/Y H:i') }}
        @endif
    </div>
</body>
</html>
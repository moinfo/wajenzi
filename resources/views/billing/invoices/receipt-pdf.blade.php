<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Receipt {{ $invoice->document_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            position: relative;
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
        .receipt-title {
            font-size: 28px;
            font-weight: bold;
            color: #28a745;
            text-align: center;
            margin: 20px 0 5px;
        }
        .receipt-subtitle {
            font-size: 14px;
            color: #666;
            text-align: center;
            margin-bottom: 20px;
        }
        .paid-stamp {
            text-align: center;
            margin: 10px 0 25px;
        }
        .paid-stamp span {
            display: inline-block;
            border: 3px solid #28a745;
            color: #28a745;
            font-size: 24px;
            font-weight: bold;
            padding: 5px 30px;
            letter-spacing: 5px;
            transform: rotate(-5deg);
        }
        .details-table {
            width: 100%;
            margin-bottom: 25px;
        }
        .details-table td {
            vertical-align: top;
            padding: 5px 0;
        }
        .client-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
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
            margin-bottom: 25px;
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
            margin-bottom: 25px;
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
        .totals-table .paid-row {
            background-color: #28a745;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            margin-top: 20px;
        }
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .payment-table th {
            background-color: #28a745;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
        }
        .payment-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .payment-table .text-right {
            text-align: right;
        }
        .fully-paid-box {
            background-color: #d4edda;
            border: 2px solid #28a745;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            color: #155724;
        }
        .signatures {
            margin-top: 50px;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 200px;
            margin: 10px auto;
            padding-top: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
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

    <!-- Receipt Title & PAID Stamp -->
    <div class="receipt-title">OFFICIAL RECEIPT</div>
    <div class="receipt-subtitle">For Invoice {{ $invoice->document_number }}</div>
    <div class="paid-stamp">
        <span>PAID IN FULL</span>
    </div>

    <!-- Invoice & Client Details -->
    <table class="details-table">
        <tr>
            <td width="50%">
                <div class="client-details">
                    <div class="client-title">RECEIVED FROM:</div>
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
            </td>
            <td width="50%">
                <div class="client-details">
                    <div class="client-title">RECEIPT DETAILS:</div>
                    <table style="width: 100%; font-size: 12px;">
                        <tr>
                            <td><strong>Invoice #:</strong></td>
                            <td>{{ $invoice->document_number }}</td>
                        </tr>
                        @if($invoice->reference_number)
                            <tr>
                                <td><strong>Reference:</strong></td>
                                <td>{{ $invoice->reference_number }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td><strong>Issue Date:</strong></td>
                            <td>{{ $invoice->issue_date->format('d/m/Y') }}</td>
                        </tr>
                        @if($invoice->paid_at)
                            <tr>
                                <td><strong>Paid Date:</strong></td>
                                <td>{{ $invoice->paid_at->format('d/m/Y') }}</td>
                            </tr>
                        @endif
                        @if($invoice->po_number)
                            <tr>
                                <td><strong>PO Number:</strong></td>
                                <td>{{ $invoice->po_number }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <div class="section-title">ITEMS:</div>
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
        <tr class="paid-row">
            <td><strong>PAID:</strong></td>
            <td class="text-right"><strong>{{ $invoice->currency_code }} {{ number_format($invoice->paid_amount, 2) }}</strong></td>
        </tr>
    </table>

    <!-- Payment History -->
    @if($invoice->payments->count() > 0)
        <div class="section-title">PAYMENT HISTORY:</div>
        <table class="payment-table">
            <thead>
                <tr>
                    <th width="15%">Receipt #</th>
                    <th width="15%">Date</th>
                    <th width="20%">Method</th>
                    <th width="20%">Reference</th>
                    <th width="15%" class="text-right">Amount</th>
                    <th width="15%">Received By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->payments->where('status', 'completed') as $payment)
                    <tr>
                        <td>{{ $payment->payment_number }}</td>
                        <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                        <td>{{ $payment->reference_number ?? '-' }}</td>
                        <td class="text-right"><strong>{{ $invoice->currency_code }} {{ number_format($payment->amount, 2) }}</strong></td>
                        <td>{{ $payment->receiver->name ?? 'System' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Fully Paid Confirmation -->
    <div class="fully-paid-box">
        INVOICE {{ $invoice->document_number }} — FULLY PAID<br>
        <span style="font-size: 13px; font-weight: normal;">
            Total Amount: {{ $invoice->currency_code }} {{ number_format($invoice->total_amount, 2) }}
            | Paid: {{ $invoice->currency_code }} {{ number_format($invoice->paid_amount, 2) }}
            | Balance: {{ $invoice->currency_code }} {{ number_format($invoice->balance_amount, 2) }}
        </span>
    </div>

    <!-- Notes -->
    @if($invoice->notes)
        <div style="margin: 20px 0;">
            <div class="section-title">NOTES:</div>
            {{ $invoice->notes }}
        </div>
    @endif

    <!-- Signatures -->
    <div class="signatures">
        <table style="width: 100%;">
            <tr>
                <td width="50%" style="text-align: center; vertical-align: bottom;">
                    <div style="height: 80px; margin-bottom: 10px;">
                        <!-- Space for customer signature -->
                    </div>
                    <div class="signature-line">
                        <strong>Customer</strong><br>
                        <small>{{ $invoice->client->first_name }} {{ $invoice->client->last_name }}</small>
                    </div>
                </td>
                <td width="50%" style="text-align: center; vertical-align: bottom;">
                    @if($invoice->is_signed && $invoice->creator_signature && file_exists(public_path($invoice->creator_signature)))
                        <img src="{{ public_path($invoice->creator_signature) }}"
                             style="max-height: 60px; max-width: 150px; margin-bottom: 10px;"
                             alt="Signature">
                    @else
                        <div style="height: 60px; margin-bottom: 10px;">
                            <!-- Signature placeholder -->
                        </div>
                    @endif
                    <div class="signature-line">
                        <strong>{{ $invoice->creator->name ?? 'System' }}</strong><br>
                        <small>{{ $invoice->creator->designation ?? 'Authorized Signatory' }}</small>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <strong>Thank you for your business!</strong><br>
        Receipt generated on {{ now()->format('d/m/Y H:i:s') }}<br>
        This is an official receipt for Invoice {{ $invoice->document_number }}.
    </div>
</body>
</html>
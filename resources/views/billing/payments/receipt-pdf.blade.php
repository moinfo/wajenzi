<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Payment Receipt {{ $payment->payment_number }}</title>
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
        .receipt-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            text-align: center;
            margin: 20px 0;
        }
        .receipt-number {
            font-size: 16px;
            font-weight: bold;
            color: #666;
            text-align: center;
            margin-bottom: 30px;
        }
        .details-table {
            width: 100%;
            margin-bottom: 30px;
        }
        .details-table td {
            vertical-align: top;
            padding: 10px 0;
        }
        .client-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .payment-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .invoice-table th {
            background-color: #333;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
        }
        .invoice-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #ddd;
        }
        .invoice-table .text-right {
            text-align: right;
        }
        .invoice-table .text-center {
            text-align: center;
        }
        .amount-paid {
            background-color: #28a745;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        .amount-words {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-style: italic;
        }
        .signatures {
            margin-top: 50px;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 200px;
            margin-top: 40px;
            text-align: center;
        }
        .signature-title {
            font-weight: bold;
            margin-top: 10px;
        }
        .signature-name {
            color: #666;
            font-size: 11px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-completed { background-color: #28a745; color: white; }
        .status-pending { background-color: #ffc107; color: black; }
        .status-voided { background-color: #dc3545; color: white; }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }
        .balance-info {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
        }
        .balance-paid {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">WAJENZI CONSTRUCTION COMPANY</div>
        <div class="company-details">
            P.O. Box 123, Dar es Salaam, Tanzania<br>
            Phone: +255 123 456 789 | Email: info@wajenzi.com<br>
            TIN: 123-456-789 | VRN: 40-123456-Q
        </div>
    </div>

    <!-- Receipt Title -->
    <div class="receipt-title">PAYMENT RECEIPT</div>
    <div class="receipt-number">Receipt No: {{ $payment->payment_number }}</div>

    <!-- Payment Status -->
    <div style="text-align: center; margin-bottom: 30px;">
        <span class="status-badge status-{{ $payment->status }}">
            {{ ucfirst($payment->status) }}
        </span>
    </div>

    <!-- Payment and Client Details -->
    <table class="details-table">
        <tr>
            <td width="50%">
                <div class="client-details">
                    <div class="section-title">RECEIVED FROM:</div>
                    <strong>{{ $payment->client->company_name }}</strong><br>
                    @if($payment->client->contact_person)
                        {{ $payment->client->contact_person }}<br>
                    @endif
                    @if($payment->client->billing_address_line1)
                        {{ $payment->client->billing_address_line1 }}<br>
                    @endif
                    @if($payment->client->billing_address_line2)
                        {{ $payment->client->billing_address_line2 }}<br>
                    @endif
                    @if($payment->client->billing_city || $payment->client->billing_postal_code)
                        {{ $payment->client->billing_city }} {{ $payment->client->billing_postal_code }}<br>
                    @endif
                    @if($payment->client->billing_country)
                        {{ $payment->client->billing_country }}<br>
                    @endif
                    @if($payment->client->phone)
                        <strong>Phone:</strong> {{ $payment->client->phone }}<br>
                    @endif
                    @if($payment->client->email)
                        <strong>Email:</strong> {{ $payment->client->email }}<br>
                    @endif
                    @if($payment->client->tax_identification_number)
                        <strong>TIN:</strong> {{ $payment->client->tax_identification_number }}
                    @endif
                </div>
            </td>
            <td width="50%">
                <div class="payment-details">
                    <div class="section-title">PAYMENT DETAILS:</div>
                    <table style="width: 100%; font-size: 12px;">
                        <tr>
                            <td><strong>Receipt No:</strong></td>
                            <td>{{ $payment->payment_number }}</td>
                        </tr>
                        <tr>
                            <td><strong>Date:</strong></td>
                            <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Time:</strong></td>
                            <td>{{ $payment->created_at->format('H:i:s') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Method:</strong></td>
                            <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                        </tr>
                        @if($payment->reference_number)
                            <tr>
                                <td><strong>Reference:</strong></td>
                                <td>{{ $payment->reference_number }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td><strong>Received By:</strong></td>
                            <td>{{ $payment->receiver->name ?? 'System' }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- Invoice Payment Breakdown -->
    <div class="section-title">PAYMENT FOR:</div>
    <table class="invoice-table">
        <thead>
            <tr>
                <th width="20%">Invoice Number</th>
                <th width="15%">Invoice Date</th>
                <th width="15%" class="text-right">Invoice Amount</th>
                <th width="15%" class="text-right">Previous Paid</th>
                <th width="15%" class="text-right">This Payment</th>
                <th width="20%" class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $payment->document->document_number }}</td>
                <td>{{ $payment->document->issue_date->format('d/m/Y') }}</td>
                <td class="text-right">{{ $payment->document->currency_code ?? 'TZS' }} {{ number_format($payment->document->total_amount, 2) }}</td>
                <td class="text-right">{{ $payment->document->currency_code ?? 'TZS' }} {{ number_format($payment->document->paid_amount - $payment->amount, 2) }}</td>
                <td class="text-right amount-paid">{{ $payment->document->currency_code ?? 'TZS' }} {{ number_format($payment->amount, 2) }}</td>
                <td class="text-right">{{ $payment->document->currency_code ?? 'TZS' }} {{ number_format($payment->document->balance_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Amount in Words -->
    <div class="amount-words">
        <strong>Amount in Words:</strong><br>
        <em>{{ ucfirst(\Illuminate\Support\Str::title(number_format($payment->amount, 2))) }} {{ $payment->document->currency_code ?? 'TZS' }} Only</em>
    </div>

    <!-- Notes -->
    @if($payment->notes)
        <div style="margin: 20px 0;">
            <div class="section-title">NOTES:</div>
            {{ $payment->notes }}
        </div>
    @endif

    <!-- Balance Status -->
    @if($payment->document->balance_amount > 0)
        <div class="balance-info">
            <strong>OUTSTANDING BALANCE: {{ $payment->document->currency_code ?? 'TZS' }} {{ number_format($payment->document->balance_amount, 2) }}</strong>
        </div>
    @else
        <div class="balance-info balance-paid">
            <strong>INVOICE FULLY PAID</strong>
        </div>
    @endif

    <!-- Signatures -->
    <table class="signatures" style="width: 100%;">
        <tr>
            <td width="50%" style="text-align: center;">
                <div class="signature-line">
                    <div class="signature-title">Customer Signature</div>
                    <div class="signature-name">{{ $payment->client->contact_person ?? $payment->client->company_name }}</div>
                </div>
            </td>
            <td width="50%" style="text-align: center;">
                <div class="signature-line">
                    <div class="signature-title">Received By</div>
                    <div class="signature-name">{{ $payment->receiver->name ?? 'System' }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        <strong>Thank you for your payment!</strong><br>
        Receipt generated on {{ now()->format('d/m/Y H:i:s') }} | Original Receipt<br>
        This is a computer-generated receipt and does not require a signature.
    </div>
</body>
</html>
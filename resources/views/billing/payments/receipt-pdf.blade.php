@php
    if (!function_exists('numberToWords')) {
        function numberToWords($number) {
            $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                     'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                     'Seventeen', 'Eighteen', 'Nineteen'];
            $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

            $number = (float) $number;
            $whole = (int) floor($number);
            $cents = round(($number - $whole) * 100);

            if ($whole === 0) return 'Zero';

            $convert = function($n) use (&$convert, $ones, $tens) {
                if ($n < 20) return $ones[$n];
                if ($n < 100) return $tens[(int)($n / 10)] . ($n % 10 ? ' ' . $ones[$n % 10] : '');
                if ($n < 1000) return $ones[(int)($n / 100)] . ' Hundred' . ($n % 100 ? ' and ' . $convert($n % 100) : '');
                if ($n < 1000000) return $convert((int)($n / 1000)) . ' Thousand' . ($n % 1000 ? ' ' . $convert($n % 1000) : '');
                if ($n < 1000000000) return $convert((int)($n / 1000000)) . ' Million' . ($n % 1000000 ? ' ' . $convert($n % 1000000) : '');
                return $convert((int)($n / 1000000000)) . ' Billion' . ($n % 1000000000 ? ' ' . $convert($n % 1000000000) : '');
            };

            $result = $convert($whole);
            if ($cents > 0) {
                $result .= ' and ' . $convert($cents) . ' Cents';
            }
            return $result;
        }
    }
    $currency = $payment->document->currency_code ?? 'TZS';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Payment Receipt {{ $payment->payment_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #28a745;
            padding-bottom: 15px;
        }
        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin-top: 8px;
        }
        .company-details {
            font-size: 11px;
            color: #666;
            margin-top: 4px;
        }
        .receipt-banner {
            background-color: #28a745;
            color: white;
            text-align: center;
            padding: 12px 0;
            margin-bottom: 20px;
        }
        .receipt-banner h1 {
            margin: 0;
            font-size: 24px;
            letter-spacing: 3px;
        }
        .receipt-banner .receipt-no {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 4px;
        }
        .two-col {
            width: 100%;
            margin-bottom: 20px;
        }
        .two-col td {
            vertical-align: top;
            padding: 0;
        }
        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 12px 15px;
            margin: 0 3px;
        }
        .info-box .box-title {
            font-size: 11px;
            font-weight: bold;
            color: #28a745;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 5px;
        }
        .info-box table td {
            padding: 2px 0;
            font-size: 12px;
        }
        .info-box table td:first-child {
            color: #666;
            width: 100px;
        }
        .amount-highlight {
            background-color: #28a745;
            color: white;
            text-align: center;
            padding: 15px;
            margin: 20px 0;
        }
        .amount-highlight .label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.9;
        }
        .amount-highlight .amount {
            font-size: 28px;
            font-weight: bold;
            margin: 5px 0;
        }
        .amount-words {
            background-color: #f0f9f4;
            border-left: 4px solid #28a745;
            padding: 10px 15px;
            margin: 15px 0;
            font-style: italic;
            font-size: 12px;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .invoice-table th {
            background-color: #343a40;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .invoice-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #dee2e6;
            font-size: 12px;
        }
        .invoice-table .text-right { text-align: right; }
        .invoice-table .highlight {
            background-color: #d4edda;
            font-weight: bold;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #333;
            margin: 20px 0 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .balance-box {
            text-align: center;
            padding: 12px;
            margin: 15px 0;
            font-weight: bold;
            font-size: 14px;
        }
        .balance-outstanding {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
        }
        .balance-paid {
            background-color: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
        }
        .notes-box {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 10px 15px;
            margin: 15px 0;
            font-size: 12px;
        }
        .signatures {
            margin-top: 40px;
        }
        .signature-cell {
            text-align: center;
            vertical-align: bottom;
            width: 50%;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 180px;
            margin: 0 auto;
            padding-top: 8px;
        }
        .footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 2px solid #28a745;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <img src="{{ public_path('media/logo/wajenzilogo.png') }}" alt="{{ config('app.name') }}" style="max-height: 55px; margin-bottom: 5px;">
        <div class="company-name">{{ config('app.name') }}</div>
        <div class="company-details">
            PSSSF COMMERCIAL COMPLEX, SAM NUJOMA ROAD, DSM-TANZANIA<br>
            P. O. Box 14492, Dar es Salaam Tanzania<br>
            Phone: +255 793 444 400 | Email: billing@wajenziprofessional.co.tz | TIN: 154-867-805
        </div>
    </div>

    <!-- Receipt Banner -->
    <div class="receipt-banner">
        <h1>PAYMENT RECEIPT</h1>
        <div class="receipt-no">{{ $payment->payment_number }}</div>
    </div>

    <!-- Client & Payment Details -->
    <table class="two-col">
        <tr>
            <td width="48%">
                <div class="info-box">
                    <div class="box-title">Received From</div>
                    <strong style="font-size: 14px;">{{ $payment->document->client->first_name }} {{ $payment->document->client->last_name }}</strong><br>
                    @if($payment->document->client->address)
                        {{ $payment->document->client->address }}<br>
                    @endif
                    @if($payment->document->client->phone_number)
                        Phone: {{ $payment->document->client->phone_number }}<br>
                    @endif
                    @if($payment->document->client->email)
                        Email: {{ $payment->document->client->email }}<br>
                    @endif
                    @if($payment->document->client->identification_number)
                        ID: {{ $payment->document->client->identification_number }}
                    @endif
                </div>
            </td>
            <td width="4%"></td>
            <td width="48%">
                <div class="info-box">
                    <div class="box-title">Payment Details</div>
                    <table style="width: 100%;">
                        <tr>
                            <td>Receipt No:</td>
                            <td><strong>{{ $payment->payment_number }}</strong></td>
                        </tr>
                        <tr>
                            <td>Date:</td>
                            <td><strong>{{ $payment->payment_date->format('d/m/Y') }}</strong></td>
                        </tr>
                        <tr>
                            <td>Time:</td>
                            <td>{{ $payment->created_at->format('H:i:s') }}</td>
                        </tr>
                        <tr>
                            <td>Method:</td>
                            <td><strong>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</strong></td>
                        </tr>
                        @if($payment->reference_number)
                            <tr>
                                <td>Reference:</td>
                                <td>{{ $payment->reference_number }}</td>
                            </tr>
                        @endif
                        @if($payment->bank_name)
                            <tr>
                                <td>Bank:</td>
                                <td>{{ $payment->bank_name }}</td>
                            </tr>
                        @endif
                        @if($payment->cheque_number)
                            <tr>
                                <td>Cheque No:</td>
                                <td>{{ $payment->cheque_number }}</td>
                            </tr>
                        @endif
                        @if($payment->transaction_id)
                            <tr>
                                <td>Transaction:</td>
                                <td>{{ $payment->transaction_id }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td>Received By:</td>
                            <td>{{ $payment->receiver->name ?? 'System' }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- Amount Highlight -->
    <div class="amount-highlight">
        <div class="label">Amount Received</div>
        <div class="amount">{{ $currency }} {{ number_format($payment->amount, 2) }}</div>
    </div>

    <!-- Amount in Words -->
    <div class="amount-words">
        <strong>Amount in Words:</strong> {{ numberToWords($payment->amount) }} {{ $currency }} Only
    </div>

    <!-- Invoice Payment Breakdown -->
    <div class="section-title">Payment For</div>
    <table class="invoice-table">
        <thead>
            <tr>
                <th>Invoice Number</th>
                <th>Invoice Date</th>
                <th class="text-right">Invoice Amount</th>
                <th class="text-right">Previously Paid</th>
                <th class="text-right">This Payment</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>{{ $payment->document->document_number }}</strong></td>
                <td>{{ $payment->document->issue_date->format('d/m/Y') }}</td>
                <td class="text-right">{{ $currency }} {{ number_format($payment->document->total_amount, 2) }}</td>
                <td class="text-right">{{ $currency }} {{ number_format($payment->document->paid_amount - $payment->amount, 2) }}</td>
                <td class="text-right highlight">{{ $currency }} {{ number_format($payment->amount, 2) }}</td>
                <td class="text-right">{{ $currency }} {{ number_format($payment->document->balance_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Balance Status -->
    @if($payment->document->balance_amount > 0)
        <div class="balance-box balance-outstanding">
            OUTSTANDING BALANCE: {{ $currency }} {{ number_format($payment->document->balance_amount, 2) }}
        </div>
    @else
        <div class="balance-box balance-paid">
            INVOICE FULLY PAID
        </div>
    @endif

    <!-- Notes -->
    @if($payment->notes)
        <div class="notes-box">
            <strong>Notes:</strong> {{ $payment->notes }}
        </div>
    @endif

    <!-- Signatures -->
    <table class="signatures" style="width: 100%;">
        <tr>
            <td class="signature-cell">
                <div style="height: 70px;">
                    <!-- Space for customer signature -->
                </div>
                <div class="signature-line">
                    <strong>Customer Signature</strong><br>
                    <small style="color: #666;">{{ $payment->document->client->first_name }} {{ $payment->document->client->last_name }}</small>
                </div>
            </td>
            <td class="signature-cell">
                @if($payment->is_receipt_signed && $payment->receiver_signature && file_exists(public_path($payment->receiver_signature)))
                    <img src="{{ public_path($payment->receiver_signature) }}"
                         style="max-height: 55px; max-width: 140px; margin-bottom: 5px;"
                         alt="Receiver Signature">
                @else
                    <div style="height: 55px;">
                        <!-- Signature placeholder -->
                    </div>
                @endif
                <div class="signature-line">
                    <strong>Received By</strong><br>
                    <small style="color: #666;">
                        {{ $payment->receiver->name ?? 'System' }}<br>
                        {{ $payment->receiver->designation ?? 'Cashier' }}
                        @if($payment->receipt_signed_at)
                            <br>{{ $payment->receipt_signed_at->format('d/m/Y H:i') }}
                        @endif
                    </small>
                </div>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        <strong>Thank you for your payment!</strong><br>
        Receipt generated on {{ now()->format('d/m/Y H:i:s') }}
        @if($payment->is_receipt_signed)
            | Digitally signed on {{ $payment->receipt_signed_at->format('d/m/Y H:i:s') }}
        @endif
        <br>This is a computer-generated receipt.
    </div>
</body>
</html>

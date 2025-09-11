<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ucfirst($document->document_type) }} {{ $document->document_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .document-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .document-info h3 {
            margin: 0 0 10px 0;
            color: #007bff;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        .message {
            margin: 20px 0;
            padding: 15px;
            background-color: #e9ecef;
            border-left: 4px solid #007bff;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 14px;
            color: #6c757d;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $message->embed(public_path('media/logo/wajenzilogo.png')) }}" alt="{{ config('app.name') }}" style="max-height: 80px; margin-bottom: 10px; display: block; margin-left: auto; margin-right: auto;">
            <div class="logo">{{ config('app.name') }}</div>
            <div style="font-size: 14px; color: #666; margin-top: 10px; line-height: 1.5;">
                PSSSF COMMERCIAL COMPLEX, SAM NUJOMA ROAD, DSM-TANZANIA<br>
                P. O. Box 14492, Dar es Salaam Tanzania<br>
                Phone: +255 793 444 400<br>
                TIN: 154-867-805
            </div>
        </div>

        <h2>Dear {{ $document->client->contact_person ?? $document->client->company_name }},</h2>

        <p>Please find attached your {{ ucfirst($document->document_type) }} for your review.</p>

        <div class="document-info">
            <h3>{{ ucfirst($document->document_type) }} Details</h3>
            <div class="info-row">
                <strong>Document Number:</strong>
                <span>{{ $document->document_number }}</span>
            </div>
            <div class="info-row">
                <strong>Issue Date:</strong>
                <span>{{ $document->issue_date ? $document->issue_date->format('d/m/Y') : 'N/A' }}</span>
            </div>
            @if($document->due_date)
            <div class="info-row">
                <strong>Due Date:</strong>
                <span>{{ $document->due_date->format('d/m/Y') }}</span>
            </div>
            @endif
            @if($document->valid_until_date && $document->document_type === 'proforma')
            <div class="info-row">
                <strong>Valid Until:</strong>
                <span>{{ $document->valid_until_date ? $document->valid_until_date->format('d/m/Y') : 'N/A' }}</span>
            </div>
            @endif
            <div class="info-row">
                <strong>Amount:</strong>
                <span><strong>{{ $document->currency_code }} {{ number_format($document->total_amount, 2) }}</strong></span>
            </div>
            @if($document->document_type === 'invoice' && $document->balance_amount > 0)
            <div class="info-row">
                <strong>Balance Due:</strong>
                <span style="color: #dc3545;"><strong>{{ $document->currency_code }} {{ number_format($document->balance_amount, 2) }}</strong></span>
            </div>
            @endif
        </div>

        @if($customMessage)
        <div class="message">
            {!! nl2br(e($customMessage)) !!}
        </div>
        @endif

        @if($document->document_type === 'invoice' && $document->balance_amount > 0)
        <p>Please ensure payment is made by the due date to avoid any late payment charges.</p>
        @elseif($document->document_type === 'proforma')
        <p>This proforma invoice is valid until {{ $document->valid_until_date ? $document->valid_until_date->format('d/m/Y') : 'the specified date' }}. Please confirm your order to proceed with the invoice.</p>
        @elseif($document->document_type === 'quote')
        <p>This quotation is valid for 30 days from the issue date. Please let us know if you would like to proceed or if you have any questions.</p>
        @endif

        <p>If you have any questions regarding this {{ $document->document_type }}, please don't hesitate to contact us.</p>

        <div class="footer">
            <p><strong>{{ config('app.name') }}</strong></p>
            <p>Email: billing@wajenziprofessional.co.tz | Phone: +255 793 444 400</p>
            <p>Thank you for your business!</p>
        </div>
    </div>
</body>
</html>
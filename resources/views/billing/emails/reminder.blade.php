<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Reminder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .company-details {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-info {
            color: #31708f;
            background-color: #d9edf7;
            border-color: #bce8f1;
        }
        .alert-warning {
            color: #8a6d3b;
            background-color: #fcf8e3;
            border-color: #faebcc;
        }
        .alert-danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
        .invoice-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-label {
            font-weight: bold;
        }
        .amount {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
        }
        .overdue {
            color: #dc3545;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .contact-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ config('app.name') }}</div>
        <div class="company-details">
            PSSSF COMMERCIAL COMPLEX, SAM NUJOMA ROAD, DSM-TANZANIA<br>
            P. O. Box 14492, Dar es Salaam Tanzania<br>
            Phone: +255 793 444 400 | Email: billing@wajenziprofessional.co.tz<br>
            TIN: 154-867-805
        </div>
    </div>

    @if ($reminderType == 'before_due')
        <div class="alert alert-info">
            <strong>Payment Reminder</strong><br>
            Your invoice payment is due in {{ $daysBeforeDue }} day{{ $daysBeforeDue > 1 ? 's' : '' }}.
        </div>
    @elseif ($reminderType == 'overdue')
        <div class="alert alert-warning">
            <strong>Overdue Payment Notice</strong><br>
            Your invoice payment is {{ $daysOverdue }} day{{ $daysOverdue > 1 ? 's' : '' }} overdue.
        </div>
    @elseif ($reminderType == 'late_fee')
        <div class="alert alert-danger">
            <strong>Late Fee Applied</strong><br>
            A late fee has been applied to your overdue invoice.
        </div>
    @endif

    <p>Dear {{ $document->client->contact_person ?? $document->client->company_name }},</p>

    @if ($customMessage)
        <p>{!! nl2br(e($customMessage)) !!}</p>
    @else
        @if ($reminderType == 'before_due')
            <p>We hope this message finds you well. This is a friendly reminder that your payment for the invoice detailed below is due in {{ $daysBeforeDue }} day{{ $daysBeforeDue > 1 ? 's' : '' }}.</p>
        @elseif ($reminderType == 'overdue')
            <p>We hope this message finds you well. This is to notify you that your payment for the invoice detailed below is now {{ $daysOverdue }} day{{ $daysOverdue > 1 ? 's' : '' }} overdue.</p>
            <p>Please arrange payment as soon as possible to avoid any additional charges or service interruptions.</p>
        @elseif ($reminderType == 'late_fee')
            <p>We hope this message finds you well. This is to inform you that a late fee has been applied to your overdue invoice as detailed below.</p>
            <p>Please arrange payment immediately to prevent further charges.</p>
        @else
            <p>We hope this message finds you well. This is a reminder regarding your outstanding invoice payment.</p>
        @endif
    @endif

    <div class="invoice-details">
        <h3 style="margin-top: 0; color: #007bff;">Invoice Details</h3>
        <div class="detail-row">
            <span class="detail-label">Invoice Number:</span>
            <span>{{ $document->document_number }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Issue Date:</span>
            <span>{{ $document->issue_date->format('d/m/Y') }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Due Date:</span>
            <span class="{{ $document->due_date && $document->due_date->isPast() ? 'overdue' : '' }}">
                {{ $document->due_date ? $document->due_date->format('d/m/Y') : 'N/A' }}
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Original Amount:</span>
            <span class="amount">{{ $document->currency_code ?? 'TZS' }} {{ number_format($document->total_amount - $document->late_fee_amount, 2) }}</span>
        </div>
        @if($document->late_fee_amount > 0)
            <div class="detail-row">
                <span class="detail-label">Late Fee ({{ $document->late_fee_percentage }}%):</span>
                <span class="amount overdue">{{ $document->currency_code ?? 'TZS' }} {{ number_format($document->late_fee_amount, 2) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Amount (with late fee):</span>
                <span class="amount overdue">{{ $document->currency_code ?? 'TZS' }} {{ number_format($document->total_amount, 2) }}</span>
            </div>
        @endif
        <div class="detail-row">
            <span class="detail-label">Paid Amount:</span>
            <span class="amount">{{ $document->currency_code ?? 'TZS' }} {{ number_format($document->paid_amount, 2) }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Outstanding Balance:</span>
            <span class="amount {{ $document->balance_amount > 0 ? 'overdue' : '' }}">{{ $document->currency_code ?? 'TZS' }} {{ number_format($document->balance_amount, 2) }}</span>
        </div>
    </div>

    @if($document->payment_terms)
        <div class="contact-info">
            <h4>Payment Terms:</h4>
            <p>{{ $document->payment_terms }}</p>
        </div>
    @endif

    <div class="contact-info">
        <h4>Payment Information:</h4>
        <p>Please arrange payment for the outstanding amount at your earliest convenience. The original invoice is attached for your reference.</p>
        <p>If you have already made this payment, please disregard this reminder and contact us with your payment reference.</p>
    </div>

    <p>If you have any questions regarding this invoice or need to discuss payment arrangements, please don't hesitate to contact us.</p>

    <p>Thank you for your business!</p>

    <p>Best regards,<br>
    {{ config('app.name') }} Accounts Team</p>

    <div class="footer">
        <strong>{{ config('app.name') }}</strong><br>
        Email: billing@wajenziprofessional.co.tz | Phone: +255 793 444 400<br>
        This is an automated reminder. Please do not reply to this email.
    </div>
</body>
</html>
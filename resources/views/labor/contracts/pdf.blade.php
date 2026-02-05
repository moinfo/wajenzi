<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Labor Contract - {{ $contract->contract_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
        }
        .contract-number {
            font-size: 14px;
            color: #666;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            background-color: #f5f5f5;
            padding: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #333;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
        }
        .details-table td {
            padding: 5px 10px;
            vertical-align: top;
        }
        .details-table .label {
            width: 30%;
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .payment-table th, .payment-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .payment-table th {
            background-color: #f5f5f5;
        }
        .payment-table .amount {
            text-align: right;
        }
        .scope-box {
            background-color: #f9f9f9;
            padding: 15px;
            border: 1px solid #ddd;
            margin-top: 10px;
        }
        .signature-section {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-row {
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 45%;
            padding: 20px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LABOR CONTRACT</h1>
        <p class="contract-number">Contract No: {{ $contract->contract_number }}</p>
        <p>Date: {{ $contract->contract_date?->format('F d, Y') }}</p>
    </div>

    <div class="section">
        <div class="section-title">1. PARTIES</div>
        <table class="details-table">
            <tr>
                <td class="label">Employer/Project:</td>
                <td>{{ $contract->project?->project_name }}</td>
            </tr>
            <tr>
                <td class="label">Contractor/Artisan:</td>
                <td>
                    {{ $contract->artisan?->name }}<br>
                    @if($contract->artisan?->trade_skill)
                        Trade: {{ $contract->artisan->trade_skill }}<br>
                    @endif
                    @if($contract->artisan?->phone)
                        Phone: {{ $contract->artisan->phone }}<br>
                    @endif
                    @if($contract->artisan?->id_number)
                        ID: {{ $contract->artisan->id_number }}
                    @endif
                </td>
            </tr>
            @if($contract->supervisor)
                <tr>
                    <td class="label">Supervisor:</td>
                    <td>{{ $contract->supervisor->name }}</td>
                </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. CONTRACT PERIOD</div>
        <table class="details-table">
            <tr>
                <td class="label">Start Date:</td>
                <td>{{ $contract->start_date?->format('F d, Y') }}</td>
            </tr>
            <tr>
                <td class="label">End Date:</td>
                <td>{{ $contract->end_date?->format('F d, Y') }}</td>
            </tr>
            <tr>
                <td class="label">Duration:</td>
                <td>{{ $contract->start_date?->diffInDays($contract->end_date) }} days</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">3. SCOPE OF WORK</div>
        <div class="scope-box">
            {{ $contract->scope_of_work }}
        </div>
    </div>

    <div class="section">
        <div class="section-title">4. CONTRACT VALUE & PAYMENT SCHEDULE</div>
        <table class="details-table">
            <tr>
                <td class="label">Total Contract Value:</td>
                <td><strong>{{ number_format($contract->total_amount, 2) }} {{ $contract->currency }}</strong></td>
            </tr>
        </table>

        <table class="payment-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Phase</th>
                    <th>Milestone</th>
                    <th>%</th>
                    <th class="amount">Amount ({{ $contract->currency }})</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contract->paymentPhases as $phase)
                    <tr>
                        <td>{{ $phase->phase_number }}</td>
                        <td>{{ $phase->phase_name }}</td>
                        <td>{{ $phase->milestone_description }}</td>
                        <td>{{ $phase->percentage }}%</td>
                        <td class="amount">{{ number_format($phase->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3"><strong>Total</strong></td>
                    <td><strong>100%</strong></td>
                    <td class="amount"><strong>{{ number_format($contract->total_amount, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if($contract->terms_conditions)
        <div class="section">
            <div class="section-title">5. TERMS & CONDITIONS</div>
            <div class="scope-box">
                {{ $contract->terms_conditions }}
            </div>
        </div>
    @endif

    <div class="section">
        <div class="section-title">{{ $contract->terms_conditions ? '6' : '5' }}. GENERAL CONDITIONS</div>
        <ol>
            <li>The Contractor agrees to perform the work described above in a professional manner.</li>
            <li>Payment shall be made upon satisfactory completion of each milestone and approval by the Supervisor.</li>
            <li>The Contractor shall be responsible for the quality of work and rectification of any defects.</li>
            <li>Either party may terminate this contract with 7 days written notice.</li>
            <li>All work must comply with applicable building codes and safety regulations.</li>
        </ol>
    </div>

    <div class="signature-section">
        <div class="section-title">SIGNATURES</div>
        <table style="width: 100%;">
            <tr>
                <td style="width: 45%; text-align: center; padding: 20px;">
                    <div class="signature-line">
                        <strong>Contractor/Artisan</strong><br>
                        {{ $contract->artisan?->name }}<br>
                        Date: _______________
                    </div>
                </td>
                <td style="width: 10%;"></td>
                <td style="width: 45%; text-align: center; padding: 20px;">
                    <div class="signature-line">
                        <strong>Employer/Supervisor</strong><br>
                        {{ $contract->supervisor?->name ?? '_______________' }}<br>
                        Date: _______________
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Generated on {{ now()->format('F d, Y H:i') }} | {{ $contract->contract_number }}
    </div>
</body>
</html>

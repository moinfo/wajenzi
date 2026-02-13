<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Labor Contract - {{ $contract->contract_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 15px;
            border-bottom: 3px solid #1BC5BD;
        }
        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #222;
            margin: 8px 0 4px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .company-details {
            font-size: 10px;
            color: #666;
            line-height: 1.6;
        }

        /* Document Title */
        .document-title {
            text-align: center;
            margin: 20px 0 5px;
        }
        .document-title h1 {
            font-size: 22px;
            font-weight: bold;
            color: #1a1a1a;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .contract-meta {
            text-align: center;
            margin-bottom: 25px;
        }
        .contract-meta span {
            display: inline-block;
            background-color: #1BC5BD;
            color: #fff;
            padding: 4px 16px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        /* Sections */
        .section {
            margin-bottom: 18px;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #fff;
            background-color: #2d3436;
            padding: 7px 12px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Parties Table */
        .parties-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .parties-table td {
            padding: 6px 12px;
            vertical-align: top;
            border-bottom: 1px solid #eee;
        }
        .parties-table .label {
            width: 28%;
            font-weight: bold;
            color: #555;
            background-color: #f8f9fa;
        }
        .parties-table .value {
            color: #222;
        }

        /* Period info */
        .period-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .period-grid td {
            width: 33.33%;
            text-align: center;
            padding: 10px;
            border: 1px solid #e0e0e0;
        }
        .period-grid .period-label {
            font-size: 10px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .period-grid .period-value {
            font-size: 14px;
            font-weight: bold;
            color: #222;
            margin-top: 3px;
        }

        /* Scope box */
        .scope-box {
            background-color: #f8f9fa;
            padding: 12px 15px;
            border-left: 3px solid #1BC5BD;
            margin-top: 8px;
            line-height: 1.7;
        }

        /* Payment Table */
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .payment-table th {
            background-color: #2d3436;
            color: #fff;
            padding: 8px 10px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .payment-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        .payment-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .payment-table .text-right {
            text-align: right;
        }
        .payment-table .text-center {
            text-align: center;
        }
        .payment-table tfoot td {
            background-color: #1BC5BD;
            color: #fff;
            font-weight: bold;
            border: none;
        }

        /* Contract value highlight */
        .value-box {
            background: linear-gradient(135deg, #f8f9fa, #e8f8f7);
            border: 2px solid #1BC5BD;
            border-radius: 4px;
            padding: 12px 15px;
            margin-bottom: 12px;
            text-align: center;
        }
        .value-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #888;
            letter-spacing: 1px;
        }
        .value-amount {
            font-size: 20px;
            font-weight: bold;
            color: #1a1a1a;
        }

        /* General conditions */
        .conditions ol {
            margin: 8px 0;
            padding-left: 20px;
        }
        .conditions li {
            margin-bottom: 6px;
            line-height: 1.6;
            color: #444;
        }

        /* Signature Section */
        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 45%;
            text-align: center;
            padding: 15px;
            vertical-align: bottom;
        }
        .signature-table td.spacer {
            width: 10%;
        }
        .signature-line {
            border-top: 2px solid #333;
            padding-top: 8px;
            margin-top: 60px;
        }
        .signature-role {
            font-weight: bold;
            font-size: 12px;
            color: #222;
        }
        .signature-name {
            color: #666;
            font-size: 11px;
            margin-top: 3px;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 2px solid #1BC5BD;
            font-size: 9px;
            color: #999;
            text-align: center;
        }

        /* Watermark for draft */
        @if($contract->status === 'draft')
        .watermark {
            position: fixed;
            top: 40%;
            left: 15%;
            font-size: 100px;
            color: rgba(0,0,0,0.04);
            transform: rotate(-35deg);
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 15px;
            z-index: -1;
        }
        @endif
    </style>
</head>
<body>
    @if($contract->status === 'draft')
    <div class="watermark">DRAFT</div>
    @endif

    <!-- Company Header -->
    <div class="header">
        <img src="{{ public_path('media/logo/wajenzilogo.png') }}" alt="Logo" style="height: 70px; margin-bottom: 5px;">
        <div class="company-name">{{ settings('ORGANIZATION_NAME') }}</div>
        <div class="company-details">
            {{ settings('COMPANY_ADDRESS_LINE_1') }}
            @if(settings('COMPANY_ADDRESS_LINE_2'))
                | {{ settings('COMPANY_ADDRESS_LINE_2') }}
            @endif
            <br>
            Phone: {{ settings('COMPANY_PHONE_NUMBER') }}
            @if(settings('TAX_IDENTIFICATION_NUMBER'))
                | TIN: {{ settings('TAX_IDENTIFICATION_NUMBER') }}
            @endif
        </div>
    </div>

    <!-- Document Title -->
    <div class="document-title">
        <h1>Labor Contract</h1>
    </div>
    <div class="contract-meta">
        <span>{{ $contract->contract_number }}</span>
        &nbsp;&nbsp;
        <span style="background-color: #636e72;">Date: {{ $contract->contract_date?->format('F d, Y') }}</span>
    </div>

    <!-- 1. Parties -->
    <div class="section">
        <div class="section-title">1. Parties to the Contract</div>
        <table class="parties-table">
            <tr>
                <td class="label">Project</td>
                <td class="value">{{ $contract->project?->project_name }}</td>
            </tr>
            <tr>
                <td class="label">Contractor / Artisan</td>
                <td class="value">
                    <strong>{{ $contract->artisan?->name }}</strong>
                    @if($contract->artisan?->trade_skill)
                        &mdash; {{ $contract->artisan->trade_skill }}
                    @endif
                    @if($contract->artisan?->phone)
                        <br>Phone: {{ $contract->artisan->phone }}
                    @endif
                    @if($contract->artisan?->id_number)
                        <br>ID No: {{ $contract->artisan->id_number }}
                    @endif
                </td>
            </tr>
            @if($contract->supervisor)
            <tr>
                <td class="label">Site Supervisor</td>
                <td class="value">{{ $contract->supervisor->name }}</td>
            </tr>
            @endif
            @if($contract->laborRequest?->constructionPhase)
            <tr>
                <td class="label">Construction Phase</td>
                <td class="value">{{ $contract->laborRequest->constructionPhase->name }}</td>
            </tr>
            @endif
        </table>
    </div>

    <!-- 2. Contract Period -->
    <div class="section">
        <div class="section-title">2. Contract Period</div>
        <table class="period-grid">
            <tr>
                <td>
                    <div class="period-label">Start Date</div>
                    <div class="period-value">{{ $contract->start_date?->format('d M Y') }}</div>
                </td>
                <td>
                    <div class="period-label">End Date</div>
                    <div class="period-value">{{ $contract->end_date?->format('d M Y') }}</div>
                </td>
                <td>
                    <div class="period-label">Duration</div>
                    <div class="period-value">{{ $contract->start_date?->diffInDays($contract->end_date) }} Days</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- 3. Scope of Work -->
    <div class="section">
        <div class="section-title">3. Scope of Work</div>
        <div class="scope-box">
            {!! nl2br(e($contract->scope_of_work)) !!}
        </div>
    </div>

    <!-- 4. Contract Value & Payment -->
    <div class="section">
        <div class="section-title">4. Contract Value &amp; Payment Schedule</div>

        <div class="value-box">
            <div class="value-label">Total Contract Value</div>
            <div class="value-amount">{{ $contract->currency }} {{ number_format($contract->total_amount, 2) }}</div>
        </div>

        @if($contract->paymentPhases->count())
        <table class="payment-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 20%;">Phase</th>
                    <th style="width: 40%;">Milestone Description</th>
                    <th class="text-center" style="width: 10%;">%</th>
                    <th class="text-right" style="width: 25%;">Amount ({{ $contract->currency }})</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contract->paymentPhases as $phase)
                <tr>
                    <td class="text-center">{{ $phase->phase_number }}</td>
                    <td><strong>{{ $phase->phase_name }}</strong></td>
                    <td>{{ $phase->milestone_description }}</td>
                    <td class="text-center">{{ $phase->percentage }}%</td>
                    <td class="text-right">{{ number_format($phase->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right"><strong>Total</strong></td>
                    <td class="text-center"><strong>100%</strong></td>
                    <td class="text-right"><strong>{{ number_format($contract->total_amount, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
        @endif
    </div>

    <!-- 5. Terms & Conditions (if any) -->
    @if($contract->terms_conditions)
    <div class="section">
        <div class="section-title">5. Special Terms &amp; Conditions</div>
        <div class="scope-box">
            {!! nl2br(e($contract->terms_conditions)) !!}
        </div>
    </div>
    @endif

    <!-- General Conditions -->
    <div class="section">
        <div class="section-title">{{ $contract->terms_conditions ? '6' : '5' }}. General Conditions</div>
        <div class="conditions">
            <ol>
                <li>The Contractor agrees to perform the work described above in a professional and workmanlike manner, consistent with accepted industry standards.</li>
                <li>Payment shall be made upon satisfactory completion of each milestone and written approval by the designated Supervisor.</li>
                <li>The Contractor shall be responsible for the quality of all work and shall rectify any defects at their own cost within a reasonable period.</li>
                <li>The Contractor shall comply with all applicable building codes, safety regulations, and site rules at all times.</li>
                <li>Either party may terminate this contract with a minimum of 7 (seven) days written notice to the other party.</li>
                <li>In the event of early termination, the Contractor shall be compensated for work satisfactorily completed up to the date of termination.</li>
            </ol>
        </div>
    </div>

    <!-- Signatures -->
    <div class="signature-section">
        <div class="section-title">Signatures</div>
        <p style="font-size: 11px; color: #666; margin-bottom: 5px;">
            IN WITNESS WHEREOF, the parties have executed this contract as of the date first written above.
        </p>
        <table class="signature-table">
            <tr>
                <td>
                    <div class="signature-line">
                        <div class="signature-role">Contractor / Artisan</div>
                        <div class="signature-name">{{ $contract->artisan?->name }}</div>
                        <div class="signature-name">Date: ___________________</div>
                    </div>
                </td>
                <td class="spacer"></td>
                <td>
                    <div class="signature-line">
                        <div class="signature-role">Employer / Authorized Representative</div>
                        <div class="signature-name">{{ $contract->supervisor?->name ?? '___________________' }}</div>
                        <div class="signature-name">Date: ___________________</div>
                    </div>
                </td>
            </tr>
        </table>
        <table class="signature-table" style="margin-top: 20px;">
            <tr>
                <td>
                    <div class="signature-line">
                        <div class="signature-role">Witness 1</div>
                        <div class="signature-name">Name: ___________________</div>
                        <div class="signature-name">Date: ___________________</div>
                    </div>
                </td>
                <td class="spacer"></td>
                <td>
                    <div class="signature-line">
                        <div class="signature-role">Witness 2</div>
                        <div class="signature-name">Name: ___________________</div>
                        <div class="signature-name">Date: ___________________</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        {{ settings('ORGANIZATION_NAME') }} &bull; {{ $contract->contract_number }} &bull; Generated on {{ now()->format('F d, Y \a\t H:i') }}
    </div>
</body>
</html>

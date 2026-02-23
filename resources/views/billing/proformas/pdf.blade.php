<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Proforma Invoice {{ $proforma->document_number }}</title>
    <style>
        @page { margin: 20px 30px; }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin-top: 5px;
        }
        .company-details {
            font-size: 10px;
            color: #555;
            margin-top: 3px;
            line-height: 1.5;
        }
        .doc-details-table {
            width: 100%;
            margin-bottom: 15px;
        }
        .doc-details-table td {
            vertical-align: top;
        }
        .doc-title {
            font-size: 26px;
            font-weight: bold;
            color: #333;
        }
        .doc-number {
            font-size: 13px;
            font-weight: bold;
            color: #555;
            margin-top: 3px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 8px;
        }
        .status-draft { background-color: #6c757d; color: white; }
        .status-pending { background-color: #f0c030; color: #333; }
        .status-sent { background-color: #17a2b8; color: white; }
        .status-viewed { background-color: #007bff; color: white; }
        .status-accepted { background-color: #28a745; color: white; }
        .status-rejected { background-color: #dc3545; color: white; }
        .status-cancelled { background-color: #6c757d; color: white; }
        .info-label {
            font-weight: bold;
            color: #555;
            font-size: 10px;
            padding: 2px 0;
        }
        .info-value {
            font-size: 11px;
            padding: 2px 0 2px 10px;
        }
        .two-boxes {
            width: 100%;
            margin-bottom: 15px;
        }
        .two-boxes td {
            vertical-align: top;
            width: 48%;
        }
        .bill-to-box {
            background-color: #f0f0f0;
            padding: 12px 15px;
            border-radius: 5px;
        }
        .bill-to-box .box-title {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .invoice-for-label {
            font-size: 13px;
            font-weight: bold;
            color: #333;
            text-transform: uppercase;
        }
        .invoice-for-title {
            display: inline;
            font-size: 12px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .items-table th {
            background-color: #333;
            color: white;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
        }
        .items-table td {
            padding: 7px 6px;
            border-bottom: 1px solid #ddd;
            font-size: 10px;
        }
        .items-table .text-right { text-align: right; }
        .items-table .text-center { text-align: center; }
        .item-description {
            color: #666;
            font-size: 9px;
            margin-top: 2px;
        }
        .totals-table {
            width: 250px;
            margin-left: auto;
            margin-bottom: 15px;
        }
        .totals-table td {
            padding: 4px 8px;
            font-size: 11px;
        }
        .totals-table .total-row {
            background-color: #f0c030;
            color: #333;
            font-weight: bold;
            font-size: 13px;
        }
        .payment-instructions {
            background-color: #fef9e7;
            border: 1px solid #f0c030;
            padding: 12px 15px;
            margin-bottom: 12px;
            border-radius: 5px;
            font-size: 11px;
        }
        .payment-info-box {
            background-color: #fef9e7;
            border: 1px solid #f0c030;
            padding: 12px 15px;
            margin-bottom: 12px;
            border-radius: 5px;
            font-size: 11px;
        }
        .payment-info-box .title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 6px;
            color: #333;
        }
        .footer-bar {
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 15px;
            font-size: 9px;
            color: #555;
        }
        .footer-bar table { width: 100%; }
        .footer-bar td { vertical-align: top; }

        /* Page 2: Terms & Conditions */
        .page-break { page-break-before: always; }
        .tc-page { font-size: 10px; line-height: 1.6; }
        .tc-page h2 {
            text-align: center;
            font-size: 16px;
            margin-bottom: 15px;
            color: #333;
            text-transform: uppercase;
            border-bottom: 2px solid #333;
            padding-bottom: 8px;
        }
        .tc-page h3 {
            font-size: 11px;
            margin: 10px 0 4px 0;
            color: #333;
        }
        .tc-page p {
            margin: 3px 0 8px 0;
            text-align: justify;
        }
        .service-desc ol, .service-desc ul {
            margin: 2px 0 2px 0;
            padding-left: 20px;
            list-style-position: outside;
        }
        .service-desc ol { list-style-type: decimal; }
        .service-desc ul { list-style-type: disc; }
        .service-desc li {
            margin-bottom: 1px;
            padding-left: 2px;
            line-height: 1.4;
            font-size: 10px;
        }
        .service-desc li ol, .service-desc li ul {
            margin: 1px 0 1px 0;
            padding-left: 16px;
        }
        .service-desc p {
            margin: 2px 0;
        }
        .tc-page ol {
            margin: 3px 0 8px 0;
            padding-left: 18px;
        }
        .tc-page ol li {
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    {{-- ==================== PAGE 1: PROFORMA INVOICE ==================== --}}

    <!-- Header -->
    <div class="header">
        <img src="{{ public_path('media/logo/wajenzilogo.png') }}" alt="{{ config('app.name') }}" style="max-height: 55px; margin-bottom: 5px;">
        <div class="company-name">WAJENZI PROFESSIONAL CO. LTD</div>
        <div class="company-details">
            PSSSF COMMERCIAL COMPLEX, SAM NUJOMA ROAD, DSM-TANZANIA<br>
            P. O. Box 14492, Dar es Salaam Tanzania<br>
            Phone: +255 793 444 400 | Email: billing@wajenziprofessional.co.tz | TIN: 154-867-805
        </div>
    </div>

    <!-- Proforma Details: Title + Meta -->
    <table class="doc-details-table">
        <tr>
            <td width="50%">
                <div class="doc-title">PROFORMA INVOICE</div>
                <div class="doc-number">{{ $proforma->document_number }}</div>
                <div>
                    <span class="status-badge status-{{ $proforma->status }}">
                        {{ ucfirst(str_replace('_', ' ', $proforma->status)) }}
                    </span>
                </div>
            </td>
            <td width="50%" style="text-align: right;">
                <table style="margin-left: auto;">
                    @if($proforma->reference_number)
                        <tr>
                            <td class="info-label">Reference:</td>
                            <td class="info-value">{{ $proforma->reference_number }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="info-label">Issue Date:</td>
                        <td class="info-value">{{ $proforma->issue_date->format('d/m/Y') }}</td>
                    </tr>
                    @if($proforma->valid_until_date)
                        <tr>
                            <td class="info-label">Valid Until:</td>
                            <td class="info-value">
                                {{ $proforma->valid_until_date->format('d/m/Y') }}
                                @if($proforma->valid_until_date->isPast() && !in_array($proforma->status, ['accepted', 'cancelled']))
                                    <span style="color: #dc3545; font-weight: bold;">(EXPIRED)</span>
                                @endif
                            </td>
                        </tr>
                    @endif
                    @if($proforma->sales_person)
                        <tr>
                            <td class="info-label">Sales Person:</td>
                            <td class="info-value">{{ $proforma->sales_person }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- Bill To + Proforma For -->
    <table class="two-boxes">
        <tr>
            <td style="padding-right: 10px; width: 42%;">
                <div class="bill-to-box">
                    <div class="box-title">Bill To:</div>
                    <strong>{{ $proforma->client->first_name }} {{ $proforma->client->last_name }}</strong><br>
                    @if($proforma->client->address)
                        {{ $proforma->client->address }}<br>
                    @endif
                    @if($proforma->client->phone_number)
                        <strong>Phone:</strong> {{ $proforma->client->phone_number }}<br>
                    @endif
                    @if($proforma->client->email)
                        <strong>Email:</strong> {{ $proforma->client->email }}
                    @endif
                </div>
            </td>
            <td style="padding-left: 10px; vertical-align: top;">
                <span class="invoice-for-label">PROFORMA FOR :</span>
                @if($proforma->title)
                    <span class="invoice-for-title">{{ $proforma->title }}</span>
                @endif
                @if($proforma->service_description)
                    <div style="margin-top: 6px; font-size: 10px;">
                        <strong style="font-size: 11px;">Service Includes:</strong>
                        <div class="service-desc" style="padding: 2px 0 0 4px; font-size: 10px;">
                            {!! str_replace(['&nbsp;', "\xC2\xA0"], ' ', $proforma->service_description) !!}
                        </div>
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th width="38%">Item / Description</th>
                <th width="8%" class="text-center">Qty</th>
                <th width="8%" class="text-center">Unit</th>
                <th width="16%" class="text-right">Unit Price</th>
                <th width="12%" class="text-right">Tax</th>
                <th width="18%" class="text-right">Amount</th>
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
            <td style="text-align:right;">{{ $proforma->currency_code }} {{ number_format($proforma->subtotal_amount, 2) }}</td>
        </tr>
        @if($proforma->discount_amount > 0)
            <tr>
                <td><strong>Discount:</strong></td>
                <td style="text-align:right; color: #28a745;">-{{ $proforma->currency_code }} {{ number_format($proforma->discount_amount, 2) }}</td>
            </tr>
        @endif
        @if($proforma->tax_amount > 0)
            <tr>
                <td><strong>Tax:</strong></td>
                <td style="text-align:right;">{{ $proforma->currency_code }} {{ number_format($proforma->tax_amount, 2) }}</td>
            </tr>
        @endif
        @if($proforma->shipping_amount > 0)
            <tr>
                <td><strong>Shipping:</strong></td>
                <td style="text-align:right;">{{ $proforma->currency_code }} {{ number_format($proforma->shipping_amount, 2) }}</td>
            </tr>
        @endif
        <tr class="total-row">
            <td><strong>TOTAL:</strong></td>
            <td style="text-align:right;"><strong>{{ $proforma->currency_code }} {{ number_format($proforma->total_amount, 2) }}</strong></td>
        </tr>
    </table>

    <!-- Validity Notice -->
    @if($proforma->valid_until_date)
        <div class="payment-instructions">
            <strong>Validity Notice</strong><br>
            This proforma invoice is valid until <strong>{{ $proforma->valid_until_date->format('d/m/Y') }}</strong>.<br>
            @if($proforma->valid_until_date->isPast() && !in_array($proforma->status, ['accepted', 'cancelled']))
                <span style="color: #dc3545;"><strong>This proforma has expired.</strong></span>
            @else
                Please confirm your acceptance before the expiry date.
            @endif
        </div>
    @endif

    <!-- Payment Information (Bank Details) -->
    <div class="payment-info-box">
        <div class="title">Payment Information</div>
        <table style="width: 100%; font-size: 10px;">
            <tr>
                <td width="50%">
                    <strong>Bank:</strong> CRDB Bank<br>
                    <strong>Account Number:</strong> 0150884401500<br>
                    <strong>Account Name:</strong> WAJENZI PROFESSIONAL COMPANY LTD
                </td>
                <td width="50%">
                    <strong>Currency:</strong> {{ $proforma->currency_code }}<br>
                    <strong>Reference:</strong> {{ $proforma->document_number }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer-bar">
        <table>
            <tr>
                <td width="75%" style="vertical-align: bottom;">
                    Email: info@wajenziprofessional.co.tz<br>
                    Instagram : wajenziprofessionaltz<br>
                    PSSSF Commercial Complex, Ground Floor, Sam Nujoma Road, Dar es salaam | +255 793 444 400
                </td>
                <td width="25%" style="text-align: right; vertical-align: bottom;">
                    @if(file_exists(public_path('media/logo/instagram-qr.png')))
                        <img src="{{ public_path('media/logo/instagram-qr.png') }}" alt="Instagram QR" style="width: 65px; height: 65px;">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- ==================== PAGE 2: TERMS & CONDITIONS ==================== --}}
    @php
        $hasTerms = !empty(trim(strip_tags($proforma->terms_conditions ?? '')));
    @endphp
    @if($hasTerms)
    <div class="page-break"></div>
    <div class="tc-page">
        <!-- Header repeated for page 2 -->
        <div style="text-align: center; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #333;">
            <img src="{{ public_path('media/logo/wajenzilogo.png') }}" alt="Wajenzi Professional" style="max-height: 40px; margin-bottom: 3px;">
            <div style="font-size: 16px; font-weight: bold;">WAJENZI PROFESSIONAL CO. LTD</div>
        </div>

        <h2>Terms & Conditions of the Proforma Invoice</h2>

        @if($proforma->terms_conditions)
            {{-- Custom T&C from proforma --}}
            {!! $proforma->terms_conditions !!}
        @else
            {{-- Default T&C --}}
            <h3>1. Payment Terms</h3>
            <p>
                Payment shall be made according to the schedule outlined in the invoice. A deposit of 60% of the total project cost is required before commencement of work. The remaining 40% balance is due upon completion and delivery of the project. Late payments may attract a penalty of 2% per month on the outstanding balance. All payments should be made via bank transfer to the account details provided in the invoice.
            </p>

            <h3>2. Project Deliverables, Changes & Revisions</h3>
            <p><strong>2D Design Stage:</strong></p>
            <ol>
                <li>Initial concept designs will be presented based on the client's brief.</li>
                <li>Up to two (2) rounds of revisions are included in the quoted price.</li>
                <li>Additional revisions beyond the included rounds will be charged at 10% of the design fee per revision.</li>
                <li>Major scope changes requested after approval of the concept design will be treated as new work and quoted separately.</li>
            </ol>
            <p><strong>3D Design Stage:</strong></p>
            <ol>
                <li>3D visualization will commence only after approval of the final 2D design.</li>
                <li>Up to two (2) rounds of revisions on 3D renders are included.</li>
                <li>Changes to the approved 2D design during the 3D phase will incur additional charges.</li>
                <li>Final high-resolution renders will be delivered upon full payment.</li>
            </ol>

            <h3>3. Validity</h3>
            <p>
                This quotation/invoice is valid for seven (7) days from the date of issue. After this period, prices may be subject to review and adjustment without prior notice. To secure the quoted rates, the client must confirm acceptance and make the required deposit within the validity period.
            </p>

            <h3>4. Taxes & Statutory Deductions</h3>
            <p>
                All prices quoted are exclusive of applicable taxes unless otherwise stated. Value Added Tax (VAT) at the prevailing rate of 18% will be applied where applicable. Withholding tax and any other statutory deductions as required by Tanzanian law shall be borne by the respective party as per the law. Tax invoices and receipts will be provided for all payments received.
            </p>

            <h3>5. Ownership of Work</h3>
            <p>
                All intellectual property rights, including but not limited to designs, drawings, 3D models, and related documentation, remain the sole property of Wajenzi Professional Company Ltd until full payment has been received. Upon receipt of full payment, ownership of the final deliverables will be transferred to the client. The company reserves the right to use completed projects for portfolio and marketing purposes unless otherwise agreed in writing.
            </p>

            <h3>6. Cancellation Policy</h3>
            <p>
                In the event of project cancellation by the client, the following terms apply: Cancellation before commencement of work &mdash; 80% refund of the deposit. Cancellation after commencement but before 50% completion &mdash; 40% refund of the deposit. Cancellation after 50% completion &mdash; no refund will be issued. All cancellation requests must be submitted in writing. Work completed up to the point of cancellation remains the property of Wajenzi Professional Company Ltd.
            </p>

            <h3>7. Dispute Resolution</h3>
            <p>
                In the event of any dispute arising from this agreement, both parties shall first attempt to resolve the matter amicably through negotiation. If the dispute cannot be resolved through negotiation within fourteen (14) days, the matter shall be referred to mediation. If mediation fails, the dispute shall be submitted to arbitration in accordance with the laws of the United Republic of Tanzania. The venue for any legal proceedings shall be Dar es Salaam, Tanzania.
            </p>

            <h3>8. Agreement</h3>
            <p>
                By making payment or confirming acceptance of this invoice/quotation, the client acknowledges that they have read, understood, and agreed to all the terms and conditions stated herein. This document, together with any annexures or addenda, constitutes the entire agreement between the parties. No verbal agreements or representations shall be binding unless confirmed in writing by both parties.
            </p>
        @endif

        <!-- Footer for T&C page -->
        <div style="border-top: 2px solid #333; padding-top: 8px; margin-top: 20px; font-size: 9px; color: #555;">
            <table style="width: 100%;">
                <tr>
                    <td>
                        <strong>WAJENZI PROFESSIONAL CO. LTD</strong> | Email: billing@wajenziprofessional.co.tz | Phone: +255 793 444 400
                    </td>
                    <td style="text-align: right; color: #999; font-size: 8px;">
                        Page 2 of 2
                    </td>
                </tr>
            </table>
        </div>
    </div>
    @endif
</body>
</html>

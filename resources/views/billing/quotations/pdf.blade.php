<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->document_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .header {
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .company-info {
            text-align: right;
            margin-bottom: 20px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .document-title {
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
            margin: 20px 0;
        }
        
        .document-info {
            width: 100%;
            margin-bottom: 30px;
        }
        
        .document-info td {
            vertical-align: top;
            padding: 5px 0;
        }
        
        .client-info {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin-bottom: 30px;
        }
        
        .client-label {
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table th {
            background: #007bff;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
        }
        
        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #ddd;
        }
        
        .items-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .totals-section {
            width: 50%;
            margin-left: auto;
            margin-top: 20px;
        }
        
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .totals-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        
        .total-row {
            background: #f8f9fa;
            font-weight: bold;
            font-size: 14px;
        }
        
        .total-row td {
            border-top: 2px solid #007bff;
            border-bottom: 2px solid #007bff;
        }
        
        .terms-section {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        
        .terms-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #007bff;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-style: italic;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-draft { background: #6c757d; color: white; }
        .status-pending { background: #17a2b8; color: white; }
        .status-sent { background: #007bff; color: white; }
        .status-viewed { background: #ffc107; color: #212529; }
        .status-accepted { background: #28a745; color: white; }
        .status-rejected { background: #dc3545; color: white; }
        .status-cancelled { background: #343a40; color: white; }
        
        .two-column {
            width: 100%;
        }
        
        .two-column td {
            width: 50%;
            vertical-align: top;
            padding: 0 10px;
        }
        
        .label {
            font-weight: bold;
            display: inline-block;
            min-width: 120px;
        }
        
        .description {
            font-size: 11px;
            color: #666;
            font-style: italic;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-info">
            <div class="company-name">Your Company Name</div>
            <div>123 Business Street</div>
            <div>City, State 12345</div>
            <div>Phone: (123) 456-7890</div>
            <div>Email: info@company.com</div>
        </div>
        
        <div class="document-title">QUOTATION</div>
        
        <span class="status-badge status-{{ $quotation->status }}">{{ strtoupper(str_replace('_', ' ', $quotation->status)) }}</span>
    </div>
    
    <!-- Document Info and Client -->
    <table class="two-column">
        <tr>
            <td>
                <table class="document-info">
                    <tr>
                        <td class="label">Quote Number:</td>
                        <td>{{ $quotation->document_number }}</td>
                    </tr>
                    @if($quotation->reference_number)
                        <tr>
                            <td class="label">Reference:</td>
                            <td>{{ $quotation->reference_number }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label">Issue Date:</td>
                        <td>{{ $quotation->issue_date->format('d/m/Y') }}</td>
                    </tr>
                    @if($quotation->valid_until_date)
                        <tr>
                            <td class="label">Valid Until:</td>
                            <td>
                                {{ $quotation->valid_until_date->format('d/m/Y') }}
                                @if($quotation->valid_until_date->isPast() && $quotation->status != 'accepted')
                                    <span style="color: #dc3545;">(Expired)</span>
                                @endif
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label">Payment Terms:</td>
                        <td>{{ ucwords(str_replace('_', ' ', $quotation->payment_terms)) }}</td>
                    </tr>
                    @if($quotation->po_number)
                        <tr>
                            <td class="label">PO Number:</td>
                            <td>{{ $quotation->po_number }}</td>
                        </tr>
                    @endif
                    @if($quotation->sales_person)
                        <tr>
                            <td class="label">Sales Person:</td>
                            <td>{{ $quotation->sales_person }}</td>
                        </tr>
                    @endif
                </table>
            </td>
            <td>
                <div class="client-info">
                    <div class="client-label">Quote For:</div>
                    <strong>{{ $quotation->client->company_name }}</strong><br>
                    @if($quotation->client->contact_person)
                        {{ $quotation->client->contact_person }}<br>
                    @endif
                    @if($quotation->client->billing_address_line1)
                        {{ $quotation->client->billing_address_line1 }}<br>
                        @if($quotation->client->billing_address_line2)
                            {{ $quotation->client->billing_address_line2 }}<br>
                        @endif
                    @endif
                    @if($quotation->client->billing_city)
                        {{ $quotation->client->billing_city }}
                        @if($quotation->client->billing_state), {{ $quotation->client->billing_state }}@endif
                        @if($quotation->client->billing_postal_code) {{ $quotation->client->billing_postal_code }}@endif<br>
                    @endif
                    @if($quotation->client->billing_country)
                        {{ $quotation->client->billing_country }}<br>
                    @endif
                    @if($quotation->client->phone)
                        Phone: {{ $quotation->client->phone }}<br>
                    @endif
                    @if($quotation->client->email)
                        Email: {{ $quotation->client->email }}<br>
                    @endif
                    @if($quotation->client->tax_identification_number)
                        TIN: {{ $quotation->client->tax_identification_number }}
                    @endif
                </div>
            </td>
        </tr>
    </table>
    
    <!-- Line Items -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 35%;">Item/Description</th>
                <th style="width: 10%;" class="text-center">Qty</th>
                <th style="width: 10%;" class="text-center">Unit</th>
                <th style="width: 15%;" class="text-right">Unit Price</th>
                <th style="width: 10%;" class="text-center">Tax %</th>
                <th style="width: 20%;" class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->item_name }}</strong>
                        @if($item->description)
                            <div class="description">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-center">{{ $item->unit_of_measure ?? '-' }}</td>
                    <td class="text-right">{{ $quotation->currency_code }} {{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-center">
                        @if($item->tax_percentage > 0)
                            {{ number_format($item->tax_percentage, 1) }}%
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">{{ $quotation->currency_code }} {{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <!-- Totals -->
    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td><strong>Subtotal:</strong></td>
                <td class="text-right">{{ $quotation->currency_code }} {{ number_format($quotation->subtotal_amount, 2) }}</td>
            </tr>
            @if($quotation->discount_amount > 0)
                <tr>
                    <td><strong>Discount:</strong></td>
                    <td class="text-right" style="color: #28a745;">-{{ $quotation->currency_code }} {{ number_format($quotation->discount_amount, 2) }}</td>
                </tr>
            @endif
            @if($quotation->tax_amount > 0)
                <tr>
                    <td><strong>Tax:</strong></td>
                    <td class="text-right">{{ $quotation->currency_code }} {{ number_format($quotation->tax_amount, 2) }}</td>
                </tr>
            @endif
            @if($quotation->shipping_amount > 0)
                <tr>
                    <td><strong>Shipping:</strong></td>
                    <td class="text-right">{{ $quotation->currency_code }} {{ number_format($quotation->shipping_amount, 2) }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td><strong>TOTAL:</strong></td>
                <td class="text-right"><strong>{{ $quotation->currency_code }} {{ number_format($quotation->total_amount, 2) }}</strong></td>
            </tr>
        </table>
    </div>
    
    <!-- Terms and Conditions / Notes -->
    @if($quotation->notes || $quotation->terms_conditions)
        <div class="terms-section">
            @if($quotation->notes)
                <div style="margin-bottom: 20px;">
                    <div class="terms-title">Notes:</div>
                    <div>{{ $quotation->notes }}</div>
                </div>
            @endif
            
            @if($quotation->terms_conditions)
                <div>
                    <div class="terms-title">Terms & Conditions:</div>
                    <div>{{ $quotation->terms_conditions }}</div>
                </div>
            @endif
        </div>
    @endif
    
    <!-- Footer -->
    @if($quotation->footer_text)
        <div class="footer">
            {{ $quotation->footer_text }}
        </div>
    @endif
    
    <!-- Document Info Footer -->
    <div style="margin-top: 50px; font-size: 10px; color: #666;">
        <div style="border-top: 1px solid #ddd; padding-top: 10px;">
            <strong>Document Information:</strong><br>
            Created: {{ $quotation->created_at->format('d/m/Y H:i') }}
            @if($quotation->created_by)
                by {{ $quotation->creator->name ?? 'System' }}
            @endif
            <br>
            @if($quotation->sent_at)
                Sent: {{ $quotation->sent_at->format('d/m/Y H:i') }}<br>
            @endif
            @if($quotation->viewed_at)
                Viewed: {{ $quotation->viewed_at->format('d/m/Y H:i') }}<br>
            @endif
        </div>
    </div>
</body>
</html>
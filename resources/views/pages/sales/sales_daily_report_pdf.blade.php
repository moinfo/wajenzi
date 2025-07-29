<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Daily Report - {{ $report->report_date->format('M d, Y') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .report-info {
            margin-bottom: 20px;
        }
        .report-info div {
            display: inline-block;
            margin-right: 30px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section h3 {
            background-color: #f5f5f5;
            padding: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #007bff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.85em;
            color: white;
        }
        .badge-success { background-color: #28a745; }
        .badge-warning { background-color: #ffc107; color: #000; }
        .badge-danger { background-color: #dc3545; }
        .badge-info { background-color: #17a2b8; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .total-row { background-color: #f8f9fa; font-weight: bold; }
        .cac-summary {
            display: flex;
            margin: 15px 0;
        }
        .cac-box {
            flex: 1;
            border: 1px solid #ddd;
            padding: 15px;
            margin-right: 15px;
        }
        .cac-box:last-child {
            margin-right: 0;
        }
        .cac-value {
            font-size: 1.5em;
            color: #007bff;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SALES & BUSINESS DEVELOPMENT DAILY REPORT</h1>
        <p>{{ $report->report_date->format('F d, Y') }}</p>
    </div>

    <div class="report-info">
        <div><strong>Report Date:</strong> {{ $report->report_date->format('F d, Y') }}</div>
        <div><strong>Prepared by:</strong> {{ $report->preparedBy->name }}</div>
        <div><strong>Department:</strong> {{ $report->department }}</div>
    </div>

    <div class="section">
        <h3>1. DAILY SUMMARY</h3>
        <p>{{ $report->daily_summary }}</p>
    </div>

    <div class="section">
        <h3>2. LEAD FOLLOW-UPS & INTERACTIONS</h3>
        <p>Summary of the Follow-ups and Customer Acquisition activities...</p>

        @if($report->leadFollowups->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">S/n</th>
                        <th>Lead Name</th>
                        <th>Interaction Type</th>
                        <th>Details/Discussion</th>
                        <th>Outcome</th>
                        <th>Next Step</th>
                        <th>Follow-Up Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report->leadFollowups as $index => $followup)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $followup->lead_name }}</td>
                            <td>{{ $followup->clientSource->name ?? '-' }}</td>
                            <td>{{ $followup->details_discussion ?: '-' }}</td>
                            <td>{{ $followup->outcome ?: '-' }}</td>
                            <td>{{ $followup->next_step ?: '-' }}</td>
                            <td>{{ $followup->followup_date?->format('M d, Y') ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p><small>*Table extracted from client interaction database/excel</small></p>
        @else
            <p><em>No lead follow-ups recorded for this date.</em></p>
        @endif
    </div>

    <div class="section">
        <h3>3. SALES ACTIVITY</h3>
        <h4>3.1 Summary of Daily Sales made, Invoice generated, payment made...etc.</h4>

        @if($report->salesActivities->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">S/n</th>
                        <th>Invoice No</th>
                        <th>Invoice sum/Price</th>
                        <th>Activity</th>
                        <th>Status/Not Paid</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report->salesActivities as $index => $activity)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $activity->invoice_no ?: '-' }}</td>
                            <td>{{ number_format($activity->invoice_sum, 2) }}</td>
                            <td>{{ $activity->activity }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $activity->status)) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="2"><strong>Total Sales</strong></td>
                        <td><strong>{{ number_format($report->getTotalSalesAmount(), 2) }}</strong></td>
                        <td colspan="2">
                            Paid: {{ number_format($report->getPaidSalesAmount(), 2) }} |
                            Unpaid: {{ number_format($report->getUnpaidSalesAmount(), 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>

            @if($report->customerAcquisitionCost)
                <h4>3.2 Daily Customer Acquisition Cost (CAC) Report</h4>
                <div class="cac-summary">
                    <div class="cac-box">
                        <h5>Cost Breakdown</h5>
                        <table>
                            <tr><td>Marketing Cost:</td><td>{{ number_format($report->customerAcquisitionCost->marketing_cost, 2) }}</td></tr>
                            <tr><td>Sales Cost:</td><td>{{ number_format($report->customerAcquisitionCost->sales_cost, 2) }}</td></tr>
                            <tr><td>Other Cost:</td><td>{{ number_format($report->customerAcquisitionCost->other_cost, 2) }}</td></tr>
                            <tr class="total-row"><td><strong>Total Cost:</strong></td><td><strong>{{ number_format($report->customerAcquisitionCost->total_cost, 2) }}</strong></td></tr>
                        </table>
                    </div>
                    <div class="cac-box text-center">
                        <h5>CAC Value</h5>
                        <div class="cac-value">{{ number_format($report->customerAcquisitionCost->cac_value, 2) }}</div>
                        <p>Cost per Customer</p>
                        <small>{{ $report->customerAcquisitionCost->new_customers }} new customers acquired</small>
                        @if($report->customerAcquisitionCost->notes)
                            <br><br><strong>Notes:</strong><br>{{ $report->customerAcquisitionCost->notes }}
                        @endif
                    </div>
                </div>
            @endif
        @else
            <p><em>No sales activities recorded for this date.</em></p>
        @endif
    </div>

    <div class="section">
        <h3>4. ISSUES OR CLIENT CONCERNS</h3>
        @if($report->clientConcerns->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">S/n</th>
                        <th>Client Name</th>
                        <th>Issue/Concern</th>
                        <th>Action Taken or Required</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report->clientConcerns as $index => $concern)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $concern->client_name }}</td>
                            <td>{{ $concern->issue_concern }}</td>
                            <td>{{ $concern->action_taken }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p><em>No client concerns reported for this date.</em></p>
        @endif
    </div>

    <div class="section">
        <h3>5. NOTES & RECOMMENDATIONS</h3>
        @if($report->notes_recommendations)
            <p>{{ $report->notes_recommendations }}</p>
        @else
            <p><em>Use this section for any important observations, client preferences, or suggestions for team coordination.</em></p>
        @endif
    </div>

    <div style="margin-top: 40px; text-align: center; font-size: 0.9em; color: #666;">
        <p>Generated on {{ now()->format('F d, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>

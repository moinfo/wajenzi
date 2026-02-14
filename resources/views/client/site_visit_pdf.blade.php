<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Site Visit Report {{ $visit->document_number }}</title>
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
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        .report-title {
            font-size: 22px;
            font-weight: bold;
            color: #228be6;
            text-align: center;
            margin: 20px 0;
        }
        .info-table {
            width: 100%;
            margin-bottom: 25px;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            font-size: 12px;
        }
        .info-table .label {
            background-color: #f8f9fa;
            font-weight: bold;
            width: 30%;
            color: #495057;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #228be6;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .section-content {
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            padding: 5px 0;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-approved { background-color: #e6fcf5; color: #099268; }
        .status-pending { background-color: #fff9db; color: #e67700; }
        .status-completed { background-color: #e7f5ff; color: #1864ab; }
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
            font-size: 10px;
            color: #868e96;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Company Header -->
    <div class="header">
        <div class="company-name">{{ settings('SYSTEM_NAME') }}</div>
        <div class="company-details">
            @if(settings('COMPANY_ADDRESS')){{ settings('COMPANY_ADDRESS') }}@endif
            @if(settings('COMPANY_PHONE'))<br>Phone: {{ settings('COMPANY_PHONE') }}@endif
            @if(settings('COMPANY_EMAIL'))<br>Email: {{ settings('COMPANY_EMAIL') }}@endif
        </div>
    </div>

    <div class="report-title">Site Visit Report</div>

    <!-- Visit Details -->
    <table class="info-table">
        <tr>
            <td class="label">Document No.</td>
            <td>{{ $visit->document_number ?? 'N/A' }}</td>
            <td class="label">Visit Date</td>
            <td>{{ $visit->visit_date?->format('M d, Y') ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Project</td>
            <td>{{ $project->project_name }}</td>
            <td class="label">Status</td>
            <td>
                <span class="status-badge status-{{ strtolower($visit->status ?? 'pending') }}">
                    {{ ucfirst($visit->status ?? 'N/A') }}
                </span>
            </td>
        </tr>
        <tr>
            <td class="label">Inspector</td>
            <td>{{ $visit->inspector->name ?? 'N/A' }}</td>
            <td class="label">Location</td>
            <td>{{ $visit->location ?? 'N/A' }}</td>
        </tr>
    </table>

    <!-- Description -->
    @if($visit->description)
        <div class="section">
            <div class="section-title">Description</div>
            <div class="section-content">{!! nl2br(e($visit->description)) !!}</div>
        </div>
    @endif

    <!-- Findings -->
    @if($visit->findings)
        <div class="section">
            <div class="section-title">Findings</div>
            <div class="section-content">{!! nl2br(e($visit->findings)) !!}</div>
        </div>
    @endif

    <!-- Recommendations -->
    @if($visit->recommendations)
        <div class="section">
            <div class="section-title">Recommendations</div>
            <div class="section-content">{!! nl2br(e($visit->recommendations)) !!}</div>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        Generated on {{ now()->format('M d, Y \a\t h:i A') }} | {{ settings('SYSTEM_NAME') }}
    </div>
</body>
</html>

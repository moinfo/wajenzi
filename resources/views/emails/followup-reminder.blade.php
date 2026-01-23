<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Follow-up Reminder</title>
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
            background-color: {{ $reminderType === 'today' ? '#dc3545' : '#ffc107' }};
            color: {{ $reminderType === 'today' ? '#fff' : '#333' }};
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-top: none;
        }
        .lead-info {
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #007bff;
        }
        .lead-info h3 {
            margin-top: 0;
            color: #007bff;
        }
        .info-row {
            margin: 10px 0;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .followup-details {
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #28a745;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>
            @if($reminderType === 'today')
                Follow-up Due TODAY!
            @else
                Follow-up Due Tomorrow
            @endif
        </h1>
    </div>

    <div class="content">
        <p>Hello {{ $followup->lead->salesperson->name ?? 'Team' }},</p>

        <p>
            @if($reminderType === 'today')
                <strong>This is a reminder that you have a follow-up scheduled for TODAY.</strong>
            @else
                This is a reminder that you have a follow-up scheduled for <strong>tomorrow ({{ $followup->followup_date->format('d M Y') }})</strong>.
            @endif
        </p>

        <div class="lead-info">
            <h3>Lead Information</h3>
            <div class="info-row">
                <span class="info-label">Lead Number:</span>
                {{ $followup->lead->lead_number ?? 'N/A' }}
            </div>
            <div class="info-row">
                <span class="info-label">Client Name:</span>
                {{ $followup->lead->name }}
            </div>
            <div class="info-row">
                <span class="info-label">Phone:</span>
                {{ $followup->lead->phone ?? 'N/A' }}
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                {{ $followup->lead->email ?? 'N/A' }}
            </div>
            @if($followup->lead->serviceInterested)
            <div class="info-row">
                <span class="info-label">Service Interested:</span>
                {{ $followup->lead->serviceInterested->name }}
            </div>
            @endif
            @if($followup->lead->estimated_value)
            <div class="info-row">
                <span class="info-label">Estimated Value:</span>
                TZS {{ number_format($followup->lead->estimated_value) }}
            </div>
            @endif
        </div>

        <div class="followup-details">
            <h3>Follow-up Details</h3>
            <div class="info-row">
                <span class="info-label">Follow-up Date:</span>
                {{ $followup->followup_date->format('d M Y') }}
            </div>
            @if($followup->details_discussion)
            <div class="info-row">
                <span class="info-label">Last Remarks:</span>
                {{ $followup->details_discussion }}
            </div>
            @endif
            @if($followup->next_step)
            <div class="info-row">
                <span class="info-label">Next Action:</span>
                {{ $followup->next_step }}
            </div>
            @endif
        </div>

        <p>
            <a href="{{ url('/leads/' . $followup->lead->id) }}" class="btn">
                View Lead Details
            </a>
        </p>
    </div>

    <div class="footer">
        <p>This is an automated reminder from Wajenzi Lead Management System.</p>
        <p>&copy; {{ date('Y') }} Wajenzi. All rights reserved.</p>
    </div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Assignment</title>
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
            background-color: #17a2b8;
            color: #fff;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0;
            opacity: 0.9;
        }
        .content {
            background-color: #f8f9fa;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-top: none;
        }
        .activity-info {
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #17a2b8;
        }
        .activity-info h3 {
            margin-top: 0;
            color: #17a2b8;
        }
        .project-info {
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #007bff;
        }
        .project-info h3 {
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
        .action-required {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        .action-required strong {
            color: #0c5460;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #17a2b8;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
            font-weight: bold;
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
        <h1>Activity Assigned to You</h1>
        <p>{{ $activity->activity_code }}: {{ $activity->name }}</p>
    </div>

    <div class="content">
        <p>Hello {{ $userName }},</p>

        <p>
            <strong>{{ $assignedBy->name }}</strong> has assigned you the following activity. Please review the details below and take action accordingly.
        </p>

        <div class="activity-info">
            <h3>Activity Details</h3>
            <div class="info-row">
                <span class="info-label">Activity Code:</span>
                {{ $activity->activity_code }}
            </div>
            <div class="info-row">
                <span class="info-label">Activity Name:</span>
                {{ $activity->name }}
            </div>
            <div class="info-row">
                <span class="info-label">Phase:</span>
                {{ $activity->phase }}
            </div>
            <div class="info-row">
                <span class="info-label">Discipline:</span>
                {{ $activity->discipline }}
            </div>
            <div class="info-row">
                <span class="info-label">Start Date:</span>
                {{ $activity->start_date->format('d M Y (l)') }}
            </div>
            <div class="info-row">
                <span class="info-label">End Date:</span>
                {{ $activity->end_date->format('d M Y (l)') }}
            </div>
            <div class="info-row">
                <span class="info-label">Duration:</span>
                {{ $activity->duration_days }} working days
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                {{ ucwords(str_replace('_', ' ', $activity->status)) }}
            </div>
            @if($activity->predecessor_code)
            <div class="info-row">
                <span class="info-label">Depends On:</span>
                Activity {{ $activity->predecessor_code }}
            </div>
            @endif
        </div>

        <div class="project-info">
            <h3>Project Information</h3>
            <div class="info-row">
                <span class="info-label">Lead Number:</span>
                {{ $schedule->lead->lead_number ?? 'N/A' }}
            </div>
            <div class="info-row">
                <span class="info-label">Client Name:</span>
                {{ $schedule->lead->name ?? 'N/A' }}
            </div>
            @if($schedule->lead->phone ?? null)
            <div class="info-row">
                <span class="info-label">Phone:</span>
                {{ $schedule->lead->phone }}
            </div>
            @endif
            <div class="info-row">
                <span class="info-label">Assigned By:</span>
                {{ $assignedBy->name }}
            </div>
        </div>

        <div class="action-required">
            <strong>What to do next:</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Review the activity details and timeline</li>
                <li>Start the activity when prerequisites are met</li>
                <li>Mark as completed when done and upload any attachments</li>
            </ul>
        </div>

        <p style="text-align: center;">
            <a href="{{ url('/project-schedules/' . $schedule->id) }}" class="btn">
                View Schedule
            </a>
        </p>
    </div>

    <div class="footer">
        <p>This is an automated notification from Wajenzi Project Management System.</p>
        <p>&copy; {{ date('Y') }} Wajenzi. All rights reserved.</p>
    </div>
</body>
</html>

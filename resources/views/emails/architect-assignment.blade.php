<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Project Assignment</title>
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
            background-color: #007bff;
            color: #fff;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            background-color: #f8f9fa;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-top: none;
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
        .schedule-info {
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #28a745;
        }
        .schedule-info h3 {
            margin-top: 0;
            color: #28a745;
        }
        .activities-preview {
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #6c757d;
        }
        .activities-preview h3 {
            margin-top: 0;
            color: #6c757d;
        }
        .activity-item {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-code {
            font-weight: bold;
            color: #007bff;
        }
        .activity-dates {
            font-size: 12px;
            color: #888;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
            font-weight: bold;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-warning {
            background-color: #ffc107;
            color: #333;
        }
        .action-required {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        .action-required strong {
            color: #856404;
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
        <h1>New Project Assignment</h1>
    </div>

    <div class="content">
        <p>Hello {{ $schedule->assignedArchitect->name ?? 'Architect' }},</p>

        <p>
            <strong>You have been assigned a new project!</strong> Please review the project details and schedule below.
        </p>

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
            @if($schedule->lead->phone)
            <div class="info-row">
                <span class="info-label">Phone:</span>
                {{ $schedule->lead->phone }}
            </div>
            @endif
            @if($schedule->lead->email)
            <div class="info-row">
                <span class="info-label">Email:</span>
                {{ $schedule->lead->email }}
            </div>
            @endif
            @if($schedule->lead->serviceInterested)
            <div class="info-row">
                <span class="info-label">Service:</span>
                {{ $schedule->lead->serviceInterested->name }}
            </div>
            @endif
            @if($schedule->lead->location)
            <div class="info-row">
                <span class="info-label">Location:</span>
                {{ $schedule->lead->location }}
            </div>
            @endif
        </div>

        <div class="schedule-info">
            <h3>Schedule Overview</h3>
            <div class="info-row">
                <span class="info-label">Project Start Date:</span>
                {{ $schedule->start_date->format('d M Y (l)') }}
            </div>
            <div class="info-row">
                <span class="info-label">Expected End Date:</span>
                {{ $schedule->end_date ? $schedule->end_date->format('d M Y (l)') : 'To be calculated' }}
            </div>
            <div class="info-row">
                <span class="info-label">Total Activities:</span>
                {{ $schedule->activities->count() }} activities
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                {{ ucwords(str_replace('_', ' ', $schedule->status)) }}
            </div>
        </div>

        @if($schedule->activities->count() > 0)
        <div class="activities-preview">
            <h3>First 5 Activities</h3>
            @foreach($schedule->activities->take(5) as $activity)
            <div class="activity-item">
                <span class="activity-code">{{ $activity->activity_code }}</span>: {{ $activity->name }}
                <div class="activity-dates">
                    {{ $activity->start_date->format('d/m/Y') }} - {{ $activity->end_date->format('d/m/Y') }} ({{ $activity->duration_days }} days)
                </div>
            </div>
            @endforeach
            @if($schedule->activities->count() > 5)
            <div class="activity-item" style="color: #888; font-style: italic;">
                ... and {{ $schedule->activities->count() - 5 }} more activities
            </div>
            @endif
        </div>
        @endif

        <div class="action-required">
            <strong>Action Required:</strong>
            <ol style="margin: 10px 0; padding-left: 20px;">
                <li>Review the project schedule</li>
                <li>Adjust the start date if needed (this will recalculate all activity dates)</li>
                <li>Confirm the schedule to activate activities</li>
            </ol>
        </div>

        <p style="text-align: center;">
            <a href="{{ url('/project-schedules/' . $schedule->id) }}" class="btn">
                View & Confirm Schedule
            </a>
        </p>
    </div>

    <div class="footer">
        <p>This is an automated notification from Wajenzi Project Management System.</p>
        <p>&copy; {{ date('Y') }} Wajenzi. All rights reserved.</p>
    </div>
</body>
</html>

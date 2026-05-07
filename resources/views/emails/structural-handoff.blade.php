<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Structural Design Handoff</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #6f42c1; color: #fff; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .header h1 { margin: 0; font-size: 22px; }
        .content { background-color: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; border-top: none; }
        .info-box { background: #fff; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #6f42c1; }
        .info-box h3 { margin-top: 0; color: #6f42c1; }
        .info-row { margin: 8px 0; }
        .info-label { font-weight: bold; color: #666; }
        .stage-list { list-style: none; padding: 0; margin: 10px 0; }
        .stage-list li { padding: 6px 10px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 8px; }
        .stage-list li:last-child { border-bottom: none; }
        .stage-num { background: #6f42c1; color: #fff; border-radius: 50%; width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; }
        .action-box { background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px; padding: 15px; margin: 15px 0; }
        .btn { display: inline-block; padding: 12px 24px; background-color: #6f42c1; color: #fff; text-decoration: none; border-radius: 5px; margin-top: 15px; font-weight: bold; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Structural Design Handoff</h1>
        <p style="margin:6px 0 0;">Architectural design approved — your work begins now</p>
    </div>

    <div class="content">
        <p>Hello,</p>
        <p>The architectural design for the project below has been fully approved by management. The structural design phase has now been handed off to the engineering team.</p>

        <div class="info-box">
            <h3>Project Details</h3>
            <div class="info-row">
                <span class="info-label">Project:</span>
                {{ $design->project->project_name ?? 'N/A' }}
            </div>
            <div class="info-row">
                <span class="info-label">Client:</span>
                @if($design->project?->client)
                    {{ $design->project->client->first_name }} {{ $design->project->client->last_name }}
                @else
                    N/A
                @endif
            </div>
            <div class="info-row">
                <span class="info-label">Reference:</span>
                {{ $design->document_number }}
            </div>
            <div class="info-row">
                <span class="info-label">Assigned Engineer:</span>
                {{ $design->assignedEngineer->name ?? 'Unassigned' }}
            </div>
        </div>

        <div class="info-box" style="border-left-color: #28a745;">
            <h3 style="color:#28a745;">Required Stages</h3>
            <ul class="stage-list">
                @foreach($design->stages as $stage)
                <li>
                    <span class="stage-num">{{ $stage->stage_order }}</span>
                    {{ $stage->name }}
                </li>
                @endforeach
            </ul>
        </div>

        <div class="action-box">
            <strong>Action Required:</strong>
            <ol style="margin: 8px 0; padding-left: 20px;">
                <li>Complete each structural design stage in order</li>
                <li>Upload drawings/documents for each stage</li>
                <li>Submit for CEO/MD approval when all stages are done</li>
            </ol>
        </div>

        <p style="text-align:center;">
            <a href="{{ url('/structural-design/' . $design->id) }}" class="btn">
                Open Structural Design
            </a>
        </p>
    </div>

    <div class="footer">
        <p>Automated notification from Wajenzi Project Management System.</p>
        <p>&copy; {{ date('Y') }} Wajenzi. All rights reserved.</p>
    </div>
</body>
</html>

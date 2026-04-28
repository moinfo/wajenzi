<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: {{ $reminderType === 'today' ? '#dc3545' : '#fd7e14' }}; color: #fff; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background-color: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; border-top: none; }
        .info-box { background: #fff; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #007bff; }
        .info-box h3 { margin-top: 0; color: #007bff; }
        .info-row { margin: 8px 0; }
        .label { font-weight: bold; color: #666; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: #fff; text-decoration: none; border-radius: 5px; margin-top: 15px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Field Marketing Follow-up @if($reminderType === 'today') Due TODAY! @else Due Tomorrow @endif</h2>
    </div>
    <div class="content">
        <p>Hello {{ $visit->session->officer->name ?? 'Officer' }},</p>
        <p>
            @if($reminderType === 'today')
                You have a field marketing follow-up scheduled <strong>today</strong>.
            @else
                You have a field marketing follow-up scheduled <strong>tomorrow ({{ $visit->next_followup_date->format('d M Y') }})</strong>.
            @endif
        </p>
        <div class="info-box">
            <h3>Visit Details</h3>
            <div class="info-row"><span class="label">Business:</span> {{ $visit->business_name }}</div>
            <div class="info-row"><span class="label">Location:</span> {{ $visit->location ?? '—' }}</div>
            <div class="info-row"><span class="label">Phone:</span> {{ $visit->phone ?? '—' }}</div>
            <div class="info-row"><span class="label">Follow-up Date:</span> {{ $visit->next_followup_date->format('d M Y') }}</div>
            @if($visit->notes)
            <div class="info-row"><span class="label">Notes:</span> {{ $visit->notes }}</div>
            @endif
            @if($visit->services->isNotEmpty())
            <div class="info-row"><span class="label">Services Pitched:</span> {{ $visit->services->pluck('name')->implode(', ') }}</div>
            @endif
        </div>
        <p>
            <a href="{{ url(route('field_marketing.sessions.show', $visit->session_id)) }}" class="btn">View Session</a>
        </p>
    </div>
    <div class="footer">
        <p>Automated reminder from Wajenzi Field Marketing. &copy; {{ date('Y') }} Wajenzi.</p>
    </div>
</body>
</html>

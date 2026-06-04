{{-- Shared header card: review number, employee, period, status badge, score summary --}}
<style>
.kpi-h-card { background:#fff; border-radius:12px; border:1px solid #eef0f3; box-shadow:0 1px 4px rgba(0,0,0,.06); padding:18px 22px; margin-bottom:18px; }
.kpi-h-title { font-size:22px; font-weight:800; color:#1a2332; margin:0; }
.kpi-h-sub { font-size:13px; color:#8a92a6; margin-top:3px; }
.kpi-h-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:14px; margin-top:16px; }
.kpi-h-stat { background:#f8fafc; border-radius:10px; padding:12px 14px; }
.kpi-h-stat .label { font-size:10.5px; color:#8a92a6; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
.kpi-h-stat .value { font-size:15px; font-weight:700; color:#1a2332; margin-top:3px; }
.kpi-h-stage { display:flex; gap:6px; margin-top:14px; flex-wrap:wrap; }
.kpi-h-step { display:flex; align-items:center; gap:6px; padding:6px 12px; border-radius:20px; font-size:11px; font-weight:600; }
.kpi-h-step.done    { background:#dcfce7; color:#166534; }
.kpi-h-step.active  { background:#dbeafe; color:#1d4ed8; }
.kpi-h-step.pending { background:#f1f5f9; color:#94a3b8; }
.kpi-h-step.rejected{ background:#fee2e2; color:#b91c1c; }
.kpi-h-step .dot { width:8px; height:8px; border-radius:50%; background:currentColor; }
</style>
@php
    $stages = [
        ['key' => 'draft',                'label' => '1. Self',       'done_after' => 'self_submitted'],
        ['key' => 'self_submitted',       'label' => '2. Supervisor', 'done_after' => 'supervisor_reviewed'],
        ['key' => 'supervisor_reviewed',  'label' => '3. MD',         'done_after' => 'md_reviewed'],
        ['key' => 'md_reviewed',          'label' => '4. CEO',        'done_after' => 'completed'],
    ];
    $orderMap = ['draft' => 0, 'self_submitted' => 1, 'supervisor_reviewed' => 2, 'md_reviewed' => 3, 'completed' => 4, 'rejected' => -1, 'returned' => 0];
    $current = $orderMap[$review->status] ?? 0;
@endphp
<div class="kpi-h-card">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h2 class="kpi-h-title">
                {{ $review->review_number }}
                <small style="font-size:13px; font-weight:600; color:#8a92a6;">— {{ optional($review->template)->name ?? '—' }}</small>
            </h2>
            <div class="kpi-h-sub">
                <i class="fa fa-user"></i> {{ optional($review->employee)->name ?? '—' }}
                &nbsp;&middot;&nbsp;
                <i class="fa fa-calendar-alt"></i> {{ $review->period_label }}
                &nbsp;&middot;&nbsp;
                <i class="fa fa-user-tie"></i> Supervisor: {{ $review->supervisor->name ?? '—' }}
            </div>
        </div>
        <a href="{{ route('performance.index') }}"
           style="background:#f3f4f6; color:#475569; padding:7px 13px; border-radius:8px; text-decoration:none; font-weight:600; font-size:13px;">
            <i class="fa fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="kpi-h-stage">
        @foreach($stages as $i => $st)
            @php
                if ($review->status === 'rejected') {
                    $cls = $i === $current ? 'rejected' : ($i < $current ? 'done' : 'pending');
                } else {
                    $cls = $i < $current ? 'done' : ($i === $current ? 'active' : 'pending');
                }
            @endphp
            <span class="kpi-h-step {{ $cls }}">
                <span class="dot"></span>{{ $st['label'] }}
            </span>
        @endforeach
    </div>

    <div class="kpi-h-grid">
        <div class="kpi-h-stat">
            <div class="label">Self Score</div>
            <div class="value">{{ $review->total_self_score !== null ? number_format($review->total_self_score, 1) . '%' : '—' }}</div>
        </div>
        <div class="kpi-h-stat">
            <div class="label">Supervisor Score</div>
            <div class="value">{{ $review->total_supervisor_score !== null ? number_format($review->total_supervisor_score, 1) . '%' : '—' }}</div>
        </div>
        <div class="kpi-h-stat">
            <div class="label">Overall Score</div>
            <div class="value" style="color:#1BC5BD;">{{ $review->total_overall_score !== null ? number_format($review->total_overall_score, 1) . '%' : '—' }}</div>
        </div>
        <div class="kpi-h-stat">
            <div class="label">Grade</div>
            <div class="value">{{ $review->grade_label ?? '—' }}</div>
        </div>
    </div>
</div>

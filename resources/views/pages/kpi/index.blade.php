@extends('layouts.backend')

@section('content')
<style>
.kpi-card { background:#fff; border-radius:12px; border:1px solid #eef0f3; box-shadow:0 1px 4px rgba(0,0,0,.06); }
.kpi-tabs { display:flex; gap:6px; padding:6px; background:#f1f5f9; border-radius:10px; margin-bottom:16px; width:fit-content; }
.kpi-tab { padding:8px 16px; border-radius:8px; font-size:13px; font-weight:600; color:#64748b; text-decoration:none; cursor:pointer; }
.kpi-tab.active { background:#fff; color:#1a2332; box-shadow:0 1px 3px rgba(0,0,0,.08); }
.kpi-tab .badge-count { background:#1BC5BD; color:#fff; border-radius:10px; padding:1px 7px; font-size:10px; margin-left:6px; }

.kpi-table { width:100%; border-collapse:collapse; }
.kpi-table thead th { background:#1a2332; color:#fff; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; padding:11px 12px; white-space:nowrap; }
.kpi-table tbody tr { border-bottom:1px solid #f3f4f6; transition:background .15s; }
.kpi-table tbody tr:hover { background:#fafbfc; }
.kpi-table td { padding:12px; font-size:13px; color:#1a2332; vertical-align:middle; }
.kpi-num { font-weight:700; color:#1BC5BD; text-decoration:none; }

.kpi-status { display:inline-block; padding:3px 10px; border-radius:20px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; white-space:nowrap; }
.s-draft               { background:#f1f5f9; color:#64748b; }
.s-self_submitted      { background:#dbeafe; color:#1d4ed8; }
.s-supervisor_reviewed { background:#fef3c7; color:#92400e; }
.s-md_reviewed         { background:#e9d5ff; color:#6b21a8; }
.s-completed           { background:#dcfce7; color:#166534; }
.s-rejected            { background:#fee2e2; color:#b91c1c; }
.s-returned            { background:#fed7aa; color:#9a3412; }

.kpi-score { font-weight:700; font-variant-numeric:tabular-nums; }
.kpi-grade { font-size:10px; color:#8a92a6; margin-top:2px; }
.kpi-empty { padding:48px 20px; text-align:center; color:#cbd5e1; }
</style>

<div class="container-fluid" style="padding:24px 28px;">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 style="font-size:22px; font-weight:800; color:#1a2332; margin:0;">
                <i class="fa fa-chart-line" style="color:#1BC5BD;"></i> Performance Reviews
            </h2>
            <p style="margin:4px 0 0; font-size:13px; color:#8a92a6;">
                Self-assess monthly, route through supervisor → Managing Director → CEO.
            </p>
        </div>
        <a href="{{ route('performance.create') }}"
           style="background:#1BC5BD; color:#fff; padding:8px 18px; border-radius:8px; text-decoration:none; font-weight:700; font-size:13px;">
            <i class="fa fa-plus"></i> New Review
        </a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))  <div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if(session('info'))   <div class="alert alert-info">{{ session('info') }}</div>@endif

    <div class="kpi-tabs">
        <a href="{{ route('performance.index', ['tab' => 'mine']) }}"
           class="kpi-tab {{ $tab === 'mine' ? 'active' : '' }}">
            My Reviews
            @if($myOpenCount > 0)<span class="badge-count">{{ $myOpenCount }}</span>@endif
        </a>
        <a href="{{ route('performance.index', ['tab' => 'awaiting']) }}"
           class="kpi-tab {{ $tab === 'awaiting' ? 'active' : '' }}">
            Awaiting My Review
            @if($awaitingCount > 0)<span class="badge-count">{{ $awaitingCount }}</span>@endif
        </a>
        @if($canSeeAll)
            <a href="{{ route('performance.index', ['tab' => 'all']) }}"
               class="kpi-tab {{ $tab === 'all' ? 'active' : '' }}">All Reviews</a>
        @endif
    </div>

    @if($tab === 'all' && $topPerformers->isNotEmpty())
        <div style="background:linear-gradient(135deg, #fff7ed 0%, #fef3c7 100%); border-radius:12px; border:1px solid #fde68a; box-shadow:0 1px 4px rgba(0,0,0,.06); padding:18px 22px; margin-bottom:18px;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:14px;">
                <span style="font-size:22px;">🏆</span>
                <div>
                    <h3 style="margin:0; font-size:15px; font-weight:800; color:#854d0e;">Top Performers — {{ now()->format('F Y') }}</h3>
                    <p style="margin:2px 0 0; font-size:12px; color:#92400e;">Ranked by overall score across all finalised reviews this month.</p>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:repeat({{ min($topPerformers->count(), 5) }}, 1fr); gap:10px;">
                @foreach($topPerformers as $i => $p)
                    @php
                        $rankIcon = $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : '⭐'));
                        $rankBg   = $i === 0 ? '#fef3c7' : '#fff';
                        $rankBorder = $i === 0 ? '#f59e0b' : '#fde68a';
                    @endphp
                    <a href="{{ route('performance.show', $p) }}"
                       style="background:{{ $rankBg }}; border:1.5px solid {{ $rankBorder }}; border-radius:10px; padding:12px; text-decoration:none; color:inherit; transition:transform .15s;">
                        <div style="font-size:18px; line-height:1;">{{ $rankIcon }}</div>
                        <div style="font-weight:800; color:#1a2332; font-size:13px; margin-top:6px;">{{ $p->employee->name }}</div>
                        <div style="font-size:10.5px; color:#92400e; font-weight:600; margin-top:2px;">{{ $p->template->name }}</div>
                        <div style="font-size:18px; font-weight:800; color:#16a34a; margin-top:6px; font-variant-numeric:tabular-nums;">
                            {{ number_format($p->total_overall_score, 1) }}%
                        </div>
                        <div style="font-size:10px; color:#64748b; margin-top:1px;">{{ $p->grade_label }}</div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="kpi-card">
        <table class="kpi-table">
            <thead>
                <tr>
                    <th>Review #</th>
                    <th>Employee</th>
                    <th>Template</th>
                    <th>Period</th>
                    <th>Supervisor</th>
                    <th>Status</th>
                    <th style="text-align:right;">Score / Grade</th>
                    <th style="width:140px; text-align:right; padding-right:18px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $r)
                    <tr>
                        <td><a href="{{ route('performance.show', $r) }}" class="kpi-num">{{ $r->review_number }}</a></td>
                        <td>{{ $r->employee->name }}</td>
                        <td>{{ $r->template->name }}</td>
                        <td>{{ $r->period_label }}</td>
                        <td>{{ $r->supervisor->name ?? '—' }}</td>
                        <td><span class="kpi-status s-{{ $r->status }}">{{ ucfirst(str_replace('_',' ',$r->status)) }}</span></td>
                        <td style="text-align:right;">
                            @if($r->total_overall_score !== null)
                                <div class="kpi-score">{{ number_format($r->total_overall_score, 1) }}%</div>
                                <div class="kpi-grade">{{ $r->grade_label }}</div>
                            @elseif($r->total_self_score !== null)
                                <div class="kpi-score" style="color:#94a3b8;">{{ number_format($r->total_self_score, 1) }}% (self)</div>
                            @else
                                <span style="color:#cbd5e1;">—</span>
                            @endif
                        </td>
                        <td style="text-align:right; padding-right:14px;">
                            <a href="{{ route('performance.show', $r) }}"
                               style="background:#e8f8f7; color:#1BC5BD; border:1px solid #c5edeb; padding:5px 10px; border-radius:7px; font-size:12px; text-decoration:none; font-weight:600;">View</a>
                            <a href="{{ route('performance.pdf', $r) }}" target="_blank"
                               style="background:#f3f4f6; color:#475569; border:1px solid #e0e2e7; padding:5px 8px; border-radius:7px; font-size:12px; text-decoration:none; font-weight:600; margin-left:4px;" title="Download PDF">
                                <i class="fa fa-file-pdf"></i>
                            </a>
                            @if($r->employee_id === auth()->id() && in_array($r->status, ['draft','returned']))
                                <a href="{{ route('performance.self', $r) }}"
                                   style="background:#e8f0fe; color:#4285f4; border:1px solid #c5d8fc; padding:5px 10px; border-radius:7px; font-size:12px; text-decoration:none; font-weight:600; margin-left:4px;">Fill</a>
                            @endif
                            @php
                                $stage = match($r->status) {
                                    'self_submitted'      => 'supervisor',
                                    'supervisor_reviewed' => 'md',
                                    'md_reviewed'         => 'ceo',
                                    default               => null,
                                };
                                $canReview = false;
                                if ($stage === 'supervisor' && $r->supervisor_id === auth()->id()) $canReview = true;
                                if ($stage === 'md'  && auth()->user()->hasRole('Managing Director')) $canReview = true;
                                if ($stage === 'ceo' && auth()->user()->hasAnyRole(['CEO','Chief Executive Officer'])) $canReview = true;
                            @endphp
                            @if($canReview)
                                <a href="{{ route('performance.review', $r) }}"
                                   style="background:#fef9c3; color:#854d0e; border:1px solid #fde047; padding:5px 10px; border-radius:7px; font-size:12px; text-decoration:none; font-weight:600; margin-left:4px;">Review</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="kpi-empty">
                        <i class="fa fa-inbox fa-2x mb-2" style="display:block;"></i>
                        <p style="margin:0; font-size:14px;">No reviews to show in this tab.</p>
                    </div></td></tr>
                @endforelse
            </tbody>
        </table>
        @if($reviews->hasPages())
            <div style="padding:16px 20px; border-top:1px solid #f0f0f0; background:#fafbfc;">
                {{ $reviews->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

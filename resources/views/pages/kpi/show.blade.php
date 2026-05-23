@extends('layouts.backend')

@section('content')
<style>
.kpi-section { background:#fff; border-radius:12px; border:1px solid #eef0f3; box-shadow:0 1px 4px rgba(0,0,0,.06); margin-bottom:18px; overflow:hidden; }
.kpi-section-head { background:#1a2332; color:#fff; padding:12px 18px; display:flex; justify-content:space-between; align-items:center; }
.kpi-section-head .title { font-weight:700; font-size:14px; letter-spacing:.3px; }
.kpi-section-head .weight { background:rgba(255,255,255,.18); padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.kpi-tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
.kpi-tbl thead th { background:#f8fafc; color:#475569; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; padding:9px 10px; border-bottom:1px solid #e5e7eb; }
.kpi-tbl tbody td { padding:11px 10px; border-bottom:1px solid #f3f4f6; color:#1a2332; vertical-align:top; }
.kpi-tbl tbody tr:last-child td { border-bottom:none; }
.kpi-tbl .col-kpa { font-weight:600; }
.kpi-tbl .col-target { color:#64748b; font-size:12px; }
.kpi-tbl .col-weight { text-align:center; font-weight:700; color:#1a2332; }
.kpi-tbl .col-rate { text-align:center; font-weight:700; font-variant-numeric:tabular-nums; }
.kpi-tbl .col-rate.self { color:#4285f4; }
.kpi-tbl .col-rate.sup  { color:#f59e0b; }
.kpi-tbl .col-rate.ovr  { color:#16a34a; }
.kpi-tbl .col-cmt { color:#64748b; font-size:11.5px; max-width:200px; }
.kpi-footer-card { background:#fff; border-radius:12px; border:1px solid #eef0f3; padding:18px 22px; margin-bottom:18px; box-shadow:0 1px 4px rgba(0,0,0,.06); }
.kpi-footer-card h4 { font-size:12px; font-weight:700; color:#8a92a6; text-transform:uppercase; letter-spacing:.5px; margin:0 0 6px; }
.kpi-footer-card .text { color:#1a2332; font-size:13px; white-space:pre-wrap; }
</style>

<div class="container-fluid" style="padding:24px 28px;">

    @include('pages.kpi._review_header')

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    @foreach($groupedRatings as $code => $bundle)
        @php $section = $bundle['section']; $ratings = $bundle['ratings']; @endphp
        @if($ratings->isEmpty()) @continue @endif
        <div class="kpi-section">
            <div class="kpi-section-head">
                <span class="title">Section {{ $section->code }} — {{ $section->title }}</span>
                <span class="weight">{{ rtrim(rtrim(number_format($section->weight_total, 2), '0'), '.') }}%</span>
            </div>
            <table class="kpi-tbl">
                <thead>
                    <tr>
                        <th>KPA</th>
                        <th>Measure</th>
                        <th>Target</th>
                        <th style="width:60px; text-align:center;">Wt</th>
                        <th style="width:65px; text-align:center;">Self</th>
                        <th style="width:80px; text-align:center;">Supervisor</th>
                        <th style="width:75px; text-align:center;">Overall</th>
                        <th>Comment</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ratings as $rating)
                        <tr>
                            <td class="col-kpa">{{ $rating->kpa_snapshot }}</td>
                            <td>{{ $rating->measure_snapshot }}</td>
                            <td class="col-target">{{ $rating->target_snapshot ?? '—' }}</td>
                            <td class="col-weight">{{ rtrim(rtrim(number_format($rating->weight_snapshot, 2), '0'), '.') }}%</td>
                            <td class="col-rate self">{{ $rating->self_rate !== null ? rtrim(rtrim(number_format($rating->self_rate, 1), '0'), '.') . '%' : '—' }}</td>
                            <td class="col-rate sup">{{ $rating->supervisor_rate !== null ? rtrim(rtrim(number_format($rating->supervisor_rate, 1), '0'), '.') . '%' : '—' }}</td>
                            <td class="col-rate ovr">{{ $rating->overall_rate !== null ? rtrim(rtrim(number_format($rating->overall_rate, 1), '0'), '.') . '%' : '—' }}</td>
                            <td class="col-cmt">{{ $rating->comment ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    @if($review->achievements || $review->areas_of_improvement || $review->training_needs || $review->employee_comments || $review->supervisor_comments || $review->md_comments || $review->ceo_comments)
        <div class="kpi-footer-card">
            <div class="row">
                @if($review->achievements)
                <div class="col-md-6 mb-3">
                    <h4>Achievements During Review Period</h4>
                    <div class="text">{{ $review->achievements }}</div>
                </div>
                @endif
                @if($review->areas_of_improvement)
                <div class="col-md-6 mb-3">
                    <h4>Areas of Improvement</h4>
                    <div class="text">{{ $review->areas_of_improvement }}</div>
                </div>
                @endif
                @if($review->training_needs)
                <div class="col-md-6 mb-3">
                    <h4>Training & Development Needs</h4>
                    <div class="text">{{ $review->training_needs }}</div>
                </div>
                @endif
                @if($review->employee_comments)
                <div class="col-md-6 mb-3">
                    <h4>Employee Comments</h4>
                    <div class="text">{{ $review->employee_comments }}</div>
                </div>
                @endif
                @if($review->supervisor_comments)
                <div class="col-md-6 mb-3">
                    <h4>Supervisor Comments</h4>
                    <div class="text">{{ $review->supervisor_comments }}</div>
                </div>
                @endif
                @if($review->md_comments)
                <div class="col-md-6 mb-3">
                    <h4>Managing Director Comments</h4>
                    <div class="text">{{ $review->md_comments }}</div>
                </div>
                @endif
                @if($review->ceo_comments)
                <div class="col-md-6 mb-3">
                    <h4>CEO Comments</h4>
                    <div class="text">{{ $review->ceo_comments }}</div>
                </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Action bar based on user + status --}}
    @php
        $u = auth()->user();
        $canFill   = $review->employee_id === $u->id && in_array($review->status, ['draft','returned']);
        $stage = match($review->status) {
            'self_submitted'      => 'supervisor',
            'supervisor_reviewed' => 'md',
            'md_reviewed'         => 'ceo',
            default               => null,
        };
        $canReview = false;
        if ($stage === 'supervisor' && $review->supervisor_id === $u->id) $canReview = true;
        if ($stage === 'md'  && $u->hasRole('Managing Director')) $canReview = true;
        if ($stage === 'ceo' && $u->hasAnyRole(['CEO','Chief Executive Officer'])) $canReview = true;
    @endphp
    @php
        // Recall: only the employee, only when supervisor hasn't started reviewing yet
        $canRecall = $review->employee_id === auth()->id()
            && $review->status === 'self_submitted'
            && !$review->supervisor_reviewed_at;
    @endphp
    <div style="display:flex; justify-content:flex-end; gap:10px;">
        <a href="{{ route('performance.pdf', $review) }}" target="_blank"
           style="background:#fff; color:#1a2332; border:1.5px solid #1a2332; padding:9px 20px; border-radius:8px; text-decoration:none; font-weight:700; font-size:13px;">
            <i class="fa fa-file-pdf"></i> Download PDF
        </a>
        @if($canRecall)
            <form method="POST" action="{{ route('performance.recall', $review) }}" style="display:inline;"
                  onsubmit="return confirm('Recall this submission for editing? Your supervisor will lose visibility until you resubmit.');">
                @csrf
                <button type="submit"
                        style="background:#fff; color:#9a3412; border:1.5px solid #f97316; padding:9px 20px; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer;">
                    <i class="fa fa-undo"></i> Recall &amp; Edit
                </button>
            </form>
        @endif
        @if($canFill)
            <a href="{{ route('performance.self', $review) }}"
               style="background:#4285f4; color:#fff; padding:9px 20px; border-radius:8px; text-decoration:none; font-weight:700; font-size:13px;">
                <i class="fa fa-edit"></i> Fill Self-Assessment
            </a>
        @endif
        @if($canReview)
            <a href="{{ route('performance.review', $review) }}"
               style="background:#f59e0b; color:#fff; padding:9px 20px; border-radius:8px; text-decoration:none; font-weight:700; font-size:13px;">
                <i class="fa fa-clipboard-check"></i> Open {{ ucfirst($stage) }} Review
            </a>
        @endif
    </div>
</div>
@endsection

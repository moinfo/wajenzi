@extends('layouts.backend')

@section('content')
<style>
.kpi-section { background:#fff; border-radius:12px; border:1px solid #eef0f3; box-shadow:0 1px 4px rgba(0,0,0,.06); margin-bottom:18px; overflow:hidden; }
.kpi-section-head { background:#1a2332; color:#fff; padding:12px 18px; display:flex; justify-content:space-between; align-items:center; }
.kpi-tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
.kpi-tbl thead th { background:#f8fafc; color:#475569; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; padding:9px 10px; border-bottom:1px solid #e5e7eb; }
.kpi-tbl tbody td { padding:10px 10px; border-bottom:1px solid #f3f4f6; vertical-align:top; }
.kpi-tbl input[type="number"] { width:75px; border:1.5px solid #e5e7eb; border-radius:6px; padding:5px 7px; font-size:12.5px; text-align:center; }
.kpi-tbl input[type="number"]:focus { border-color:#f59e0b; outline:none; box-shadow:0 0 0 3px rgba(245,158,11,.12); }
.kpi-tbl input[type="number"].overall:focus { border-color:#16a34a; box-shadow:0 0 0 3px rgba(22,163,74,.12); }
.kpi-tbl input[disabled] { background:#f8fafc; color:#94a3b8; }
.kpi-tbl textarea { width:100%; min-height:32px; border:1.5px solid #e5e7eb; border-radius:6px; padding:6px 8px; font-size:12px; resize:vertical; }
.kpi-tbl .self-shown { font-weight:700; color:#4285f4; }
.kpi-footer-edit { background:#fff; border-radius:12px; border:1px solid #eef0f3; padding:18px 22px; margin-bottom:18px; box-shadow:0 1px 4px rgba(0,0,0,.06); }
.kpi-footer-edit label { font-size:11px; font-weight:700; color:#8a92a6; text-transform:uppercase; letter-spacing:.5px; }
.kpi-footer-edit textarea { width:100%; min-height:80px; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px; font-size:13px; }
.kpi-footer-edit .readonly-text { background:#f8fafc; border-radius:8px; padding:12px; color:#475569; font-size:13px; white-space:pre-wrap; min-height:60px; }
</style>

<div class="container-fluid" style="padding:24px 28px;">

    @include('pages.kpi._review_header')

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger" style="border-radius:10px;">
            <strong>Please fix the following:</strong>
            <ul style="margin:6px 0 0 18px;">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    @php
        $stageLabel = ['supervisor' => 'Supervisor', 'md' => 'Managing Director', 'ceo' => 'CEO'][$stage] ?? ucfirst($stage);
        $stageColor = ['supervisor' => '#f59e0b', 'md' => '#8b5cf6', 'ceo' => '#16a34a'][$stage] ?? '#1BC5BD';
        // Supervisor sets both their rate AND the agreed overall rate.
        // MD/CEO can override the overall rate if they disagree, otherwise it stays as-is.
        $editSupervisorRate = $stage === 'supervisor';
        $editOverallRate    = in_array($stage, ['supervisor', 'md', 'ceo'], true);
        $editSupervisorComments = $stage === 'supervisor';
        $editMdComments         = $stage === 'md';
        $editCeoComments        = $stage === 'ceo';
    @endphp

    <div class="alert" style="background:rgba(245,158,11,.12); color:#92400e; border-radius:10px; padding:12px 16px; border:none; margin-bottom:18px;">
        <i class="fa fa-clipboard-check"></i>
        You are reviewing as <strong>{{ $stageLabel }}</strong>.
        @if($stage === 'supervisor')
            Rate the employee in the <strong>Supervisor</strong> column, set the agreed <strong>Overall</strong> rate, and add your comments. When done, approve to forward to the Managing Director.
        @elseif($stage === 'md')
            Review the supervisor's ratings. You may override the <strong>Overall</strong> rate if you disagree. Approve to forward to the CEO.
        @else
            Confirm the overall ratings (override if needed) and approve to finalise this review.
        @endif
    </div>

    <form method="POST" action="{{ route('performance.review.update', $review) }}" id="kpiReviewForm">
        @csrf
        @method('PATCH')

        @foreach($groupedRatings as $code => $bundle)
            @php $section = $bundle['section']; $ratings = $bundle['ratings']; @endphp
            @if($ratings->isEmpty()) @continue @endif
            <div class="kpi-section">
                <div class="kpi-section-head">
                    <span style="font-weight:700;">Section {{ $section->code }} — {{ $section->title }}</span>
                    <span style="background:rgba(255,255,255,.18); padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700;">
                        {{ rtrim(rtrim(number_format($section->weight_total, 2), '0'), '.') }}%
                    </span>
                </div>
                <table class="kpi-tbl">
                    <thead>
                        <tr>
                            <th>KPA</th>
                            <th>Measure</th>
                            <th style="width:60px; text-align:center;">Wt</th>
                            <th style="width:60px; text-align:center;">Self</th>
                            <th style="width:95px; text-align:center;">Supervisor</th>
                            <th style="width:90px; text-align:center;">Overall</th>
                            <th>Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ratings as $rating)
                            <tr>
                                <td style="font-weight:600;">{{ $rating->kpa_snapshot }}</td>
                                <td>{{ $rating->measure_snapshot }}
                                    @if($rating->target_snapshot)
                                        <br><small style="color:#94a3b8;">Target: {{ $rating->target_snapshot }}</small>
                                    @endif
                                </td>
                                @php $wt = (float) $rating->weight_snapshot; $wtFmt = rtrim(rtrim(number_format($wt, 2), '0'), '.'); @endphp
                                <td style="text-align:center; font-weight:700;">{{ $wtFmt }}%</td>
                                <td style="text-align:center;" class="self-shown">{{ $rating->self_rate !== null ? rtrim(rtrim(number_format($rating->self_rate, 1), '0'), '.') . '%' : '—' }}</td>
                                <td style="text-align:center;">
                                    <input type="number"
                                           class="sup-rate"
                                           data-rating-id="{{ $rating->id }}"
                                           data-max="{{ $wt }}"
                                           name="ratings[{{ $rating->id }}][supervisor_rate]"
                                           min="0" max="{{ $wt }}" step="0.1"
                                           placeholder="0–{{ $wtFmt }}"
                                           aria-label="Supervisor rate for {{ $rating->kpa_snapshot }} (max {{ $wtFmt }})"
                                           value="{{ old('ratings.' . $rating->id . '.supervisor_rate', $rating->supervisor_rate) }}"
                                           {{ $editSupervisorRate ? '' : 'disabled' }}>
                                </td>
                                <td style="text-align:center;">
                                    <input type="number"
                                           class="overall ovr-rate"
                                           data-rating-id="{{ $rating->id }}"
                                           data-max="{{ $wt }}"
                                           name="ratings[{{ $rating->id }}][overall_rate]"
                                           min="0" max="{{ $wt }}" step="0.1"
                                           placeholder="0–{{ $wtFmt }}"
                                           aria-label="Overall rate for {{ $rating->kpa_snapshot }} (max {{ $wtFmt }})"
                                           value="{{ old('ratings.' . $rating->id . '.overall_rate', $rating->overall_rate ?? $rating->supervisor_rate) }}"
                                           {{ $editOverallRate ? '' : 'disabled' }}>
                                </td>
                                <td>
                                    <textarea name="ratings[{{ $rating->id }}][comment]" rows="1"
                                              aria-label="Comment for {{ $rating->kpa_snapshot }}"
                                              placeholder="Optional notes…">{{ old('ratings.' . $rating->id . '.comment', $rating->comment) }}</textarea>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

        <div class="kpi-footer-edit">
            <h4 style="font-size:13px; font-weight:700; color:#1a2332; margin:0 0 14px;">Period Summary</h4>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Achievements (from employee)</label>
                    <div class="readonly-text">{{ $review->achievements ?: '—' }}</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Areas of Improvement (from employee)</label>
                    <div class="readonly-text">{{ $review->areas_of_improvement ?: '—' }}</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Training Needs (from employee)</label>
                    <div class="readonly-text">{{ $review->training_needs ?: '—' }}</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Employee Comments</label>
                    <div class="readonly-text">{{ $review->employee_comments ?: '—' }}</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Supervisor Comments</label>
                    @if($editSupervisorComments)
                        <textarea name="supervisor_comments">{{ old('supervisor_comments', $review->supervisor_comments) }}</textarea>
                    @else
                        <div class="readonly-text">{{ $review->supervisor_comments ?: '—' }}</div>
                    @endif
                </div>
                <div class="col-md-6 mb-3">
                    <label>Managing Director Comments</label>
                    @if($editMdComments)
                        <textarea name="md_comments">{{ old('md_comments', $review->md_comments) }}</textarea>
                    @else
                        <div class="readonly-text">{{ $review->md_comments ?: '—' }}</div>
                    @endif
                </div>
                <div class="col-md-6 mb-3">
                    <label>CEO Comments</label>
                    @if($editCeoComments)
                        <textarea name="ceo_comments">{{ old('ceo_comments', $review->ceo_comments) }}</textarea>
                    @else
                        <div class="readonly-text">{{ $review->ceo_comments ?: '—' }}</div>
                    @endif
                </div>
            </div>
        </div>

        <input type="hidden" name="rejection_reason" id="rejectionReasonField">

        <div style="display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('performance.show', $review) }}"
               style="background:#f3f4f6; color:#475569; padding:10px 18px; border-radius:8px; font-weight:600; font-size:13px; text-decoration:none;">Cancel</a>
            <button type="submit" name="action" value="save"
                    style="background:#fff; color:{{ $stageColor }}; border:1.5px solid {{ $stageColor }}; padding:9px 20px; border-radius:8px; font-weight:700; font-size:13px;">
                <i class="fa fa-save"></i> Save Draft
            </button>
            <button type="button" onclick="promptAction('return')"
                    style="background:#fff; color:#9a3412; border:1.5px solid #f97316; padding:9px 20px; border-radius:8px; font-weight:700; font-size:13px;">
                <i class="fa fa-undo"></i> Return for Changes
            </button>
            <button type="button" onclick="promptAction('reject')"
                    style="background:#fff; color:#b91c1c; border:1.5px solid #ef4444; padding:9px 20px; border-radius:8px; font-weight:700; font-size:13px;">
                <i class="fa fa-times"></i> Reject
            </button>
            <button type="submit" name="action" value="approve"
                    style="background:{{ $stageColor }}; color:#fff; padding:10px 24px; border-radius:8px; font-weight:700; font-size:13px; border:none;">
                <i class="fa fa-check"></i>
                Approve &amp; Forward
            </button>
        </div>
    </form>
</div>

<script>
// Clamp any rate input to its row's Wt — the rate IS the weighted contribution
// (max possible score for that row equals its weight). Server enforces this too.
function clampToMax(el) {
    const max = parseFloat(el.dataset.max);
    if (isNaN(max) || el.value === '') return;
    const v = parseFloat(el.value);
    if (!isNaN(v) && v > max) {
        el.value = max;
    } else if (!isNaN(v) && v < 0) {
        el.value = 0;
    }
}
document.querySelectorAll('.sup-rate, .ovr-rate').forEach(function (el) {
    el.addEventListener('blur', function () { clampToMax(el); });
});

// When supervisor types in their rate, mirror it to the Overall column so they
// don't need to type the same number twice. The supervisor (or MD/CEO) can
// still override Overall manually after the auto-fill.
document.querySelectorAll('.sup-rate').forEach(function (el) {
    el.addEventListener('input', function () {
        const id = el.dataset.ratingId;
        const ovr = document.querySelector('.ovr-rate[data-rating-id="' + id + '"]');
        if (!ovr || ovr.disabled) return;
        // Only mirror if Overall is still empty — don't clobber manual overrides
        if (ovr.value === '' || ovr.dataset.autofilled === '1') {
            ovr.value = el.value;
            ovr.dataset.autofilled = '1';
        }
    });
});
// Mark Overall as user-touched the moment they type into it directly
document.querySelectorAll('.ovr-rate').forEach(function (el) {
    el.addEventListener('input', function () { el.dataset.autofilled = '0'; });
});

// Before forwarding to the next stage, require a rate on every row at the
// stage's owning column (Supervisor at the supervisor stage, Overall at MD/CEO).
// Mirrors the server-side guard in KpiController::updateReviewer.
// Save Draft is exempt so reviewers can leave mid-flight.
document.getElementById('kpiReviewForm').addEventListener('submit', function (e) {
    const action = e.submitter && e.submitter.value;
    if (action !== 'approve') return;
    const stage = @json($stage);
    const selector = stage === 'supervisor' ? '.sup-rate' : '.ovr-rate';
    const columnLabel = stage === 'supervisor' ? 'Supervisor' : 'Overall';
    const blanks = Array.from(document.querySelectorAll(selector))
        .filter(el => !el.disabled && (el.value === '' || el.value === null));
    if (blanks.length > 0) {
        e.preventDefault();
        blanks.forEach(el => el.style.background = '#fee2e2');
        blanks[0].focus();
        alert('Please fill the ' + columnLabel + ' rate on every KPI before approving. ' + blanks.length + ' row(s) are blank.');
        return;
    }
    if (!confirm('Approve and forward to the next stage?')) {
        e.preventDefault();
    }
});

function promptAction(act) {
    const reason = prompt(
        act === 'reject'
            ? 'Reason for rejection (required):'
            : 'Notes for the employee on what to fix (required):'
    );
    if (reason === null || reason.trim() === '') {
        return;
    }
    document.getElementById('rejectionReasonField').value = reason;
    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'action';
    hidden.value = act;
    document.getElementById('kpiReviewForm').appendChild(hidden);
    document.getElementById('kpiReviewForm').submit();
}
</script>
@endsection

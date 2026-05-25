@extends('layouts.backend')

@section('content')
<style>
.kpi-section { background:#fff; border-radius:12px; border:1px solid #eef0f3; box-shadow:0 1px 4px rgba(0,0,0,.06); margin-bottom:18px; overflow:hidden; }
.kpi-section-head { background:#1a2332; color:#fff; padding:12px 18px; display:flex; justify-content:space-between; align-items:center; }
.kpi-tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
.kpi-tbl thead th { background:#f8fafc; color:#475569; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; padding:9px 10px; border-bottom:1px solid #e5e7eb; }
.kpi-tbl tbody td { padding:10px 10px; border-bottom:1px solid #f3f4f6; vertical-align:top; }
.kpi-tbl .col-kpa { font-weight:600; }
.kpi-tbl .col-target { color:#64748b; }
.kpi-tbl input[type="number"] { width:80px; border:1.5px solid #e5e7eb; border-radius:6px; padding:5px 8px; font-size:12.5px; text-align:center; }
.kpi-tbl input[type="number"]:focus { border-color:#4285f4; outline:none; box-shadow:0 0 0 3px rgba(66,133,244,.12); }
.kpi-tbl textarea { width:100%; min-height:38px; border:1.5px solid #e5e7eb; border-radius:6px; padding:6px 8px; font-size:12px; resize:vertical; }
.kpi-footer-edit { background:#fff; border-radius:12px; border:1px solid #eef0f3; padding:18px 22px; margin-bottom:18px; box-shadow:0 1px 4px rgba(0,0,0,.06); }
.kpi-footer-edit label { font-size:11px; font-weight:700; color:#8a92a6; text-transform:uppercase; letter-spacing:.5px; }
.kpi-footer-edit textarea { width:100%; min-height:80px; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px; font-size:13px; }
</style>

<div class="container-fluid" style="padding:24px 28px;">

    @include('pages.kpi._review_header')

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="alert" style="background:#dbeafe; color:#1d4ed8; border-radius:10px; padding:12px 16px; border:none; margin-bottom:18px;">
        <i class="fa fa-info-circle"></i>
        Rate yourself on each KPI between <strong>0 and that KPI's weight (Wt)</strong>.
        The rate is your weighted score for that row — the maximum equals the weight.
        Save as draft any time; if you submit by mistake you can recall it from the show page <em>before</em> your supervisor starts reviewing.
    </div>

    {{-- Sticky running-total card — JS keeps it in sync as the user types. --}}
    <div id="kpi-live-total"
         style="position:sticky; top:10px; z-index:10; background:#fff; border:2px solid #1BC5BD; border-radius:12px; padding:12px 18px; margin-bottom:16px; box-shadow:0 2px 8px rgba(0,0,0,.06); display:flex; justify-content:space-between; align-items:center;">
        <div>
            <div style="font-size:11px; color:#8a92a6; font-weight:700; text-transform:uppercase; letter-spacing:.5px;">Your Live Self Score</div>
            <div style="font-size:11.5px; color:#475569; margin-top:2px;">Updates as you type. Final score = sum of your rates across all rows.</div>
        </div>
        <div style="text-align:right;">
            <div id="kpi-total-score" style="font-size:26px; font-weight:800; color:#1BC5BD; line-height:1; font-variant-numeric:tabular-nums;">0.0%</div>
            <div id="kpi-total-grade" style="font-size:11px; color:#8a92a6; margin-top:3px;">Out of 100</div>
        </div>
    </div>

    <form method="POST" action="{{ route('performance.self.update', $review) }}">
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
                            <th>Target</th>
                            <th style="width:55px; text-align:center;">Wt</th>
                            <th style="width:100px; text-align:center;">Self Rate</th>
                            <th style="width:240px;">Your Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ratings as $rating)
                            @php $wt = (float) $rating->weight_snapshot; $wtFmt = rtrim(rtrim(number_format($wt, 2), '0'), '.'); @endphp
                            <tr>
                                <td class="col-kpa">{{ $rating->kpa_snapshot }}</td>
                                <td>{{ $rating->measure_snapshot }}</td>
                                <td class="col-target">{{ $rating->target_snapshot ?? '—' }}</td>
                                <td style="text-align:center; font-weight:700;">{{ $wtFmt }}%</td>
                                <td style="text-align:center;">
                                    <input type="number"
                                           class="kpi-self-input self-rate-input"
                                           data-weight="{{ $wt }}"
                                           data-max="{{ $wt }}"
                                           name="ratings[{{ $rating->id }}][self_rate]"
                                           min="0" max="{{ $wt }}" step="0.1"
                                           placeholder="0–{{ $wtFmt }}"
                                           aria-label="Self rate for {{ $rating->kpa_snapshot }} (max {{ $wtFmt }})"
                                           value="{{ old('ratings.' . $rating->id . '.self_rate', $rating->self_rate) }}">
                                    <div class="kpi-weighted-hint" style="font-size:10px; color:#1BC5BD; font-weight:700; margin-top:3px; height:12px;">—</div>
                                </td>
                                <td>
                                    <textarea name="ratings[{{ $rating->id }}][comment]" rows="1"
                                              placeholder="Optional notes…">{{ old('ratings.' . $rating->id . '.comment', $rating->comment) }}</textarea>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

        <div class="kpi-footer-edit">
            <h4 style="font-size:13px; font-weight:700; color:#1a2332; margin:0 0 14px;">Review Period Summary</h4>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Achievements During Review Period</label>
                    <textarea name="achievements">{{ old('achievements', $review->achievements) }}</textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Areas of Improvement</label>
                    <textarea name="areas_of_improvement">{{ old('areas_of_improvement', $review->areas_of_improvement) }}</textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Training & Development Needs</label>
                    <textarea name="training_needs">{{ old('training_needs', $review->training_needs) }}</textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Your Comments</label>
                    <textarea name="employee_comments">{{ old('employee_comments', $review->employee_comments) }}</textarea>
                </div>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <a href="{{ route('performance.show', $review) }}"
               style="background:#f3f4f6; color:#475569; padding:10px 18px; border-radius:8px; font-weight:600; font-size:13px; text-decoration:none;">Cancel</a>
            <button type="submit" name="action" value="save"
                    style="background:#fff; color:#4285f4; border:1.5px solid #4285f4; padding:9px 20px; border-radius:8px; font-weight:700; font-size:13px;">
                <i class="fa fa-save"></i> Save Draft
            </button>
            <button type="submit" name="action" value="submit"
                    style="background:#1BC5BD; color:#fff; padding:10px 24px; border-radius:8px; font-weight:700; font-size:13px; border:none;"
                    onclick="return confirm('Submit this self-assessment to your supervisor? You can recall it before they start reviewing, but not after.')">
                <i class="fa fa-paper-plane"></i> Submit to Supervisor
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    const inputs  = document.querySelectorAll('.kpi-self-input');
    const totalEl = document.getElementById('kpi-total-score');
    const gradeEl = document.getElementById('kpi-total-grade');

    function gradeFor(score) {
        if (score >= 90) return 'Excellent';
        if (score >= 80) return 'Very Good';
        if (score >= 70) return 'Good';
        if (score >= 60) return 'Average';
        if (score >= 50) return 'Poor';
        return 'Ungraded';
    }

    // Per-row hint: rate IS the weighted contribution now, so just show "x of Wt%".
    function formatHint(rate, weight) {
        if (!Number.isFinite(rate)) return '—';
        const clamped = Math.max(0, Math.min(weight, rate));
        return clamped.toFixed(1) + ' of ' + weight + '%';
    }

    // Clamp to 0..weight on blur — server enforces this too.
    function clampToMax(el) {
        const max = parseFloat(el.dataset.max);
        if (isNaN(max) || el.value === '') return;
        const v = parseFloat(el.value);
        if (!isNaN(v) && v > max) el.value = max;
        else if (!isNaN(v) && v < 0) el.value = 0;
    }

    function recalc() {
        let total = 0;
        inputs.forEach(el => {
            const weight = parseFloat(el.dataset.weight) || 0;
            const rate   = parseFloat(el.value);
            const hintEl = el.parentElement.querySelector('.kpi-weighted-hint');
            if (hintEl) hintEl.textContent = formatHint(rate, weight);
            if (!isNaN(rate) && rate >= 0 && rate <= weight) {
                total += rate;
            }
        });
        totalEl.textContent = total.toFixed(1) + '%';
        gradeEl.textContent = gradeFor(total) + ' (out of 100)';
    }

    inputs.forEach(el => {
        el.addEventListener('input', recalc);
        el.addEventListener('blur',  function () { clampToMax(el); recalc(); });
    });
    recalc();
}());
</script>
@endsection

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
        Rate yourself on each KPI between <strong>0 and 100</strong>. Save as draft any time; once submitted you can't change it.
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
                            <th style="width:100px; text-align:center;">Self Rate (%)</th>
                            <th style="width:240px;">Your Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ratings as $rating)
                            <tr>
                                <td class="col-kpa">{{ $rating->kpa_snapshot }}</td>
                                <td>{{ $rating->measure_snapshot }}</td>
                                <td class="col-target">{{ $rating->target_snapshot ?? '—' }}</td>
                                <td style="text-align:center; font-weight:700;">{{ rtrim(rtrim(number_format($rating->weight_snapshot, 2), '0'), '.') }}%</td>
                                <td style="text-align:center;">
                                    <input type="number" name="ratings[{{ $rating->id }}][self_rate]"
                                           min="0" max="100" step="0.1"
                                           value="{{ old('ratings.' . $rating->id . '.self_rate', $rating->self_rate) }}">
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
                    onclick="return confirm('Submit this self-assessment to your supervisor? You will not be able to edit it after.')">
                <i class="fa fa-paper-plane"></i> Submit to Supervisor
            </button>
        </div>
    </form>
</div>
@endsection

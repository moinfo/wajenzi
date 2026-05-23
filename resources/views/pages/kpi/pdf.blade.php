<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>KPI {{ $review->employee->name }} {{ $review->period_label }}</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a2332; }

        /* Teal accent bar at the very top of every page */
        .hdr-accent { height: 4px; background: #1BC5BD; margin: -8px -28px 8px; }

        /* Company letterhead */
        .letterhead { width: 100%; border-collapse: collapse; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #1a2332; }
        .letterhead td { vertical-align: middle; }
        .letterhead .logo-td { width: 80px; }
        .letterhead .logo-td img { height: 60px; }
        .letterhead .co-td { padding-left: 12px; }
        .letterhead .co-name { font-size: 13px; font-weight: bold; color: #1a2332; letter-spacing: .4px; }
        .letterhead .co-addr { font-size: 8.5px; color: #475569; margin-top: 2px; line-height: 1.4; }
        .letterhead .contact-td { text-align: right; font-size: 8.5px; color: #475569; line-height: 1.45; }
        .letterhead .contact-td strong { color: #1a2332; }

        .doc-title { text-align: center; font-size: 13px; font-weight: bold; margin: 0 0 6px; letter-spacing: 1px; }
        .period { text-align: center; font-size: 11px; font-weight: bold; color: #374151; margin: 0 0 12px; letter-spacing: .5px; }

        /* Header table — employee info */
        .hdr-tbl { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .hdr-tbl td { border: 1px solid #1a2332; padding: 5px 7px; font-size: 9px; vertical-align: top; }
        .hdr-tbl .label { background: #f1f5f9; font-weight: bold; width: 22%; }
        .hdr-tbl .value { width: 28%; }

        /* Rating scale box */
        .rating-scale { border: 1px solid #1a2332; padding: 7px 10px; margin-bottom: 8px; background: #fafbfc; font-size: 8.5px; }
        .rating-scale strong { display: block; margin-bottom: 3px; font-size: 9.5px; }
        .rating-scale span { display: inline-block; min-width: 31%; padding: 1px 0; }

        .note { font-size: 8.5px; color: #475569; padding: 5px 0; margin-bottom: 8px; line-height: 1.4; }
        .note strong { color: #1a2332; }

        /* Section header — kept as the first row of <thead> so it can NEVER orphan
           and AUTOMATICALLY repeats on every page-slice when a table wraps. */
        .kpi-tbl .section-row {
            background: #1a2332; color: #fff;
            padding: 7px 10px; font-size: 10px; font-weight: bold;
            text-align: left; text-transform: none; letter-spacing: .3px;
            border: 1px solid #1a2332;
        }
        /* Kept for backwards-compat with anything that still uses a free-standing bar */
        .section-bar { background: #1a2332; color: #fff; padding: 5px 10px; font-weight: bold; font-size: 10px; margin: 10px 0 0; letter-spacing: .3px; page-break-after: avoid; }

        /* KPI table — repeats header on every page slice + avoids breaking inside a row */
        .kpi-tbl { width: 100%; border-collapse: collapse; margin-bottom: 4px; page-break-inside: auto; }
        .kpi-tbl thead { display: table-header-group; }  /* repeat <thead> across page breaks */
        .kpi-tbl tr    { page-break-inside: avoid; }     /* never split a single row */
        .kpi-tbl th { background: #e5e7eb; padding: 4px 5px; font-size: 8px; font-weight: bold; text-align: left; border: 1px solid #94a3b8; text-transform: uppercase; letter-spacing: .2px; }
        .kpi-tbl td { padding: 4px 5px; font-size: 8.5px; border: 1px solid #cbd5e1; vertical-align: top; }
        .kpi-tbl .col-sn { width: 4%; text-align: center; }
        .kpi-tbl .col-kpa { width: 14%; font-weight: bold; }
        .kpi-tbl .col-resp { width: 18%; }
        .kpi-tbl .col-measure { width: 18%; }
        .kpi-tbl .col-target { width: 18%; }
        .kpi-tbl .col-weight { width: 5%; text-align: center; font-weight: bold; }
        .kpi-tbl .col-rate { width: 5%; text-align: center; font-variant-numeric: tabular-nums; }
        .kpi-tbl .col-rate.self { color: #1d4ed8; font-weight: bold; }
        .kpi-tbl .col-rate.sup  { color: #92400e; font-weight: bold; }
        .kpi-tbl .col-rate.ovr  { color: #166534; font-weight: bold; }
        .kpi-tbl .col-cmt { width: 13%; }
        .kpi-tbl .total-row td { background: #1a2332; color: #fff; font-weight: bold; }

        /* Footer free-text sections */
        .footer-tbl { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .footer-tbl td { border: 1px solid #1a2332; padding: 6px 8px; vertical-align: top; }
        .footer-tbl .lbl { background: #f1f5f9; font-weight: bold; font-size: 8.5px; width: 26%; text-transform: uppercase; letter-spacing: .3px; }
        .footer-tbl .val { font-size: 9px; line-height: 1.45; min-height: 30px; white-space: pre-wrap; }
        .footer-tbl tr { page-break-inside: avoid; }  /* each free-text row stays on one page */

        /* Signature block — never split */
        .sig-block { margin-top: 14px; page-break-inside: avoid; }
        .sig-block .sig-title { font-size: 9.5px; font-weight: bold; color: #1a2332; text-transform: uppercase; letter-spacing: .4px; padding: 6px 8px; background: #1a2332; color: #fff; border-radius: 0; }
        .sig-grid { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .sig-grid td { width: 25%; border: 1px solid #cbd5e1; padding: 8px 10px; vertical-align: bottom; height: 86px; background: #fafbfc; }
        .sig-grid .role-label { font-size: 8px; font-weight: bold; color: #475569; text-transform: uppercase; letter-spacing: .4px; }
        .sig-grid .sig-img-wrap { height: 38px; text-align: center; margin-top: 4px; }
        .sig-grid .sig-img { max-height: 38px; max-width: 95%; }
        .sig-grid .sig-name { font-size: 9px; font-weight: bold; color: #1a2332; margin-top: 4px; padding-top: 3px; border-top: 1px solid #94a3b8; }
        .sig-grid .sig-date { font-size: 8px; color: #64748b; margin-top: 1px; }
        .sig-grid .sig-pending { font-size: 8px; color: #cbd5e1; font-style: italic; margin-top: 22px; }

        .grade-band { float: right; padding: 3px 11px; border-radius: 14px; font-size: 9.5px; font-weight: bold; }
        .grade-band.excellent { background: #dcfce7; color: #166534; }
        .grade-band.very-good { background: #d1fae5; color: #047857; }
        .grade-band.good      { background: #dbeafe; color: #1d4ed8; }
        .grade-band.average   { background: #fef9c3; color: #854d0e; }
        .grade-band.poor      { background: #fee2e2; color: #b91c1c; }
        .grade-band.ungraded  { background: #f1f5f9; color: #475569; }
    </style>
</head>
<body>

    {{-- Teal accent stripe --}}
    <div class="hdr-accent"></div>

    {{-- Company letterhead --}}
    <table class="letterhead">
        <tr>
            <td class="logo-td">
                @if(file_exists(public_path('media/logo/wajenzilogo.png')))
                    <img src="{{ public_path('media/logo/wajenzilogo.png') }}" alt="Wajenzi">
                @endif
            </td>
            <td class="co-td">
                <div class="co-name">WAJENZI PROFESSIONAL CO. LTD</div>
                <div class="co-addr">
                    PSSSF Commercial Complex, Sam Nujoma Road, Dar es Salaam, Tanzania<br>
                    P.O. Box 14492, Dar es Salaam &nbsp;|&nbsp; TIN: 154-867-805
                </div>
            </td>
            <td class="contact-td">
                <strong>+255 793 444 400</strong><br>
                hr@wajenziprofessional.co.tz<br>
                www.wajenziprofessional.co.tz
            </td>
        </tr>
    </table>

    {{-- Document title --}}
    <h1 class="doc-title">OPEN PERFORMANCE REVIEW FORM</h1>
    <div class="period">PERFORMANCE REVIEW FOR {{ strtoupper($review->period_label) }}</div>

    {{-- Header: employee/supervisor info --}}
    <table class="hdr-tbl">
        <tr>
            <td class="label">Name of Employee</td>
            <td class="value">{{ strtoupper($review->employee->name ?? '—') }}</td>
            <td class="label">Position / Role</td>
            <td class="value">{{ strtoupper($review->employee->designation ?? $review->template->role->name ?? '—') }}</td>
        </tr>
        <tr>
            <td class="label">Employment Date</td>
            <td class="value">{{ $review->employee->employment_date ? \Carbon\Carbon::parse($review->employee->employment_date)->format('d/m/Y') : '—' }}</td>
            <td class="label">Department</td>
            <td class="value">{{ $review->employee->department->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Name of Supervisor</td>
            <td class="value">{{ strtoupper($review->supervisor->name ?? '—') }}</td>
            <td class="label">Date of Review</td>
            <td class="value">{{ $review->updated_at->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Review Number</td>
            <td class="value">{{ $review->review_number }}</td>
            <td class="label">Status</td>
            <td class="value">{{ ucfirst(str_replace('_', ' ', $review->status)) }}</td>
        </tr>
    </table>

    {{-- Rating scale legend --}}
    <div class="rating-scale">
        <strong>Rating:</strong>
        <span>90% – 100% = Excellent performance</span>
        <span>80% – 89% = Very good performance</span>
        <span>70% – 79% = Good performance</span>
        <span>60% – 69% = Average performance</span>
        <span>50% – 59% = Poor performance</span>
        <span>49% and below = Ungraded performance</span>
    </div>

    <div class="note">
        <strong>Note:</strong> Employee marks are only for self-assessment.
        Review form format may change from time to time due to the nature of the performance required.
        Forms shall be filled and returned to HR within 2 working days.
        Non-return of forms within the required time may lead to poor performance.
    </div>

    {{-- KPI sections: Section A (General Performance) + Section B (Departmental Objectives)

         Section title is the FIRST row in <thead> so dompdf:
           (a) never orphans the dark bar at the bottom of a page, and
           (b) automatically repeats both bar + column headers on every page slice. --}}
    @foreach($groupedRatings as $code => $bundle)
        @php $section = $bundle['section']; $ratings = $bundle['ratings']; @endphp
        @if($ratings->isEmpty()) @continue @endif

        <table class="kpi-tbl">
            <thead>
                <tr>
                    <th colspan="9" class="section-row">
                        Section {{ $section->code }} — {{ strtoupper($section->title) }}
                        ({{ rtrim(rtrim(number_format($section->weight_total, 2), '0'), '.') }}%)
                    </th>
                </tr>
                <tr>
                    <th class="col-sn">S/N</th>
                    <th class="col-kpa">KPA</th>
                    <th class="col-measure">KPI / Measure</th>
                    <th class="col-target">Target</th>
                    <th class="col-weight">Wt %</th>
                    <th class="col-rate">Self %</th>
                    <th class="col-rate">Sup %</th>
                    <th class="col-rate">Overall %</th>
                    <th class="col-cmt">Comment</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ratings as $i => $rating)
                    <tr>
                        <td class="col-sn">{{ $i + 1 }}</td>
                        <td class="col-kpa">{{ $rating->kpa_snapshot }}</td>
                        <td class="col-measure">{{ $rating->measure_snapshot }}</td>
                        <td class="col-target">{{ $rating->target_snapshot ?? '—' }}</td>
                        <td class="col-weight">{{ rtrim(rtrim(number_format($rating->weight_snapshot, 2), '0'), '.') }}</td>
                        <td class="col-rate self">{{ $rating->self_rate !== null ? rtrim(rtrim(number_format($rating->self_rate, 1), '0'), '.') : '—' }}</td>
                        <td class="col-rate sup">{{ $rating->supervisor_rate !== null ? rtrim(rtrim(number_format($rating->supervisor_rate, 1), '0'), '.') : '—' }}</td>
                        <td class="col-rate ovr">{{ $rating->overall_rate !== null ? rtrim(rtrim(number_format($rating->overall_rate, 1), '0'), '.') : '—' }}</td>
                        <td class="col-cmt">{{ $rating->comment ?? '' }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="4" style="text-align:right;">Section {{ $section->code }} Total</td>
                    <td class="col-weight">{{ rtrim(rtrim(number_format($ratings->sum('weight_snapshot'), 2), '0'), '.') }}</td>
                    <td class="col-rate">—</td>
                    <td class="col-rate">—</td>
                    <td class="col-rate">—</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @endforeach

    {{-- Total / Grade row --}}
    @php
        $grade = $review->grade_label ?? 'Ungraded';
        $gradeClass = strtolower(str_replace(' ', '-', $grade));
    @endphp
    <table class="kpi-tbl" style="margin-top:6px;">
        <tr>
            <td class="col-sn"></td>
            <td colspan="3" style="text-align:right; font-weight:bold; background:#1a2332; color:#fff;">GRAND TOTAL (out of 100%)</td>
            <td class="col-weight" style="background:#1a2332; color:#fff;">100</td>
            <td class="col-rate self" style="background:#dbeafe;">
                {{ $review->total_self_score !== null ? rtrim(rtrim(number_format($review->total_self_score, 2), '0'), '.') : '—' }}
            </td>
            <td class="col-rate sup" style="background:#fef3c7;">
                {{ $review->total_supervisor_score !== null ? rtrim(rtrim(number_format($review->total_supervisor_score, 2), '0'), '.') : '—' }}
            </td>
            <td class="col-rate ovr" style="background:#dcfce7;">
                {{ $review->total_overall_score !== null ? rtrim(rtrim(number_format($review->total_overall_score, 2), '0'), '.') : '—' }}
            </td>
            <td style="background:#1a2332; color:#fff; text-align:center; font-weight:bold;">
                <span class="grade-band {{ $gradeClass }}">{{ $grade }}</span>
            </td>
        </tr>
    </table>

    {{-- Footer free-text sections --}}
    <table class="footer-tbl">
        <tr>
            <td class="lbl">Achievements during review period</td>
            <td class="val">{{ $review->achievements ?? '' }}</td>
        </tr>
        <tr>
            <td class="lbl">Areas of improvement</td>
            <td class="val">{{ $review->areas_of_improvement ?? '' }}</td>
        </tr>
        <tr>
            <td class="lbl">Training and development needs</td>
            <td class="val">{{ $review->training_needs ?? '' }}</td>
        </tr>
        <tr>
            <td class="lbl">Employee's comments</td>
            <td class="val">{{ $review->employee_comments ?? '' }}</td>
        </tr>
        <tr>
            <td class="lbl">Supervisor's comments</td>
            <td class="val">{{ $review->supervisor_comments ?? '' }}</td>
        </tr>
        @if($review->md_comments)
            <tr>
                <td class="lbl">Managing Director's comments</td>
                <td class="val">{{ $review->md_comments }}</td>
            </tr>
        @endif
        @if($review->ceo_comments)
            <tr>
                <td class="lbl">CEO's comments</td>
                <td class="val">{{ $review->ceo_comments }}</td>
            </tr>
        @endif
    </table>

    {{-- Signatures: pull each stage's actor + their stored signature image (User.file).
         A signature is rendered ONLY when that lifecycle stage has been completed —
         pending stages show "Awaiting" so unsigned reviews don't look misleading. --}}
    @php
        // Resolve each role's actor & signature
        $mdActor  = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'Managing Director'))->first();
        $ceoActor = \App\Models\User::whereHas('roles', fn($q) => $q->whereIn('name', ['CEO', 'Chief Executive Officer']))->first();

        $signers = [
            [
                'role'  => 'Employee',
                'user'  => $review->employee,
                'when'  => $review->self_submitted_at,
            ],
            [
                'role'  => 'Supervisor',
                'user'  => $review->supervisor,
                'when'  => $review->supervisor_reviewed_at,
            ],
            [
                'role'  => 'Managing Director',
                'user'  => $mdActor,
                'when'  => $review->md_reviewed_at,
            ],
            [
                'role'  => 'CEO',
                'user'  => $ceoActor,
                'when'  => $review->completed_at,
            ],
        ];
    @endphp

    <div class="sig-block">
        <div class="sig-title">Signatures</div>
        <table class="sig-grid">
            <tr>
                @foreach($signers as $s)
                    @php
                        $sigPath = optional($s['user'])->file;
                        // dompdf reads files from disk; convert /storage/uploads/... to absolute path
                        $sigAbs  = $sigPath ? public_path(ltrim($sigPath, '/')) : null;
                        $hasSig  = $s['when'] && $sigAbs && file_exists($sigAbs);
                    @endphp
                    <td>
                        <div class="role-label">{{ $s['role'] }}</div>
                        <div class="sig-img-wrap">
                            @if($hasSig)
                                <img src="{{ $sigAbs }}" class="sig-img" alt="signature">
                            @elseif($s['when'])
                                {{-- Stage was approved but no signature uploaded yet — keep the role/name/date but skip image --}}
                                <span style="font-size:8px; color:#16a34a; font-weight:bold;">✓ Approved</span>
                            @else
                                <div class="sig-pending">Awaiting…</div>
                            @endif
                        </div>
                        <div class="sig-name">{{ $s['user'] ? strtoupper($s['user']->name) : '—' }}</div>
                        <div class="sig-date">{{ $s['when'] ? $s['when']->format('d M Y') : '—' }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    </div>

</body>
</html>

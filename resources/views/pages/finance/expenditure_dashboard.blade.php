{{-- expenditure_dashboard.blade.php — Daily/Weekly/Monthly per-site expenditures --}}
@extends('layouts.backend')

@section('css_after')
<style>
.exp-tab-bar { display: flex; gap: 6px; background: #fff; padding: 4px; border-radius: 10px;
    border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,.04); margin-bottom: 16px; width: max-content; }
.exp-tab-btn { padding: 7px 16px; font-size: 13px; font-weight: 600; color: #64748b;
    border-radius: 7px; text-decoration: none; transition: .15s; }
.exp-tab-btn:hover { background: #f1f5f9; color: #1e293b; text-decoration: none; }
.exp-tab-btn.active { background: #2563eb; color: #fff; box-shadow: 0 2px 6px rgba(37,99,235,.3); }
.exp-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 18px; }
.exp-card { background: #fff; border-radius: 10px; padding: 14px 18px; border: 1px solid #e8edf3;
    box-shadow: 0 1px 3px rgba(0,0,0,.05); position: relative; overflow: hidden; }
.exp-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background: var(--c, #2563eb); }
.exp-card-label { font-size: 11px; text-transform: uppercase; letter-spacing: .8px; font-weight: 700; color: #94a3b8; }
.exp-card-value { font-size: 22px; font-weight: 800; color: #1e293b; margin-top: 6px; }
.exp-card-pct { font-size: 11px; color: #64748b; margin-top: 4px; }
.exp-section-head { font-size: 13px; font-weight: 700; color: #1e293b; padding: 12px 16px; border-bottom: 1px solid #e8edf3; }
.exp-table { width: 100%; }
.exp-table th { background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase;
    letter-spacing: .6px; padding: 10px 14px; border-bottom: 1px solid #e2e8f0; }
.exp-table td { padding: 9px 14px; font-size: 13px; border-bottom: 1px solid #f1f5f9; }
.exp-table tr:hover td { background: #fafbfc; }
.exp-num { text-align: right; font-variant-numeric: tabular-nums; }
.exp-tot-row td { background: #f1f5f9; font-weight: 700; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">Expenditure Dashboard
            <small style="font-size:12px;color:#64748b;font-weight:500;display:block;margin-top:4px;">
                Material · Labour · Overhead — per site & all sites
            </small>
        </div>

        {{-- Granularity tabs --}}
        <div class="exp-tab-bar">
            @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $g => $lbl)
                <a class="exp-tab-btn {{ $granularity === $g ? 'active' : '' }}"
                   href="{{ route('finance.expenditure_dashboard', array_merge(request()->except('page'), ['granularity' => $g])) }}">
                    {{ $lbl }}
                </a>
            @endforeach
        </div>

        {{-- Filters --}}
        <div class="block">
            <div class="block-content">
                <form method="GET" action="{{ route('finance.expenditure_dashboard') }}" class="row align-items-end">
                    <input type="hidden" name="granularity" value="{{ $granularity }}">
                    <div class="col-md-3">
                        <label class="font-w600" style="font-size:12px;">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="font-w600" style="font-size:12px;">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-4">
                        <label class="font-w600" style="font-size:12px;">Project (Site)</label>
                        <select name="project_id" class="form-control">
                            <option value="">All Sites</option>
                            @foreach($projects as $p)
                                <option value="{{ $p->id }}" {{ (string)$projectId === (string)$p->id ? 'selected' : '' }}>
                                    {{ $p->project_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fa fa-filter mr-1"></i> Apply
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Summary cards --}}
        @php
            $colors = ['Material' => '#2563eb', 'Labour Charge' => '#7c3aed', 'Overhead Expense' => '#f59e0b'];
        @endphp
        <div class="exp-summary">
            @foreach($categories as $cat)
                @php
                    $val = $categoryTotals[$cat] ?? 0;
                    $pct = $grandTotal > 0 ? round(($val / $grandTotal) * 100, 1) : 0;
                @endphp
                <div class="exp-card" style="--c:{{ $colors[$cat] ?? '#2563eb' }};">
                    <div class="exp-card-label">{{ $cat }}</div>
                    <div class="exp-card-value">TZS {{ number_format($val, 0) }}</div>
                    <div class="exp-card-pct">{{ $pct }}% of total</div>
                </div>
            @endforeach
            <div class="exp-card" style="--c:#16a34a;">
                <div class="exp-card-label">Grand Total</div>
                <div class="exp-card-value">TZS {{ number_format($grandTotal, 0) }}</div>
                <div class="exp-card-pct">
                    {{ $startDate }} → {{ $endDate }}
                    @if($projectId)
                        @php $proj = $projects->firstWhere('id', $projectId); @endphp
                        · {{ $proj?->project_name }}
                    @else
                        · all sites
                    @endif
                </div>
            </div>
        </div>

        {{-- Period (time-bucket) table --}}
        <div class="block">
            <div class="exp-section-head">
                <i class="fa fa-chart-line mr-1" style="color:#2563eb;"></i>
                {{ ucfirst($granularity) }} breakdown
            </div>
            <div class="block-content p-0">
                <table class="exp-table">
                    <thead>
                        <tr>
                            <th style="width:160px;">Period</th>
                            <th class="exp-num">Material</th>
                            <th class="exp-num">Labour</th>
                            <th class="exp-num">Overhead</th>
                            <th class="exp-num">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($series['rows'] as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td class="exp-num">{{ number_format($row['material'], 0) }}</td>
                                <td class="exp-num">{{ number_format($row['labour'], 0) }}</td>
                                <td class="exp-num">{{ number_format($row['overhead'], 0) }}</td>
                                <td class="exp-num"><strong>{{ number_format($row['total'], 0) }}</strong></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted" style="padding:24px;">No expenses in this window.</td></tr>
                        @endforelse
                    </tbody>
                    @if(count($series['rows']))
                    <tfoot>
                        <tr class="exp-tot-row">
                            <td>Total</td>
                            <td class="exp-num">{{ number_format($categoryTotals['Material'] ?? 0, 0) }}</td>
                            <td class="exp-num">{{ number_format($categoryTotals['Labour Charge'] ?? 0, 0) }}</td>
                            <td class="exp-num">{{ number_format($categoryTotals['Overhead Expense'] ?? 0, 0) }}</td>
                            <td class="exp-num">{{ number_format($grandTotal, 0) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- Per-site breakdown --}}
        <div class="block">
            <div class="exp-section-head">
                <i class="fa fa-building mr-1" style="color:#7c3aed;"></i>
                Per-site breakdown
                <small class="text-muted ml-2">{{ $perSite->count() }} {{ \Illuminate\Support\Str::plural('site', $perSite->count()) }}</small>
            </div>
            <div class="block-content p-0">
                <table class="exp-table">
                    <thead>
                        <tr>
                            <th>Site</th>
                            <th class="exp-num">Material</th>
                            <th class="exp-num">Labour</th>
                            <th class="exp-num">Overhead</th>
                            <th class="exp-num">Total</th>
                            <th style="width: 70px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($perSite as $row)
                            <tr>
                                <td>
                                    <strong>{{ $row['project_name'] }}</strong>
                                    @if($row['document_no'])
                                        <small class="text-muted d-block">{{ $row['document_no'] }}</small>
                                    @endif
                                </td>
                                <td class="exp-num">{{ number_format($row['material'], 0) }}</td>
                                <td class="exp-num">{{ number_format($row['labour'], 0) }}</td>
                                <td class="exp-num">{{ number_format($row['overhead'], 0) }}</td>
                                <td class="exp-num"><strong>{{ number_format($row['total'], 0) }}</strong></td>
                                <td>
                                    <a href="{{ route('finance.expenditure_dashboard', array_merge(request()->except('page'), ['project_id' => $row['project_id']])) }}"
                                       class="btn btn-sm btn-light border" title="Drill into this site">
                                        <i class="fa fa-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted" style="padding:24px;">No expenses for any site in this window.</td></tr>
                        @endforelse
                    </tbody>
                    @if($perSite->count())
                    <tfoot>
                        <tr class="exp-tot-row">
                            <td>All sites</td>
                            <td class="exp-num">{{ number_format($categoryTotals['Material'] ?? 0, 0) }}</td>
                            <td class="exp-num">{{ number_format($categoryTotals['Labour Charge'] ?? 0, 0) }}</td>
                            <td class="exp-num">{{ number_format($categoryTotals['Overhead Expense'] ?? 0, 0) }}</td>
                            <td class="exp-num">{{ number_format($grandTotal, 0) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

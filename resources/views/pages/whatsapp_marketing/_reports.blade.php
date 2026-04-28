@php
    $fmt = fn($n) => 'TZS ' . number_format((float)$n, 0);
    $pct = fn($n, $d) => $d > 0 ? round(($n / $d) * 100, 1) : 0;
@endphp

{{-- ── ROI Summary Cards ─────────────────────────────────────────────────── --}}
<div class="row mb-4">
    @php
        $cards = [
            ['label' => 'Total Leads',       'value' => number_format($totalContacts),  'icon' => 'fa-users',          'color' => 'primary',  'sub' => 'contacts collected'],
            ['label' => 'Converted',         'value' => number_format($converted),       'icon' => 'fa-user-check',     'color' => 'success',  'sub' => $convRate . '% conversion rate'],
            ['label' => 'Total Revenue',     'value' => $fmt($totalRevenue),             'icon' => 'fa-money-bill-wave','color' => 'success',  'sub' => 'from deal values'],
            ['label' => 'Total Ad Spend',    'value' => $fmt($totalAdSpend),             'icon' => 'fa-bullhorn',       'color' => 'warning',  'sub' => 'campaign budgets'],
            ['label' => 'Net Profit',        'value' => $fmt($netProfit),                'icon' => 'fa-chart-line',     'color' => $netProfit >= 0 ? 'success' : 'danger', 'sub' => 'revenue − spend'],
            ['label' => 'ROI',               'value' => $roi !== null ? $roi . '%' : '—','icon' => 'fa-percentage',    'color' => ($roi !== null && $roi >= 0) ? 'success' : 'danger', 'sub' => 'return on ad spend'],
        ];
    @endphp
    @foreach($cards as $card)
    <div class="col-6 col-md-4 col-xl-2 mb-3">
        <div class="block block-rounded mb-0 h-100" style="border-top: 3px solid var(--bs-{{ $card['color'] }}, #0d6efd)">
            <div class="block-content py-3 px-3">
                <div class="d-flex align-items-center mb-1">
                    <i class="fa {{ $card['icon'] }} fa-lg text-{{ $card['color'] }} mr-2" style="opacity:.7"></i>
                    <span class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.4px">{{ $card['label'] }}</span>
                </div>
                <div class="font-w700" style="font-size:18px;line-height:1.2" class="text-{{ $card['color'] }}">
                    {{ $card['value'] }}
                </div>
                <div class="text-muted" style="font-size:11px">{{ $card['sub'] }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Campaign Performance ──────────────────────────────────────────────── --}}
<div class="block block-rounded mb-4">
    <div class="block-header block-header-default">
        <h3 class="block-title"><i class="fa fa-bullhorn mr-2 text-warning"></i>Campaign ROI Breakdown</h3>
    </div>
    <div class="block-content block-content-full p-0">
        <div class="table-responsive">
            <table class="table table-hover table-vcenter table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Campaign</th>
                        <th>Status</th>
                        <th class="text-right">Budget</th>
                        <th class="text-center">Leads</th>
                        <th class="text-center">Converted</th>
                        <th class="text-center">Conv%</th>
                        <th class="text-right">Cost / Lead</th>
                        <th class="text-right">Cost / Conv</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">ROI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportCampaigns as $camp)
                    @php
                        $cLeads   = $camp->contacts_count;
                        $cConv    = $camp->converted_count;
                        $cBudget  = (float)($camp->budget ?? 0);
                        $cRev     = (float)($camp->revenue ?? 0);
                        $cProfit  = $cRev - $cBudget;
                        $cRoi     = $cBudget > 0 ? round(($cProfit / $cBudget) * 100, 1) : null;
                        $cCpl     = $cLeads > 0 && $cBudget > 0 ? number_format($cBudget / $cLeads, 0) : '—';
                        $cCpc     = $cConv > 0 && $cBudget > 0 ? number_format($cBudget / $cConv, 0) : '—';
                    @endphp
                    <tr class="{{ $camp->status === 'closed' ? 'text-muted' : '' }}">
                        <td class="font-w600">{{ $camp->name }}</td>
                        <td>
                            @if($camp->status === 'closed')
                                <span class="badge badge-secondary" style="border-radius:20px">Closed</span>
                            @else
                                <span class="badge badge-success" style="border-radius:20px">Active</span>
                            @endif
                        </td>
                        <td class="text-right">{{ $cBudget > 0 ? 'TZS ' . number_format($cBudget, 0) : '—' }}</td>
                        <td class="text-center"><span class="badge badge-primary px-2">{{ $cLeads }}</span></td>
                        <td class="text-center"><span class="badge badge-success px-2">{{ $cConv }}</span></td>
                        <td class="text-center">
                            @if($cLeads > 0)
                                <span class="badge badge-{{ $pct($cConv, $cLeads) >= 10 ? 'success' : 'warning' }} px-2">
                                    {{ $pct($cConv, $cLeads) }}%
                                </span>
                            @else —
                            @endif
                        </td>
                        <td class="text-right small">{{ $cCpl !== '—' ? 'TZS ' . $cCpl : '—' }}</td>
                        <td class="text-right small">{{ $cCpc !== '—' ? 'TZS ' . $cCpc : '—' }}</td>
                        <td class="text-right small text-success">{{ $cRev > 0 ? 'TZS ' . number_format($cRev, 0) : '—' }}</td>
                        <td class="text-right font-w600">
                            @if($cRoi !== null)
                                <span class="{{ $cRoi >= 0 ? 'text-success' : 'text-danger' }}">{{ $cRoi }}%</span>
                            @else —
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No campaigns yet.</td></tr>
                    @endforelse
                </tbody>
                @if($reportCampaigns->count() > 1)
                <tfoot class="thead-light font-w700">
                    @php
                        $tBudget = $reportCampaigns->sum('budget');
                        $tLeads  = $reportCampaigns->sum('contacts_count');
                        $tConv   = $reportCampaigns->sum('converted_count');
                        $tRev    = $reportCampaigns->sum('revenue');
                        $tProfit = $tRev - $tBudget;
                        $tRoi    = $tBudget > 0 ? round(($tProfit / $tBudget) * 100, 1) : null;
                    @endphp
                    <tr>
                        <td colspan="2">TOTAL</td>
                        <td class="text-right">TZS {{ number_format($tBudget, 0) }}</td>
                        <td class="text-center">{{ $tLeads }}</td>
                        <td class="text-center">{{ $tConv }}</td>
                        <td class="text-center">{{ $pct($tConv, $tLeads) }}%</td>
                        <td class="text-right">{{ $tLeads > 0 && $tBudget > 0 ? 'TZS ' . number_format($tBudget / $tLeads, 0) : '—' }}</td>
                        <td class="text-right">{{ $tConv > 0 && $tBudget > 0 ? 'TZS ' . number_format($tBudget / $tConv, 0) : '—' }}</td>
                        <td class="text-right text-success">TZS {{ number_format($tRev, 0) }}</td>
                        <td class="text-right {{ $tRoi !== null && $tRoi >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $tRoi !== null ? $tRoi . '%' : '—' }}
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<div class="row mb-4">
    {{-- ── Stage Funnel ──────────────────────────────────────────────────────── --}}
    <div class="col-md-6 mb-3">
        <div class="block block-rounded h-100 mb-0">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-filter mr-2 text-primary"></i>Stage Funnel</h3>
            </div>
            <div class="block-content">
                @php $stageTotal = $stageFunnel->sum(); @endphp
                @foreach(\App\Models\WhatsAppContact::STAGES as $key => $meta)
                @php $cnt = $stageFunnel[$key] ?? 0; $width = $stageTotal > 0 ? round(($cnt / $stageTotal) * 100) : 0; @endphp
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:12px">{{ $meta['label'] }}</span>
                        <span class="font-w600" style="font-size:12px">{{ $cnt }} <span class="text-muted">({{ $width }}%)</span></span>
                    </div>
                    <div class="progress" style="height:8px;border-radius:4px">
                        <div class="progress-bar bg-{{ explode('-', $meta['color'])[1] ?? 'primary' }}"
                             style="width:{{ $width }}%;border-radius:4px"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Source Breakdown ─────────────────────────────────────────────────── --}}
    <div class="col-md-6 mb-3">
        <div class="block block-rounded h-100 mb-0">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-sitemap mr-2 text-info"></i>Source Performance</h3>
            </div>
            <div class="block-content p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Source</th>
                            <th class="text-center">Leads</th>
                            <th class="text-center">Converted</th>
                            <th class="text-center">Conv%</th>
                            <th class="text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sourceBreakdown as $row)
                        <tr>
                            <td>{{ \App\Models\WhatsAppContact::SOURCES[$row->source] ?? ucfirst($row->source) }}</td>
                            <td class="text-center">{{ $row->total }}</td>
                            <td class="text-center">{{ (int)$row->converted_count }}</td>
                            <td class="text-center">
                                <span class="badge badge-{{ $pct($row->converted_count, $row->total) >= 10 ? 'success' : 'secondary' }}" style="border-radius:20px">
                                    {{ $pct($row->converted_count, $row->total) }}%
                                </span>
                            </td>
                            <td class="text-right small text-success">
                                {{ $row->revenue > 0 ? 'TZS ' . number_format($row->revenue, 0) : '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    {{-- ── Monthly Trend ────────────────────────────────────────────────────── --}}
    <div class="col-md-6 mb-3">
        <div class="block block-rounded h-100 mb-0">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-calendar mr-2 text-success"></i>Monthly Trend (Last 6 Months)</h3>
            </div>
            <div class="block-content p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Month</th>
                            <th class="text-center">New Leads</th>
                            <th class="text-center">Converted</th>
                            <th class="text-center">Conv%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($monthlyTrend as $row)
                        <tr>
                            <td>{{ \Carbon\Carbon::createFromFormat('Y-m', $row->month)->format('M Y') }}</td>
                            <td class="text-center font-w600">{{ $row->total }}</td>
                            <td class="text-center text-success">{{ (int)$row->converted_count }}</td>
                            <td class="text-center">{{ $pct($row->converted_count, $row->total) }}%</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No data yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Services Demand + Call Activity ──────────────────────────────────── --}}
    <div class="col-md-6 mb-3">
        <div class="block block-rounded mb-3">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-tags mr-2 text-purple"></i>Top Services in Demand</h3>
            </div>
            <div class="block-content p-0">
                @if($servicesDemand->isEmpty())
                    <p class="text-muted text-center py-3 small">No service data yet.</p>
                @else
                @php $maxSvc = $servicesDemand->max('cnt'); @endphp
                <div class="px-3 py-2">
                    @foreach($servicesDemand as $svc)
                    @php $w = $maxSvc > 0 ? round(($svc->cnt / $maxSvc) * 100) : 0; @endphp
                    <div class="d-flex align-items-center mb-2" style="gap:8px">
                        <span style="font-size:12px;min-width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="{{ $svc->name }}">{{ $svc->name }}</span>
                        <div class="flex-fill progress" style="height:6px;border-radius:3px">
                            <div class="progress-bar bg-info" style="width:{{ $w }}%;border-radius:3px"></div>
                        </div>
                        <span class="font-w600 small" style="min-width:24px;text-align:right">{{ $svc->cnt }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        <div class="block block-rounded mb-0">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-phone mr-2 text-info"></i>Call Activity</h3>
            </div>
            <div class="block-content py-2">
                <div class="d-flex align-items-center mb-2">
                    <span class="text-muted mr-2" style="font-size:12px">Total Calls Logged</span>
                    <span class="badge badge-info px-2">{{ $totalCalls }}</span>
                </div>
                @foreach(\App\Models\WhatsAppContactCall::OUTCOMES as $key => $meta)
                @php $cnt = $callOutcomes[$key] ?? 0; @endphp
                <div class="d-flex justify-content-between align-items-center py-1" style="border-bottom:1px solid #f0f0f0">
                    <span class="small">{{ $meta['label'] }}</span>
                    <span class="badge badge-{{ $meta['color'] }} px-2">{{ $cnt }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@if($totalRevenue == 0 && $totalContacts > 0)
<div class="alert alert-info alert-dismissible fade show">
    <i class="fa fa-info-circle mr-1"></i>
    <strong>Tip:</strong> Add a <strong>Deal Value (TZS)</strong> to converted contacts to enable revenue, profit, and ROI tracking.
    <button type="button" class="close" data-dismiss="alert">&times;</button>
</div>
@endif

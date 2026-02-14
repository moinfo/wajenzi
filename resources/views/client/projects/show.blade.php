@extends('layouts.client')

@section('title', $project->project_name)

@section('content')
    <!-- Project Header -->
    <div style="margin-bottom: var(--m-md);">
        <a href="{{ route('client.dashboard') }}" class="m-dimmed m-text-xs" style="text-decoration: none;">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: var(--m-sm); flex-wrap: wrap; gap: 0.5rem;">
            <div>
                <h1 class="m-title m-title-3" style="margin: 0;">{{ $project->project_name }}</h1>
                <span class="m-text-sm m-dimmed">{{ $project->document_number }}</span>
            </div>
            @php
                $badgeMap = ['APPROVED' => 'teal', 'PENDING' => 'yellow', 'REJECTED' => 'red', 'COMPLETED' => 'blue'];
            @endphp
            <span class="m-badge m-badge-{{ $badgeMap[$project->status] ?? 'gray' }}">
                {{ $project->status ?? 'N/A' }}
            </span>
        </div>
    </div>

    @include('client.partials.project_tabs')

    <!-- Work Progress Section -->
    @if($schedule && $progress && $progress['total'] > 0)
        <div class="row g-3 mb-3">
            <!-- Overall Progress -->
            <div class="col-lg-4">
                <div class="m-paper" style="height: 100%;">
                    <div class="m-paper-header">
                        <h5><i class="fas fa-chart-pie me-2" style="color: var(--m-blue-6);"></i>Overall Progress</h5>
                    </div>
                    <div class="m-paper-body" style="text-align: center; padding: 1.5rem;">
                        <!-- Circular Progress -->
                        <div style="position: relative; width: 140px; height: 140px; margin: 0 auto 1rem;">
                            <canvas id="overallProgressChart" width="140" height="140"></canvas>
                            <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                                <span style="font-size: 1.75rem; font-weight: 700; color: var(--m-blue-6);">{{ $progress['percentage'] }}%</span>
                                <span class="m-text-xs m-dimmed">Complete</span>
                            </div>
                        </div>
                        <!-- Status Breakdown -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; text-align: left;">
                            <div style="display: flex; align-items: center; gap: 0.375rem;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--m-teal-6); flex-shrink: 0;"></span>
                                <span class="m-text-xs">Completed: <strong>{{ $progress['completed'] }}</strong></span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.375rem;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--m-blue-6); flex-shrink: 0;"></span>
                                <span class="m-text-xs">In Progress: <strong>{{ $progress['in_progress'] }}</strong></span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.375rem;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--m-gray-4); flex-shrink: 0;"></span>
                                <span class="m-text-xs">Pending: <strong>{{ $progress['pending'] }}</strong></span>
                            </div>
                            @if($progress['overdue'] > 0)
                                <div style="display: flex; align-items: center; gap: 0.375rem;">
                                    <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--m-red-6); flex-shrink: 0;"></span>
                                    <span class="m-text-xs">Overdue: <strong>{{ $progress['overdue'] }}</strong></span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress by Phase -->
            <div class="col-lg-8">
                <div class="m-paper" style="height: 100%;">
                    <div class="m-paper-header">
                        <h5><i class="fas fa-chart-bar me-2" style="color: var(--m-teal-6);"></i>Progress by Phase</h5>
                    </div>
                    <div class="m-paper-body">
                        @if(count($progressByPhase) > 0)
                            <canvas id="phaseProgressChart" height="{{ max(count($progressByPhase) * 35, 120) }}"></canvas>
                            <!-- Phase details table -->
                            <div class="table-responsive" style="margin-top: 1rem;">
                                <table class="m-table">
                                    <thead>
                                        <tr>
                                            <th>Phase</th>
                                            <th style="text-align: center;">Completed</th>
                                            <th style="text-align: center;">Total</th>
                                            <th style="text-align: right;">Progress</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($progressByPhase as $phaseName => $phaseData)
                                            <tr>
                                                <td class="m-fw-500">{{ $phaseName }}</td>
                                                <td style="text-align: center;">{{ $phaseData['completed'] }}</td>
                                                <td style="text-align: center;">{{ $phaseData['total'] }}</td>
                                                <td style="text-align: right;">
                                                    <span class="m-badge {{ $phaseData['percentage'] == 100 ? 'm-badge-teal' : ($phaseData['percentage'] > 0 ? 'm-badge-blue' : 'm-badge-gray') }}">
                                                        {{ $phaseData['percentage'] }}%
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div style="text-align: center; padding: 2rem;">
                                <p class="m-text-sm m-dimmed">No phase data available.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Overview Content -->
    <div class="row g-3">
        <!-- Project Details -->
        <div class="col-lg-8">
            <div class="m-paper mb-3">
                <div class="m-paper-header">
                    <h5><i class="fas fa-info-circle me-2" style="color: var(--m-blue-6);"></i>Project Details</h5>
                </div>
                <div class="m-paper-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="m-dimmed d-block m-text-xs" style="text-transform: uppercase; letter-spacing: 0.05em;">Project Type</label>
                                <span class="m-fw-500">{{ $project->projectType->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="m-dimmed d-block m-text-xs" style="text-transform: uppercase; letter-spacing: 0.05em;">Service Type</label>
                                <span class="m-fw-500">{{ $project->serviceType->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="m-dimmed d-block m-text-xs" style="text-transform: uppercase; letter-spacing: 0.05em;">Project Manager</label>
                                <span class="m-fw-500">{{ $project->projectManager->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="m-dimmed d-block m-text-xs" style="text-transform: uppercase; letter-spacing: 0.05em;">Priority</label>
                                <span class="m-fw-500">{{ ucfirst($project->priority ?? 'N/A') }}</span>
                            </div>
                        </div>
                        @if($project->description)
                            <div class="col-12">
                                <label class="m-dimmed d-block m-text-xs" style="text-transform: uppercase; letter-spacing: 0.05em;">Description</label>
                                <span class="m-text-sm">{{ $project->description }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Construction Phases -->
            @if($project->constructionPhases->count())
                <div class="m-paper">
                    <div class="m-paper-header">
                        <h5><i class="fas fa-tasks me-2" style="color: var(--m-teal-6);"></i>Construction Phases</h5>
                    </div>
                    <div style="padding: 0;">
                        <div class="table-responsive">
                            <table class="m-table">
                                <thead>
                                    <tr>
                                        <th>Phase</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($project->constructionPhases as $phase)
                                        <tr>
                                            <td class="m-fw-500">{{ $phase->phase_name }}</td>
                                            <td>{{ $phase->start_date?->format('M d, Y') ?? '-' }}</td>
                                            <td>{{ $phase->end_date?->format('M d, Y') ?? '-' }}</td>
                                            <td>
                                                @php
                                                    $phaseMap = ['pending' => 'yellow', 'in_progress' => 'blue', 'completed' => 'teal'];
                                                @endphp
                                                <span class="m-badge m-badge-{{ $phaseMap[$phase->status] ?? 'gray' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $phase->status ?? 'N/A')) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Timeline -->
            <div class="m-paper mb-3">
                <div class="m-paper-header">
                    <h5><i class="fas fa-calendar me-2" style="color: var(--m-orange-6);"></i>Timeline</h5>
                </div>
                <div class="m-paper-body">
                    <div class="mb-3">
                        <label class="m-dimmed d-block m-text-xs" style="text-transform: uppercase; letter-spacing: 0.05em;">Start Date</label>
                        <span class="m-fw-500">{{ $project->start_date?->format('M d, Y') ?? 'Not set' }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="m-dimmed d-block m-text-xs" style="text-transform: uppercase; letter-spacing: 0.05em;">Expected End</label>
                        <span class="m-fw-500">{{ $project->expected_end_date?->format('M d, Y') ?? 'Not set' }}</span>
                    </div>
                    @if($project->planned_duration)
                        <div class="mb-3">
                            <label class="m-dimmed d-block m-text-xs" style="text-transform: uppercase; letter-spacing: 0.05em;">Planned Duration</label>
                            <span class="m-fw-500">{{ $project->planned_duration }} days</span>
                        </div>
                    @endif
                    @if($project->delay !== null && $project->delay > 0)
                        <div style="background: #fff5f5; border: 1px solid #ffc9c9; border-radius: var(--m-radius-sm); padding: 0.5rem 0.75rem; font-size: 0.875rem; color: var(--m-red-6);">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            {{ $project->delay }} days behind schedule
                        </div>
                    @endif
                </div>
            </div>

            <!-- Contract Value -->
            <div class="m-paper">
                <div class="m-paper-header">
                    <h5><i class="fas fa-money-bill-wave me-2" style="color: var(--m-green-6);"></i>Contract Value</h5>
                </div>
                <div class="m-paper-body" style="text-align: center;">
                    <div style="font-size: 1.75rem; font-weight: 700; color: var(--m-blue-6);">
                        TZS {{ number_format($project->contract_value ?? 0, 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
@if($schedule && $progress && $progress['total'] > 0)
<script src="{{ asset('js/plugins/chartjs/Chart.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Detect dark mode
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var textColor = isDark ? '#C1C2C5' : '#495057';
    var gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.08)';

    // Overall Progress Doughnut
    var overallCtx = document.getElementById('overallProgressChart');
    if (overallCtx) {
        new Chart(overallCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [{{ $progress['percentage'] }}, {{ 100 - $progress['percentage'] }}],
                    backgroundColor: ['#228be6', isDark ? '#373A40' : '#e9ecef'],
                    borderWidth: 0
                }]
            },
            options: {
                cutoutPercentage: 75,
                responsive: false,
                legend: { display: false },
                tooltips: { enabled: false },
                animation: { animateRotate: true }
            }
        });
    }

    // Phase Progress Horizontal Bar
    var phaseCtx = document.getElementById('phaseProgressChart');
    if (phaseCtx) {
        var phaseData = @json($progressByPhase);
        var labels = Object.keys(phaseData);
        var percentages = labels.map(function(k) { return phaseData[k].percentage; });

        new Chart(phaseCtx.getContext('2d'), {
            type: 'horizontalBar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Completion %',
                    data: percentages,
                    backgroundColor: percentages.map(function(p) {
                        return p === 100 ? '#12b886' : (p > 0 ? '#228be6' : (isDark ? '#5C5F66' : '#ced4da'));
                    }),
                    borderRadius: 4,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                    xAxes: [{
                        ticks: { min: 0, max: 100, fontColor: textColor, callback: function(v) { return v + '%'; } },
                        gridLines: { color: gridColor }
                    }],
                    yAxes: [{
                        ticks: { fontColor: textColor },
                        gridLines: { display: false }
                    }]
                },
                tooltips: {
                    callbacks: {
                        label: function(item) {
                            var phase = phaseData[item.yLabel];
                            return item.xLabel + '% (' + phase.completed + '/' + phase.total + ' activities)';
                        }
                    }
                }
            }
        });
    }
});
</script>
@endif
@endsection

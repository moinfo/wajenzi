@extends('layouts.client')

@section('title', 'Reports - ' . $project->project_name)

@section('content')
    <div style="margin-bottom: var(--m-md);">
        <a href="{{ route('client.dashboard') }}" class="m-dimmed m-text-xs" style="text-decoration: none;">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
        <h1 class="m-title m-title-3" style="margin-top: var(--m-sm); margin-bottom: 0;">{{ $project->project_name }}</h1>
    </div>

    @include('client.partials.project_tabs')

    <div class="row g-3">
        <!-- Daily Reports -->
        <div class="col-lg-7">
            <div class="m-paper">
                <div class="m-paper-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h5 style="margin: 0;"><i class="fas fa-clipboard-list me-2" style="color: var(--m-blue-6);"></i>Daily Reports</h5>
                    <span class="m-badge m-badge-blue">{{ $dailyReports->count() }}</span>
                </div>
                @if($dailyReports->count())
                    <div style="padding: 0;">
                        <div class="accordion" id="dailyReportsAccordion">
                            @foreach($dailyReports as $report)
                                <div class="accordion-item" style="border: none; border-bottom: 1px solid var(--m-gray-3);">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#report-{{ $report->id }}"
                                                style="padding: 0.75rem var(--m-lg); font-size: 0.875rem;">
                                            <div style="display: flex; align-items: center; gap: 0.5rem; width: 100%;">
                                                <i class="fas fa-calendar-day" style="color: var(--m-blue-6);"></i>
                                                <strong>{{ $report->report_date?->format('M d, Y') ?? 'N/A' }}</strong>
                                                @if($report->weather_conditions)
                                                    <span class="m-badge m-badge-gray" style="margin-left: auto; margin-right: 1rem;">
                                                        <i class="fas fa-cloud-sun" style="margin-right: 0.25rem;"></i>{{ $report->weather_conditions }}
                                                    </span>
                                                @endif
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="report-{{ $report->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#dailyReportsAccordion">
                                        <div class="accordion-body" style="padding: 0.75rem var(--m-lg) var(--m-lg);">
                                            @if($report->supervisor)
                                                <div style="font-size: 0.8125rem; color: var(--m-gray-6); margin-bottom: 0.5rem;">
                                                    <i class="fas fa-user me-1"></i> Supervisor: <strong>{{ $report->supervisor->name }}</strong>
                                                </div>
                                            @endif
                                            @if($report->work_completed)
                                                <div style="margin-bottom: 0.5rem;">
                                                    <label class="m-fw-600 d-block m-text-xs m-dimmed" style="text-transform: uppercase; letter-spacing: 0.05em;">Work Completed</label>
                                                    <p class="m-text-sm" style="margin: 0.125rem 0 0;">{{ $report->work_completed }}</p>
                                                </div>
                                            @endif
                                            @if($report->materials_used)
                                                <div style="margin-bottom: 0.5rem;">
                                                    <label class="m-fw-600 d-block m-text-xs m-dimmed" style="text-transform: uppercase; letter-spacing: 0.05em;">Materials Used</label>
                                                    <p class="m-text-sm" style="margin: 0.125rem 0 0;">{{ $report->materials_used }}</p>
                                                </div>
                                            @endif
                                            @if($report->labor_hours)
                                                <div style="margin-bottom: 0.5rem;">
                                                    <label class="m-fw-600 d-block m-text-xs m-dimmed" style="text-transform: uppercase; letter-spacing: 0.05em;">Labor Hours</label>
                                                    <p class="m-text-sm" style="margin: 0.125rem 0 0;">{{ $report->labor_hours }} hours</p>
                                                </div>
                                            @endif
                                            @if($report->issues_faced)
                                                <div>
                                                    <label class="m-fw-600 d-block m-text-xs" style="color: var(--m-red-6); text-transform: uppercase; letter-spacing: 0.05em;">Issues Faced</label>
                                                    <p class="m-text-sm" style="margin: 0.125rem 0 0;">{{ $report->issues_faced }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="m-paper-body" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-clipboard-list" style="font-size: 2rem; color: var(--m-gray-3); margin-bottom: 0.5rem;"></i>
                        <p class="m-text-sm m-dimmed" style="margin: 0;">No daily reports submitted yet.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Site Visits -->
        <div class="col-lg-5">
            <div class="m-paper">
                <div class="m-paper-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h5 style="margin: 0;"><i class="fas fa-map-marker-alt me-2" style="color: var(--m-orange-6);"></i>Site Visits</h5>
                    <span class="m-badge m-badge-yellow">{{ $siteVisits->count() }}</span>
                </div>
                @if($siteVisits->count())
                    <div style="padding: 0;">
                        @foreach($siteVisits as $visit)
                            <div style="padding: 0.75rem var(--m-lg); {{ !$loop->last ? 'border-bottom: 1px solid var(--m-gray-3);' : '' }}">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.375rem;">
                                    <div>
                                        <strong class="m-text-sm">{{ $visit->visit_date?->format('M d, Y') ?? 'N/A' }}</strong>
                                        @if($visit->inspector)
                                            <div style="font-size: 0.8125rem; color: var(--m-gray-6);">
                                                <i class="fas fa-user-tie me-1"></i>{{ $visit->inspector->name }}
                                            </div>
                                        @endif
                                    </div>
                                    @php
                                        $vMap = ['approved' => 'teal', 'pending' => 'yellow', 'completed' => 'teal'];
                                    @endphp
                                    <span class="m-badge m-badge-{{ $vMap[$visit->status] ?? 'gray' }}">
                                        {{ ucfirst($visit->status ?? 'N/A') }}
                                    </span>
                                </div>
                                @if($visit->location)
                                    <div class="m-text-sm" style="margin-bottom: 0.25rem;">
                                        <i class="fas fa-map-pin me-1 m-dimmed"></i>{{ $visit->location }}
                                    </div>
                                @endif
                                @if($visit->findings)
                                    <div class="m-text-sm" style="margin-bottom: 0.25rem;">
                                        <strong>Findings:</strong> {{ Str::limit($visit->findings, 200) }}
                                    </div>
                                @endif
                                @if($visit->recommendations)
                                    <div class="m-text-sm" style="color: var(--m-blue-6);">
                                        <strong>Recommendations:</strong> {{ Str::limit($visit->recommendations, 200) }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="m-paper-body" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-map-marker-alt" style="font-size: 2rem; color: var(--m-gray-3); margin-bottom: 0.5rem;"></i>
                        <p class="m-text-sm m-dimmed" style="margin: 0;">No site visits recorded yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

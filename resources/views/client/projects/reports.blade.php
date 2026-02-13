@extends('layouts.client')

@section('title', 'Reports - ' . $project->project_name)

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <a href="{{ route('client.dashboard') }}" class="text-muted text-decoration-none" style="font-size: 0.8125rem;">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
            <h4 class="fw-bold mt-2 mb-0">{{ $project->project_name }}</h4>
        </div>
    </div>

    @include('client.partials.project_tabs')

    <div class="row g-3">
        <!-- Daily Reports -->
        <div class="col-lg-7">
            <div class="portal-card">
                <div class="portal-card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Daily Reports</h5>
                    <span class="badge bg-primary rounded-pill">{{ $dailyReports->count() }}</span>
                </div>
                @if($dailyReports->count())
                    <div class="portal-card-body p-0">
                        <div class="accordion" id="dailyReportsAccordion">
                            @foreach($dailyReports as $report)
                                <div class="accordion-item border-0 border-bottom">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }} py-3" type="button" data-bs-toggle="collapse" data-bs-target="#report-{{ $report->id }}">
                                            <div class="d-flex align-items-center gap-2 w-100">
                                                <i class="fas fa-calendar-day text-primary"></i>
                                                <strong>{{ $report->report_date?->format('M d, Y') ?? 'N/A' }}</strong>
                                                @if($report->weather_conditions)
                                                    <span class="badge bg-light text-dark ms-auto me-3" style="font-size: 0.75rem;">
                                                        <i class="fas fa-cloud-sun me-1"></i>{{ $report->weather_conditions }}
                                                    </span>
                                                @endif
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="report-{{ $report->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#dailyReportsAccordion">
                                        <div class="accordion-body">
                                            @if($report->supervisor)
                                                <div class="mb-2" style="font-size: 0.8125rem; color: var(--wajenzi-gray-600);">
                                                    <i class="fas fa-user me-1"></i> Supervisor: <strong>{{ $report->supervisor->name }}</strong>
                                                </div>
                                            @endif
                                            @if($report->work_completed)
                                                <div class="mb-2">
                                                    <label class="fw-bold d-block" style="font-size: 0.8125rem; color: var(--wajenzi-gray-600);">Work Completed</label>
                                                    <p class="mb-0">{{ $report->work_completed }}</p>
                                                </div>
                                            @endif
                                            @if($report->materials_used)
                                                <div class="mb-2">
                                                    <label class="fw-bold d-block" style="font-size: 0.8125rem; color: var(--wajenzi-gray-600);">Materials Used</label>
                                                    <p class="mb-0">{{ $report->materials_used }}</p>
                                                </div>
                                            @endif
                                            @if($report->labor_hours)
                                                <div class="mb-2">
                                                    <label class="fw-bold d-block" style="font-size: 0.8125rem; color: var(--wajenzi-gray-600);">Labor Hours</label>
                                                    <p class="mb-0">{{ $report->labor_hours }} hours</p>
                                                </div>
                                            @endif
                                            @if($report->issues_faced)
                                                <div class="mb-0">
                                                    <label class="fw-bold d-block" style="font-size: 0.8125rem; color: #DC2626;">Issues Faced</label>
                                                    <p class="mb-0">{{ $report->issues_faced }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="portal-card-body text-center py-4 text-muted">
                        <i class="fas fa-clipboard-list fa-2x mb-2"></i>
                        <p class="mb-0">No daily reports submitted yet.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Site Visits -->
        <div class="col-lg-5">
            <div class="portal-card">
                <div class="portal-card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Site Visits</h5>
                    <span class="badge bg-primary rounded-pill">{{ $siteVisits->count() }}</span>
                </div>
                @if($siteVisits->count())
                    <div class="portal-card-body p-0">
                        @foreach($siteVisits as $visit)
                            <div class="p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong style="font-size: 0.875rem;">{{ $visit->visit_date?->format('M d, Y') ?? 'N/A' }}</strong>
                                        @if($visit->inspector)
                                            <div style="font-size: 0.8125rem; color: var(--wajenzi-gray-600);">
                                                <i class="fas fa-user-tie me-1"></i>{{ $visit->inspector->name }}
                                            </div>
                                        @endif
                                    </div>
                                    @php
                                        $vMap = ['approved' => 'success', 'pending' => 'warning', 'completed' => 'success'];
                                    @endphp
                                    <span class="status-badge {{ $vMap[$visit->status] ?? 'secondary' }}" style="font-size: 0.7rem;">
                                        {{ ucfirst($visit->status ?? 'N/A') }}
                                    </span>
                                </div>
                                @if($visit->location)
                                    <div style="font-size: 0.8125rem;" class="mb-1">
                                        <i class="fas fa-map-pin me-1 text-muted"></i>{{ $visit->location }}
                                    </div>
                                @endif
                                @if($visit->findings)
                                    <div style="font-size: 0.8125rem;" class="mb-1">
                                        <strong>Findings:</strong> {{ Str::limit($visit->findings, 200) }}
                                    </div>
                                @endif
                                @if($visit->recommendations)
                                    <div style="font-size: 0.8125rem; color: #2563EB;">
                                        <strong>Recommendations:</strong> {{ Str::limit($visit->recommendations, 200) }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="portal-card-body text-center py-4 text-muted">
                        <i class="fas fa-map-marker-alt fa-2x mb-2"></i>
                        <p class="mb-0">No site visits recorded yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

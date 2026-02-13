@extends('layouts.client')

@section('title', $project->project_name)

@section('content')
    <!-- Project Header -->
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <a href="{{ route('client.dashboard') }}" class="text-muted text-decoration-none" style="font-size: 0.8125rem;">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
            <h4 class="fw-bold mt-2 mb-0">{{ $project->project_name }}</h4>
            <span class="text-muted" style="font-size: 0.875rem;">{{ $project->document_number }}</span>
        </div>
        @php
            $statusMap = ['APPROVED' => 'success', 'PENDING' => 'warning', 'REJECTED' => 'danger', 'COMPLETED' => 'info'];
        @endphp
        <span class="status-badge {{ $statusMap[$project->status] ?? 'secondary' }}" style="font-size: 0.875rem;">
            {{ $project->status ?? 'N/A' }}
        </span>
    </div>

    @include('client.partials.project_tabs')

    <!-- Overview Content -->
    <div class="row g-3">
        <!-- Project Details -->
        <div class="col-lg-8">
            <div class="portal-card mb-3">
                <div class="portal-card-header">
                    <h5><i class="fas fa-info-circle me-2"></i>Project Details</h5>
                </div>
                <div class="portal-card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="text-muted d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Project Type</label>
                                <span class="fw-semibold">{{ $project->projectType->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="text-muted d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Service Type</label>
                                <span class="fw-semibold">{{ $project->serviceType->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="text-muted d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Project Manager</label>
                                <span class="fw-semibold">{{ $project->projectManager->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="text-muted d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Priority</label>
                                <span class="fw-semibold">{{ ucfirst($project->priority ?? 'N/A') }}</span>
                            </div>
                        </div>
                        @if($project->description)
                            <div class="col-12">
                                <label class="text-muted d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Description</label>
                                <span>{{ $project->description }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Construction Phases -->
            @if($project->constructionPhases->count())
                <div class="portal-card">
                    <div class="portal-card-header">
                        <h5><i class="fas fa-tasks me-2"></i>Construction Phases</h5>
                    </div>
                    <div class="portal-card-body p-0">
                        <div class="table-responsive">
                            <table class="portal-table">
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
                                            <td class="fw-semibold">{{ $phase->phase_name }}</td>
                                            <td>{{ $phase->start_date?->format('M d, Y') ?? '-' }}</td>
                                            <td>{{ $phase->end_date?->format('M d, Y') ?? '-' }}</td>
                                            <td>
                                                @php
                                                    $phaseStatusMap = ['pending' => 'warning', 'in_progress' => 'info', 'completed' => 'success'];
                                                @endphp
                                                <span class="status-badge {{ $phaseStatusMap[$phase->status] ?? 'secondary' }}">
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
            <div class="portal-card mb-3">
                <div class="portal-card-header">
                    <h5><i class="fas fa-calendar me-2"></i>Timeline</h5>
                </div>
                <div class="portal-card-body">
                    <div class="mb-3">
                        <label class="text-muted d-block" style="font-size: 0.75rem;">START DATE</label>
                        <span class="fw-semibold">{{ $project->start_date?->format('M d, Y') ?? 'Not set' }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted d-block" style="font-size: 0.75rem;">EXPECTED END</label>
                        <span class="fw-semibold">{{ $project->expected_end_date?->format('M d, Y') ?? 'Not set' }}</span>
                    </div>
                    @if($project->planned_duration)
                        <div class="mb-3">
                            <label class="text-muted d-block" style="font-size: 0.75rem;">PLANNED DURATION</label>
                            <span class="fw-semibold">{{ $project->planned_duration }} days</span>
                        </div>
                    @endif
                    @if($project->delay !== null && $project->delay > 0)
                        <div class="alert alert-warning py-2 px-3 mb-0" style="font-size: 0.875rem;">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            {{ $project->delay }} days behind schedule
                        </div>
                    @endif
                </div>
            </div>

            <!-- Contract Value -->
            <div class="portal-card">
                <div class="portal-card-header">
                    <h5><i class="fas fa-money-bill-wave me-2"></i>Contract Value</h5>
                </div>
                <div class="portal-card-body text-center">
                    <div style="font-size: 1.75rem; font-weight: 700; color: #2563EB;">
                        TZS {{ number_format($project->contract_value ?? 0, 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

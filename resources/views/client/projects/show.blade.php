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

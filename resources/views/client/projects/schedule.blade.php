@extends('layouts.client')

@section('title', 'Schedule - ' . $project->project_name)

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

    <!-- Construction Phases -->
    <div class="portal-card mb-3">
        <div class="portal-card-header">
            <h5><i class="fas fa-layer-group me-2"></i>Construction Phases</h5>
        </div>
        @if($phases->count())
            <div class="portal-card-body p-0">
                <div class="table-responsive">
                    <table class="portal-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Phase Name</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($phases as $phase)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">{{ $phase->phase_name }}</td>
                                    <td>{{ $phase->start_date?->format('M d, Y') ?? '-' }}</td>
                                    <td>{{ $phase->end_date?->format('M d, Y') ?? '-' }}</td>
                                    <td>
                                        @php
                                            $map = ['pending' => 'warning', 'in_progress' => 'info', 'completed' => 'success'];
                                        @endphp
                                        <span class="status-badge {{ $map[$phase->status] ?? 'secondary' }}">
                                            {{ ucfirst(str_replace('_', ' ', $phase->status ?? 'N/A')) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="portal-card-body text-center py-4 text-muted">
                <i class="fas fa-layer-group fa-2x mb-2"></i>
                <p class="mb-0">No construction phases defined yet.</p>
            </div>
        @endif
    </div>

    <!-- Schedule Activities -->
    <div class="portal-card">
        <div class="portal-card-header">
            <h5><i class="fas fa-calendar-check me-2"></i>Schedule Activities</h5>
        </div>
        @if($activities->count())
            <div class="portal-card-body p-0">
                <div class="table-responsive">
                    <table class="portal-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Activity</th>
                                <th>Phase</th>
                                <th>Start</th>
                                <th>Duration</th>
                                <th>End</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activities as $activity)
                                <tr>
                                    <td><code>{{ $activity->activity_code }}</code></td>
                                    <td class="fw-semibold">{{ $activity->name }}</td>
                                    <td>{{ $activity->phase ?? '-' }}</td>
                                    <td>{{ $activity->start_date?->format('M d, Y') ?? '-' }}</td>
                                    <td>{{ $activity->duration_days }} days</td>
                                    <td>{{ $activity->end_date?->format('M d, Y') ?? '-' }}</td>
                                    <td>
                                        @php
                                            $actMap = ['pending' => 'warning', 'in_progress' => 'info', 'completed' => 'success', 'overdue' => 'danger'];
                                            $actStatus = $activity->status;
                                            if ($actStatus !== 'completed' && $activity->isOverdue()) $actStatus = 'overdue';
                                        @endphp
                                        <span class="status-badge {{ $actMap[$actStatus] ?? 'secondary' }}">
                                            {{ ucfirst(str_replace('_', ' ', $actStatus)) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="portal-card-body text-center py-4 text-muted">
                <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                <p class="mb-0">No schedule activities defined yet.</p>
            </div>
        @endif
    </div>
@endsection

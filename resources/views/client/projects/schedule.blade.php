@extends('layouts.client')

@section('title', 'Schedule - ' . $project->project_name)

@section('content')
    <div style="margin-bottom: var(--m-md);">
        <a href="{{ route('client.dashboard') }}" class="m-dimmed m-text-xs" style="text-decoration: none;">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
        <h1 class="m-title m-title-3" style="margin-top: var(--m-sm); margin-bottom: 0;">{{ $project->project_name }}</h1>
    </div>

    @include('client.partials.project_tabs')

    <!-- Construction Phases -->
    <div class="m-paper mb-3">
        <div class="m-paper-header">
            <h5><i class="fas fa-layer-group me-2" style="color: var(--m-violet-6);"></i>Construction Phases</h5>
        </div>
        @if($phases->count())
            <div style="padding: 0;">
                <div class="table-responsive">
                    <table class="m-table">
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
                                    <td class="m-fw-500">{{ $phase->phase_name }}</td>
                                    <td>{{ $phase->start_date?->format('M d, Y') ?? '-' }}</td>
                                    <td>{{ $phase->end_date?->format('M d, Y') ?? '-' }}</td>
                                    <td>
                                        @php
                                            $map = ['pending' => 'yellow', 'in_progress' => 'blue', 'completed' => 'teal'];
                                        @endphp
                                        <span class="m-badge m-badge-{{ $map[$phase->status] ?? 'gray' }}">
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
            <div class="m-paper-body" style="text-align: center; padding: 2rem;">
                <i class="fas fa-layer-group" style="font-size: 2rem; color: var(--m-gray-3); margin-bottom: 0.5rem;"></i>
                <p class="m-text-sm m-dimmed" style="margin: 0;">No construction phases defined yet.</p>
            </div>
        @endif
    </div>

    <!-- Completed Design Stages (milestone visibility for the client) -->
    @if(isset($completedActivities) && $completedActivities->count() > 0)
    <div class="m-paper mb-3">
        <div class="m-paper-header" style="background: linear-gradient(135deg, #d4edda 0%, #f0fff4 100%); border-bottom: 2px solid #28a745;">
            <h5 style="color: #155724;"><i class="fas fa-check-circle me-2"></i>Completed Design Stages</h5>
            <span class="m-badge m-badge-teal">{{ $completedActivities->count() }} completed</span>
        </div>
        <div style="padding: 0;">
            <div class="table-responsive">
                <table class="m-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Stage / Activity</th>
                            <th>Phase</th>
                            <th>Completed On</th>
                            <th>Completed By</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($completedActivities as $activity)
                            <tr>
                                <td><code style="background: #d4edda; color: #155724; padding: 0.125rem 0.375rem; border-radius: var(--m-radius-xs); font-size: 0.8125rem;">{{ $activity->activity_code }}</code></td>
                                <td class="m-fw-500">{{ $activity->name }}</td>
                                <td>{{ $activity->phase ?? '-' }}</td>
                                <td>
                                    @if($activity->completed_at)
                                        <span style="color: #155724; font-weight: 500;">
                                            <i class="fas fa-calendar-check me-1"></i>
                                            {{ $activity->completed_at->format('M d, Y') }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($activity->completedByUser)
                                        <span class="m-text-sm">{{ $activity->completedByUser->name }}</span>
                                    @else
                                        <span class="m-dimmed">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($activity->completion_notes)
                                        <span class="m-text-sm m-dimmed">{{ Str::limit($activity->completion_notes, 60) }}</span>
                                    @else
                                        <span class="m-dimmed">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Schedule Activities (pending / in progress) -->
    <div class="m-paper">
        <div class="m-paper-header">
            <h5><i class="fas fa-calendar-check me-2" style="color: var(--m-teal-6);"></i>Schedule Activities</h5>
        </div>
        @if($activities->count())
            <div style="padding: 0;">
                <div class="table-responsive">
                    <table class="m-table">
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
                                    <td><code style="background: var(--m-gray-1); padding: 0.125rem 0.375rem; border-radius: var(--m-radius-xs); font-size: 0.8125rem;">{{ $activity->activity_code }}</code></td>
                                    <td class="m-fw-500">{{ $activity->name }}</td>
                                    <td>{{ $activity->phase ?? '-' }}</td>
                                    <td>{{ $activity->start_date?->format('M d, Y') ?? '-' }}</td>
                                    <td>{{ $activity->duration_days }} days</td>
                                    <td>{{ $activity->end_date?->format('M d, Y') ?? '-' }}</td>
                                    <td>
                                        @php
                                            $actMap = ['pending' => 'yellow', 'in_progress' => 'blue', 'overdue' => 'red'];
                                            $actStatus = $activity->status;
                                            if ($activity->isOverdue()) $actStatus = 'overdue';
                                        @endphp
                                        <span class="m-badge m-badge-{{ $actMap[$actStatus] ?? 'gray' }}">
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
            <div class="m-paper-body" style="text-align: center; padding: 2rem;">
                <i class="fas fa-calendar-alt" style="font-size: 2rem; color: var(--m-gray-3); margin-bottom: 0.5rem;"></i>
                <p class="m-text-sm m-dimmed" style="margin: 0;">
                    @if(isset($completedActivities) && $completedActivities->count() > 0)
                        All activities are completed. See the completed stages above.
                    @else
                        No schedule activities defined yet.
                    @endif
                </p>
            </div>
        @endif
    </div>
@endsection

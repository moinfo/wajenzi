@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading d-flex justify-content-between align-items-center mb-3">
            <h2 class="flex-grow-1"><i class="fa fa-calendar-alt text-primary mr-2"></i> Project Schedules</h2>
        </div>

        <!-- Filters -->
        <div class="block block-themed">
            <div class="block-content">
                <form method="GET" class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="pending_confirmation" {{ request('status') == 'pending_confirmation' ? 'selected' : '' }}>Pending Confirmation</option>
                                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Schedules List -->
        <div class="block block-themed">
            <div class="block-header bg-primary">
                <h3 class="block-title">All Schedules</h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Lead</th>
                                <th>Client</th>
                                <th>Architect</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($schedules as $schedule)
                                <tr>
                                    <td>
                                        <a href="{{ route('leads.show', $schedule->lead_id) }}">
                                            {{ $schedule->lead->lead_number ?? 'N/A' }}
                                        </a>
                                    </td>
                                    <td>{{ $schedule->client ? $schedule->client->first_name . ' ' . $schedule->client->last_name : 'N/A' }}</td>
                                    <td>{{ $schedule->assignedArchitect->name ?? 'Unassigned' }}</td>
                                    <td>{{ $schedule->start_date->format('d/m/Y') }}</td>
                                    <td>{{ $schedule->end_date ? $schedule->end_date->format('d/m/Y') : 'N/A' }}</td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $schedule->progress }}%">
                                                {{ $schedule->progress }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'draft' => 'secondary',
                                                'pending_confirmation' => 'warning',
                                                'confirmed' => 'info',
                                                'in_progress' => 'primary',
                                                'completed' => 'success',
                                                'cancelled' => 'danger',
                                            ];
                                        @endphp
                                        <span class="badge badge-{{ $statusColors[$schedule->status] ?? 'secondary' }}">
                                            {{ ucwords(str_replace('_', ' ', $schedule->status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('project-schedules.show', $schedule) }}" class="btn btn-sm btn-primary">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        @if(!$schedule->isConfirmed())
                                            <a href="{{ route('project-schedules.edit', $schedule) }}" class="btn btn-sm btn-warning">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">No schedules found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $schedules->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

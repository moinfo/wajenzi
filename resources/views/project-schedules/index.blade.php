@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading d-flex justify-content-between align-items-center mb-3">
            <h2 class="flex-grow-1"><i class="fa fa-calendar-alt text-primary mr-2"></i> Project Schedules</h2>
        </div>

        <!-- Filters -->
        <div class="block block-themed">
            <div class="block-content">
                <form method="GET" class="row" style="align-items:flex-end;">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Search</label>
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                                       placeholder="Project, lead, client or architect…">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </div>
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
                    @if(request('search') || request('status'))
                        <div class="col-md-2">
                            <div class="form-group">
                                <a href="{{ route('project-schedules.index') }}" class="btn btn-alt-secondary btn-block">
                                    <i class="fa fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                    @endif
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
                                <th>Project / Lead</th>
                                <th>Client</th>
                                <th>Architect</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th class="text-center">Approval Flow</th>
                                <th class="text-center">Approval Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($schedules as $schedule)
                                <tr>
                                    <td>
                                        @if($schedule->project)
                                            <i class="fa fa-building text-primary mr-1"></i>
                                            {{ $schedule->project->project_name }}
                                        @elseif($schedule->lead)
                                            <a href="{{ route('leads.show', $schedule->lead_id) }}">
                                                <i class="fa fa-bullseye text-info mr-1"></i>
                                                {{ $schedule->lead->lead_number ?? $schedule->lead->name ?? 'N/A' }}
                                            </a>
                                        @else
                                            N/A
                                        @endif
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
                                    <td class="text-center">
                                        <x-ringlesoft-approval-status-summary :model="$schedule" />
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $approvalStatus = $schedule->approvalStatus?->status ?? 'Pending';
                                            $apStatusClass = [
                                                'Created'   => 'secondary',
                                                'Pending'   => 'warning',
                                                'Submitted' => 'info',
                                                'Returned'  => 'warning',
                                                'Approved'  => 'success',
                                                'Rejected'  => 'danger',
                                                'Discarded' => 'dark',
                                                'Overridden'=> 'success',
                                            ][$approvalStatus] ?? 'secondary';

                                            $apStatusIcon = [
                                                'Created'   => '<i class="fas fa-pencil-alt"></i>',
                                                'Pending'   => '<i class="fas fa-clock"></i>',
                                                'Submitted' => '<i class="fas fa-paper-plane"></i>',
                                                'Returned'  => '<i class="fas fa-undo"></i>',
                                                'Approved'  => '<i class="fas fa-check"></i>',
                                                'Rejected'  => '<i class="fas fa-times"></i>',
                                                'Discarded' => '<i class="fas fa-trash"></i>',
                                                'Overridden'=> '<i class="fas fa-check-double"></i>',
                                            ][$approvalStatus] ?? '<i class="fas fa-question-circle"></i>';
                                        @endphp
                                        <span class="badge badge-{{ $apStatusClass }} badge-pill" style="font-size: 0.9em; padding: 6px 10px;">
                                            {!! $apStatusIcon !!} {{ $approvalStatus }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('project-schedules.show', $schedule) }}" class="btn btn-sm btn-primary">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        @can('Edit Project Schedule')
                                            <a href="{{ route('project-schedules.edit', $schedule) }}" class="btn btn-sm btn-warning">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                        @endcan
                                        @can('Delete Project Schedule')
                                            <form action="{{ route('project-schedules.destroy', $schedule) }}" method="POST" class="d-inline js-delete-schedule">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete Schedule">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">No schedules found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $schedules->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('js_after')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form.js-delete-schedule').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            Swal.fire({
                title: 'Delete this schedule?',
                text: 'All its activities and assignments will also be removed. This cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel',
            }).then(function (result) {
                if (result && (result.value || result.isConfirmed)) form.submit();
            });
        });
    });

    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Done',
            text: @json(session('success')),
            timer: 2200,
            showConfirmButton: false,
        });
    @elseif(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: @json(session('error')),
        });
    @endif
});
</script>
@endsection

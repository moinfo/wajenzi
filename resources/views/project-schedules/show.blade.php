@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <!-- Header -->
        <div class="content-heading d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2><i class="fa fa-calendar-alt text-primary mr-2"></i> Project Schedule</h2>
                <small class="text-muted">{{ $projectSchedule->lead->lead_number ?? 'Lead' }} - {{ $projectSchedule->lead->name ?? '' }}</small>
            </div>
            <div>
                <a href="{{ route('leads.show', $projectSchedule->lead_id) }}" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Back to Lead
                </a>
                @if(!$projectSchedule->isConfirmed())
                    <a href="{{ route('project-schedules.edit', $projectSchedule) }}" class="btn btn-warning">
                        <i class="fa fa-edit"></i> Edit Schedule
                    </a>
                    <form action="{{ route('project-schedules.confirm', $projectSchedule) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to confirm this schedule? This will make activities visible on dashboard and calendar.')">
                            <i class="fa fa-check"></i> Confirm Schedule
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                {{ session('error') }}
            </div>
        @endif

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="block block-rounded">
                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-0">Status</p>
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
                            <h4 class="mb-0">
                                <span class="badge badge-{{ $statusColors[$projectSchedule->status] ?? 'secondary' }} badge-lg">
                                    {{ ucwords(str_replace('_', ' ', $projectSchedule->status)) }}
                                </span>
                            </h4>
                        </div>
                        <div class="ml-3">
                            <i class="fa fa-2x fa-flag text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded">
                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-0">Start Date</p>
                            <h4 class="mb-0">{{ $projectSchedule->start_date->format('d M Y') }}</h4>
                        </div>
                        <div class="ml-3">
                            <i class="fa fa-2x fa-calendar-plus text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded">
                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-0">End Date</p>
                            <h4 class="mb-0">{{ $projectSchedule->end_date ? $projectSchedule->end_date->format('d M Y') : 'N/A' }}</h4>
                        </div>
                        <div class="ml-3">
                            <i class="fa fa-2x fa-calendar-check text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded">
                    <div class="block-content block-content-full">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <p class="text-muted mb-0">Progress</p>
                            <span class="font-weight-bold text-primary">{{ $projectSchedule->progress }}%</span>
                        </div>
                        @php
                            $progress = $projectSchedule->progress;
                            $progressClass = $progress >= 75 ? 'success' : ($progress >= 50 ? 'info' : ($progress >= 25 ? 'warning' : 'danger'));
                        @endphp
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-{{ $progressClass }}" style="width: {{ $progress }}%"></div>
                        </div>
                        @php $details = $projectSchedule->progress_details; @endphp
                        <small class="text-muted">
                            <span class="text-success"><i class="fa fa-check"></i> {{ $details['completed'] }}</span>
                            <span class="mx-1">|</span>
                            <span class="text-primary"><i class="fa fa-spinner"></i> {{ $details['in_progress'] }}</span>
                            <span class="mx-1">|</span>
                            <span class="text-muted"><i class="fa fa-clock"></i> {{ $details['pending'] }}</span>
                            @if($details['overdue'] > 0)
                                <span class="mx-1">|</span>
                                <span class="text-danger"><i class="fa fa-exclamation"></i> {{ $details['overdue'] }}</span>
                            @endif
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assigned Architect -->
        <div class="block block-themed mb-4">
            <div class="block-header bg-info">
                <h3 class="block-title">Assignment Information</h3>
            </div>
            <div class="block-content">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Assigned Architect:</strong>
                        <p>{{ $projectSchedule->assignedArchitect->name ?? 'Not Assigned' }}</p>
                    </div>
                    <div class="col-md-4">
                        <strong>Client:</strong>
                        <p>{{ $projectSchedule->client ? $projectSchedule->client->first_name . ' ' . $projectSchedule->client->last_name : 'N/A' }}</p>
                    </div>
                    <div class="col-md-4">
                        <strong>Confirmed:</strong>
                        <p>{{ $projectSchedule->confirmed_at ? $projectSchedule->confirmed_at->format('d M Y H:i') : 'Not yet confirmed' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Info Box (before confirmation) -->
        @if(!$projectSchedule->isConfirmed())
        <div class="block block-rounded mb-4 border-left-0 overflow-hidden" style="border-left: 4px solid #3498db !important;">
            <div class="block-content py-3">
                <div class="row align-items-center">
                    <div class="col-auto pr-0">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="fa fa-edit fa-lg"></i>
                        </div>
                    </div>
                    <div class="col">
                        <h5 class="mb-1 font-weight-bold text-primary">Customize Schedule Before Confirming</h5>
                        <p class="text-muted mb-0 small">All dates auto-recalculate based on working days (excluding weekends & holidays)</p>
                    </div>
                </div>
                <hr class="my-3">
                <div class="row text-center">
                    <div class="col-md-4 mb-2 mb-md-0">
                        <div class="p-2 rounded" style="background: rgba(52, 152, 219, 0.1);">
                            <i class="fa fa-clock fa-2x text-primary mb-2"></i>
                            <h6 class="mb-1 font-weight-bold">Edit Days</h6>
                            <small class="text-muted">Change duration directly in the table</small>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2 mb-md-0">
                        <div class="p-2 rounded" style="background: rgba(231, 76, 60, 0.1);">
                            <i class="fa fa-trash-alt fa-2x text-danger mb-2"></i>
                            <h6 class="mb-1 font-weight-bold">Remove Activity</h6>
                            <small class="text-muted">Delete activities not needed</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 rounded" style="background: rgba(241, 196, 15, 0.1);">
                            <i class="fa fa-calendar-alt fa-2x text-warning mb-2"></i>
                            <h6 class="mb-1 font-weight-bold">Change Start Date</h6>
                            <small class="text-muted">Use "Edit Schedule" button above</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Progress by Phase Summary -->
        @php $progressByPhase = $projectSchedule->progress_by_phase; @endphp
        @if(count($progressByPhase) > 0)
        <div class="block block-rounded mb-4">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-chart-bar mr-2"></i>Progress by Phase</h3>
            </div>
            <div class="block-content">
                <div class="row">
                    @foreach($progressByPhase as $phaseName => $phaseData)
                        @php
                            $phaseProgress = $phaseData['percentage'];
                            $phaseClass = $phaseProgress >= 100 ? 'success' : ($phaseProgress >= 50 ? 'info' : ($phaseProgress >= 25 ? 'warning' : 'secondary'));
                        @endphp
                        <div class="col-md-4 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="font-weight-bold text-truncate" title="{{ $phaseName }}">{{ $phaseName }}</span>
                                <span class="badge badge-{{ $phaseClass }}">{{ $phaseData['completed'] }}/{{ $phaseData['total'] }}</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-{{ $phaseClass }}" style="width: {{ $phaseProgress }}%"></div>
                            </div>
                            <small class="text-muted">{{ $phaseProgress }}% complete</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Activities by Phase -->
        @foreach($activitiesByPhase as $phase => $activities)
            <div class="block block-themed mb-4">
                <div class="block-header bg-primary">
                    <h3 class="block-title">{{ $phase }}</h3>
                    <div class="block-options">
                        @php
                            $completed = $activities->where('status', 'completed')->count();
                            $total = $activities->count();
                        @endphp
                        <span class="badge badge-light">{{ $completed }}/{{ $total }} Completed</span>
                    </div>
                </div>
                <div class="block-content">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th width="8%">Code</th>
                                    <th width="22%">Activity</th>
                                    <th width="12%">Discipline</th>
                                    <th width="10%">Start</th>
                                    <th width="10%">End</th>
                                    <th width="10%">Days</th>
                                    <th width="10%">Status</th>
                                    <th width="18%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activities as $activity)
                                    <tr class="{{ $activity->isOverdue() ? 'table-danger' : '' }}">
                                        <td><strong>{{ $activity->activity_code }}</strong></td>
                                        <td>
                                            {{ $activity->name }}
                                            @if($activity->predecessor_code)
                                                <br><small class="text-muted">After: {{ $activity->predecessor_code }}</small>
                                            @endif
                                        </td>
                                        <td><small>{{ $activity->discipline }}</small></td>
                                        <td>{{ $activity->start_date->format('d/m/Y') }}</td>
                                        <td>{{ $activity->end_date->format('d/m/Y') }}</td>
                                        <td>
                                            @if(!$projectSchedule->isConfirmed())
                                                <form action="{{ route('project-schedules.activity.update-days', $activity) }}" method="POST" class="d-inline days-form">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="number" name="duration_days" value="{{ $activity->duration_days }}"
                                                           min="1" max="60" class="form-control form-control-sm d-inline"
                                                           style="width: 60px;" onchange="this.form.submit()">
                                                </form>
                                            @else
                                                {{ $activity->duration_days }}
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $activityStatusColors = [
                                                    'pending' => 'secondary',
                                                    'in_progress' => 'primary',
                                                    'completed' => 'success',
                                                    'skipped' => 'warning',
                                                ];
                                            @endphp
                                            <span class="badge badge-{{ $activityStatusColors[$activity->status] ?? 'secondary' }}">
                                                {{ ucwords(str_replace('_', ' ', $activity->status)) }}
                                            </span>
                                            @if($activity->isOverdue())
                                                <span class="badge badge-danger">Overdue</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($projectSchedule->isConfirmed())
                                                @if($activity->status === 'pending' && $activity->canStart())
                                                    <form action="{{ route('project-schedules.activity.start', $activity) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-xs btn-primary" title="Start Activity">
                                                            <i class="fa fa-play"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                @if($activity->status === 'in_progress')
                                                    <button type="button" class="btn btn-xs btn-success" title="Complete Activity"
                                                            data-toggle="modal" data-target="#completeActivityModal{{ $activity->id }}">
                                                        <i class="fa fa-check"></i>
                                                    </button>
                                                @endif
                                                @if($activity->status === 'completed')
                                                    <span class="text-success" title="Completed {{ $activity->completed_at ? $activity->completed_at->format('d/m/Y H:i') : '' }}">
                                                        <i class="fa fa-check-circle"></i>
                                                    </span>
                                                    @if($activity->attachment_path)
                                                        <a href="{{ Storage::url($activity->attachment_path) }}" target="_blank"
                                                           class="btn btn-xs btn-outline-info ml-1" title="View Attachment: {{ $activity->attachment_name }}">
                                                            <i class="fa fa-paperclip"></i>
                                                        </a>
                                                    @endif
                                                    @if($activity->completion_notes)
                                                        <button type="button" class="btn btn-xs btn-outline-secondary ml-1" title="View Notes"
                                                                data-toggle="modal" data-target="#viewNotesModal{{ $activity->id }}">
                                                            <i class="fa fa-sticky-note"></i>
                                                        </button>
                                                    @endif
                                                @endif
                                            @else
                                                {{-- Edit and Remove buttons before confirmation --}}
                                                <form action="{{ route('project-schedules.activity.remove', $activity) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to remove this activity? Activities depending on this will also be affected.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-xs btn-danger" title="Remove Activity">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Timeline View -->
        <div class="block block-themed">
            <div class="block-header bg-dark">
                <h3 class="block-title"><i class="fa fa-project-diagram mr-2"></i> Timeline Overview</h3>
            </div>
            <div class="block-content">
                <div class="timeline">
                    @foreach($projectSchedule->activities as $activity)
                        <div class="timeline-item">
                            <div class="timeline-marker
                                @if($activity->status === 'completed') bg-success
                                @elseif($activity->status === 'in_progress') bg-primary
                                @elseif($activity->isOverdue()) bg-danger
                                @else bg-secondary
                                @endif">
                            </div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ $activity->activity_code }}: {{ $activity->name }}</strong>
                                    <small class="text-muted">{{ $activity->start_date->format('d/m') }} - {{ $activity->end_date->format('d/m/Y') }}</small>
                                </div>
                                <small class="text-muted">{{ $activity->phase }} | {{ $activity->duration_days }} working days</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}
.timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}
.timeline-item {
    position: relative;
    padding-bottom: 20px;
}
.timeline-marker {
    position: absolute;
    left: -25px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}
.timeline-content {
    padding: 10px 15px;
    background: #f8f9fa;
    border-radius: 4px;
}
.badge-lg {
    font-size: 1rem;
    padding: 0.5rem 1rem;
}
</style>

{{-- Complete Activity Modals --}}
@foreach($projectSchedule->activities->where('status', 'in_progress') as $activity)
<div class="modal fade" id="completeActivityModal{{ $activity->id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="{{ route('project-schedules.activity.complete', $activity) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fa fa-check-circle mr-2"></i>Complete Activity
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border mb-3">
                        <strong>{{ $activity->activity_code }}:</strong> {{ $activity->name }}
                        <br><small class="text-muted">{{ $activity->phase }} | {{ $activity->discipline }}</small>
                    </div>

                    <div class="form-group">
                        <label for="completion_notes{{ $activity->id }}">
                            <i class="fa fa-sticky-note text-warning mr-1"></i>Completion Notes
                        </label>
                        <textarea name="completion_notes" id="completion_notes{{ $activity->id }}"
                                  class="form-control" rows="3"
                                  placeholder="Add any notes about this activity completion..."></textarea>
                        <small class="text-muted">Optional: Describe what was done, any issues encountered, etc.</small>
                    </div>

                    <div class="form-group mb-0">
                        <label for="attachment{{ $activity->id }}">
                            <i class="fa fa-paperclip text-info mr-1"></i>Attachment
                        </label>
                        <div class="custom-file">
                            <input type="file" name="attachment" id="attachment{{ $activity->id }}"
                                   class="custom-file-input" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip,.dwg">
                            <label class="custom-file-label" for="attachment{{ $activity->id }}">Choose file...</label>
                        </div>
                        <small class="text-muted">Optional: PDF, DOC, XLS, Images, ZIP, DWG (max 10MB)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-check mr-1"></i>Mark as Completed
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

{{-- View Notes Modals --}}
@foreach($projectSchedule->activities->where('status', 'completed')->whereNotNull('completion_notes') as $activity)
<div class="modal fade" id="viewNotesModal{{ $activity->id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fa fa-sticky-note mr-2"></i>Completion Notes
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border mb-3">
                    <strong>{{ $activity->activity_code }}:</strong> {{ $activity->name }}
                    <br>
                    <small class="text-muted">
                        Completed: {{ $activity->completed_at ? $activity->completed_at->format('d M Y H:i') : 'N/A' }}
                        @if($activity->completedByUser)
                            by {{ $activity->completedByUser->name }}
                        @endif
                    </small>
                </div>

                <div class="p-3 bg-light rounded">
                    {!! nl2br(e($activity->completion_notes)) !!}
                </div>

                @if($activity->attachment_path)
                <div class="mt-3">
                    <strong><i class="fa fa-paperclip mr-1"></i>Attachment:</strong>
                    <a href="{{ Storage::url($activity->attachment_path) }}" target="_blank" class="btn btn-sm btn-outline-primary ml-2">
                        <i class="fa fa-download mr-1"></i>{{ $activity->attachment_name }}
                    </a>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endforeach

<script>
// Update file input label with selected filename
document.querySelectorAll('.custom-file-input').forEach(function(input) {
    input.addEventListener('change', function(e) {
        var fileName = e.target.files[0] ? e.target.files[0].name : 'Choose file...';
        var label = e.target.nextElementSibling;
        label.textContent = fileName;
    });
});
</script>
@endsection

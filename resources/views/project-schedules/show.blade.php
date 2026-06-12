@extends('layouts.backend')

@section('content')
<style>
/* === Activity table polish === */
.activity-table { font-size: 12.5px; }
.activity-table thead th {
    background: #1a2332 !important; color: #fff !important;
    font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
    padding: 11px 10px; border: none; vertical-align: middle;
}
.activity-table tbody td { padding: 11px 10px; vertical-align: middle; border-top: 1px solid #f0f2f5; }
.activity-table tbody tr:hover { background: #fafbfd; }
.activity-table .act-code { font-weight: 800; color: #1a2332; font-size: 13px; }
.activity-table .act-name { font-weight: 600; color: #1a2332; }
.activity-table .act-after { color: #94a3b8; font-size: 10.5px; margin-top: 2px; }
.activity-table .act-discipline { color: #475569; font-size: 11.5px; }

/* Role pill — always visible, color-coded by role family */
.role-pill {
    display: inline-block;
    padding: 3px 11px;
    border-radius: 20px;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .4px;
    white-space: nowrap;
    border: 1px solid transparent;
}
.role-pill.role-architect   { background: #dbeafe; color: #1d4ed8; border-color: #93c5fd; }
.role-pill.role-engineer    { background: #dcfce7; color: #166534; border-color: #86efac; }
.role-pill.role-client      { background: #fef3c7; color: #92400e; border-color: #fcd34d; }
.role-pill.role-quantity    { background: #fce7f3; color: #9d174d; border-color: #f9a8d4; }
.role-pill.role-supervisor  { background: #ede9fe; color: #5b21b6; border-color: #c4b5fd; }
.role-pill.role-manager     { background: #e0f2fe; color: #075985; border-color: #7dd3fc; }
.role-pill.role-director    { background: #1a2332; color: #fff; border-color: #1a2332; }
.role-pill.role-default     { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
.role-pill.role-empty {
    background: transparent; color: #cbd5e1; border-style: dashed; border-color: #e2e8f0;
    font-weight: 600; font-style: italic; text-transform: none; letter-spacing: 0;
}

/* Assignee pill */
.assignee-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px 4px 4px;
    border-radius: 20px;
    background: #f1f5f9; color: #1a2332;
    font-size: 11.5px; font-weight: 600;
    border: 1px solid #e2e8f0;
    max-width: 100%;
}
.assignee-pill.is-architect { background: #1a2332; color: #fff; border-color: #1a2332; }
.assignee-pill .avatar {
    width: 22px; height: 22px; border-radius: 50%;
    background: #cbd5e1; color: #1a2332;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 10px; font-weight: 800; flex-shrink: 0;
}
.assignee-pill.is-architect .avatar { background: #1BC5BD; color: #fff; }
.assignee-pill .name { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.reassign-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 26px; height: 26px; border-radius: 6px;
    background: #f1f5f9; color: #1a2332; border: 1px solid #e2e8f0;
    margin-left: 4px; cursor: pointer; transition: all .15s;
}
.reassign-btn:hover { background: #1BC5BD; color: #fff; border-color: #1BC5BD; }

/* Status badge */
.act-status {
    display: inline-block; padding: 4px 10px; border-radius: 20px;
    font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px;
}
.act-status.s-pending     { background: #f1f5f9; color: #64748b; }
.act-status.s-in_progress { background: #dbeafe; color: #1d4ed8; }
.act-status.s-completed   { background: #dcfce7; color: #166534; }
.act-status.s-skipped     { background: #fef3c7; color: #92400e; }
.act-status.s-overdue     { background: #fee2e2; color: #b91c1c; margin-left: 4px; }

/* Phase header */
.phase-block { border-radius: 12px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.06); border: 1px solid #eef0f3; background: #fff; }
.phase-header { background: linear-gradient(90deg, #1a2332 0%, #2d3e54 100%); padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; }
.phase-header .phase-title { color: #fff; font-weight: 700; font-size: 14px; margin: 0; letter-spacing: .3px; }
.phase-header .phase-progress { background: rgba(255,255,255,.18); color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; letter-spacing: .3px; }
</style>

<div class="container-fluid">
    <div class="content">
        <!-- Header -->
        <div class="content-heading d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2><i class="fa fa-calendar-alt text-primary mr-2"></i> Project Schedule</h2>
                <small class="text-muted">
                    @if($projectSchedule->project)
                        {{ $projectSchedule->project->project_name }}
                    @elseif($projectSchedule->lead)
                        {{ $projectSchedule->lead->lead_number ?? 'Lead' }} - {{ $projectSchedule->lead->name ?? '' }}
                    @endif
                </small>
                @php $bonusTask = $projectSchedule->bonusTask; @endphp
                @if($bonusTask)
                    <div class="mt-1">
                        <a href="{{ url('/architect-bonus/' . $bonusTask->id) }}"
                           style="background:#fef9c3; color:#854d0e; border:1px solid #fde047; padding:3px 10px; border-radius:12px; font-size:11px; font-weight:600; text-decoration:none;">
                            <i class="fa fa-trophy"></i> Architect Bonus: {{ $bonusTask->task_number }}
                            ({{ ucfirst($bonusTask->status) }})
                        </a>
                    </div>
                @endif
            </div>
            <div>
                @if(auth()->user()->hasAnyRole(['System Administrator', 'Managing Director']))
                    @if($projectSchedule->project)
                        <a href="{{ route('individual_projects', [$projectSchedule->project_id, 10]) }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back to Project
                        </a>
                    @elseif($projectSchedule->lead_id)
                        <a href="{{ route('leads.show', $projectSchedule->lead_id) }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back to Lead
                        </a>
                    @endif
                    @if(!$projectSchedule->isConfirmed())
                        <a href="{{ route('project-schedules.edit', $projectSchedule) }}" class="btn btn-warning">
                            <i class="fa fa-edit"></i> Edit Schedule
                        </a>
                    @endif
                @endif
                @if($canAddActivity ?? false)
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addActivityModal">
                        <i class="fa fa-plus"></i> Add Activity
                    </button>
                @endif
                @php
                    $canSubmitForApproval = auth()->user()->hasAnyRole(['System Administrator', 'Managing Director'])
                        || $projectSchedule->assigned_architect_id === auth()->id();
                @endphp
                @if($projectSchedule->status === 'draft' && $canSubmitForApproval)
                    <form action="{{ route('project-schedules.submit', $projectSchedule) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success"
                                onclick="return confirm('Submit this schedule for CEO/MD approval? You will not be able to edit it after submission.')">
                            <i class="fa fa-paper-plane"></i> Submit for Approval
                        </button>
                    </form>
                @elseif($projectSchedule->isPendingApproval())
                    @php
                        $nextStep = method_exists($projectSchedule, 'nextApprovalStep') ? $projectSchedule->nextApprovalStep() : null;
                        $nextRoleName = $nextStep && $nextStep->role ? $nextStep->role->name : 'Approver';
                    @endphp
                    <span class="badge badge-warning badge-lg px-3 py-2">
                        <i class="fa fa-clock-o mr-1"></i> Awaiting {{ $nextRoleName }} Approval
                    </span>
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

        {{-- MD/CEO Approval Actions (shown when pending) --}}
        @if($projectSchedule->isPendingApproval())
        <div class="block block-rounded mb-4">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-check-circle text-warning mr-2"></i> Approval Required</h3>
            </div>
            <div class="block-content">
                <p class="text-muted mb-3">This schedule has been submitted by the architect and is awaiting your approval before work can begin.</p>
                <x-ringlesoft-approval-actions :model="$projectSchedule" />
            </div>
        </div>
        @endif

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
        @php
            // Map a role name to a CSS class. Keep this here so it lives next to where the badge is rendered.
            $roleClassFor = function ($roleName) {
                if (!$roleName) return 'role-empty';
                $n = strtolower($roleName);
                if (str_contains($n, 'architect'))    return 'role-architect';
                if (str_contains($n, 'engineer'))     return 'role-engineer';
                if (str_contains($n, 'client'))       return 'role-client';
                if (str_contains($n, 'quantity'))     return 'role-quantity';
                if (str_contains($n, 'supervisor'))   return 'role-supervisor';
                if (str_contains($n, 'manager'))      return 'role-manager';
                if (str_contains($n, 'managing director') ||
                    $n === 'ceo' ||
                    str_contains($n, 'chief executive')) return 'role-director';
                return 'role-default';
            };
            $initialsOf = function ($name) {
                if (!$name) return '?';
                $parts = preg_split('/\s+/', trim($name));
                $first = mb_substr($parts[0] ?? '', 0, 1);
                $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
                return strtoupper($first . $last);
            };
        @endphp
        @forelse($activitiesByPhase as $phase => $activities)
            <div class="phase-block mb-4">
                <div class="phase-header">
                    <h3 class="phase-title">{{ $phase }}</h3>
                    @php
                        $completed = $activities->where('status', 'completed')->count();
                        $total = $activities->count();
                    @endphp
                    <span class="phase-progress">{{ $completed }}/{{ $total }} Completed</span>
                </div>
                <div class="block-content p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover activity-table mb-0">
                            <thead>
                                <tr>
                                    @can('Assign Project Activities')
                                    <th width="3%" class="text-center">
                                        <input type="checkbox" class="select-all-activities" title="Select all in this phase">
                                    </th>
                                    @endcan
                                    <th width="6%">Code</th>
                                    <th width="15%">Activity</th>
                                    <th width="9%">Discipline</th>
                                    <th width="8%">Role</th>
                                    <th width="11%">Assigned To</th>
                                    <th width="7%">Start</th>
                                    <th width="7%">End</th>
                                    <th width="6%">Days</th>
                                    <th width="9%">Status</th>
                                    <th width="@can('Assign Project Activities')16%@else19%@endcan">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activities as $activity)
                                    <tr class="{{ $activity->isOverdue() ? 'table-danger' : '' }}">
                                        @can('Assign Project Activities')
                                        <td class="text-center">
                                            <input type="checkbox" class="activity-checkbox" value="{{ $activity->id }}"
                                                   data-name="{{ $activity->activity_code }}: {{ $activity->name }}">
                                        </td>
                                        @endcan
                                        <td><span class="act-code">{{ $activity->activity_code }}</span></td>
                                        <td>
                                            <div class="act-name">{{ $activity->name }}</div>
                                            @if($activity->predecessor_code)
                                                <div class="act-after">After: {{ $activity->predecessor_code }}</div>
                                            @endif
                                        </td>
                                        <td><span class="act-discipline">{{ $activity->discipline }}</span></td>
                                        <td>
                                            @php $roleName = $activity->role ? $activity->role->name : null; @endphp
                                            <span class="role-pill {{ $roleClassFor($roleName) }}">
                                                {{ $roleName ?: 'Not set' }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $architectName = $projectSchedule->assignedArchitect->name ?? null;
                                                if ($activity->assignedUser) {
                                                    $assigneeName = $activity->assignedUser->name;
                                                    $isArchitect  = $activity->assigned_to == $projectSchedule->assigned_architect_id;
                                                } elseif ($architectName) {
                                                    $assigneeName = $architectName;
                                                    $isArchitect  = true;
                                                } else {
                                                    $assigneeName = 'Unassigned';
                                                    $isArchitect  = false;
                                                }
                                            @endphp
                                            <span class="assignee-pill {{ $isArchitect ? 'is-architect' : '' }}" title="{{ $assigneeName }}">
                                                <span class="avatar">{{ $initialsOf($assigneeName) }}</span>
                                                <span class="name">{{ $assigneeName }}</span>
                                            </span>
                                            @can('Assign Project Activities')
                                                <button type="button" class="reassign-btn" title="Reassign"
                                                        data-toggle="modal" data-target="#assignModal{{ $activity->id }}">
                                                    <i class="fa fa-exchange-alt"></i>
                                                </button>
                                            @endcan
                                        </td>
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
                                            <span class="act-status s-{{ $activity->status }}">
                                                {{ ucwords(str_replace('_', ' ', $activity->status)) }}
                                            </span>
                                            @if($activity->isOverdue())
                                                <span class="act-status s-overdue">Overdue</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($projectSchedule->isConfirmed())
                                                @if($activity->status === 'pending')
                                                    @php
                                                        $canStart    = $activity->canStart();
                                                        $predecessor = $activity->predecessor_code ? $activity->predecessor() : null;
                                                        $predLabel   = $predecessor
                                                            ? "{$predecessor->activity_code}: {$predecessor->name}"
                                                            : $activity->predecessor_code;
                                                        $confirmMsg  = $canStart
                                                            ? ''
                                                            : "Predecessor activity {$predLabel} is not yet completed. Start " .
                                                              "{$activity->activity_code} anyway? (You may be working out of sequence.)";
                                                    @endphp
                                                    <form action="{{ route('project-schedules.activity.start', $activity) }}" method="POST" class="d-inline"
                                                          @if(!$canStart)
                                                              data-confirm-message="{{ $confirmMsg }}"
                                                              onsubmit="return confirm(this.dataset.confirmMessage);"
                                                          @endif>
                                                        @csrf
                                                        <button type="submit"
                                                                class="btn btn-xs {{ $canStart ? 'btn-primary' : 'btn-warning' }}"
                                                                title="{{ $canStart ? 'Start Work' : 'Start Work (predecessor not completed)' }}">
                                                            <i class="fa {{ $canStart ? 'fa-play' : 'fa-exclamation-triangle' }} mr-1"></i>Start Work
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
                                                    @if($activity->attachments->count())
                                                        <button type="button" class="btn btn-xs btn-outline-info ml-1"
                                                                title="{{ $activity->attachments->count() }} attachment(s)"
                                                                data-toggle="modal" data-target="#viewNotesModal{{ $activity->id }}">
                                                            <i class="fa fa-paperclip"></i> {{ $activity->attachments->count() }}
                                                        </button>
                                                    @else
                                                        <button type="button" class="btn btn-xs btn-outline-info ml-1"
                                                                title="Add attachment"
                                                                data-toggle="modal" data-target="#viewNotesModal{{ $activity->id }}">
                                                            <i class="fa fa-paperclip"></i>
                                                        </button>
                                                    @endif
                                                    @if($activity->completion_notes)
                                                        <button type="button" class="btn btn-xs btn-outline-secondary ml-1" title="View Notes"
                                                                data-toggle="modal" data-target="#viewNotesModal{{ $activity->id }}">
                                                            <i class="fa fa-sticky-note"></i>
                                                        </button>
                                                    @endif
                                                @endif
                                            @elseif($projectSchedule->status === 'draft')
                                                {{-- Remove allowed only while still a draft (before submission for approval). --}}
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
        @empty
            <div class="block block-themed mb-4">
                <div class="block-content text-center py-4">
                    <i class="fa fa-tasks fa-2x text-muted mb-2"></i>
                    <p class="text-muted">No activities have been generated for this schedule.<br>
                    Please ensure activity templates are configured in the system.</p>
                </div>
            </div>
        @endforelse

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
                                <small class="text-muted">
                                    {{ $activity->phase }} | {{ $activity->duration_days }} working days
                                    @if($activity->role) | <i class="fa fa-shield-alt"></i> {{ $activity->role->name }} @endif
                                    | <i class="fa fa-user"></i>
                                    {{ $activity->assignedUser ? $activity->assignedUser->name : ($projectSchedule->assignedArchitect->name ?? 'N/A') }}
                                </small>
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
                        <label for="attachments{{ $activity->id }}">
                            <i class="fa fa-paperclip text-info mr-1"></i>Attachments <small class="text-muted">(you can pick more than one)</small>
                        </label>
                        <div class="custom-file">
                            <input type="file" name="attachments[]" id="attachments{{ $activity->id }}" multiple
                                   class="custom-file-input" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip,.dwg">
                            <label class="custom-file-label" for="attachments{{ $activity->id }}">Choose files...</label>
                        </div>
                        <small class="text-muted">Optional: PDF, DOC, XLS, Images, ZIP, DWG. Up to 10 files, 50MB each.</small>
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

{{-- View Notes / Attachments Modals (for completed activities) --}}
@foreach($projectSchedule->activities->where('status', 'completed') as $activity)
<div class="modal fade" id="viewNotesModal{{ $activity->id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fa fa-paperclip mr-2"></i>Notes &amp; Attachments
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

                @if($activity->completion_notes)
                    <h6 class="text-muted mb-2"><i class="fa fa-sticky-note mr-1"></i>Completion Notes</h6>
                    <div class="p-3 bg-light rounded mb-3">
                        {!! nl2br(e($activity->completion_notes)) !!}
                    </div>
                @endif

                <h6 class="text-muted mb-2">
                    <i class="fa fa-paperclip mr-1"></i>Attachments
                    <span class="badge badge-secondary">{{ $activity->attachments->count() }}</span>
                </h6>
                @if($activity->attachments->count())
                    <ul class="list-group mb-3">
                        @foreach($activity->attachments as $att)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    <a href="{{ Storage::url($att->path) }}" target="_blank">
                                        <i class="fa fa-download mr-1"></i>{{ $att->name }}
                                    </a>
                                    @if($att->uploader)
                                        <small class="text-muted ml-2">
                                            by {{ $att->uploader->name }}, {{ $att->created_at->format('d/m/Y H:i') }}
                                        </small>
                                    @endif
                                </span>
                                @if($att->uploaded_by === auth()->id() || auth()->user()->hasAnyRole(['Managing Director', 'CEO', 'Chief Executive Officer', 'System Administrator']))
                                    <form action="{{ route('project-schedules.activity.attachments.remove', $att) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Remove this attachment?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted small">No attachments yet.</p>
                @endif

                <form action="{{ route('project-schedules.activity.attachments.add', $activity) }}" method="POST" enctype="multipart/form-data" class="mt-3 pt-3 border-top">
                    @csrf
                    <label class="text-muted mb-1"><strong>Add more attachments</strong></label>
                    <div class="custom-file mb-2">
                        <input type="file" name="attachments[]" id="addAttachments{{ $activity->id }}" multiple
                               class="custom-file-input" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip,.dwg">
                        <label class="custom-file-label" for="addAttachments{{ $activity->id }}">Choose files...</label>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fa fa-upload mr-1"></i>Upload
                    </button>
                    <small class="text-muted ml-2">PDF, DOC, XLS, Images, ZIP, DWG. Up to 10 files, 50MB each.</small>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endforeach

{{-- Bulk Action Floating Bar --}}
@can('Assign Project Activities')
<div id="bulkActionBar" class="d-none"
     style="position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:1050;
            background:#2c3e50;color:#fff;padding:12px 20px;border-radius:10px;
            box-shadow:0 4px 20px rgba(0,0,0,0.35);white-space:nowrap;">
    <i class="fa fa-check-square mr-2 text-info"></i>
    <span id="selectedCount">0</span> activities selected &nbsp;
    <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#bulkAssignModal">
        <i class="fa fa-exchange-alt mr-1"></i> Bulk Reassign
    </button>
    <button type="button" class="btn btn-sm btn-secondary ml-1" id="clearSelectionBtn">
        <i class="fa fa-times"></i>
    </button>
</div>

{{-- Bulk Reassign Modal --}}
<div class="modal fade" id="bulkAssignModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="{{ route('project-schedules.activities.bulk-assign', $projectSchedule) }}" method="POST" id="bulkAssignForm">
                @csrf
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fa fa-exchange-alt mr-2"></i>Bulk Reassign Activities</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="bulkAssignSummary" class="alert alert-info border-0 mb-3"></div>
                    <div id="bulkActivityList" class="mb-3" style="max-height:160px;overflow-y:auto;font-size:12px;"></div>
                    <div class="form-group mb-0">
                        <label><i class="fa fa-user-plus text-primary mr-1"></i>Assign all selected to</label>
                        <select name="assigned_to" class="form-control">
                            <option value="">-- Reset to Default Architect ({{ $projectSchedule->assignedArchitect->name ?? 'Default' }}) --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} – {{ $user->roles->pluck('name')->implode(', ') ?: 'No Role' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="bulkActivityIds"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info"><i class="fa fa-check mr-1"></i>Reassign All</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

{{-- Add Activity Modal --}}
@if($canAddActivity ?? false)
<div class="modal fade" id="addActivityModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="{{ route('project-schedules.activity.add', $projectSchedule) }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fa fa-plus mr-2"></i>Add Activity</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <small class="text-muted d-block mb-3">
                        New activities are appended to the end of the schedule and get an auto-generated code (X1, X2, …).
                        Dates are computed from the predecessor's end date (or the schedule start if none).
                    </small>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="required">Activity Name</label>
                                <input type="text" name="name" class="form-control" required maxlength="255"
                                       placeholder="e.g. Additional Site Visit">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="required">Duration (working days)</label>
                                <input type="number" name="duration_days" class="form-control" min="1" max="60" value="1" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required">Phase</label>
                                @php $existingPhases = $projectSchedule->activities->pluck('phase')->filter()->unique()->values(); @endphp
                                <input type="text" name="phase" class="form-control" list="phasesList" required
                                       placeholder="Select or type a phase">
                                <datalist id="phasesList">
                                    @foreach($existingPhases as $p)
                                        <option value="{{ $p }}">
                                    @endforeach
                                </datalist>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Discipline</label>
                                @php $existingDisciplines = $projectSchedule->activities->pluck('discipline')->filter()->unique()->values(); @endphp
                                <input type="text" name="discipline" class="form-control" list="disciplinesList"
                                       placeholder="Optional (e.g. Architectural)">
                                <datalist id="disciplinesList">
                                    @foreach($existingDisciplines as $d)
                                        <option value="{{ $d }}">
                                    @endforeach
                                </datalist>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Predecessor</label>
                                <select name="predecessor_code" class="form-control">
                                    <option value="">-- None (start from schedule start) --</option>
                                    @foreach($projectSchedule->activities()->orderBy('sort_order')->get() as $existing)
                                        <option value="{{ $existing->activity_code }}">
                                            {{ $existing->activity_code }} — {{ \Illuminate\Support\Str::limit($existing->name, 50) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Role</label>
                                <select name="role_id" class="form-control">
                                    <option value="">-- Not set --</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label>Assign To (optional)</label>
                        <select name="assigned_to" class="form-control">
                            <option value="">-- Default Architect ({{ $projectSchedule->assignedArchitect->name ?? 'Unassigned' }}) --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} – {{ $user->roles->pluck('name')->implode(', ') ?: 'No Role' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-plus mr-1"></i>Add Activity</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Reassign Activity Modals --}}
@can('Assign Project Activities')
@foreach($projectSchedule->activities as $activity)
@php
    $currentAssignee = $activity->assigned_to ?? $projectSchedule->assigned_architect_id;
@endphp
<div class="modal fade" id="assignModal{{ $activity->id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="{{ route('project-schedules.activity.assign', $activity) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fa fa-exchange-alt mr-2"></i>Reassign Activity
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

                    <div class="form-group mb-2">
                        <label class="text-muted mb-1"><small>Currently assigned to:</small></label>
                        <div>
                            @if($activity->assignedUser)
                                <span class="badge badge-info badge-lg"><i class="fa fa-user mr-1"></i>{{ $activity->assignedUser->name }}</span>
                            @else
                                <span class="badge badge-secondary badge-lg"><i class="fa fa-user-tie mr-1"></i>{{ $projectSchedule->assignedArchitect->name ?? 'N/A' }} (Architect)</span>
                            @endif
                        </div>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label for="assigned_to_{{ $activity->id }}">
                            <i class="fa fa-user-plus text-primary mr-1"></i>Reassign to
                        </label>
                        <select name="assigned_to" id="assigned_to_{{ $activity->id }}" class="form-control">
                            <option value="">-- Reset to Architect ({{ $projectSchedule->assignedArchitect->name ?? 'Default' }}) --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ $currentAssignee == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} - {{ $user->roles->pluck('name')->implode(', ') ?: 'No Role' }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Choose a different person to handle this activity. Progress remains visible to everyone.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fa fa-check mr-1"></i>Reassign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endcan

<script>
// ── File input label ──────────────────────────────────────────────────────────
document.querySelectorAll('.custom-file-input').forEach(function(input) {
    input.addEventListener('change', function(e) {
        var fileName = e.target.files[0] ? e.target.files[0].name : 'Choose file...';
        e.target.nextElementSibling.textContent = fileName;
    });
});

// ── Bulk activity selection ───────────────────────────────────────────────────
(function () {
    var bar          = document.getElementById('bulkActionBar');
    var countEl      = document.getElementById('selectedCount');
    var clearBtn     = document.getElementById('clearSelectionBtn');
    var listEl       = document.getElementById('bulkActivityList');
    var summaryEl    = document.getElementById('bulkAssignSummary');
    var idsContainer = document.getElementById('bulkActivityIds');

    if (!bar) return; // user lacks permission

    function getChecked() {
        return Array.from(document.querySelectorAll('.activity-checkbox:checked'));
    }

    function refreshBar() {
        var checked = getChecked();
        if (checked.length > 0) {
            bar.classList.remove('d-none');
            countEl.textContent = checked.length;
        } else {
            bar.classList.add('d-none');
        }
    }

    // Per-phase "select all" checkboxes
    document.querySelectorAll('.select-all-activities').forEach(function(cb) {
        cb.addEventListener('change', function() {
            var table = this.closest('table');
            table.querySelectorAll('.activity-checkbox').forEach(function(c) {
                c.checked = cb.checked;
            });
            refreshBar();
        });
    });

    // Individual checkboxes — also update phase select-all indeterminate state
    document.querySelectorAll('.activity-checkbox').forEach(function(cb) {
        cb.addEventListener('change', function() {
            refreshBar();
            var table    = this.closest('table');
            var all      = table.querySelectorAll('.activity-checkbox');
            var checked  = table.querySelectorAll('.activity-checkbox:checked');
            var selectAll = table.querySelector('.select-all-activities');
            if (selectAll) {
                selectAll.checked       = all.length === checked.length;
                selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
            }
        });
    });

    // Clear all selections
    clearBtn.addEventListener('click', function() {
        document.querySelectorAll('.activity-checkbox, .select-all-activities').forEach(function(c) {
            c.checked = false;
            c.indeterminate = false;
        });
        bar.classList.add('d-none');
    });

    // Populate bulk modal when the trigger button is clicked (avoids jQuery $ dependency)
    function populateBulkModal() {
        var checked = getChecked();

        // Rebuild hidden activity_ids[] inputs
        while (idsContainer.firstChild) { idsContainer.removeChild(idsContainer.firstChild); }
        checked.forEach(function(cb) {
            var inp   = document.createElement('input');
            inp.type  = 'hidden';
            inp.name  = 'activity_ids[]';
            inp.value = cb.value;
            idsContainer.appendChild(inp);
        });

        // Summary line
        summaryEl.textContent = checked.length + ' activities will be reassigned.';

        // Activity badge list — textContent only, safe against XSS
        while (listEl.firstChild) { listEl.removeChild(listEl.firstChild); }
        checked.forEach(function(cb) {
            var badge         = document.createElement('span');
            badge.className   = 'badge badge-light border mr-1 mb-1';
            badge.textContent = cb.dataset.name;
            listEl.appendChild(badge);
        });
    }

    var bulkTrigger = bar.querySelector('[data-target="#bulkAssignModal"]');
    if (bulkTrigger) { bulkTrigger.addEventListener('click', populateBulkModal); }
}());
</script>
@endsection

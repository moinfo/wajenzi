@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">

        {{-- Header --}}
        <div class="content-heading">
            Structural Design — {{ $design->project->project_name ?? '' }}
            <div class="float-right">
                <a href="{{ route('structural_design.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
                {{-- Final submission: only when all stages approved and schedule approved --}}
                @if($design->scheduleApproved() && !$design->isSubmitted() && !in_array($design->status, ['approved','rejected']))
                    @php $allStagesApproved = $design->stages->count() > 0 && $design->stages->where('approval_status','!=','approved')->count() === 0; @endphp
                    @if($allStagesApproved)
                    <form action="{{ route('structural_design.submit', $design) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success"
                            onclick="return confirm('Submit final structural design for CEO/MD approval?')">
                            <i class="fa fa-paper-plane"></i> Submit Final Design
                        </button>
                    </form>
                    @endif
                @endif
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }} <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }} <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        @endif

        @php
        $statusColors = [
            'pending'     => 'warning',
            'in_progress' => 'primary',
            'submitted'   => 'info',
            'approved'    => 'success',
            'rejected'    => 'danger',
        ];
        $scheduleStatusColors = [
            'not_submitted' => 'secondary',
            'submitted'     => 'info',
            'approved'      => 'success',
            'rejected'      => 'danger',
        ];
        @endphp

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- STEP 1: WORK SCHEDULE                                          --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <div class="block block-rounded border-left border-{{ $scheduleStatusColors[$design->schedule_status] ?? 'secondary' }} pl-3 mb-4">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    @if($design->scheduleApproved())
                        <i class="fa fa-check-circle text-success mr-2"></i>
                    @elseif($design->schedulePending())
                        <i class="fa fa-clock text-info mr-2"></i>
                    @else
                        <i class="fa fa-calendar-alt text-warning mr-2"></i>
                    @endif
                    Step 1 — Work Schedule
                    <span class="badge badge-{{ $scheduleStatusColors[$design->schedule_status] ?? 'secondary' }} ml-2">
                        {{ ucwords(str_replace('_',' ',$design->schedule_status)) }}
                    </span>
                </h3>
            </div>
            <div class="block-content">

                @if($design->schedule_status === 'rejected')
                <div class="alert alert-danger">
                    <strong>Rejected:</strong> {{ $design->schedule_rejection_notes }}
                    <br><small>Please revise and resubmit.</small>
                </div>
                @endif

                @if($design->scheduleApproved())
                {{-- Show approved schedule details --}}
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th class="text-muted" style="width:160px;">Planned Start</th>
                        <td>{{ $design->schedule_planned_start?->format('d/m/Y') ?? '—' }}</td>
                        <th class="text-muted" style="width:160px;">Planned End</th>
                        <td>{{ $design->schedule_planned_end?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Approved On</th>
                        <td>{{ $design->schedule_approved_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    </tr>
                    @if($design->schedule_description)
                    <tr>
                        <th class="text-muted">Scope / Plan</th>
                        <td colspan="3">{{ $design->schedule_description }}</td>
                    </tr>
                    @endif
                </table>

                @elseif($design->schedulePending())
                {{-- MD/CEO approval panel --}}
                <p class="text-muted mb-3">The engineer has submitted a work schedule. Please review and approve or reject.</p>
                <table class="table table-sm table-borderless mb-3">
                    <tr>
                        <th class="text-muted" style="width:160px;">Planned Start</th>
                        <td>{{ $design->schedule_planned_start?->format('d/m/Y') }}</td>
                        <th class="text-muted" style="width:160px;">Planned End</th>
                        <td>{{ $design->schedule_planned_end?->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Scope / Plan</th>
                        <td colspan="3">{{ $design->schedule_description }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Submitted</th>
                        <td>{{ $design->schedule_submitted_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>
                @can('Approve Structural Design')
                <div class="d-flex gap-2">
                    <form action="{{ route('structural_design.schedule.approve', $design) }}" method="POST" class="d-inline mr-2">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm"
                            onclick="return confirm('Approve this work schedule?')">
                            <i class="fa fa-check"></i> Approve Schedule
                        </button>
                    </form>
                    <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#rejectScheduleModal">
                        <i class="fa fa-times"></i> Reject Schedule
                    </button>
                </div>
                @endcan

                @else
                {{-- Not yet submitted — engineer fills in --}}
                @if(!in_array($design->status, ['approved']))
                <p class="text-muted mb-3">
                    Before starting any stage work, submit your work schedule for management approval.
                </p>
                <form action="{{ route('structural_design.schedule.submit', $design) }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Planned Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="schedule_planned_start" class="form-control"
                                       value="{{ $design->schedule_planned_start?->format('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Planned End Date <span class="text-danger">*</span></label>
                                <input type="date" name="schedule_planned_end" class="form-control"
                                       value="{{ $design->schedule_planned_end?->format('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Work Plan / Scope Description <span class="text-danger">*</span></label>
                        <textarea name="schedule_description" class="form-control" rows="4"
                            placeholder="Describe your planned approach, methodology, tools, deliverables per stage, and any dependencies or assumptions..."
                            required>{{ $design->schedule_description }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa fa-paper-plane"></i> Submit Work Schedule for Approval
                    </button>
                </form>
                @endif
                @endif

            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- STEP 2: DESIGN STAGES (locked until schedule approved)         --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <div class="block block-rounded mb-4">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-layer-group mr-2"></i>Step 2 — Design Stages
                    @if(!$design->scheduleApproved())
                        <span class="badge badge-secondary ml-2"><i class="fa fa-lock mr-1"></i>Locked — schedule not yet approved</span>
                    @endif
                </h3>
            </div>
            <div class="block-content">

                @if(!$design->scheduleApproved())
                <div class="alert alert-warning">
                    <i class="fa fa-lock mr-2"></i>
                    Stage work is locked until the work schedule is approved by management.
                </div>
                @else

                @foreach($design->stages as $stage)
                @php
                $stageColors = ['pending'=>'secondary','in_progress'=>'primary','completed'=>'success'];
                $approvalColors = ['pending'=>'secondary','submitted'=>'info','approved'=>'success','rejected'=>'danger'];
                $canEdit = !in_array($design->status, ['submitted','approved'])
                           && in_array($stage->approval_status, ['pending','rejected']);
                $canSubmitStage = $stage->status === 'completed' && $stage->file_path
                                  && in_array($stage->approval_status, ['pending','rejected']);
                @endphp

                <div class="card mb-4 border-{{ $stageColors[$stage->status] ?? 'secondary' }}">
                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                        <div>
                            <span class="badge badge-{{ $stageColors[$stage->status] ?? 'secondary' }} mr-2">{{ $stage->stage_order }}</span>
                            <strong>{{ $stage->name }}</strong>
                        </div>
                        <div>
                            <span class="badge badge-{{ $stageColors[$stage->status] ?? 'secondary' }} mr-1">
                                {{ ucwords(str_replace('_',' ',$stage->status)) }}
                            </span>
                            <span class="badge badge-{{ $approvalColors[$stage->approval_status] ?? 'secondary' }}">
                                @if($stage->approval_status === 'pending') <i class="fa fa-circle mr-1"></i>Not submitted
                                @elseif($stage->approval_status === 'submitted') <i class="fa fa-clock mr-1"></i>Awaiting approval
                                @elseif($stage->approval_status === 'approved') <i class="fa fa-check mr-1"></i>Approved
                                @else <i class="fa fa-times mr-1"></i>Rejected
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="card-body py-3">

                        {{-- Rejection note --}}
                        @if($stage->rejection_notes)
                        <div class="alert alert-danger py-2 mb-2">
                            <strong>Rejected:</strong> {{ $stage->rejection_notes }}<br>
                            <small>Please revise and resubmit.</small>
                        </div>
                        @endif

                        {{-- Uploaded file --}}
                        @if($stage->file_path)
                        <div class="mb-2">
                            <i class="fa fa-paperclip text-muted"></i>
                            <a href="{{ Storage::url($stage->file_path) }}" target="_blank">
                                {{ $stage->file_name ?? 'Download File' }}
                            </a>
                        </div>
                        @endif

                        @if($stage->notes)
                        <p class="text-muted small mb-2">{{ $stage->notes }}</p>
                        @endif

                        @if($stage->completed_at)
                        <small class="text-muted d-block mb-2">
                            Completed {{ $stage->completed_at->format('d/m/Y') }}
                            by {{ $stage->completedByUser->name ?? '—' }}
                        </small>
                        @endif

                        @if($stage->approved_at)
                        <small class="text-success d-block mb-2">
                            <i class="fa fa-check-circle"></i>
                            Approved {{ $stage->approved_at->format('d/m/Y') }}
                        </small>
                        @endif

                        {{-- Engineer edit form (locked if submitted/approved) --}}
                        @if($canEdit)
                        <form action="{{ route('structural_design.stage', [$design, $stage]) }}"
                              method="POST" enctype="multipart/form-data" class="mt-2 border-top pt-2">
                            @csrf
                            <div class="row">
                                <div class="col-md-4">
                                    <select name="status" class="form-control form-control-sm">
                                        @foreach(['pending','in_progress','completed'] as $s)
                                        <option value="{{ $s }}" {{ $stage->status === $s ? 'selected' : '' }}>
                                            {{ ucwords(str_replace('_',' ',$s)) }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="file" name="file" class="form-control-file form-control-sm"
                                           accept=".pdf,.dwg,.dxf,.jpg,.jpeg,.png,.zip">
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-sm btn-alt-primary">
                                        <i class="fa fa-save"></i> Save
                                    </button>
                                </div>
                            </div>
                            <div class="mt-1">
                                <input type="text" name="notes" class="form-control form-control-sm"
                                       placeholder="Notes (optional)" value="{{ $stage->notes }}">
                            </div>
                        </form>
                        @endif

                        {{-- Submit stage for approval --}}
                        @if($canSubmitStage)
                        <form action="{{ route('structural_design.stage.submit', [$design, $stage]) }}"
                              method="POST" class="mt-2">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-info"
                                onclick="return confirm('Submit \"{{ $stage->name }}\" for management approval?')">
                                <i class="fa fa-paper-plane"></i> Submit Stage for Approval
                            </button>
                        </form>
                        @endif

                        {{-- MD/CEO approve/reject actions for this stage --}}
                        @if($stage->approval_status === 'submitted')
                        @can('Approve Structural Design')
                        <div class="border-top pt-2 mt-2 d-flex gap-2">
                            <form action="{{ route('structural_design.stage.approve', [$design, $stage]) }}" method="POST" class="d-inline mr-2">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success"
                                    onclick="return confirm('Approve stage \"{{ $stage->name }}\"?')">
                                    <i class="fa fa-check"></i> Approve Stage
                                </button>
                            </form>
                            <button type="button" class="btn btn-sm btn-danger"
                                data-toggle="modal" data-target="#rejectStageModal{{ $stage->id }}">
                                <i class="fa fa-times"></i> Reject Stage
                            </button>
                        </div>
                        @endcan
                        @endif

                    </div>
                </div>

                {{-- Reject stage modal --}}
                <div class="modal fade" id="rejectStageModal{{ $stage->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Reject Stage — {{ $stage->name }}</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <form action="{{ route('structural_design.stage.reject', [$design, $stage]) }}" method="POST">
                                @csrf
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Reason for rejection <span class="text-danger">*</span></label>
                                        <textarea name="rejection_notes" class="form-control" rows="3"
                                            placeholder="Explain what needs to be corrected..." required></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger"><i class="fa fa-times"></i> Reject</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                @endforeach

                @if($design->stages->count() === 0)
                <div class="alert alert-info mb-0">
                    <i class="fa fa-info-circle"></i> No stages have been created yet.
                </div>
                @endif

                @endif {{-- end scheduleApproved --}}
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- STEP 3: FINAL MD/CEO APPROVAL                                  --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        @if($design->isSubmitted() || in_array($design->status, ['approved','rejected']))
        <div class="block block-rounded mb-4">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-check-circle text-{{ $statusColors[$design->status] ?? 'secondary' }} mr-2"></i>
                    Step 3 — Final Approval
                </h3>
            </div>
            <div class="block-content">
                @if($design->approvalStatus)
                    @include('partials.approval_status', ['approvable' => $design])
                @endif
                @if($design->isSubmitted())
                    <x-ringlesoft-approval-actions :model="$design" />
                @endif
            </div>
        </div>
        @endif

        {{-- Project Info + Reassign (sidebar-style below) --}}
        <div class="row">
            <div class="col-md-4">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Overview</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-sm table-borderless">
                            <tr><th class="text-muted">Reference</th><td>{{ $design->document_number }}</td></tr>
                            <tr><th class="text-muted">Project</th><td>{{ $design->project->project_name ?? '—' }}</td></tr>
                            <tr>
                                <th class="text-muted">Client</th>
                                <td>{{ $design->project?->client?->first_name }} {{ $design->project?->client?->last_name }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Status</th>
                                <td><span class="badge badge-{{ $statusColors[$design->status] ?? 'secondary' }}">{{ ucwords(str_replace('_',' ',$design->status)) }}</span></td>
                            </tr>
                            <tr><th class="text-muted">Engineer</th><td>{{ $design->assignedEngineer->name ?? '—' }}</td></tr>
                            <tr><th class="text-muted">Created</th><td>{{ $design->created_at->format('d/m/Y') }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            @if(!in_array($design->status, ['approved','rejected']))
            <div class="col-md-4">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Reassign Engineer</h3>
                    </div>
                    <div class="block-content">
                        <form action="{{ route('structural_design.reassign', $design) }}" method="POST">
                            @csrf
                            <div class="form-group mb-2">
                                <select name="assigned_engineer_id" class="form-control form-control-sm" required>
                                    <option value="">Select Engineer</option>
                                    @foreach($engineers as $eng)
                                    <option value="{{ $eng->id }}" {{ $design->assigned_engineer_id == $eng->id ? 'selected' : '' }}>
                                        {{ $eng->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-sm btn-alt-primary">Update Engineer</button>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        </div>

    </div>
</div>

{{-- Reject Schedule Modal --}}
<div class="modal fade" id="rejectScheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Work Schedule</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('structural_design.schedule.reject', $design) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Reason for rejection <span class="text-danger">*</span></label>
                        <textarea name="rejection_notes" class="form-control" rows="3"
                            placeholder="Explain what needs to be revised..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fa fa-times"></i> Reject Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

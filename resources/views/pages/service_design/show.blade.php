@extends('layouts.backend')

@section('content')
<div class="container-fluid">
<div class="content">

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

{{-- Page Header --}}
<div class="content-heading d-flex align-items-center justify-content-between">
    <div>
        <a href="{{ route('service_design.index') }}" class="btn btn-sm btn-secondary mr-2">
            <i class="fa fa-arrow-left"></i>
        </a>
        <i class="fa fa-tools text-info mr-2"></i>
        Service Design — {{ $design->document_number }}
        <span class="badge badge-{{ ['pending'=>'warning','in_progress'=>'primary','submitted'=>'info','approved'=>'success','rejected'=>'danger'][$design->status] ?? 'secondary' }} ml-2">
            {{ ucwords(str_replace('_', ' ', $design->status)) }}
        </span>
    </div>
</div>

<div class="row">
    {{-- Main Workflow Column --}}
    <div class="col-lg-8">

        {{-- ─── STEP 1: Work Schedule ─────────────────────────────────── --}}
        <div class="block border-left border-info pl-0 mb-4" style="border-left: 4px solid #17a2b8 !important;">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <span class="badge badge-info mr-2">Step 1</span>
                    Work Schedule
                    @if($design->schedule_status === 'approved')
                        <span class="badge badge-success ml-2"><i class="fa fa-check"></i> Approved</span>
                    @elseif($design->schedule_status === 'submitted')
                        <span class="badge badge-warning ml-2"><i class="fa fa-clock"></i> Awaiting Approval</span>
                    @elseif($design->schedule_status === 'rejected')
                        <span class="badge badge-danger ml-2"><i class="fa fa-times"></i> Rejected</span>
                    @else
                        <span class="badge badge-secondary ml-2">Not Submitted</span>
                    @endif
                </h3>
            </div>
            <div class="block-content">

                @if($design->schedule_status === 'approved')
                    {{-- Approved — show details --}}
                    <div class="alert alert-success">
                        <strong><i class="fa fa-check-circle mr-1"></i>Work Schedule Approved</strong>
                        — Engineer may now work on the design stages below.
                    </div>
                    <table class="table table-sm table-borderless">
                        <tr><th width="200">Description</th><td>{{ $design->schedule_description }}</td></tr>
                        <tr><th>Planned Start</th><td>{{ $design->schedule_planned_start?->format('d/m/Y') }}</td></tr>
                        <tr><th>Planned End</th><td>{{ $design->schedule_planned_end?->format('d/m/Y') }}</td></tr>
                        <tr><th>Approved At</th><td>{{ $design->schedule_approved_at?->format('d/m/Y H:i') }}</td></tr>
                    </table>

                @elseif($design->schedule_status === 'submitted')
                    {{-- Awaiting MD approval --}}
                    <div class="alert alert-warning">
                        <i class="fa fa-clock mr-1"></i>
                        Work schedule submitted on {{ $design->schedule_submitted_at?->format('d/m/Y') }} — awaiting management approval.
                    </div>
                    <table class="table table-sm table-borderless">
                        <tr><th width="200">Description</th><td>{{ $design->schedule_description }}</td></tr>
                        <tr><th>Planned Start</th><td>{{ $design->schedule_planned_start?->format('d/m/Y') }}</td></tr>
                        <tr><th>Planned End</th><td>{{ $design->schedule_planned_end?->format('d/m/Y') }}</td></tr>
                    </table>

                    @if(auth()->user()->hasAnyRole(['Managing Director', 'CEO', 'Chief Executive Officer', 'System Administrator']))
                    <div class="mt-3 d-flex gap-2">
                        <form action="{{ route('service_design.schedule.approve', $design) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-success btn-sm" onclick="return confirm('Approve this work schedule?')">
                                <i class="fa fa-check mr-1"></i>Approve Schedule
                            </button>
                        </form>
                        <button class="btn btn-danger btn-sm ml-2" data-toggle="modal" data-target="#rejectScheduleModal">
                            <i class="fa fa-times mr-1"></i>Reject Schedule
                        </button>
                    </div>
                    @endif

                @elseif($design->schedule_status === 'rejected')
                    {{-- Rejected — show reason and allow resubmit --}}
                    <div class="alert alert-danger">
                        <strong><i class="fa fa-times-circle mr-1"></i>Schedule Rejected:</strong>
                        {{ $design->schedule_rejection_notes }}
                    </div>
                    {{-- Fall through to show the submit form again --}}
                    @if(auth()->id() == $design->assigned_engineer_id || auth()->user()->hasAnyRole(['Managing Director', 'CEO', 'Chief Executive Officer', 'System Administrator']))
                    <form action="{{ route('service_design.schedule.submit', $design) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>Revised Description <span class="text-danger">*</span></label>
                            <textarea name="schedule_description" class="form-control" rows="4" required>{{ $design->schedule_description }}</textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Planned Start <span class="text-danger">*</span></label>
                                <input type="date" name="schedule_planned_start" class="form-control"
                                       value="{{ $design->schedule_planned_start?->format('Y-m-d') }}" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Planned End <span class="text-danger">*</span></label>
                                <input type="date" name="schedule_planned_end" class="form-control"
                                       value="{{ $design->schedule_planned_end?->format('Y-m-d') }}" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-info btn-sm">
                            <i class="fa fa-paper-plane mr-1"></i>Resubmit Schedule
                        </button>
                    </form>
                    @endif

                @else
                    {{-- Not yet submitted — show form for engineer --}}
                    @if(auth()->id() == $design->assigned_engineer_id || auth()->user()->hasAnyRole(['Managing Director', 'CEO', 'Chief Executive Officer', 'System Administrator']))
                    <p class="text-muted mb-3">
                        <i class="fa fa-info-circle mr-1"></i>
                        Fill in your work schedule and submit it for management approval before beginning any stage.
                    </p>
                    <form action="{{ route('service_design.schedule.submit', $design) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>Schedule Description — include Electrical, FADS, ICT, HVAC plan <span class="text-danger">*</span></label>
                            <textarea name="schedule_description" class="form-control" rows="5" required
                                      placeholder="Describe the timeline and approach for each service discipline..."></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Planned Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="schedule_planned_start" class="form-control" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Planned End Date <span class="text-danger">*</span></label>
                                <input type="date" name="schedule_planned_end" class="form-control" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-info btn-sm">
                            <i class="fa fa-paper-plane mr-1"></i>Submit Schedule for Approval
                        </button>
                    </form>
                    @else
                    <p class="text-muted"><i class="fa fa-hourglass mr-1"></i>Waiting for assigned engineer to submit a work schedule.</p>
                    @endif
                @endif
            </div>
        </div>

        {{-- ─── STEP 2: Design Stages ──────────────────────────────────── --}}
        <div class="block mb-4" style="border-left: 4px solid {{ $design->scheduleApproved() ? '#28a745' : '#6c757d' }} !important;">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <span class="badge badge-{{ $design->scheduleApproved() ? 'success' : 'secondary' }} mr-2">Step 2</span>
                    Design Stages
                    @if(!$design->scheduleApproved())
                        <small class="text-muted ml-2">(Locked until schedule is approved)</small>
                    @endif
                </h3>
            </div>
            <div class="block-content">

            @if(!$design->scheduleApproved())
                <div class="alert alert-secondary">
                    <i class="fa fa-lock mr-1"></i> Work schedule must be approved before stage work can begin.
                </div>
            @else

            @foreach($design->stages as $stage)
            @php
            $stageStatusColors = [
                'pending'     => 'secondary',
                'in_progress' => 'primary',
                'completed'   => 'success',
            ];
            $approvalColors = [
                'pending'   => 'secondary',
                'submitted' => 'warning',
                'approved'  => 'success',
                'rejected'  => 'danger',
            ];
            $isEngineer  = auth()->id() == $design->assigned_engineer_id;
            $isMgmt      = auth()->user()->hasAnyRole(['Managing Director', 'CEO', 'Chief Executive Officer', 'System Administrator']);
            $canEdit     = ($isEngineer || $isMgmt) && !in_array($stage->approval_status, ['submitted', 'approved']);
            @endphp

            <div class="card mb-3 border">
                <div class="card-header d-flex justify-content-between align-items-center py-2 px-3">
                    <div>
                        <strong>{{ $stage->stage_order }}. {{ $stage->name }}</strong>
                        <span class="badge badge-{{ $stageStatusColors[$stage->status] ?? 'secondary' }} ml-2">
                            {{ ucfirst($stage->status) }}
                        </span>
                        <span class="badge badge-{{ $approvalColors[$stage->approval_status] ?? 'secondary' }} ml-1">
                            {{ ucfirst($stage->approval_status) }}
                        </span>
                    </div>
                    @if($stage->file_path)
                    <a href="{{ Storage::url($stage->file_path) }}" target="_blank"
                       class="btn btn-xs btn-alt-info">
                        <i class="fa fa-download mr-1"></i>{{ $stage->file_name }}
                    </a>
                    @endif
                </div>
                <div class="card-body py-3 px-3">

                    @if($stage->approval_status === 'rejected')
                    <div class="alert alert-danger py-2 mb-3">
                        <strong>Rejected:</strong> {{ $stage->rejection_notes }}
                    </div>
                    @endif

                    @if($stage->approval_status === 'approved')
                    <div class="alert alert-success py-2 mb-0">
                        <i class="fa fa-check-circle mr-1"></i>
                        Approved on {{ $stage->approved_at?->format('d/m/Y') }}
                        @if($stage->notes) — <em>{{ $stage->notes }}</em>@endif
                    </div>
                    @elseif($stage->approval_status === 'submitted')
                    <div class="alert alert-warning py-2">
                        <i class="fa fa-clock mr-1"></i>Awaiting management approval since {{ $stage->submitted_at?->format('d/m/Y') }}.
                    </div>

                    @if($isMgmt)
                    <div class="d-flex mt-2">
                        <form action="{{ route('service_design.stage.approve', [$design, $stage]) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-success btn-sm" onclick="return confirm('Approve this stage?')">
                                <i class="fa fa-check mr-1"></i>Approve
                            </button>
                        </form>
                        <button class="btn btn-danger btn-sm ml-2" data-toggle="modal"
                                data-target="#rejectStageModal{{ $stage->id }}">
                            <i class="fa fa-times mr-1"></i>Reject
                        </button>
                    </div>
                    @endif

                    @else
                    {{-- Edit form --}}
                    @if($canEdit)
                    <form action="{{ route('service_design.stage.update', [$design, $stage]) }}" method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label class="small mb-1">Status</label>
                                <select name="status" class="form-control form-control-sm">
                                    <option value="pending"     {{ $stage->status === 'pending'     ? 'selected' : '' }}>Pending</option>
                                    <option value="in_progress" {{ $stage->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="completed"   {{ $stage->status === 'completed'   ? 'selected' : '' }}>Completed</option>
                                </select>
                            </div>
                            <div class="form-group col-md-8">
                                <label class="small mb-1">Upload Document (PDF, DWG, DXF, ZIP)</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="file_{{ $stage->id }}" name="file"
                                           accept=".pdf,.dwg,.dxf,.jpg,.jpeg,.png,.zip">
                                    <label class="custom-file-label" for="file_{{ $stage->id }}">
                                        {{ $stage->file_name ?? 'Choose file...' }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <textarea name="notes" class="form-control form-control-sm" rows="2"
                                      placeholder="Notes (optional)">{{ $stage->notes }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fa fa-save mr-1"></i>Save
                        </button>
                    </form>
                    @else
                    <p class="text-muted small mb-0">
                        @if($stage->notes)<em>{{ $stage->notes }}</em><br>@endif
                        Completed: {{ $stage->completed_at?->format('d/m/Y') ?? '—' }}
                        @if($stage->completedByUser) by {{ $stage->completedByUser->name }}@endif
                    </p>
                    @endif

                    {{-- Submit for approval button --}}
                    @if(($isEngineer || $isMgmt) && $stage->status === 'completed' && $stage->file_path && $stage->approval_status !== 'submitted' && $stage->approval_status !== 'approved')
                    <form action="{{ route('service_design.stage.submit', [$design, $stage]) }}" method="POST" class="mt-3">
                        @csrf
                        <button type="submit" class="btn btn-info btn-sm"
                                onclick="return confirm('Submit this stage for management approval?')">
                            <i class="fa fa-paper-plane mr-1"></i>Submit for Approval
                        </button>
                    </form>
                    @endif

                    @endif {{-- end approval_status checks --}}
                </div>
            </div>

            {{-- Reject Stage Modal --}}
            <div class="modal fade" id="rejectStageModal{{ $stage->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('service_design.stage.reject', [$design, $stage]) }}" method="POST">
                            @csrf
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title">Reject Stage — {{ $stage->name }}</h5>
                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Rejection Reason <span class="text-danger">*</span></label>
                                    <textarea name="rejection_notes" class="form-control" rows="4" required
                                              placeholder="Explain what needs to be corrected..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger">Reject Stage</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach

            @endif {{-- end scheduleApproved check --}}
            </div>
        </div>

        {{-- ─── STEP 3: Final Approval ─────────────────────────────────── --}}
        @php
        $allStagesApproved = $design->stages->isNotEmpty() && $design->stages->every(fn($s) => $s->approval_status === 'approved');
        @endphp
        <div class="block mb-4" style="border-left: 4px solid {{ $allStagesApproved ? '#6f42c1' : '#6c757d' }} !important;">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <span class="badge badge-{{ $allStagesApproved ? 'purple' : 'secondary' }} mr-2"
                          style="{{ $allStagesApproved ? 'background:#6f42c1;color:#fff' : '' }}">Step 3</span>
                    Final CEO/MD Approval
                    @if(!$allStagesApproved)
                        <small class="text-muted ml-2">(Locked until all stages are approved)</small>
                    @endif
                </h3>
            </div>
            <div class="block-content">

            @if(!$allStagesApproved)
                <div class="alert alert-secondary">
                    <i class="fa fa-lock mr-1"></i> All design stages must be individually approved before final submission.
                </div>
            @else
                @if(in_array($design->status, ['pending', 'in_progress']) && $allStagesApproved)
                <div class="alert alert-info">
                    <i class="fa fa-info-circle mr-1"></i>
                    All stages approved. Submit the complete service design for CEO/MD final sign-off.
                </div>
                @if(auth()->id() == $design->assigned_engineer_id || auth()->user()->hasAnyRole(['Managing Director', 'CEO', 'Chief Executive Officer', 'System Administrator']))
                <form action="{{ route('service_design.submit', $design) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary"
                            onclick="return confirm('Submit the final service design for CEO/MD approval?')">
                        <i class="fa fa-paper-plane mr-1"></i>Submit Final Service Design
                    </button>
                </form>
                @endif
                @endif

                @if(in_array($design->status, ['submitted', 'approved', 'rejected']))
                <x-ringlesoft-approval-actions :model="$design" />
                @endif
            @endif
            </div>
        </div>

    </div>{{-- /col-lg-8 --}}

    {{-- Sidebar --}}
    <div class="col-lg-4">

        {{-- Project Info --}}
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Project Details</h3>
            </div>
            <div class="block-content">
                <table class="table table-sm table-borderless">
                    <tr><th>Project</th><td>{{ $design->project->project_name ?? '—' }}</td></tr>
                    <tr><th>Client</th><td>{{ optional($design->project->client)->first_name }} {{ optional($design->project->client)->last_name }}</td></tr>
                    <tr><th>Engineer</th><td>{{ $design->assignedEngineer->name ?? '<em class="text-muted">Unassigned</em>' }}</td></tr>
                    <tr><th>Created</th><td>{{ $design->created_at->format('d/m/Y') }}</td></tr>
                    @if($design->submitted_at)
                    <tr><th>Submitted</th><td>{{ $design->submitted_at->format('d/m/Y') }}</td></tr>
                    @endif
                    @if($design->approved_at)
                    <tr><th>Approved</th><td>{{ $design->approved_at->format('d/m/Y') }}</td></tr>
                    @endif
                </table>
            </div>
        </div>

        {{-- Reassign Engineer --}}
        @if(auth()->user()->hasAnyRole(['Managing Director', 'CEO', 'Chief Executive Officer', 'System Administrator']))
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Reassign Engineer</h3>
            </div>
            <div class="block-content">
                <form action="{{ route('service_design.reassign', $design) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <select name="assigned_engineer_id" class="form-control form-control-sm">
                            <option value="">— Unassigned —</option>
                            @foreach($engineers as $eng)
                            <option value="{{ $eng->id }}" {{ $design->assigned_engineer_id == $eng->id ? 'selected' : '' }}>
                                {{ $eng->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-alt-secondary">
                        <i class="fa fa-user-edit mr-1"></i>Reassign
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Stage Summary --}}
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Stage Summary</h3>
            </div>
            <div class="block-content">
                @foreach($design->stages as $stage)
                @php
                $approvalColors = ['pending'=>'secondary','submitted'=>'warning','approved'=>'success','rejected'=>'danger'];
                @endphp
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small>{{ $stage->stage_order }}. {{ $stage->name }}</small>
                    <span class="badge badge-{{ $approvalColors[$stage->approval_status] ?? 'secondary' }}">
                        {{ ucfirst($stage->approval_status) }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>

    </div>{{-- /col-lg-4 --}}
</div>{{-- /row --}}

</div>
</div>

{{-- Reject Schedule Modal --}}
<div class="modal fade" id="rejectScheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('service_design.schedule.reject', $design) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Reject Work Schedule</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_notes" class="form-control" rows="4" required
                                  placeholder="Explain what needs to be revised..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

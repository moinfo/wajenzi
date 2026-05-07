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
                @if(!$design->isSubmitted() && !in_array($design->status, ['approved','rejected']))
                    @if($design->allStagesCompleted())
                    <form action="{{ route('structural_design.submit', $design) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success"
                            onclick="return confirm('Submit for CEO/MD approval?')">
                            <i class="fa fa-paper-plane"></i> Submit for Approval
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

        <div class="row">

            {{-- Left: Project Info + Approval --}}
            <div class="col-md-4">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Overview</h3>
                    </div>
                    <div class="block-content">
                        @php
                        $statusColors = [
                            'pending'     => 'warning',
                            'in_progress' => 'primary',
                            'submitted'   => 'info',
                            'approved'    => 'success',
                            'rejected'    => 'danger',
                        ];
                        @endphp
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th class="text-muted">Reference</th>
                                <td>{{ $design->document_number }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Project</th>
                                <td>{{ $design->project->project_name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Client</th>
                                <td>
                                    @if($design->project?->client)
                                        {{ $design->project->client->first_name }}
                                        {{ $design->project->client->last_name }}
                                    @else —
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted">Status</th>
                                <td>
                                    <span class="badge badge-{{ $statusColors[$design->status] ?? 'secondary' }}">
                                        {{ ucwords(str_replace('_', ' ', $design->status)) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted">Engineer</th>
                                <td>{{ $design->assignedEngineer->name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Started</th>
                                <td>{{ $design->created_at->format('d/m/Y') }}</td>
                            </tr>
                            @if($design->submitted_at)
                            <tr>
                                <th class="text-muted">Submitted</th>
                                <td>{{ $design->submitted_at->format('d/m/Y') }}</td>
                            </tr>
                            @endif
                            @if($design->approved_at)
                            <tr>
                                <th class="text-muted">Approved</th>
                                <td>{{ $design->approved_at->format('d/m/Y') }}</td>
                            </tr>
                            @endif
                        </table>

                        @if($design->notes)
                        <div class="alert alert-light border mt-2">
                            <small class="text-muted">Notes:</small><br>
                            {{ $design->notes }}
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Reassign engineer --}}
                @if(!in_array($design->status, ['approved','rejected']))
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
                                    <option value="{{ $eng->id }}"
                                        {{ $design->assigned_engineer_id == $eng->id ? 'selected' : '' }}>
                                        {{ $eng->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-sm btn-alt-primary">
                                Update Engineer
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                {{-- Approval status panel (RingleSoft standard) --}}
                @if($design->approvalStatus)
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Approval</h3>
                    </div>
                    <div class="block-content">
                        @include('partials.approval_status', ['approvable' => $design])
                    </div>
                </div>
                @endif
            </div>

            {{-- Right: Stages --}}
            <div class="col-md-8">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Design Stages</h3>
                    </div>
                    <div class="block-content">

                        @foreach($design->stages as $stage)
                        @php
                        $stageColors = ['pending'=>'secondary','in_progress'=>'primary','completed'=>'success'];
                        $canEdit = !in_array($design->status, ['submitted','approved']);
                        @endphp
                        <div class="card mb-3 border-{{ $stageColors[$stage->status] ?? 'secondary' }}">
                            <div class="card-header d-flex justify-content-between align-items-center py-2">
                                <div>
                                    <span class="badge badge-{{ $stageColors[$stage->status] ?? 'secondary' }} mr-2">
                                        {{ $stage->stage_order }}
                                    </span>
                                    <strong>{{ $stage->name }}</strong>
                                </div>
                                <span class="badge badge-{{ $stageColors[$stage->status] ?? 'secondary' }}">
                                    {{ ucwords(str_replace('_', ' ', $stage->status)) }}
                                </span>
                            </div>
                            <div class="card-body py-2">

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
                                <small class="text-muted">
                                    Completed {{ $stage->completed_at->format('d/m/Y') }}
                                    by {{ $stage->completedByUser->name ?? '—' }}
                                </small>
                                @endif

                                @if($canEdit)
                                <form action="{{ route('structural_design.stage', [$design, $stage]) }}"
                                      method="POST" enctype="multipart/form-data" class="mt-2 border-top pt-2">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-4">
                                            <select name="status" class="form-control form-control-sm">
                                                @foreach(['pending','in_progress','completed'] as $s)
                                                <option value="{{ $s }}" {{ $stage->status === $s ? 'selected' : '' }}>
                                                    {{ ucwords(str_replace('_', ' ', $s)) }}
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
                                                Update Stage
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-1">
                                        <input type="text" name="notes" class="form-control form-control-sm"
                                               placeholder="Notes (optional)" value="{{ $stage->notes }}">
                                    </div>
                                </form>
                                @endif
                            </div>
                        </div>
                        @endforeach

                        @if(!$design->allStagesCompleted() && !in_array($design->status, ['submitted','approved']))
                        <div class="alert alert-info mb-0">
                            <i class="fa fa-info-circle"></i>
                            Complete all {{ $design->stages->count() }} stages to enable submission for approval.
                        </div>
                        @endif

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

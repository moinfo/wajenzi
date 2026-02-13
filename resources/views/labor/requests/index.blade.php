@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <i class="fa fa-clipboard-list"></i> Labor Requests
            <div class="float-right">
                <a href="{{ route('labor.dashboard') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="fa fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="{{ route('labor.requests.create') }}" class="btn btn-rounded btn-primary min-width-100 mb-10">
                    <i class="fa fa-plus"></i> New Request
                </a>
            </div>
        </div>

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">All Labor Requests</h3>
            </div>
            <div class="block-content">
                <form method="post" id="filter-form" autocomplete="off">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <div class="input-group">
                                <span class="input-group-text">From</span>
                                <input type="text" name="start_date" class="form-control datepicker"
                                    value="{{ $start_date ?? date('Y-m-01') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="input-group">
                                <span class="input-group-text">To</span>
                                <input type="text" name="end_date" class="form-control datepicker"
                                    value="{{ $end_date ?? date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="project_id" class="form-control">
                                <option value="">All Projects</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ $selected_project == $project->id ? 'selected' : '' }}>
                                        {{ $project->project_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="draft" {{ $selected_status == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="pending" {{ $selected_status == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ $selected_status == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ $selected_status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                <option value="contracted" {{ $selected_status == 'contracted' ? 'selected' : '' }}>Contracted</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>Request #</th>
                                <th>Project</th>
                                <th>Artisan</th>
                                <th>Work Description</th>
                                <th class="text-right">Amount</th>
                                <th class="text-center">Duration</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $request)
                                <tr>
                                    <td class="text-center">{{ $loop->index + 1 }}</td>
                                    <td>
                                        <a href="{{ route('labor.requests.show', $request->id) }}">
                                            <strong>{{ $request->request_number }}</strong>
                                        </a>
                                        <br>
                                        <small class="text-muted">{{ $request->created_at?->format('Y-m-d') }}</small>
                                    </td>
                                    <td>{{ Str::limit($request->project?->project_name, 25) }}</td>
                                    <td>
                                        {{ $request->artisan?->name ?? 'Not assigned' }}
                                        @if($request->artisan?->trade_skill)
                                            <br><small class="text-muted">{{ $request->artisan->trade_skill }}</small>
                                        @endif
                                    </td>
                                    <td>{{ Str::limit($request->work_description, 40) }}</td>
                                    <td class="text-right">
                                        {{ number_format($request->final_amount, 0) }}
                                        <br><small class="text-muted">{{ $request->currency }}</small>
                                    </td>
                                    <td class="text-center">
                                        {{ $request->estimated_duration_days ?? '-' }} days
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $request->status_badge_class }}">
                                            {{ ucfirst($request->status) }}
                                        </span>
                                        @if(!$request->isDraft())
                                            <br>
                                            <x-ringlesoft-approval-status-summary :model="$request" />
                                        @endif
                                        @if($request->contract)
                                            <br>
                                            <a href="{{ route('labor.contracts.show', $request->contract->id) }}" class="small">
                                                {{ $request->contract->contract_number }}
                                            </a>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="{{ route('labor.requests.show', $request->id) }}"
                                                class="btn btn-sm btn-info" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            @if($request->isDraft())
                                                <a href="{{ route('labor.requests.edit', $request->id) }}"
                                                    class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <form action="{{ route('labor.requests.submit', $request->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="Submit for Approval"
                                                        onclick="return confirm('Submit this request for approval?')">
                                                        <i class="fa fa-paper-plane"></i>
                                                    </button>
                                                </form>
                                            @elseif($request->isPending())
                                                <a href="{{ route('labor.requests.approval', ['id' => $request->id, 'document_type_id' => 0]) }}"
                                                    class="btn btn-sm btn-success" title="Approval">
                                                    <i class="fa fa-check-circle"></i>
                                                </a>
                                            @elseif($request->canCreateContract())
                                                <a href="{{ route('labor.contracts.create', $request->id) }}"
                                                    class="btn btn-sm btn-primary" title="Create Contract">
                                                    <i class="fa fa-file-contract"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js_after')
<script>
    $(document).ready(function() {
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });
    });
</script>
@endsection

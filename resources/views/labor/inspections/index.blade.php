@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <i class="fa fa-search-plus"></i> Labor Inspections
            <div class="float-right">
                <a href="{{ route('labor.dashboard') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="fa fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
        </div>

        @if($contractsPendingInspection->count() > 0)
        <div class="block">
            <div class="block-header block-header-default bg-warning">
                <h3 class="block-title text-white">
                    <i class="fa fa-clock"></i> Contracts Ready for Inspection ({{ $contractsPendingInspection->count() }})
                </h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Contract #</th>
                                <th>Project</th>
                                <th>Artisan</th>
                                <th>Current Progress</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contractsPendingInspection as $contract)
                                <tr>
                                    <td>{{ $contract->contract_number }}</td>
                                    <td>{{ $contract->project?->project_name }}</td>
                                    <td>{{ $contract->artisan?->name }}</td>
                                    <td>{{ number_format($contract->latest_progress, 1) }}%</td>
                                    <td class="text-center">
                                        <a href="{{ route('labor.inspections.create', $contract->id) }}"
                                            class="btn btn-sm btn-primary">
                                            <i class="fa fa-clipboard-check"></i> Create Inspection
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">All Inspections</h3>
            </div>
            <div class="block-content">
                <form method="get" id="filter-form" autocomplete="off">
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <input type="text" name="start_date" class="form-control datepicker"
                                value="{{ $start_date }}" placeholder="Start Date">
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="end_date" class="form-control datepicker"
                                value="{{ $end_date }}" placeholder="End Date">
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
                                <option value="verified" {{ $selected_status == 'verified' ? 'selected' : '' }}>Verified</option>
                                <option value="approved" {{ $selected_status == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ $selected_status == 'rejected' ? 'selected' : '' }}>Rejected</option>
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
                                <th>Inspection #</th>
                                <th>Contract</th>
                                <th>Artisan</th>
                                <th class="text-center">Type</th>
                                <th class="text-center">Completion</th>
                                <th class="text-center">Quality</th>
                                <th class="text-center">Result</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inspections as $inspection)
                                <tr>
                                    <td class="text-center">{{ $loop->index + 1 }}</td>
                                    <td>
                                        <a href="{{ route('labor.inspections.show', $inspection->id) }}">
                                            <strong>{{ $inspection->inspection_number }}</strong>
                                        </a>
                                        <br>
                                        <small class="text-muted">{{ $inspection->inspection_date?->format('Y-m-d') }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('labor.contracts.show', $inspection->labor_contract_id) }}">
                                            {{ $inspection->contract?->contract_number }}
                                        </a>
                                    </td>
                                    <td>{{ $inspection->contract?->artisan?->name }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $inspection->type_badge_class }}">
                                            {{ ucfirst($inspection->inspection_type) }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ number_format($inspection->completion_percentage, 1) }}%</td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $inspection->quality_badge_class }}">
                                            {{ ucfirst($inspection->work_quality) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $inspection->result_badge_class }}">
                                            {{ ucfirst($inspection->result) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $inspection->status_badge_class }}">
                                            {{ ucfirst($inspection->status) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="{{ route('labor.inspections.show', $inspection->id) }}"
                                                class="btn btn-sm btn-info" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            @if($inspection->isPending() || $inspection->isVerified())
                                                <a href="{{ route('labor.inspections.approval', ['id' => $inspection->id, 'document_type_id' => 0]) }}"
                                                    class="btn btn-sm btn-success" title="Approval">
                                                    <i class="fa fa-check-circle"></i>
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

@section('js')
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

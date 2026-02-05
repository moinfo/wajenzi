@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <i class="fa fa-clipboard-check"></i> Work Logs
            <div class="float-right">
                <a href="{{ route('labor.dashboard') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="fa fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
        </div>

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">All Work Logs</h3>
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
                        <div class="col-md-3">
                            <select name="contract_id" class="form-control">
                                <option value="">All Contracts</option>
                                @foreach($activeContracts as $contract)
                                    <option value="{{ $contract->id }}" {{ $selected_contract == $contract->id ? 'selected' : '' }}>
                                        {{ $contract->contract_number }} - {{ $contract->artisan?->name }}
                                    </option>
                                @endforeach
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
                                <th>Date</th>
                                <th>Contract</th>
                                <th>Artisan</th>
                                <th>Work Done</th>
                                <th class="text-center">Workers</th>
                                <th class="text-center">Hours</th>
                                <th class="text-center">Progress</th>
                                <th>Logged By</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr>
                                    <td class="text-center">{{ $loop->index + 1 }}</td>
                                    <td>{{ $log->log_date->format('Y-m-d') }}</td>
                                    <td>
                                        <a href="{{ route('labor.contracts.show', $log->labor_contract_id) }}">
                                            {{ $log->contract?->contract_number }}
                                        </a>
                                    </td>
                                    <td>{{ $log->contract?->artisan?->name }}</td>
                                    <td>{{ Str::limit($log->work_done, 50) }}</td>
                                    <td class="text-center">{{ $log->workers_present }}</td>
                                    <td class="text-center">{{ $log->hours_worked ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($log->progress_percentage)
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-info" style="width: {{ $log->progress_percentage }}%">
                                                    {{ number_format($log->progress_percentage, 0) }}%
                                                </div>
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $log->logger?->name }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('labor.logs.show', $log->id) }}" class="btn btn-sm btn-info">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        @if($log->log_date->diffInDays(now()) <= 3)
                                            <a href="{{ route('labor.logs.edit', $log->id) }}" class="btn btn-sm btn-warning">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                        @endif
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

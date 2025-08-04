@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">Site Daily Reports</h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item">Projects</li>
                    <li class="breadcrumb-item active" aria-current="page">Site Daily Reports</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    @include('partials.alerts')

    <!-- Filters -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Filters</h3>
        </div>
        <div class="block-content">
            <form method="GET" class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Site</label>
                        <select name="site_id" class="form-control">
                            <option value="">All Sites</option>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                                    {{ $site->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="text" name="start_date" class="form-control datepicker"
                               value="{{ request('start_date') }}"
                               placeholder="dd/mm/yyyy">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="text" name="end_date" class="form-control datepicker"
                               value="{{ request('end_date') }}"
                               placeholder="dd/mm/yyyy">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="DRAFT" {{ request('status') == 'DRAFT' ? 'selected' : '' }}>Draft</option>
                            <option value="PENDING" {{ request('status') == 'PENDING' ? 'selected' : '' }}>Pending</option>
                            <option value="APPROVED" {{ request('status') == 'APPROVED' ? 'selected' : '' }}>Approved</option>
                            <option value="REJECTED" {{ request('status') == 'REJECTED' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Supervisor</label>
                        <select name="supervisor_id" class="form-control">
                            <option value="">All Supervisors</option>
                            @foreach($supervisors as $supervisor)
                                <option value="{{ $supervisor->id }}" {{ request('supervisor_id') == $supervisor->id ? 'selected' : '' }}>
                                    {{ $supervisor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('site-daily-reports.index') }}" class="btn btn-secondary">Clear</a>
                    @can('Add Site Reports')
                        <a href="{{ route('site-daily-reports.create') }}" class="btn btn-success float-right">
                            <i class="fa fa-plus"></i> New Report
                        </a>
                    @endcan
                </div>
            </form>
        </div>
    </div>

    <!-- Reports List -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Reports List ({{ $reports->total() }})</h3>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-vcenter">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Site</th>
                            <th>Supervisor</th>
                            <th>Progress</th>
                            <th>Work Activities</th>
                            <th>Payments</th>
                            <th>Status</th>
                            <th>Approval</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $report)
                            <tr>
                                <td>{{ $report->report_date->format('M d, Y') }}</td>
                                <td>
                                    <strong>{{ $report->site->name }}</strong>
                                    <br><small class="text-muted">{{ $report->site->location }}</small>
                                </td>
                                <td>{{ $report->supervisor->name }}</td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: {{ $report->progress_percentage }}%"
                                             aria-valuenow="{{ $report->progress_percentage }}" 
                                             aria-valuemin="0" aria-valuemax="100">
                                            {{ number_format($report->progress_percentage, 1) }}%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ $report->workActivities->count() }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-primary">{{ $report->payments->count() }}</span>
                                    @if($report->payments->count() > 0)
                                        <br><small>Total: {{ number_format($report->getTotalPayments(), 2) }} TSH</small>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusClass = [
                                            'DRAFT' => 'secondary',
                                            'PENDING' => 'warning',
                                            'APPROVED' => 'success', 
                                            'REJECTED' => 'danger'
                                        ][$report->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge badge-{{ $statusClass }}">{{ $report->status }}</span>
                                </td>
                                <td>
                                    <x-ringlesoft-approval-status-summary :model="$report" />
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a class="btn btn-sm btn-success" href="{{ route('site-daily-reports.show', $report) }}">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        @if($report->canEdit() && (Auth::user()->can('Edit All Site Reports') || Auth::user()->id == $report->prepared_by))
                                            <a class="btn btn-sm btn-primary" href="{{ route('site-daily-reports.edit', $report) }}">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        @endif
                                        @can('Export Site Reports')
                                            <a class="btn btn-sm btn-info" href="{{ route('site-daily-reports.export', $report) }}">
                                                <i class="fa fa-download"></i>
                                            </a>
                                        @endcan
                                        @can('Share Site Reports')
                                            <a class="btn btn-sm btn-success" href="{{ route('site-daily-reports.share', $report) }}" target="_blank">
                                                <i class="fa fa-share"></i>
                                            </a>
                                        @endcan
                                        @if($report->canDelete() && (Auth::user()->can('Delete All Site Reports') || Auth::user()->id == $report->prepared_by))
                                            <form method="POST" action="{{ route('site-daily-reports.destroy', $report) }}" 
                                                  style="display: inline-block;"
                                                  onsubmit="return confirm('Are you sure you want to delete this report?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">No reports found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $reports->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.datepicker').datepicker({
        autoclose: true,
        format: 'dd/mm/yyyy',
        todayHighlight: true,
        defaultViewDate: new Date()
    });
});
</script>
@endsection
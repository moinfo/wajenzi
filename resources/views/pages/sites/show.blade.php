@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">Site Details: {{ $site->name }}</h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item"><a href="{{ route('sites.index') }}">Sites</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $site->name }}</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    @include('partials.alerts')

    <div class="row">
        <!-- Site Information -->
        <div class="col-lg-8">
            <div class="block block-rounded">
                <div class="block-header">
                    <h3 class="block-title">Site Information</h3>
                    <div class="block-options">
                        @can('Edit Sites')
                            <a href="{{ route('sites.edit', $site) }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-pencil"></i> Edit Site
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Name:</strong></td>
                                    <td>{{ $site->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Location:</strong></td>
                                    <td>{{ $site->location }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        @php
                                            $statusClass = [
                                                'ACTIVE' => 'success',
                                                'INACTIVE' => 'warning',
                                                'COMPLETED' => 'primary'
                                            ][$site->status] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-{{ $statusClass }}">{{ $site->status }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Created By:</strong></td>
                                    <td>{{ $site->createdBy->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Created:</strong></td>
                                    <td>{{ $site->created_at->format('M d, Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Start Date:</strong></td>
                                    <td>{{ $site->start_date ? $site->start_date->format('M d, Y') : 'Not set' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Expected End:</strong></td>
                                    <td>{{ $site->expected_end_date ? $site->expected_end_date->format('M d, Y') : 'Not set' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Actual End:</strong></td>
                                    <td>{{ $site->actual_end_date ? $site->actual_end_date->format('M d, Y') : 'Not set' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Current Supervisor:</strong></td>
                                    <td>
                                        @if($site->currentSupervisor)
                                            <i class="fa fa-user"></i> {{ $site->currentSupervisor->name }}
                                        @else
                                            <span class="text-muted">No supervisor assigned</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Progress:</strong></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: {{ $site->getProgressPercentage() }}%"
                                                 aria-valuenow="{{ $site->getProgressPercentage() }}" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                {{ number_format($site->getProgressPercentage(), 1) }}%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    @if($site->description)
                        <div class="row">
                            <div class="col-12">
                                <hr>
                                <h5>Description</h5>
                                <p>{{ $site->description }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Daily Reports -->
            <div class="block block-rounded">
                <div class="block-header">
                    <h3 class="block-title">Recent Daily Reports</h3>
                    <div class="block-options">
                        @can('Add Site Daily Reports')
                            <a href="{{ route('site-daily-reports.create') }}?site_id={{ $site->id }}" class="btn btn-sm btn-success">
                                <i class="fa fa-plus"></i> New Report
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="block-content">
                    @if($recentReports->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-vcenter">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Supervisor</th>
                                        <th>Activities</th>
                                        <th>Progress</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentReports as $report)
                                        <tr>
                                            <td>{{ $report->report_date->format('M d, Y') }}</td>
                                            <td>{{ $report->supervisor->name ?? 'N/A' }}</td>
                                            <td>{{ Str::limit($report->work_activities, 50) }}</td>
                                            <td>{{ $report->progress_percentage }}%</td>
                                            <td>
                                                <a href="{{ route('site-daily-reports.show', $report) }}" class="btn btn-sm btn-primary">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center">
                            <a href="{{ route('site-daily-reports.index') }}?site_id={{ $site->id }}" class="btn btn-secondary">
                                View All Reports
                            </a>
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <p>No daily reports yet.</p>
                            @can('Add Site Daily Reports')
                                <a href="{{ route('site-daily-reports.create') }}?site_id={{ $site->id }}" class="btn btn-success">
                                    <i class="fa fa-plus"></i> Create First Report
                                </a>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Supervisor History -->
            <div class="block block-rounded">
                <div class="block-header">
                    <h3 class="block-title">Supervisor History</h3>
                    <div class="block-options">
                        @can('Add Site Supervisor Assignments')
                            <a href="{{ route('site-supervisor-assignments.create') }}?site_id={{ $site->id }}" class="btn btn-sm btn-success">
                                <i class="fa fa-plus"></i> Assign
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="block-content">
                    @if($site->supervisorAssignments->count() > 0)
                        @foreach($site->supervisorAssignments->sortByDesc('assigned_from') as $assignment)
                            <div class="mb-3 p-2 border rounded">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>{{ $assignment->supervisor->name }}</strong>
                                        @if($assignment->is_active)
                                            <span class="badge badge-success ml-1">Current</span>
                                        @endif
                                        <br>
                                        <small class="text-muted">
                                            Assigned: {{ $assignment->assigned_from ? $assignment->assigned_from->format('M d, Y') : 'N/A' }}
                                            @if($assignment->assigned_to)
                                                <br>Unassigned: {{ $assignment->assigned_to->format('M d, Y') }}
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted">No supervisors assigned yet.</p>
                    @endif
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="block block-rounded">
                <div class="block-header">
                    <h3 class="block-title">Quick Stats</h3>
                </div>
                <div class="block-content">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="mb-3">
                                <div class="h4 mb-0">{{ $site->dailyReports->count() }}</div>
                                <small class="text-muted">Daily Reports</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <div class="h4 mb-0">{{ $site->supervisorAssignments->count() }}</div>
                                <small class="text-muted">Supervisors</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
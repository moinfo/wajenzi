@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">Site Supervisor Assignments</h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item">Projects</li>
                    <li class="breadcrumb-item">Sites</li>
                    <li class="breadcrumb-item active" aria-current="page">Assignments</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    @include('partials.alerts')

    <!-- Unassigned Sites Alert -->
    @if($unassignedSites->count() > 0)
        <div class="alert alert-warning">
            <h5><i class="fa fa-exclamation-triangle"></i> Unassigned Sites</h5>
            <p>The following sites do not have active supervisors:</p>
            <ul class="mb-2">
                @foreach($unassignedSites as $site)
                    <li>{{ $site->name }} ({{ $site->location }})</li>
                @endforeach
            </ul>
            @can('Add Site Assignments')
                <a href="{{ route('site-supervisor-assignments.create') }}" class="btn btn-warning btn-sm">
                    <i class="fa fa-plus"></i> Assign Supervisors
                </a>
            @endcan
        </div>
    @endif

    <!-- Filters -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Filters</h3>
        </div>
        <div class="block-content">
            <form method="GET" class="row">
                <div class="col-md-4">
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
                <div class="col-md-4">
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
                <div class="col-md-4">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ route('site-supervisor-assignments.index') }}" class="btn btn-secondary">Clear</a>
                            @can('Add Site Assignments')
                                <a href="{{ route('site-supervisor-assignments.create') }}" class="btn btn-success float-right">
                                    <i class="fa fa-plus"></i> New Assignment
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Active Assignments -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Active Assignments ({{ $assignments->total() }})</h3>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-vcenter">
                    <thead>
                        <tr>
                            <th>Site</th>
                            <th>Supervisor</th>
                            <th>Assigned From</th>
                            <th>Assigned To</th>
                            <th>Assigned By</th>
                            <th>Duration</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $assignment)
                            <tr>
                                <td>
                                    <strong>{{ $assignment->site->name }}</strong>
                                    <br><small class="text-muted">{{ $assignment->site->location }}</small>
                                </td>
                                <td>
                                    <i class="fa fa-user"></i> {{ $assignment->supervisor->name }}
                                    <br><small class="text-muted">{{ $assignment->supervisor->email }}</small>
                                </td>
                                <td>{{ $assignment->assigned_from->format('M d, Y') }}</td>
                                <td>
                                    @if($assignment->assigned_to)
                                        {{ $assignment->assigned_to->format('M d, Y') }}
                                    @else
                                        <span class="text-muted">Ongoing</span>
                                    @endif
                                </td>
                                <td>{{ $assignment->assignedBy->name }}</td>
                                <td>
                                    @php
                                        $endDate = $assignment->assigned_to ?? now();
                                        $duration = $assignment->assigned_from->diffInDays($endDate);
                                    @endphp
                                    {{ $duration }} day{{ $duration != 1 ? 's' : '' }}
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a class="btn btn-sm btn-success" href="{{ route('sites.show', $assignment->site) }}">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        @can('Edit Site Assignments')
                                            <a class="btn btn-sm btn-primary" href="{{ route('site-supervisor-assignments.edit', $assignment) }}">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        @endcan
                                        @can('Delete Site Assignments')
                                            <form method="POST" action="{{ route('site-supervisor-assignments.destroy', $assignment) }}" 
                                                  style="display: inline-block;"
                                                  onsubmit="return confirm('Are you sure you want to end this assignment?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-warning" title="End Assignment">
                                                    <i class="fa fa-stop"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No active assignments found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $assignments->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">Assignment History: {{ $site->name }}</h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item"><a href="{{ route('sites.index') }}">Sites</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('sites.show', $site) }}">{{ $site->name }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Assignment History</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    @include('partials.alerts')

    <!-- Site Info -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Site Information</h3>
            <div class="block-options">
                <a href="{{ route('sites.show', $site) }}" class="btn btn-sm btn-primary">
                    <i class="fa fa-eye"></i> View Site
                </a>
                @can('Add Site Assignments')
                    @if(!$site->hasActiveSupervisor())
                        <a href="{{ route('site-supervisor-assignments.create') }}?site_id={{ $site->id }}" class="btn btn-sm btn-success">
                            <i class="fa fa-plus"></i> Assign Supervisor
                        </a>
                    @endif
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
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Current Supervisor:</strong></td>
                            <td>
                                @if($site->currentSupervisor)
                                    <i class="fa fa-user"></i> {{ $site->currentSupervisor->name }}
                                    <span class="badge badge-success ml-1">Active</span>
                                @else
                                    <span class="text-muted">No active supervisor</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Total Assignments:</strong></td>
                            <td>{{ $assignments->total() }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Assignment History -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Assignment History ({{ $assignments->total() }})</h3>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-vcenter">
                    <thead>
                        <tr>
                            <th>Supervisor</th>
                            <th>Assigned From</th>
                            <th>Assigned To</th>
                            <th>Duration</th>
                            <th>Assigned By</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $assignment)
                            <tr class="{{ $assignment->is_active ? 'table-success' : '' }}">
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
                                <td>
                                    @php
                                        $endDate = $assignment->assigned_to ?? now();
                                        $duration = $assignment->assigned_from->diffInDays($endDate);
                                    @endphp
                                    {{ $duration }} day{{ $duration != 1 ? 's' : '' }}
                                </td>
                                <td>{{ $assignment->assignedBy->name }}</td>
                                <td>
                                    @if($assignment->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Ended</span>
                                    @endif
                                </td>
                                <td>
                                    @if($assignment->notes)
                                        {{ Str::limit($assignment->notes, 50) }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($assignment->is_active)
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
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No assignment history found</td>
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
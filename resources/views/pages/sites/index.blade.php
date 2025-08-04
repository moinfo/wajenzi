@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">Sites Management</h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item">Projects</li>
                    <li class="breadcrumb-item active" aria-current="page">Sites</li>
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
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" name="search" class="form-control" 
                               value="{{ request('search') }}" 
                               placeholder="Search by name or location">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="ACTIVE" {{ request('status') == 'ACTIVE' ? 'selected' : '' }}>Active</option>
                            <option value="INACTIVE" {{ request('status') == 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                            <option value="COMPLETED" {{ request('status') == 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ route('sites.index') }}" class="btn btn-secondary">Clear</a>
                            @can('Add Sites')
                                <a href="{{ route('sites.create') }}" class="btn btn-success float-right">
                                    <i class="fa fa-plus"></i> New Site
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Sites List -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Sites List ({{ $sites->total() }})</h3>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-vcenter">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Current Supervisor</th>
                            <th>Progress</th>
                            <th>Created</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sites as $site)
                            <tr>
                                <td>
                                    <strong>{{ $site->name }}</strong>
                                    @if($site->description)
                                        <br><small class="text-muted">{{ Str::limit($site->description, 50) }}</small>
                                    @endif
                                </td>
                                <td>{{ $site->location }}</td>
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
                                <td>
                                    @if($site->currentSupervisor)
                                        <i class="fa fa-user"></i> {{ $site->currentSupervisor->name }}
                                    @else
                                        <span class="text-muted">No supervisor assigned</span>
                                    @endif
                                </td>
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
                                <td>
                                    {{ $site->created_at->format('M d, Y') }}
                                    <br><small class="text-muted">by {{ $site->createdBy->name }}</small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a class="btn btn-sm btn-success" href="{{ route('sites.show', $site) }}">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        @can('Edit Sites')
                                            <a class="btn btn-sm btn-primary" href="{{ route('sites.edit', $site) }}">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        @endcan
                                        @can('Delete Sites')
                                            @if($site->canDelete())
                                                <form method="POST" action="{{ route('sites.destroy', $site) }}" 
                                                      style="display: inline-block;"
                                                      onsubmit="return confirm('Are you sure you want to delete this site?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No sites found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $sites->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
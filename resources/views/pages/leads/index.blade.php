@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">Lead Management</h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item">Projects</li>
                    <li class="breadcrumb-item active" aria-current="page">Leads</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Filters -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Filters</h3>
        </div>
        <div class="block-content">
            <form method="GET" class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" name="search" class="form-control" 
                               value="{{ request('search') }}" 
                               placeholder="Search by name, email, or phone">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="converted" {{ request('status') == 'converted' ? 'selected' : '' }}>Converted</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Source</label>
                        <select name="client_source_id" class="form-control">
                            <option value="">All Sources</option>
                            @foreach($clientSources as $source)
                                <option value="{{ $source->id }}" {{ request('client_source_id') == $source->id ? 'selected' : '' }}>
                                    {{ $source->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label style="visibility: hidden;">Action</label>
                        <div>
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ route('leads.index') }}" class="btn btn-secondary">Clear</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Leads List -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Leads List</h3>
            <div class="block-options">
                <a href="{{ route('leads.create') }}" class="btn btn-success">
                    <i class="fa fa-plus"></i> New Lead
                </a>
            </div>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-vcenter">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leads as $lead)
                            <tr>
                                <td><strong>{{ $lead->name }}</strong></td>
                                <td>{{ $lead->email }}</td>
                                <td>{{ $lead->phone ?: '-' }}</td>
                                <td>
                                    @if($lead->clientSource)
                                        <span class="badge badge-info">{{ $lead->clientSource->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($lead->status == 'active')
                                        <span class="badge badge-success">Active</span>
                                    @elseif($lead->status == 'converted')
                                        <span class="badge badge-primary">Converted</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $lead->createdBy->name }}</td>
                                <td>{{ $lead->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown">
                                            Actions
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="{{ route('leads.show', $lead->id) }}">
                                                <i class="fa fa-eye"></i> View
                                            </a>
                                            <a class="dropdown-item" href="{{ route('leads.edit', $lead->id) }}">
                                                <i class="fa fa-edit"></i> Edit
                                            </a>
                                            <form method="POST" action="{{ route('leads.destroy', $lead->id) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Delete this lead?')">
                                                    <i class="fa fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No leads found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $leads->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

@endsection
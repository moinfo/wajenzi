@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">Field Territories</h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item">Field Marketing</li>
                    <li class="breadcrumb-item active" aria-current="page">Territories</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div>
    @endif

    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Territories <small class="text-muted">({{ $territories->total() }} total)</small></h3>
            <div class="block-options">
                <a href="{{ route('field_marketing.territories.create') }}" class="btn btn-success btn-sm">
                    <i class="fa fa-plus mr-1"></i> New Territory
                </a>
            </div>
        </div>
        <div class="block-content block-content-full">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search name or region...">
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button class="btn btn-secondary" type="submit"><i class="fa fa-search"></i></button>
                        @if(request()->hasAny(['search','status']))
                            <a href="{{ route('field_marketing.territories.index') }}" class="btn btn-outline-secondary ml-1">Clear</a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover table-vcenter">
                    <thead>
                        <tr>
                            <th>Territory</th>
                            <th>Region</th>
                            <th>Assigned Agent</th>
                            <th class="text-center">Campaigns</th>
                            <th class="text-center">Activities</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($territories as $territory)
                        <tr>
                            <td class="font-w600">{{ $territory->name }}</td>
                            <td>{{ $territory->region ?? '—' }}</td>
                            <td>{{ $territory->assignedUser?->name ?? '—' }}</td>
                            <td class="text-center">{{ $territory->campaigns->count() }}</td>
                            <td class="text-center">{{ $territory->activities->count() }}</td>
                            <td class="text-center">
                                <span class="badge badge-{{ $territory->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($territory->status) }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="btn-group">
                                    <a href="{{ route('field_marketing.territories.edit', $territory->id) }}" class="btn btn-sm btn-alt-secondary" title="Edit">
                                        <i class="fa fa-pencil-alt"></i>
                                    </a>
                                    <form action="{{ route('field_marketing.territories.destroy', $territory->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-alt-danger" onclick="return confirm('Delete this territory?')" title="Delete">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fa fa-map-marker-alt fa-2x mb-2 d-block"></i>
                                No territories found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($territories->hasPages())
            <div class="row align-items-center mt-3">
                <div class="col-sm-5 text-muted">Showing {{ $territories->firstItem() }}–{{ $territories->lastItem() }} of {{ $territories->total() }}</div>
                <div class="col-sm-7"><nav class="d-flex justify-content-end">{{ $territories->appends(request()->query())->links() }}</nav></div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

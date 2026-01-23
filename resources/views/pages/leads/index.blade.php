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
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <!-- Filters -->
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Filters</h3>
        </div>
        <div class="block-content">
            <form method="GET" class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" name="search" class="form-control"
                               value="{{ request('search') }}"
                               placeholder="Name, ID, Phone, City">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Lead Status</label>
                        <select name="lead_status_id" class="form-control">
                            <option value="">All Statuses</option>
                            @foreach($leadStatuses as $status)
                                <option value="{{ $status->id }}" {{ request('lead_status_id') == $status->id ? 'selected' : '' }}>
                                    {{ $status->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Lead Source</label>
                        <select name="lead_source_id" class="form-control">
                            <option value="">All Sources</option>
                            @foreach($leadSources as $source)
                                <option value="{{ $source->id }}" {{ request('lead_source_id') == $source->id ? 'selected' : '' }}>
                                    {{ $source->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Salesperson</label>
                        <select name="salesperson_id" class="form-control">
                            <option value="">All Salespeople</option>
                            @foreach($salespeople as $person)
                                <option value="{{ $person->id }}" {{ request('salesperson_id') == $person->id ? 'selected' : '' }}>
                                    {{ $person->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
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
        <div class="block-header block-header-default">
            <h3 class="block-title">Leads List</h3>
            <div class="block-options">
                <a href="{{ route('leads.create') }}" class="btn btn-success btn-sm">
                    <i class="fa fa-plus"></i> New Lead
                </a>
            </div>
        </div>
        <div class="block-content block-content-full">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                    <thead>
                        <tr>
                            <th>Lead ID</th>
                            <th>Lead Date</th>
                            <th>Client Name</th>
                            <th>Phone</th>
                            <th>Lead Source</th>
                            <th>Service Interested</th>
                            <th>Site Location</th>
                            <th>Est. Value (TZS)</th>
                            <th>Lead Status</th>
                            <th>City</th>
                            <th>Salesperson</th>
                            <th>Followup Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leads as $lead)
                            <tr>
                                <td><strong>{{ $lead->lead_number }}</strong></td>
                                <td>{{ $lead->lead_date ? $lead->lead_date->format('d-M-Y') : '-' }}</td>
                                <td>{{ $lead->name }}</td>
                                <td>{{ $lead->phone ?: '-' }}</td>
                                <td>
                                    @if($lead->leadSource)
                                        <span class="badge badge-info">{{ $lead->leadSource->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $lead->serviceInterested->name ?? '-' }}</td>
                                <td>{{ $lead->site_location ?: '-' }}</td>
                                <td class="text-right">{{ $lead->estimated_value ? number_format($lead->estimated_value) : '-' }}</td>
                                <td>
                                    @if($lead->leadStatus)
                                        @php
                                            $statusClass = match(strtolower($lead->leadStatus->name)) {
                                                'won' => 'badge-success',
                                                'lost' => 'badge-danger',
                                                'proposal sent' => 'badge-warning',
                                                'new' => 'badge-primary',
                                                default => 'badge-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">{{ $lead->leadStatus->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $lead->city ?: '-' }}</td>
                                <td>{{ $lead->salesperson->name ?? '-' }}</td>
                                <td>
                                    @if($lead->latestFollowup && $lead->latestFollowup->followup_date)
                                        {{ $lead->latestFollowup->followup_date->format('d-M-Y') }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="{{ route('leads.show', $lead->id) }}" class="btn btn-sm btn-alt-secondary" title="View">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="{{ route('leads.edit', $lead->id) }}" class="btn btn-sm btn-alt-secondary" title="Edit">
                                            <i class="fa fa-pencil-alt"></i>
                                        </a>
                                        <form method="POST" action="{{ route('leads.destroy', $lead->id) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-alt-danger" title="Delete" onclick="return confirm('Delete this lead?')">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center">No leads found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($leads->hasPages())
            <div class="d-flex justify-content-center mt-3">
                {{ $leads->appends(request()->query())->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

@endsection

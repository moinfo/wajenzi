@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">Field Activities</h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item">Field Marketing</li>
                    <li class="breadcrumb-item active" aria-current="page">Activities</li>
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
            <h3 class="block-title">Activities <small class="text-muted">({{ $activities->total() }} total)</small></h3>
            <div class="block-options">
                <a href="{{ route('field_marketing.activities.create') }}" class="btn btn-success btn-sm">
                    <i class="fa fa-plus mr-1"></i> Log Activity
                </a>
            </div>
        </div>
        <div class="block-content block-content-full">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search by number or location...">
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="campaign_id" class="form-control" onchange="this.form.submit()">
                            <option value="">All Campaigns</option>
                            @foreach($campaigns as $c)
                                <option value="{{ $c->id }}" {{ request('campaign_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="outcome" class="form-control" onchange="this.form.submit()">
                            <option value="">All Outcomes</option>
                            @foreach(\App\Models\FieldActivity::outcomeLabels() as $val => $label)
                                <option value="{{ $val }}" {{ request('outcome') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="planned" {{ request('status') == 'planned' ? 'selected' : '' }}>Planned</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button class="btn btn-secondary" type="submit"><i class="fa fa-search"></i></button>
                        @if(request()->hasAny(['search','campaign_id','outcome','status','agent_id']))
                            <a href="{{ route('field_marketing.activities.index') }}" class="btn btn-outline-secondary ml-1">Clear</a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover table-vcenter">
                    <thead>
                        <tr>
                            <th>Activity #</th>
                            <th>Date</th>
                            <th>Agent</th>
                            <th>Campaign</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th class="text-center">Leads</th>
                            <th class="text-center">Outcome</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $activity)
                        <tr>
                            <td class="font-w600">{{ $activity->activity_number }}</td>
                            <td>{{ $activity->activity_date->format('d M Y') }}</td>
                            <td>{{ $activity->agent?->name }}</td>
                            <td>{{ $activity->campaign?->name ?? '—' }}</td>
                            <td class="text-capitalize">{{ str_replace('_',' ',$activity->activity_type) }}</td>
                            <td>{{ $activity->location ?? '—' }}</td>
                            <td class="text-center">{{ $activity->leads_count }}</td>
                            <td class="text-center">
                                <span class="badge badge-{{ $activity->outcome_badge_class }}">
                                    {{ str_replace('_',' ', ucfirst($activity->outcome)) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @php $sc = ['planned'=>'info','completed'=>'success','cancelled'=>'secondary'][$activity->status] ?? 'light'; @endphp
                                <span class="badge badge-{{ $sc }}">{{ ucfirst($activity->status) }}</span>
                            </td>
                            <td class="text-right text-nowrap">
                                <a href="{{ route('field_marketing.activities.show', $activity->id) }}" class="btn btn-sm btn-alt-primary"><i class="fa fa-eye"></i></a>
                                <a href="{{ route('field_marketing.activities.edit', $activity->id) }}" class="btn btn-sm btn-alt-secondary"><i class="fa fa-pencil-alt"></i></a>
                                <form action="{{ route('field_marketing.activities.destroy', $activity->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-alt-danger" onclick="return confirm('Delete this activity?')"><i class="fa fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="fa fa-walking fa-2x mb-2 d-block"></i>No activities found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($activities->hasPages())
            <div class="row align-items-center mt-3">
                <div class="col-sm-5 text-muted">Showing {{ $activities->firstItem() }}–{{ $activities->lastItem() }} of {{ $activities->total() }}</div>
                <div class="col-sm-7"><nav class="d-flex justify-content-end">{{ $activities->appends(request()->query())->links() }}</nav></div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

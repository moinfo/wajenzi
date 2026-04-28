@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">Field Marketing Campaigns</h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item">Field Marketing</li>
                    <li class="breadcrumb-item active" aria-current="page">Campaigns</li>
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
            <h3 class="block-title">Campaigns <small class="text-muted">({{ $campaigns->total() }} total)</small></h3>
            <div class="block-options">
                <a href="{{ route('field_marketing.campaigns.create') }}" class="btn btn-success btn-sm">
                    <i class="fa fa-plus mr-1"></i> New Campaign
                </a>
            </div>
        </div>
        <div class="block-content block-content-full">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search campaign...">
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            @foreach(['draft','active','completed','cancelled'] as $s)
                                <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <select name="territory_id" class="form-control" onchange="this.form.submit()">
                            <option value="">All Territories</option>
                            @foreach($territories as $t)
                                <option value="{{ $t->id }}" {{ request('territory_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button class="btn btn-secondary" type="submit"><i class="fa fa-search"></i></button>
                        @if(request()->hasAny(['search','status','territory_id']))
                            <a href="{{ route('field_marketing.campaigns.index') }}" class="btn btn-outline-secondary ml-1">Clear</a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover table-vcenter">
                    <thead>
                        <tr>
                            <th>Campaign #</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Territory</th>
                            <th>Dates</th>
                            <th class="text-center">Budget</th>
                            <th class="text-center">Leads Progress</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($campaigns as $campaign)
                        @php
                            $leadsCount = $campaign->leads()->count();
                            $progress = $campaign->target_leads ? min(100, round(($leadsCount / $campaign->target_leads) * 100)) : 0;
                        @endphp
                        <tr>
                            <td class="font-w600 text-nowrap">{{ $campaign->campaign_number }}</td>
                            <td>
                                <a href="{{ route('field_marketing.campaigns.show', $campaign->id) }}" class="font-w600">
                                    {{ $campaign->name }}
                                </a>
                            </td>
                            <td><span class="badge badge-light text-capitalize">{{ str_replace('_', ' ', $campaign->campaign_type) }}</span></td>
                            <td>{{ $campaign->territory?->name ?? '—' }}</td>
                            <td class="text-nowrap">
                                {{ $campaign->start_date->format('d M Y') }}
                                @if($campaign->end_date) <br><small class="text-muted">→ {{ $campaign->end_date->format('d M Y') }}</small> @endif
                            </td>
                            <td class="text-center">{{ number_format($campaign->budget) }}</td>
                            <td class="text-center" style="min-width:140px">
                                <div class="d-flex align-items-center justify-content-center">
                                    <div class="progress w-75 mr-2" style="height:6px">
                                        <div class="progress-bar bg-{{ $progress >= 100 ? 'success' : 'primary' }}" style="width:{{ $progress }}%"></div>
                                    </div>
                                    <small>{{ $leadsCount }}/{{ $campaign->target_leads }}</small>
                                </div>
                            </td>
                            <td class="text-center">
                                @php
                                    $statusColor = ['draft'=>'secondary','active'=>'success','completed'=>'info','cancelled'=>'danger'][$campaign->status] ?? 'light';
                                @endphp
                                <span class="badge badge-{{ $statusColor }}">{{ ucfirst($campaign->status) }}</span>
                            </td>
                            <td class="text-right text-nowrap">
                                <a href="{{ route('field_marketing.campaigns.show', $campaign->id) }}" class="btn btn-sm btn-alt-primary" title="View"><i class="fa fa-eye"></i></a>
                                <a href="{{ route('field_marketing.campaigns.edit', $campaign->id) }}" class="btn btn-sm btn-alt-secondary" title="Edit"><i class="fa fa-pencil-alt"></i></a>
                                <form action="{{ route('field_marketing.campaigns.destroy', $campaign->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-alt-danger" onclick="return confirm('Delete this campaign?')"><i class="fa fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="fa fa-bullhorn fa-2x mb-2 d-block"></i>No campaigns found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($campaigns->hasPages())
            <div class="row align-items-center mt-3">
                <div class="col-sm-5 text-muted">Showing {{ $campaigns->firstItem() }}–{{ $campaigns->lastItem() }} of {{ $campaigns->total() }}</div>
                <div class="col-sm-7"><nav class="d-flex justify-content-end">{{ $campaigns->appends(request()->query())->links() }}</nav></div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

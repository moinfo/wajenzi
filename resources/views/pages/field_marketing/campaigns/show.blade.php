@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">
                {{ $campaign->name }}
                <small class="text-muted ml-2">{{ $campaign->campaign_number }}</small>
            </h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item"><a href="{{ route('field_marketing.campaigns.index') }}">Campaigns</a></li>
                    <li class="breadcrumb-item active">{{ $campaign->name }}</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div>
    @endif

    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-6 col-md-3">
            <div class="block block-rounded text-center">
                <div class="block-content py-3">
                    <div class="font-size-h2 font-w700 text-primary">{{ $leadsTotal }}</div>
                    <div class="text-muted">Leads Generated</div>
                    @if($campaign->target_leads)
                    <div class="progress mt-2" style="height:4px">
                        <div class="progress-bar bg-primary" style="width:{{ min(100, round($leadsTotal / $campaign->target_leads * 100)) }}%"></div>
                    </div>
                    <small class="text-muted">Target: {{ $campaign->target_leads }}</small>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="block block-rounded text-center">
                <div class="block-content py-3">
                    <div class="font-size-h2 font-w700 text-success">{{ $campaign->activities->count() }}</div>
                    <div class="text-muted">Activities</div>
                    <small class="text-muted">{{ $campaign->activities->where('status','completed')->count() }} completed</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="block block-rounded text-center">
                <div class="block-content py-3">
                    <div class="font-size-h2 font-w700 text-warning">{{ number_format($campaign->budget) }}</div>
                    <div class="text-muted">Budget (TZS)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="block block-rounded text-center">
                <div class="block-content py-3">
                    @php $statusColor = ['draft'=>'secondary','active'=>'success','completed'=>'info','cancelled'=>'danger'][$campaign->status] ?? 'light'; @endphp
                    <div class="font-size-h2 font-w700">
                        <span class="badge badge-{{ $statusColor }} font-size-base px-3 py-2">{{ ucfirst($campaign->status) }}</span>
                    </div>
                    <div class="text-muted mt-1">
                        {{ $campaign->start_date->format('d M') }}
                        @if($campaign->end_date) – {{ $campaign->end_date->format('d M Y') }} @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Campaign Info -->
        <div class="col-md-4">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Campaign Info</h3>
                    <div class="block-options">
                        <a href="{{ route('field_marketing.campaigns.edit', $campaign->id) }}" class="btn btn-sm btn-alt-secondary">
                            <i class="fa fa-pencil-alt mr-1"></i> Edit
                        </a>
                    </div>
                </div>
                <div class="block-content">
                    <table class="table table-borderless table-sm">
                        <tr><td class="text-muted font-w500">Type</td><td class="text-capitalize">{{ str_replace('_',' ',$campaign->campaign_type) }}</td></tr>
                        <tr><td class="text-muted font-w500">Territory</td><td>{{ $campaign->territory?->name ?? '—' }}</td></tr>
                        <tr><td class="text-muted font-w500">Lead Source</td><td>{{ $campaign->leadSource?->name ?? '—' }}</td></tr>
                        <tr><td class="text-muted font-w500">Start Date</td><td>{{ $campaign->start_date->format('d M Y') }}</td></tr>
                        <tr><td class="text-muted font-w500">End Date</td><td>{{ $campaign->end_date?->format('d M Y') ?? '—' }}</td></tr>
                        <tr><td class="text-muted font-w500">Created By</td><td>{{ $campaign->createdBy?->name ?? '—' }}</td></tr>
                    </table>
                    @if($campaign->description)
                        <p class="text-muted mt-2">{{ $campaign->description }}</p>
                    @endif
                    @if($campaign->notes)
                        <div class="alert alert-light mt-2 p-2"><small>{{ $campaign->notes }}</small></div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Activities -->
        <div class="col-md-8">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Activities <small class="text-muted">({{ $campaign->activities->count() }})</small></h3>
                    <div class="block-options">
                        <a href="{{ route('field_marketing.activities.create', ['campaign_id' => $campaign->id]) }}" class="btn btn-sm btn-success">
                            <i class="fa fa-plus mr-1"></i> Log Activity
                        </a>
                    </div>
                </div>
                <div class="block-content block-content-full">
                    @if($campaign->activities->isEmpty())
                        <p class="text-muted text-center py-3">No activities yet. <a href="{{ route('field_marketing.activities.create', ['campaign_id' => $campaign->id]) }}">Log the first one</a>.</p>
                    @else
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Activity #</th>
                                    <th>Date</th>
                                    <th>Agent</th>
                                    <th>Type</th>
                                    <th class="text-center">Leads</th>
                                    <th class="text-center">Outcome</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($campaign->activities->sortByDesc('activity_date') as $activity)
                                <tr>
                                    <td class="font-w600">{{ $activity->activity_number }}</td>
                                    <td>{{ $activity->activity_date->format('d M Y') }}</td>
                                    <td>{{ $activity->agent?->name }}</td>
                                    <td class="text-capitalize">{{ str_replace('_',' ',$activity->activity_type) }}</td>
                                    <td class="text-center">{{ $activity->leads->count() }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $activity->outcome_badge_class }}">
                                            {{ str_replace('_',' ', ucfirst($activity->outcome)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('field_marketing.activities.show', $activity->id) }}" class="btn btn-xs btn-alt-primary">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Leads generated -->
            @if($leadsTotal > 0)
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Leads Generated <small class="text-muted">({{ $leadsTotal }})</small></h3>
                </div>
                <div class="block-content block-content-full">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr><th>Lead #</th><th>Name</th><th>Phone</th><th>Status</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                @foreach($campaign->leads()->with(['leadStatus'])->orderBy('lead_date','desc')->get() as $lead)
                                <tr>
                                    <td><a href="{{ route('leads.show', $lead->id) }}">{{ $lead->lead_number }}</a></td>
                                    <td>{{ $lead->name }}</td>
                                    <td>{{ $lead->phone }}</td>
                                    <td><span class="badge badge-light">{{ $lead->leadStatus?->name }}</span></td>
                                    <td>{{ $lead->lead_date->format('d M Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

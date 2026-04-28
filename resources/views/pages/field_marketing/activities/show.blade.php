@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">
                Field Activity
                <small class="text-muted ml-2">{{ $activity->activity_number }}</small>
            </h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item"><a href="{{ route('field_marketing.activities.index') }}">Activities</a></li>
                    <li class="breadcrumb-item active">{{ $activity->activity_number }}</li>
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

    <div class="row">
        <!-- Activity Details -->
        <div class="col-md-5">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Activity Details</h3>
                    <div class="block-options">
                        <a href="{{ route('field_marketing.activities.edit', $activity->id) }}" class="btn btn-sm btn-alt-secondary">
                            <i class="fa fa-pencil-alt mr-1"></i> Edit
                        </a>
                    </div>
                </div>
                <div class="block-content">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td class="text-muted font-w500" width="40%">Agent</td>
                            <td class="font-w600">{{ $activity->agent?->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted font-w500">Date</td>
                            <td>{{ $activity->activity_date->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted font-w500">Type</td>
                            <td class="text-capitalize">{{ str_replace('_',' ',$activity->activity_type) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted font-w500">Location</td>
                            <td>{{ $activity->location ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted font-w500">Campaign</td>
                            <td>
                                @if($activity->campaign)
                                    <a href="{{ route('field_marketing.campaigns.show', $activity->campaign->id) }}">
                                        {{ $activity->campaign->name }}
                                    </a>
                                @else —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted font-w500">Territory</td>
                            <td>{{ $activity->territory?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted font-w500">Outcome</td>
                            <td>
                                <span class="badge badge-{{ $activity->outcome_badge_class }}">
                                    {{ str_replace('_',' ', ucfirst($activity->outcome)) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted font-w500">Status</td>
                            <td>
                                @php $sc = ['planned'=>'info','completed'=>'success','cancelled'=>'secondary'][$activity->status] ?? 'light'; @endphp
                                <span class="badge badge-{{ $sc }}">{{ ucfirst($activity->status) }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted font-w500">Leads Count</td>
                            <td class="font-w600 text-primary">{{ $activity->leads_count }}</td>
                        </tr>
                    </table>
                    @if($activity->description)
                        <div class="border-top pt-3 mt-2">
                            <p class="font-w500 mb-1">Description</p>
                            <p class="text-muted">{{ $activity->description }}</p>
                        </div>
                    @endif
                    @if($activity->notes)
                        <div class="border-top pt-3 mt-2">
                            <p class="font-w500 mb-1">Notes</p>
                            <p class="text-muted">{{ $activity->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Leads panel -->
        <div class="col-md-7">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Leads from this Activity <small class="text-muted">({{ $activity->leads->count() }})</small></h3>
                </div>
                <div class="block-content">
                    @if($activity->leads->isNotEmpty())
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr><th>Lead #</th><th>Name</th><th>Phone</th><th>Status</th><th>Date</th><th></th></tr>
                            </thead>
                            <tbody>
                                @foreach($activity->leads as $lead)
                                <tr>
                                    <td><a href="{{ route('leads.show', $lead->id) }}" class="font-w600">{{ $lead->lead_number }}</a></td>
                                    <td>{{ $lead->name }}</td>
                                    <td>{{ $lead->phone }}</td>
                                    <td><span class="badge badge-light">{{ $lead->leadStatus?->name }}</span></td>
                                    <td>{{ $lead->lead_date->format('d M Y') }}</td>
                                    <td><a href="{{ route('leads.show', $lead->id) }}" class="btn btn-xs btn-alt-primary"><i class="fa fa-eye"></i></a></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    <!-- Quick Lead Create Form -->
                    <div class="border-top pt-3">
                        <h5 class="mb-3"><i class="fa fa-user-plus mr-1 text-success"></i> Add Lead from this Activity</h5>
                        <form action="{{ route('field_marketing.activities.lead.store', $activity->id) }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Full Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                               value="{{ old('name') }}" required>
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Phone <span class="text-danger">*</span></label>
                                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                               value="{{ old('phone') }}" required>
                                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Service Interested In <span class="text-danger">*</span></label>
                                        <select name="service_interested_id" class="form-control" required>
                                            <option value="">— Select —</option>
                                            @foreach(\App\Models\ServiceInterested::all() as $si)
                                                <option value="{{ $si->id }}" {{ old('service_interested_id') == $si->id ? 'selected' : '' }}>{{ $si->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Lead Status <span class="text-danger">*</span></label>
                                        <select name="lead_status_id" class="form-control" required>
                                            <option value="">— Select —</option>
                                            @foreach($leadStatuses as $ls)
                                                <option value="{{ $ls->id }}" {{ old('lead_status_id') == $ls->id ? 'selected' : '' }}>{{ $ls->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Assign Salesperson <span class="text-danger">*</span></label>
                                        <select name="salesperson_id" class="form-control" required>
                                            <option value="">— Select —</option>
                                            @foreach($agents as $agent)
                                                <option value="{{ $agent->id }}"
                                                    {{ old('salesperson_id', $activity->agent_id) == $agent->id ? 'selected' : '' }}>
                                                    {{ $agent->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Notes</label>
                                        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-plus mr-1"></i> Create Lead
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

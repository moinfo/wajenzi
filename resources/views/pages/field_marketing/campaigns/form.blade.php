@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">{{ $object->id ? 'Edit Campaign' : 'New Campaign' }}</h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item"><a href="{{ route('field_marketing.campaigns.index') }}">Campaigns</a></li>
                    <li class="breadcrumb-item active">{{ $object->id ? 'Edit' : 'Create' }}</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Campaign Details</h3>
            @if($object->id)
                <div class="block-options">
                    <span class="text-muted small">{{ $object->campaign_number }}</span>
                </div>
            @endif
        </div>
        <div class="block-content">
            <form action="{{ $object->id ? route('field_marketing.campaigns.update', $object->id) : route('field_marketing.campaigns.store') }}" method="POST">
                @csrf
                @if($object->id) @method('PUT') @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Campaign Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $object->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Campaign Type <span class="text-danger">*</span></label>
                            <select name="campaign_type" class="form-control @error('campaign_type') is-invalid @enderror" required>
                                @foreach(['outdoor'=>'Outdoor','event'=>'Event','canvassing'=>'Canvassing','demo'=>'Demo / Show House','referral'=>'Referral Drive','other'=>'Other'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('campaign_type', $object->campaign_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('campaign_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                                   value="{{ old('start_date', $object->start_date?->format('Y-m-d')) }}" required>
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror"
                                   value="{{ old('end_date', $object->end_date?->format('Y-m-d')) }}">
                            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Budget (TZS)</label>
                            <input type="number" name="budget" class="form-control" min="0" step="1000"
                                   value="{{ old('budget', $object->budget) }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Target Leads</label>
                            <input type="number" name="target_leads" class="form-control" min="0"
                                   value="{{ old('target_leads', $object->target_leads) }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Territory</label>
                            <select name="territory_id" class="form-control">
                                <option value="">— No Territory —</option>
                                @foreach($territories as $t)
                                    <option value="{{ $t->id }}" {{ old('territory_id', $object->territory_id) == $t->id ? 'selected' : '' }}>
                                        {{ $t->name }}{{ $t->region ? ' ('.$t->region.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Lead Source</label>
                            <select name="lead_source_id" class="form-control">
                                <option value="">— Select Source —</option>
                                @foreach($leadSources as $src)
                                    <option value="{{ $src->id }}" {{ old('lead_source_id', $object->lead_source_id) == $src->id ? 'selected' : '' }}>
                                        {{ $src->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Leads created from activities in this campaign inherit this source.</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-control" required>
                                @foreach(['draft'=>'Draft','active'=>'Active','completed'=>'Completed','cancelled'=>'Cancelled'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('status', $object->status ?? 'draft') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $object->description) }}</textarea>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $object->notes) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save mr-1"></i> {{ $object->id ? 'Update' : 'Create' }} Campaign
                    </button>
                    <a href="{{ route('field_marketing.campaigns.index') }}" class="btn btn-secondary ml-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

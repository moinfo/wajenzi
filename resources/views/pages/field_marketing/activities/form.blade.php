@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">{{ $object->id ? 'Edit Activity' : 'Log Field Activity' }}</h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item"><a href="{{ route('field_marketing.activities.index') }}">Activities</a></li>
                    <li class="breadcrumb-item active">{{ $object->id ? 'Edit' : 'Log' }}</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Activity Details</h3>
            @if($object->id)
                <div class="block-options"><span class="text-muted small">{{ $object->activity_number }}</span></div>
            @endif
        </div>
        <div class="block-content">
            <form action="{{ $object->id ? route('field_marketing.activities.update', $object->id) : route('field_marketing.activities.store') }}" method="POST">
                @csrf
                @if($object->id) @method('PUT') @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Campaign</label>
                            <select name="campaign_id" class="form-control">
                                <option value="">— No Campaign —</option>
                                @foreach($campaigns as $c)
                                    <option value="{{ $c->id }}"
                                        {{ old('campaign_id', $object->campaign_id ?? $preselected_campaign_id) == $c->id ? 'selected' : '' }}>
                                        {{ $c->name }} ({{ $c->campaign_number }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
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
                            <label>Activity Type <span class="text-danger">*</span></label>
                            <select name="activity_type" class="form-control" required>
                                @foreach(\App\Models\FieldActivity::activityTypeLabels() as $val => $label)
                                    <option value="{{ $val }}" {{ old('activity_type', $object->activity_type ?? 'visit') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Field Agent <span class="text-danger">*</span></label>
                            <select name="agent_id" class="form-control" required>
                                <option value="">— Select Agent —</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" {{ old('agent_id', $object->agent_id) == $agent->id ? 'selected' : '' }}>
                                        {{ $agent->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Activity Date <span class="text-danger">*</span></label>
                            <input type="date" name="activity_date" class="form-control @error('activity_date') is-invalid @enderror"
                                   value="{{ old('activity_date', $object->activity_date?->format('Y-m-d') ?? date('Y-m-d')) }}" required>
                            @error('activity_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Location / Address</label>
                            <input type="text" name="location" class="form-control"
                                   value="{{ old('location', $object->location) }}" placeholder="e.g. Kimara, Dar es Salaam">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Outcome <span class="text-danger">*</span></label>
                            <select name="outcome" class="form-control" required>
                                @foreach(\App\Models\FieldActivity::outcomeLabels() as $val => $label)
                                    <option value="{{ $val }}" {{ old('outcome', $object->outcome ?? 'pending') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-control" required>
                                <option value="planned" {{ old('status', $object->status ?? 'planned') === 'planned' ? 'selected' : '' }}>Planned</option>
                                <option value="completed" {{ old('status', $object->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ old('status', $object->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label>Description / What was done</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $object->description) }}</textarea>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label>Notes / Follow-up Actions</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $object->notes) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save mr-1"></i> {{ $object->id ? 'Update' : 'Log' }} Activity
                    </button>
                    <a href="{{ route('field_marketing.activities.index') }}" class="btn btn-secondary ml-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

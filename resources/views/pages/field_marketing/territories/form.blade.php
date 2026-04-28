@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">{{ $object->id ? 'Edit Territory' : 'New Territory' }}</h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item"><a href="{{ route('field_marketing.territories.index') }}">Territories</a></li>
                    <li class="breadcrumb-item active">{{ $object->id ? 'Edit' : 'Create' }}</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Territory Details</h3>
        </div>
        <div class="block-content">
            <form action="{{ $object->id ? route('field_marketing.territories.update', $object->id) : route('field_marketing.territories.store') }}" method="POST">
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
                            <label>Territory Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $object->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Region / Area</label>
                            <input type="text" name="region" class="form-control @error('region') is-invalid @enderror"
                                   value="{{ old('region', $object->region) }}" placeholder="e.g. Dar es Salaam North">
                            @error('region')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Assigned Field Agent</label>
                            <select name="assigned_user_id" class="form-control">
                                <option value="">— Unassigned —</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" {{ old('assigned_user_id', $object->assigned_user_id) == $agent->id ? 'selected' : '' }}>
                                        {{ $agent->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-control" required>
                                <option value="active" {{ old('status', $object->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $object->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $object->description) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save mr-1"></i> {{ $object->id ? 'Update' : 'Create' }} Territory
                    </button>
                    <a href="{{ route('field_marketing.territories.index') }}" class="btn btn-secondary ml-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

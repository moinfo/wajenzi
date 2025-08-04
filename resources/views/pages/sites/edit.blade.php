@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">Edit Site: {{ $site->name }}</h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item"><a href="{{ route('sites.index') }}">Sites</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('sites.show', $site) }}">{{ $site->name }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    @include('partials.alerts')

    <form action="{{ route('sites.update', $site) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Site Information</h3>
            </div>
            <div class="block-content">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="form-group">
                            <label for="name">Site Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $site->name) }}" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="location">Location <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('location') is-invalid @enderror" 
                                   id="location" 
                                   name="location" 
                                   value="{{ old('location', $site->location) }}" 
                                   required>
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="4">{{ old('description', $site->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="form-group">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select class="form-control @error('status') is-invalid @enderror" 
                                    id="status" 
                                    name="status" 
                                    required>
                                <option value="">Select Status</option>
                                <option value="ACTIVE" {{ old('status', $site->status) == 'ACTIVE' ? 'selected' : '' }}>Active</option>
                                <option value="INACTIVE" {{ old('status', $site->status) == 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                                <option value="COMPLETED" {{ old('status', $site->status) == 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" 
                                   class="form-control @error('start_date') is-invalid @enderror" 
                                   id="start_date" 
                                   name="start_date" 
                                   value="{{ old('start_date', $site->start_date ? $site->start_date->format('Y-m-d') : '') }}">
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="expected_end_date">Expected End Date</label>
                            <input type="date" 
                                   class="form-control @error('expected_end_date') is-invalid @enderror" 
                                   id="expected_end_date" 
                                   name="expected_end_date" 
                                   value="{{ old('expected_end_date', $site->expected_end_date ? $site->expected_end_date->format('Y-m-d') : '') }}">
                            @error('expected_end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($site->status == 'COMPLETED')
                            <div class="form-group">
                                <label for="actual_end_date">Actual End Date</label>
                                <input type="date" 
                                       class="form-control @error('actual_end_date') is-invalid @enderror" 
                                       id="actual_end_date" 
                                       name="actual_end_date" 
                                       value="{{ old('actual_end_date', $site->actual_end_date ? $site->actual_end_date->format('Y-m-d') : '') }}">
                                @error('actual_end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-content">
                <div class="row">
                    <div class="col-lg-8">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Update Site
                        </button>
                        <a href="{{ route('sites.show', $site) }}" class="btn btn-secondary">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('status');
    const actualEndDateGroup = document.querySelector('#actual_end_date').closest('.form-group');
    
    function toggleActualEndDate() {
        if (statusSelect.value === 'COMPLETED') {
            actualEndDateGroup.style.display = 'block';
        } else {
            actualEndDateGroup.style.display = 'none';
            document.getElementById('actual_end_date').value = '';
        }
    }
    
    statusSelect.addEventListener('change', toggleActualEndDate);
    toggleActualEndDate(); // Initial call
});
</script>
@endsection
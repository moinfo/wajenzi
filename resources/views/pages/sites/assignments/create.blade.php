@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">Assign Supervisor to Site</h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item"><a href="{{ route('site-supervisor-assignments.index') }}">Assignments</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    @include('partials.alerts')

    @if($availableSites->count() == 0)
        <div class="alert alert-info">
            <h5><i class="fa fa-info-circle"></i> No Available Sites</h5>
            <p>All active sites currently have assigned supervisors. You can only assign supervisors to sites without active assignments.</p>
            <a href="{{ route('site-supervisor-assignments.index') }}" class="btn btn-primary">
                <i class="fa fa-arrow-left"></i> Back to Assignments
            </a>
        </div>
    @else
        <form action="{{ route('site-supervisor-assignments.store') }}" method="POST">
            @csrf
            
            <div class="block block-rounded">
                <div class="block-header">
                    <h3 class="block-title">Assignment Information</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="form-group">
                                <label for="site_id">Site <span class="text-danger">*</span></label>
                                <select class="form-control @error('site_id') is-invalid @enderror" 
                                        id="site_id" 
                                        name="site_id" 
                                        required>
                                    <option value="">Select Site</option>
                                    @foreach($availableSites as $site)
                                        <option value="{{ $site->id }}" {{ old('site_id', request('site_id')) == $site->id ? 'selected' : '' }}>
                                            {{ $site->name }} - {{ $site->location }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('site_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="user_id">Supervisor <span class="text-danger">*</span></label>
                                <select class="form-control @error('user_id') is-invalid @enderror" 
                                        id="user_id" 
                                        name="user_id" 
                                        required>
                                    <option value="">Select Supervisor</option>
                                    @foreach($supervisors as $supervisor)
                                        <option value="{{ $supervisor->id }}" {{ old('user_id') == $supervisor->id ? 'selected' : '' }}>
                                            {{ $supervisor->name }} ({{ $supervisor->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('user_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="notes">Notes</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" 
                                          id="notes" 
                                          name="notes" 
                                          rows="3">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="form-group">
                                <label for="assigned_from">Assigned From <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control datepicker @error('assigned_from') is-invalid @enderror" 
                                       id="assigned_from" 
                                       name="assigned_from" 
                                       value="{{ old('assigned_from', date('d/m/Y')) }}" 
                                       placeholder="dd/mm/yyyy"
                                       required>
                                @error('assigned_from')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Format: dd/mm/yyyy</small>
                            </div>

                            <div class="form-group">
                                <label for="assigned_to">Assigned To (Optional)</label>
                                <input type="date" 
                                       class="form-control @error('assigned_to') is-invalid @enderror" 
                                       id="assigned_to" 
                                       name="assigned_to" 
                                       value="{{ old('assigned_to') }}">
                                @error('assigned_to')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Leave empty for ongoing assignment</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block block-rounded">
                <div class="block-content">
                    <div class="row">
                        <div class="col-lg-8">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Assign Supervisor
                            </button>
                            <a href="{{ route('site-supervisor-assignments.index') }}" class="btn btn-secondary">
                                <i class="fa fa-times"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simple date input handling
    const dateInputs = document.querySelectorAll('.datepicker');
    dateInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const value = this.value;
            // Basic validation for dd/mm/yyyy format
            const dateRegex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
            if (value && !dateRegex.test(value)) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    });
});
</script>
@endsection
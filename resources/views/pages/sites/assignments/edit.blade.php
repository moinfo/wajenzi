@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">Edit Assignment: {{ $assignment->site->name }}</h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item"><a href="{{ route('site-supervisor-assignments.index') }}">Assignments</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    @include('partials.alerts')

    <form action="{{ route('site-supervisor-assignments.update', $assignment) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Assignment Information</h3>
            </div>
            <div class="block-content">
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Read-only information -->
                        <div class="form-group">
                            <label>Site</label>
                            <input type="text" class="form-control" value="{{ $assignment->site->name }} - {{ $assignment->site->location }}" readonly>
                        </div>

                        <div class="form-group">
                            <label>Supervisor</label>
                            <input type="text" class="form-control" value="{{ $assignment->supervisor->name }} ({{ $assignment->supervisor->email }})" readonly>
                        </div>

                        <div class="form-group">
                            <label>Assigned From</label>
                            <input type="text" class="form-control" value="{{ $assignment->assigned_from->format('d/m/Y') }}" readonly>
                        </div>

                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" 
                                      name="notes" 
                                      rows="3">{{ old('notes', $assignment->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="form-group">
                            <label for="assigned_to">End Assignment Date</label>
                            <input type="text" 
                                   class="form-control datepicker @error('assigned_to') is-invalid @enderror" 
                                   id="assigned_to" 
                                   name="assigned_to" 
                                   value="{{ old('assigned_to', $assignment->assigned_to ? $assignment->assigned_to->format('d/m/Y') : '') }}" 
                                   placeholder="dd/mm/yyyy">
                            @error('assigned_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Set a date to end this assignment. Leave empty to keep ongoing.
                            </small>
                        </div>

                        <div class="alert alert-info">
                            <h6><i class="fa fa-info-circle"></i> Assignment Details</h6>
                            <small>
                                <strong>Assigned By:</strong> {{ $assignment->assignedBy->name }}<br>
                                <strong>Created:</strong> {{ $assignment->created_at->format('M d, Y H:i') }}<br>
                                <strong>Status:</strong> 
                                @if($assignment->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </small>
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
                            <i class="fa fa-save"></i> Update Assignment
                        </button>
                        <a href="{{ route('site-supervisor-assignments.index') }}" class="btn btn-secondary">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                        
                        @can('Delete Site Assignments')
                            <form method="POST" 
                                  action="{{ route('site-supervisor-assignments.destroy', $assignment) }}" 
                                  style="display: inline-block; margin-left: 10px;"
                                  onsubmit="return confirm('Are you sure you want to end this assignment immediately?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-warning">
                                    <i class="fa fa-stop"></i> End Assignment Now
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </form>
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
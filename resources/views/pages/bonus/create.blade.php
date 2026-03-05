@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">
                <i class="fa fa-plus text-success mr-2"></i>Create Bonus Task
            </h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item"><a href="{{ route('architect-bonus.index') }}">Architect Bonus</a></li>
                    <li class="breadcrumb-item active">Create Task</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <form action="{{ route('architect-bonus.store') }}" method="POST">
        @csrf
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Task Details</h3>
            </div>
            <div class="block-content">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Select Project</strong></label>
                            <select name="project_id" id="projectSelect" class="form-control">
                                <option value="">-- Select Existing Project --</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}"
                                            data-name="{{ $project->project_name }}"
                                            data-budget="{{ $project->contract_value ?? 0 }}"
                                            {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                        {{ $project->project_name }}
                                        @if($project->contract_value)
                                            (TZS {{ number_format($project->contract_value) }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select a project to auto-fill name and budget, or type manually below</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Project Name</strong> <span class="text-danger" id="nameRequired">*</span></label>
                            <input type="text" name="project_name" id="projectName"
                                   class="form-control @error('project_name') is-invalid @enderror"
                                   value="{{ old('project_name') }}" placeholder="Auto-filled from project or type manually">
                            @error('project_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Assign Architect</strong> <span class="text-danger">*</span></label>
                            <select name="architect_id" class="form-control @error('architect_id') is-invalid @enderror" required>
                                <option value="">-- Select Architect --</option>
                                @foreach($architects as $arch)
                                    <option value="{{ $arch->id }}" {{ old('architect_id') == $arch->id ? 'selected' : '' }}>
                                        {{ $arch->name }} ({{ $arch->designation ?? $arch->roles->first()->name ?? 'Staff' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('architect_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Project Budget (TZS)</strong> <span class="text-danger">*</span></label>
                            <input type="number" name="project_budget" id="projectBudget"
                                   class="form-control @error('project_budget') is-invalid @enderror"
                                   value="{{ old('project_budget') }}" min="0" step="1" required>
                            @error('project_budget')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Max Units</strong></label>
                            <input type="text" id="maxUnitsDisplay" class="form-control" readonly
                                   value="Enter budget to calculate">
                            <small class="text-muted">Auto-calculated from budget tier</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Max Bonus (TZS)</strong></label>
                            <input type="text" id="maxBonusDisplay" class="form-control" readonly value="-">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Start Date</strong> <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                                   value="{{ old('start_date', date('Y-m-d')) }}" required>
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Scheduled Completion Date</strong> <span class="text-danger">*</span></label>
                            <input type="date" name="scheduled_completion_date" class="form-control @error('scheduled_completion_date') is-invalid @enderror"
                                   value="{{ old('scheduled_completion_date') }}" required>
                            @error('scheduled_completion_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Link to Lead</strong></label>
                            <select name="lead_id" class="form-control">
                                <option value="">-- None --</option>
                                @foreach($leads as $lead)
                                    <option value="{{ $lead->id }}" {{ old('lead_id') == $lead->id ? 'selected' : '' }}>
                                        {{ $lead->lead_number }} - {{ $lead->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label><strong>Notes</strong></label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>

                <!-- Tier Reference -->
                <div class="alert alert-light border">
                    <strong><i class="fa fa-info-circle mr-1"></i>Unit Tiers Reference:</strong>
                    <div class="row mt-2">
                        @foreach($tiers as $tier)
                            <div class="col-md-3 col-6 mb-1">
                                <small>{{ number_format($tier->min_amount/1000000, 1) }}M - {{ number_format($tier->max_amount/1000000, 1) }}M = <strong>{{ $tier->max_units }} units</strong></small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="block-content block-content-full block-content-sm bg-body-light">
                <div class="row">
                    <div class="col-6">
                        <a href="{{ route('architect-bonus.index') }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left mr-1"></i> Back
                        </a>
                    </div>
                    <div class="col-6 text-right">
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-check mr-1"></i> Create Task
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('js_after')
<script>
// Project selection auto-fills name and budget
document.getElementById('projectSelect').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    if (this.value) {
        document.getElementById('projectName').value = selected.dataset.name;
        document.getElementById('projectBudget').value = selected.dataset.budget;
        document.getElementById('projectName').readOnly = true;
        // Trigger budget calculation
        document.getElementById('projectBudget').dispatchEvent(new Event('input'));
    } else {
        document.getElementById('projectName').value = '';
        document.getElementById('projectName').readOnly = false;
        document.getElementById('projectBudget').value = '';
        document.getElementById('maxUnitsDisplay').value = 'Enter budget to calculate';
        document.getElementById('maxBonusDisplay').value = '-';
    }
});

// Budget change fetches max units
document.getElementById('projectBudget').addEventListener('input', function() {
    var amount = parseFloat(this.value) || 0;
    fetch('{{ route("architect-bonus.max-units") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({amount: amount})
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        document.getElementById('maxUnitsDisplay').value = data.max_units > 0 ? data.max_units + ' units' : 'No tier found';
        document.getElementById('maxBonusDisplay').value = data.max_units > 0
            ? 'TZS ' + (data.max_units * data.unit_value).toLocaleString()
            : '-';
    });
});
</script>
@endsection

@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">Create Site Daily Report</h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item">Projects</li>
                    <li class="breadcrumb-item"><a href="{{ route('site-daily-reports.index') }}">Site Daily Reports</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    @include('partials.alerts')

    <form method="POST" action="{{ route('site-daily-reports.store') }}">
        @csrf
        
        <!-- Basic Information -->
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Report Information</h3>
            </div>
            <div class="block-content">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="report_date">Report Date <span class="text-danger">*</span></label>
                            <input type="text" class="form-control datepicker @error('report_date') is-invalid @enderror" 
                                   id="report_date" name="report_date" value="{{ old('report_date', date('d/m/Y')) }}" required>
                            @error('report_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="site_id">Site <span class="text-danger">*</span></label>
                            <select class="form-control @error('site_id') is-invalid @enderror" 
                                    id="site_id" name="site_id" required>
                                <option value="">Select Site</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}" {{ old('site_id') == $site->id ? 'selected' : '' }}>
                                        {{ $site->name }} - {{ $site->location }}
                                    </option>
                                @endforeach
                            </select>
                            @error('site_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="progress_percentage">Progress Percentage <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control @error('progress_percentage') is-invalid @enderror" 
                                       id="progress_percentage" name="progress_percentage" 
                                       value="{{ old('progress_percentage') }}" min="0" max="100" step="0.01" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            @error('progress_percentage')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Work Activities -->
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">🛠️ Work Activities (Kazi)</h3>
                <div class="block-options">
                    <button type="button" class="btn btn-sm btn-primary" onclick="addWorkActivity()">
                        <i class="fa fa-plus"></i> Add Activity
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div id="work-activities-container">
                    @if(old('work_activities'))
                        @foreach(old('work_activities') as $index => $activity)
                            <div class="form-group work-activity-row">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="work_activities[]" 
                                           value="{{ $activity }}" placeholder="Describe work activity">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-danger" onclick="removeRow(this)">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="form-group work-activity-row">
                            <div class="input-group">
                                <input type="text" class="form-control" name="work_activities[]" 
                                       placeholder="Describe work activity">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-danger" onclick="removeRow(this)">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Materials Used -->
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">📦 Materials Used (Vifaa)</h3>
                <div class="block-options">
                    <button type="button" class="btn btn-sm btn-primary" onclick="addMaterial()">
                        <i class="fa fa-plus"></i> Add Material
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div id="materials-container">
                    @if(old('materials'))
                        @foreach(old('materials') as $index => $material)
                            <div class="form-group material-row">
                                <div class="row">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="materials[{{ $index }}][name]" 
                                               value="{{ $material['name'] ?? '' }}" placeholder="Material name">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" name="materials[{{ $index }}][quantity]" 
                                               value="{{ $material['quantity'] ?? '' }}" placeholder="Quantity">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control" name="materials[{{ $index }}][unit]" 
                                               value="{{ $material['unit'] ?? '' }}" placeholder="Unit">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-danger btn-block" onclick="removeRow(this)">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="form-group material-row">
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="materials[0][name]" placeholder="Material name">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="materials[0][quantity]" placeholder="Quantity">
                                </div>
                                <div class="col-md-2">
                                    <input type="text" class="form-control" name="materials[0][unit]" placeholder="Unit">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-danger btn-block" onclick="removeRow(this)">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Payments -->
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">💰 Payments (Malipo)</h3>
                <div class="block-options">
                    <button type="button" class="btn btn-sm btn-primary" onclick="addPayment()">
                        <i class="fa fa-plus"></i> Add Payment
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div id="payments-container">
                    @if(old('payments'))
                        @foreach(old('payments') as $index => $payment)
                            <div class="form-group payment-row">
                                <div class="row">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="payments[{{ $index }}][description]" 
                                               value="{{ $payment['description'] ?? '' }}" placeholder="Payment description">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" class="form-control" name="payments[{{ $index }}][amount]" 
                                               value="{{ $payment['amount'] ?? '' }}" placeholder="Amount" step="0.01" min="0">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" name="payments[{{ $index }}][payment_to]" 
                                               value="{{ $payment['payment_to'] ?? '' }}" placeholder="Payment to">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-danger btn-block" onclick="removeRow(this)">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="form-group payment-row">
                            <div class="row">
                                <div class="col-md-5">
                                    <input type="text" class="form-control" name="payments[0][description]" placeholder="Payment description">
                                </div>
                                <div class="col-md-3">
                                    <input type="number" class="form-control" name="payments[0][amount]" placeholder="Amount" step="0.01" min="0">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="payments[0][payment_to]" placeholder="Payment to">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-danger btn-block" onclick="removeRow(this)">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Labor Needed -->
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">🧑🏾‍🔧 Labor Needed</h3>
                <div class="block-options">
                    <button type="button" class="btn btn-sm btn-primary" onclick="addLabor()">
                        <i class="fa fa-plus"></i> Add Labor
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div id="labor-container">
                    @if(old('labor'))
                        @foreach(old('labor') as $index => $labor)
                            <div class="form-group labor-row">
                                <div class="row">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="labor[{{ $index }}][type]" 
                                               value="{{ $labor['type'] ?? '' }}" placeholder="Labor type">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="labor[{{ $index }}][description]" 
                                               value="{{ $labor['description'] ?? '' }}" placeholder="Description">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-danger btn-block" onclick="removeRow(this)">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="form-group labor-row">
                            <div class="row">
                                <div class="col-md-5">
                                    <input type="text" class="form-control" name="labor[0][type]" placeholder="Labor type">
                                </div>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="labor[0][description]" placeholder="Description">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-danger btn-block" onclick="removeRow(this)">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Challenges and Next Steps -->
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Additional Information</h3>
            </div>
            <div class="block-content">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="challenges">⚠️ Challenges (Changamoto)</label>
                            <textarea class="form-control @error('challenges') is-invalid @enderror" 
                                      id="challenges" name="challenges" rows="4">{{ old('challenges') }}</textarea>
                            @error('challenges')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="next_steps">➡️ Next Steps (Hatua zinazofuata)</label>
                            <textarea class="form-control @error('next_steps') is-invalid @enderror" 
                                      id="next_steps" name="next_steps" rows="4">{{ old('next_steps') }}</textarea>
                            @error('next_steps')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="block block-rounded">
            <div class="block-content">
                <button type="submit" class="btn btn-success">
                    <i class="fa fa-check"></i> Create Report
                </button>
                <a href="{{ route('site-daily-reports.index') }}" class="btn btn-secondary">
                    <i class="fa fa-times"></i> Cancel
                </a>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    $('.datepicker').datepicker({
        autoclose: true,
        format: 'dd/mm/yyyy',
        todayHighlight: true,
        defaultViewDate: new Date()
    });
});

function addWorkActivity() {
    const container = document.getElementById('work-activities-container');
    const div = document.createElement('div');
    div.className = 'form-group work-activity-row';
    div.innerHTML = `
        <div class="input-group">
            <input type="text" class="form-control" name="work_activities[]" placeholder="Describe work activity">
            <div class="input-group-append">
                <button type="button" class="btn btn-danger" onclick="removeRow(this)">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(div);
}

function addMaterial() {
    const container = document.getElementById('materials-container');
    const index = container.children.length;
    const div = document.createElement('div');
    div.className = 'form-group material-row';
    div.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <input type="text" class="form-control" name="materials[${index}][name]" placeholder="Material name">
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" name="materials[${index}][quantity]" placeholder="Quantity">
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control" name="materials[${index}][unit]" placeholder="Unit">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-block" onclick="removeRow(this)">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(div);
}

function addPayment() {
    const container = document.getElementById('payments-container');
    const index = container.children.length;
    const div = document.createElement('div');
    div.className = 'form-group payment-row';
    div.innerHTML = `
        <div class="row">
            <div class="col-md-5">
                <input type="text" class="form-control" name="payments[${index}][description]" placeholder="Payment description">
            </div>
            <div class="col-md-3">
                <input type="number" class="form-control" name="payments[${index}][amount]" placeholder="Amount" step="0.01" min="0">
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" name="payments[${index}][payment_to]" placeholder="Payment to">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-block" onclick="removeRow(this)">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(div);
}

function addLabor() {
    const container = document.getElementById('labor-container');
    const index = container.children.length;
    const div = document.createElement('div');
    div.className = 'form-group labor-row';
    div.innerHTML = `
        <div class="row">
            <div class="col-md-5">
                <input type="text" class="form-control" name="labor[${index}][type]" placeholder="Labor type">
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control" name="labor[${index}][description]" placeholder="Description">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-block" onclick="removeRow(this)">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(div);
}

function removeRow(button) {
    button.closest('.form-group').remove();
}
</script>
@endsection
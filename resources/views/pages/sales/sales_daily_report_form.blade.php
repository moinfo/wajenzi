@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">
                {{ isset($object->id) ? 'Edit' : 'Create' }} Sales Daily Report
            </h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item">Projects</li>
                    <li class="breadcrumb-item"><a href="{{ route('sales_daily_reports') }}">Sales Daily Reports</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ isset($object->id) ? 'Edit' : 'Create' }}</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ isset($object->id) ? route('sales_daily_report.update', $object->id) : route('sales_daily_report.store') }}">
        @csrf
        
        <!-- Basic Information -->
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Basic Information</h3>
            </div>
            <div class="block-content">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="report_date" class="required">Report Date</label>
                            <input type="text" class="form-control datepicker @error('report_date') is-invalid @enderror" 
                                   id="report_date" name="report_date" 
                                   value="{{ old('report_date', $object->report_date?->format('d/m/Y') ?? now()->format('d/m/Y')) }}" required
                                   placeholder="Select date">
                            @error('report_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="prepared_by">Prepared By</label>
                            <input type="text" class="form-control" value="{{ Auth::user()->name }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="department_id" class="required">Department</label>
                            <select class="form-control @error('department_id') is-invalid @enderror" 
                                    id="department_id" name="department_id" required>
                                <option value="">Select Department</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" 
                                            {{ old('department_id', $object->department_id ?? ($department->name == 'Sales and Marketing' ? $department->id : null)) == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="daily_summary" class="required">Daily Summary</label>
                            <textarea class="form-control @error('daily_summary') is-invalid @enderror" 
                                      id="daily_summary" name="daily_summary" rows="4" required 
                                      placeholder="Provide a brief overview of today's activities, meetings, and outcomes.">{{ old('daily_summary', $object->daily_summary) }}</textarea>
                            @error('daily_summary')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lead Follow-ups & Interactions -->
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Lead Follow-ups & Interactions</h3>
                <div class="block-options">
                    <button type="button" class="btn btn-sm btn-primary" onclick="addLeadFollowup()">
                        <i class="fa fa-plus"></i> Add Lead
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered" id="leadFollowupsTable">
                        <thead>
                            <tr>
                                <th>Lead</th>
                                <th>Interaction Type</th>
                                <th>Details/Discussion</th>
                                <th>Outcome</th>
                                <th>Next Step</th>
                                <th>Follow-Up Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($object->id) && $object->leadFollowups->count() > 0)
                                @foreach($object->leadFollowups as $index => $followup)
                                    <tr>
                                        <td>
                                            <select class="form-control" name="lead_followups[{{ $index }}][lead_id]">
                                                <option value="">Select Lead</option>
                                                @foreach($leads as $lead)
                                                    <option value="{{ $lead->id }}" {{ $followup->lead_id == $lead->id ? 'selected' : '' }}>
                                                        {{ $lead->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-control" name="lead_followups[{{ $index }}][client_source_id]">
                                                <option value="">Select Type</option>
                                                @foreach($client_sources as $source)
                                                    <option value="{{ $source->id }}" {{ $followup->client_source_id == $source->id ? 'selected' : '' }}>
                                                        {{ $source->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><textarea class="form-control" name="lead_followups[{{ $index }}][details_discussion]" rows="2">{{ $followup->details_discussion }}</textarea></td>
                                        <td><textarea class="form-control" name="lead_followups[{{ $index }}][outcome]" rows="2">{{ $followup->outcome }}</textarea></td>
                                        <td><textarea class="form-control" name="lead_followups[{{ $index }}][next_step]" rows="2">{{ $followup->next_step }}</textarea></td>
                                        <td><input type="text" class="form-control datepicker" name="lead_followups[{{ $index }}][followup_date]" value="{{ $followup->followup_date?->format('d/m/Y') }}" placeholder="Select date"></td>
                                        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="fa fa-trash"></i></button></td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td>
                                        <select class="form-control" name="lead_followups[0][lead_id]">
                                            <option value="">Select Lead</option>
                                            @foreach($leads as $lead)
                                                <option value="{{ $lead->id }}">{{ $lead->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control" name="lead_followups[0][client_source_id]">
                                            <option value="">Select Type</option>
                                            @foreach($client_sources as $source)
                                                <option value="{{ $source->id }}">{{ $source->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><textarea class="form-control" name="lead_followups[0][details_discussion]" rows="2" placeholder="Details/Discussion"></textarea></td>
                                    <td><textarea class="form-control" name="lead_followups[0][outcome]" rows="2" placeholder="Outcome"></textarea></td>
                                    <td><textarea class="form-control" name="lead_followups[0][next_step]" rows="2" placeholder="Next Step"></textarea></td>
                                    <td><input type="text" class="form-control datepicker" name="lead_followups[0][followup_date]" placeholder="Select date"></td>
                                    <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="fa fa-trash"></i></button></td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sales Activities -->
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Sales Activities</h3>
                <div class="block-options">
                    <button type="button" class="btn btn-sm btn-primary" onclick="addSalesActivity()">
                        <i class="fa fa-plus"></i> Add Activity
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered" id="salesActivitiesTable">
                        <thead>
                            <tr>
                                <th>Invoice No</th>
                                <th>Invoice Sum/Price</th>
                                <th>Activity</th>
                                <th>Status/Payment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($object->id) && $object->salesActivities->count() > 0)
                                @foreach($object->salesActivities as $index => $activity)
                                    <tr>
                                        <td><input type="text" class="form-control" name="sales_activities[{{ $index }}][invoice_no]" value="{{ $activity->invoice_no }}"></td>
                                        <td><input type="number" class="form-control" name="sales_activities[{{ $index }}][invoice_sum]" step="0.01" value="{{ $activity->invoice_sum }}"></td>
                                        <td><input type="text" class="form-control" name="sales_activities[{{ $index }}][activity]" value="{{ $activity->activity }}"></td>
                                        <td>
                                            <select class="form-control" name="sales_activities[{{ $index }}][status]">
                                                <option value="paid" {{ $activity->status == 'paid' ? 'selected' : '' }}>Paid</option>
                                                <option value="not_paid" {{ $activity->status == 'not_paid' ? 'selected' : '' }}>Not Paid</option>
                                                <option value="partial" {{ $activity->status == 'partial' ? 'selected' : '' }}>Partial</option>
                                            </select>
                                        </td>
                                        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="fa fa-trash"></i></button></td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td><input type="text" class="form-control" name="sales_activities[0][invoice_no]" placeholder="Invoice No"></td>
                                    <td><input type="number" class="form-control" name="sales_activities[0][invoice_sum]" step="0.01" placeholder="0.00"></td>
                                    <td><input type="text" class="form-control" name="sales_activities[0][activity]" placeholder="Activity Description"></td>
                                    <td>
                                        <select class="form-control" name="sales_activities[0][status]">
                                            <option value="not_paid">Not Paid</option>
                                            <option value="paid">Paid</option>
                                            <option value="partial">Partial</option>
                                        </select>
                                    </td>
                                    <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="fa fa-trash"></i></button></td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Customer Acquisition Cost -->
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Daily Customer Acquisition Cost (CAC) Report</h3>
            </div>
            <div class="block-content">
                @php
                    $cacData = isset($object->id) ? $object->customerAcquisitionCost : null;
                @endphp
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Marketing Cost</label>
                            <input type="number" class="form-control" name="cac_data[marketing_cost]" step="0.01" 
                                   value="{{ old('cac_data.marketing_cost', $cacData->marketing_cost ?? 0) }}" 
                                   onchange="calculateCAC()">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Sales Cost</label>
                            <input type="number" class="form-control" name="cac_data[sales_cost]" step="0.01" 
                                   value="{{ old('cac_data.sales_cost', $cacData->sales_cost ?? 0) }}" 
                                   onchange="calculateCAC()">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Other Cost</label>
                            <input type="number" class="form-control" name="cac_data[other_cost]" step="0.01" 
                                   value="{{ old('cac_data.other_cost', $cacData->other_cost ?? 0) }}" 
                                   onchange="calculateCAC()">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>New Customers</label>
                            <input type="number" class="form-control" name="cac_data[new_customers]" 
                                   value="{{ old('cac_data.new_customers', $cacData->new_customers ?? 0) }}" 
                                   onchange="calculateCAC()">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Total Cost</label>
                            <input type="number" class="form-control" id="total_cost" readonly 
                                   value="{{ old('cac_data.total_cost', $cacData->total_cost ?? 0) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>CAC Value</label>
                            <input type="number" class="form-control" id="cac_value" readonly 
                                   value="{{ old('cac_data.cac_value', $cacData->cac_value ?? 0) }}">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea class="form-control" name="cac_data[notes]" rows="3" 
                                      placeholder="Additional notes about customer acquisition costs">{{ old('cac_data.notes', $cacData->notes ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Client Concerns -->
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Issues or Client Concerns</h3>
                <div class="block-options">
                    <button type="button" class="btn btn-sm btn-primary" onclick="addClientConcern()">
                        <i class="fa fa-plus"></i> Add Concern
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered" id="clientConcernsTable">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Issue/Concern</th>
                                <th>Action Taken or Required</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($object->id) && $object->clientConcerns->count() > 0)
                                @foreach($object->clientConcerns as $index => $concern)
                                    <tr>
                                        <td>
                                            <select class="form-control" name="client_concerns[{{ $index }}][client_id]">
                                                <option value="">Select Client</option>
                                                @foreach($clients as $client)
                                                    <option value="{{ $client->id }}" {{ $concern->client_id == $client->id ? 'selected' : '' }}>
                                                        {{ $client->first_name }} {{ $client->last_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><textarea class="form-control" name="client_concerns[{{ $index }}][issue_concern]" rows="2">{{ $concern->issue_concern }}</textarea></td>
                                        <td><textarea class="form-control" name="client_concerns[{{ $index }}][action_taken]" rows="2">{{ $concern->action_taken }}</textarea></td>
                                        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="fa fa-trash"></i></button></td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td>
                                        <select class="form-control" name="client_concerns[0][client_id]">
                                            <option value="">Select Client</option>
                                            @foreach($clients as $client)
                                                <option value="{{ $client->id }}">{{ $client->first_name }} {{ $client->last_name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><textarea class="form-control" name="client_concerns[0][issue_concern]" rows="2" placeholder="Issue/Concern"></textarea></td>
                                    <td><textarea class="form-control" name="client_concerns[0][action_taken]" rows="2" placeholder="Action Taken or Required"></textarea></td>
                                    <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="fa fa-trash"></i></button></td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Notes & Recommendations -->
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Notes & Recommendations</h3>
            </div>
            <div class="block-content">
                <div class="form-group">
                    <textarea class="form-control" name="notes_recommendations" rows="4" 
                              placeholder="Use this section for any important observations, client preferences, or suggestions for team coordination.">{{ old('notes_recommendations', $object->notes_recommendations) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="block block-rounded">
            <div class="block-content">
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> {{ isset($object->id) ? 'Update' : 'Create' }} Report
                        </button>
                        <a href="{{ route('sales_daily_reports') }}" class="btn btn-secondary">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let leadFollowupIndex = {{ isset($object->id) && $object->leadFollowups->count() > 0 ? $object->leadFollowups->count() : 1 }};
let salesActivityIndex = {{ isset($object->id) && $object->salesActivities->count() > 0 ? $object->salesActivities->count() : 1 }};
let clientConcernIndex = {{ isset($object->id) && $object->clientConcerns->count() > 0 ? $object->clientConcerns->count() : 1 }};

function addLeadFollowup() {
    const table = document.getElementById('leadFollowupsTable').getElementsByTagName('tbody')[0];
    const newRow = table.insertRow();
    newRow.innerHTML = `
        <td>
            <select class="form-control" name="lead_followups[${leadFollowupIndex}][lead_id]">
                <option value="">Select Lead</option>
                @foreach($leads as $lead)
                    <option value="{{ $lead->id }}">{{ $lead->name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <select class="form-control" name="lead_followups[${leadFollowupIndex}][client_source_id]">
                <option value="">Select Type</option>
                @foreach($client_sources as $source)
                    <option value="{{ $source->id }}">{{ $source->name }}</option>
                @endforeach
            </select>
        </td>
        <td><textarea class="form-control" name="lead_followups[${leadFollowupIndex}][details_discussion]" rows="2" placeholder="Details/Discussion"></textarea></td>
        <td><textarea class="form-control" name="lead_followups[${leadFollowupIndex}][outcome]" rows="2" placeholder="Outcome"></textarea></td>
        <td><textarea class="form-control" name="lead_followups[${leadFollowupIndex}][next_step]" rows="2" placeholder="Next Step"></textarea></td>
        <td><input type="text" class="form-control datepicker" name="lead_followups[${leadFollowupIndex}][followup_date]" placeholder="Select date"></td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="fa fa-trash"></i></button></td>
    `;
    leadFollowupIndex++;
    // Initialize datepicker for new row
    initializeDatepickers();
}

function addSalesActivity() {
    const table = document.getElementById('salesActivitiesTable').getElementsByTagName('tbody')[0];
    const newRow = table.insertRow();
    newRow.innerHTML = `
        <td><input type="text" class="form-control" name="sales_activities[${salesActivityIndex}][invoice_no]" placeholder="Invoice No"></td>
        <td><input type="number" class="form-control" name="sales_activities[${salesActivityIndex}][invoice_sum]" step="0.01" placeholder="0.00"></td>
        <td><input type="text" class="form-control" name="sales_activities[${salesActivityIndex}][activity]" placeholder="Activity Description"></td>
        <td>
            <select class="form-control" name="sales_activities[${salesActivityIndex}][status]">
                <option value="not_paid">Not Paid</option>
                <option value="paid">Paid</option>
                <option value="partial">Partial</option>
            </select>
        </td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="fa fa-trash"></i></button></td>
    `;
    salesActivityIndex++;
}

function addClientConcern() {
    const table = document.getElementById('clientConcernsTable').getElementsByTagName('tbody')[0];
    const newRow = table.insertRow();
    newRow.innerHTML = `
        <td>
            <select class="form-control" name="client_concerns[${clientConcernIndex}][client_id]">
                <option value="">Select Client</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->first_name }} {{ $client->last_name }}</option>
                @endforeach
            </select>
        </td>
        <td><textarea class="form-control" name="client_concerns[${clientConcernIndex}][issue_concern]" rows="2" placeholder="Issue/Concern"></textarea></td>
        <td><textarea class="form-control" name="client_concerns[${clientConcernIndex}][action_taken]" rows="2" placeholder="Action Taken or Required"></textarea></td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="fa fa-trash"></i></button></td>
    `;
    clientConcernIndex++;
}

function removeRow(button) {
    button.closest('tr').remove();
}

function calculateCAC() {
    const marketingCost = parseFloat(document.querySelector('input[name="cac_data[marketing_cost]"]').value) || 0;
    const salesCost = parseFloat(document.querySelector('input[name="cac_data[sales_cost]"]').value) || 0;
    const otherCost = parseFloat(document.querySelector('input[name="cac_data[other_cost]"]').value) || 0;
    const newCustomers = parseInt(document.querySelector('input[name="cac_data[new_customers]"]').value) || 0;
    
    const totalCost = marketingCost + salesCost + otherCost;
    const cacValue = newCustomers > 0 ? totalCost / newCustomers : 0;
    
    document.getElementById('total_cost').value = totalCost.toFixed(2);
    document.getElementById('cac_value').value = cacValue.toFixed(2);
}

// Initialize datepickers for dynamically added elements
function initializeDatepickers() {
    $('.datepicker').datepicker({
        autoclose: true,
        format: 'dd/mm/yyyy',
        todayHighlight: true,
        defaultViewDate: new Date()
    });
}

// Calculate CAC on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateCAC();
    initializeDatepickers();
});
</script>

@endsection
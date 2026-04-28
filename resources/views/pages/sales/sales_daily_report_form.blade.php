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
                            @php
                                if(old('report_date')) {
                                    $reportDate = old('report_date');
                                } elseif($object->report_date) {
                                    $reportDate = \Carbon\Carbon::parse($object->report_date)->format('Y-m-d');
                                } else {
                                    $reportDate = now()->format('Y-m-d');
                                }
                            @endphp
                             <div class="input-group date" id="report-datepicker" data-target-input="nearest">
                                 <input type="text" class="form-control datetimepicker-input datepicker @error('report_date') is-invalid @enderror" 
                                        data-target="#report-datepicker" id="report_date" name="report_date" 
                                        value="{{ $reportDate }}" 
                                        placeholder="YYYY-MM-DD" required>
                             </div>
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
                            @php
                                $selDeptId = old('department_id', $object->department_id ?? null);
                                if (!$selDeptId && !isset($object->id)) {
                                    foreach($departments as $dept) {
                                        if ($dept->name == 'Sales and Marketing') {
                                            $selDeptId = $dept->id;
                                            break;
                                        }
                                    }
                                }
                            @endphp
                            <select class="form-control @error('department_id') is-invalid @enderror" 
                                    id="department_id" name="department_id" required>
                                <option value="">Select Department</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" 
                                            {{ $selDeptId == $department->id ? 'selected' : '' }}>
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
                                        <td>
                                            <div class="input-group date" id="followup-datepicker-{{ $index }}" data-target-input="nearest">
                                                <input type="text" class="form-control datetimepicker-input datepicker" 
                                                       data-target="#followup-datepicker-{{ $index }}" name="lead_followups[{{ $index }}][followup_date]" 
                                                       value="{{ $followup->followup_date ? \Carbon\Carbon::parse($followup->followup_date)->format('Y-m-d') : '' }}" 
                                                       placeholder="YYYY-MM-DD">
                                            </div>
                                        </td>
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
                                    <td>
                                        @php
                                            $oldFollowupDate = old('lead_followups.0.followup_date');
                                        @endphp
                                        <div class="input-group date" id="followup-datepicker-0" data-target-input="nearest">
                                            <input type="text" class="form-control datetimepicker-input datepicker" 
                                                   data-target="#followup-datepicker-0" name="lead_followups[0][followup_date]" 
                                                   value="{{ $oldFollowupDate }}" 
                                                   placeholder="YYYY-MM-DD">
                                        </div>
                                    </td>
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
                                <th>Payment Amount</th>
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
                                            <select class="form-control sales-status-select" name="sales_activities[{{ $index }}][status]" onchange="togglePaymentAmount(this)">
                                                <option value="not_paid" {{ $activity->status == 'not_paid' ? 'selected' : '' }}>Not Paid</option>
                                                <option value="paid" {{ $activity->status == 'paid' ? 'selected' : '' }}>Paid</option>
                                                <option value="partial" {{ $activity->status == 'partial' ? 'selected' : '' }}>Partial</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control payment-amount-input" name="sales_activities[{{ $index }}][payment_amount]" 
                                                   step="0.01" value="{{ $activity->payment_amount }}" placeholder="0.00"
                                                   style="display: {{ in_array($activity->status, ['paid', 'partial']) ? 'block' : 'none' }}">
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
                                        <select class="form-control sales-status-select" name="sales_activities[0][status]" onchange="togglePaymentAmount(this)">
                                            <option value="not_paid">Not Paid</option>
                                            <option value="paid">Paid</option>
                                            <option value="partial">Partial</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control payment-amount-input" name="sales_activities[0][payment_amount]" 
                                               step="0.01" placeholder="0.00" style="display: none;">
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
                    $marketingCost = old('cac_data[marketing_cost]', $cacData->marketing_cost ?? 0);
                    $salesCost = old('cac_data[sales_cost]', $cacData->sales_cost ?? 0);
                    $otherCost = old('cac_data[other_cost]', $cacData->other_cost ?? 0);
                    $newCustomers = old('cac_data[new_customers]', $cacData->new_customers ?? 0);
                    $totalCost = old('cac_data[total_cost]', $cacData->total_cost ?? 0);
                    $cacValue = old('cac_data[cac_value]', $cacData->cac_value ?? 0);
                    $cacNotes = old('cac_data[notes]', $cacData->notes ?? '');
                @endphp
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Marketing Cost</label>
                            <input type="number" class="form-control" name="cac_data[marketing_cost]" step="0.01" 
                                   value="{{ $marketingCost }}" 
                                   onchange="calculateCAC()">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Sales Cost</label>
                            <input type="number" class="form-control" name="cac_data[sales_cost]" step="0.01" 
                                   value="{{ $salesCost }}" 
                                   onchange="calculateCAC()">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Other Cost</label>
                            <input type="number" class="form-control" name="cac_data[other_cost]" step="0.01" 
                                   value="{{ $otherCost }}" 
                                   onchange="calculateCAC()">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>New Customers</label>
                            <input type="number" class="form-control" name="cac_data[new_customers]" 
                                   value="{{ $newCustomers }}" 
                                   onchange="calculateCAC()">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Total Cost</label>
                            <input type="number" class="form-control" id="total_cost" readonly 
                                   value="{{ $totalCost }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>CAC Value</label>
                            <input type="number" class="form-control" id="cac_value" readonly 
                                   value="{{ $cacValue }}">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea class="form-control" name="cac_data[notes]" rows="3" 
                                      placeholder="Additional notes about customer acquisition costs">{{ $cacNotes }}</textarea>
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
    </form>
</div>

@php
    $leadOptionsHtml = '';
    foreach($leads as $lead) {
        $leadOptionsHtml .= '<option value="' . $lead->id . '">' . htmlspecialchars($lead->name) . '</option>';
    }
    
    $sourceOptionsHtml = '';
    foreach($client_sources as $source) {
        $sourceOptionsHtml .= '<option value="' . $source->id . '">' . htmlspecialchars($source->name) . '</option>';
    }
    
    $clientOptionsHtml = '';
    foreach($clients as $client) {
        $clientOptionsHtml .= '<option value="' . $client->id . '">' . htmlspecialchars($client->first_name . ' ' . $client->last_name) . '</option>';
    }
@endphp

<script>
let leadOptionsHtml = @json($leadOptionsHtml);
let sourceOptionsHtml = @json($sourceOptionsHtml);
let clientOptionsHtml = @json($clientOptionsHtml);
let leadFollowupIndex = 1;
let salesActivityIndex = 1;
let clientConcernIndex = 1;

@if(isset($object->id))
    leadFollowupIndex = {{ $object->leadFollowups->count() }};
    salesActivityIndex = {{ $object->salesActivities->count() }};
@endif

function addLeadFollowup() {
    const table = document.getElementById('leadFollowupsTable').getElementsByTagName('tbody')[0];
    const newRow = table.insertRow();
    const idx = leadFollowupIndex;
    newRow.innerHTML =
        '<td>' +
            '<select class="form-control" name="lead_followups[' + idx + '][lead_id]">' +
                '<option value="">Select Lead</option>' +
                leadOptionsHtml +
            '</select>' +
        '</td>' +
        '<td>' +
            '<select class="form-control" name="lead_followups[' + idx + '][client_source_id]">' +
                '<option value="">Select Type</option>' +
                sourceOptionsHtml +
            '</select>' +
        '</td>' +
        '<td><textarea class="form-control" name="lead_followups[' + idx + '][details_discussion]" rows="2" placeholder="Details/Discussion"></textarea></td>' +
        '<td><textarea class="form-control" name="lead_followups[' + idx + '][outcome]" rows="2" placeholder="Outcome"></textarea></td>' +
        '<td><textarea class="form-control" name="lead_followups[' + idx + '][next_step]" rows="2" placeholder="Next Step"></textarea></td>' +
        '<td>' +
            '<div class="input-group date" id="followup-datepicker-' + idx + '" data-target-input="nearest">' +
                '<input type="text" class="form-control datetimepicker-input datepicker" ' +
                       'data-target="#followup-datepicker-' + idx + '" name="lead_followups[' + idx + '][followup_date]" ' +
                       'placeholder="YYYY-MM-DD">' +
            '</div>' +
        '</td>' +
        '<td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="fa fa-trash"></i></button></td>';
    leadFollowupIndex++;
    initBootstrapDatepickers(document);
}

function addSalesActivity() {
    const table = document.getElementById('salesActivitiesTable').getElementsByTagName('tbody')[0];
    const newRow = table.insertRow();
    newRow.innerHTML = 
        '<td><input type="text" class="form-control" name="sales_activities[' + salesActivityIndex + '][invoice_no]" placeholder="Invoice No"></td>' +
        '<td><input type="number" class="form-control" name="sales_activities[' + salesActivityIndex + '][invoice_sum]" step="0.01" placeholder="0.00"></td>' +
        '<td><input type="text" class="form-control" name="sales_activities[' + salesActivityIndex + '][activity]" placeholder="Activity Description"></td>' +
        '<td>' +
            '<select class="form-control sales-status-select" name="sales_activities[' + salesActivityIndex + '][status]" onchange="togglePaymentAmount(this)">' +
                '<option value="not_paid">Not Paid</option>' +
                '<option value="paid">Paid</option>' +
                '<option value="partial">Partial</option>' +
            '</select>' +
        '</td>' +
        '<td>' +
            '<input type="number" class="form-control payment-amount-input" name="sales_activities[' + salesActivityIndex + '][payment_amount]" ' +
                   'step="0.01" placeholder="0.00" style="display: none;">' +
        '</td>' +
        '<td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="fa fa-trash"></i></button></td>';
    salesActivityIndex++;
}

function addClientConcern() {
    const table = document.getElementById('clientConcernsTable').getElementsByTagName('tbody')[0];
    const newRow = table.insertRow();
    newRow.innerHTML =
        '<td>' +
            '<select class="form-control" name="client_concerns[' + clientConcernIndex + '][client_id]">' +
                '<option value="">Select Client</option>' +
                clientOptionsHtml +
            '</select>' +
        '</td>' +
        '<td><textarea class="form-control" name="client_concerns[' + clientConcernIndex + '][issue_concern]" rows="2" placeholder="Issue/Concern"></textarea></td>' +
        '<td><textarea class="form-control" name="client_concerns[' + clientConcernIndex + '][action_taken]" rows="2" placeholder="Action Taken or Required"></textarea></td>' +
        '<td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="fa fa-trash"></i></button></td>';
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

// Toggle payment amount field visibility
function togglePaymentAmount(selectElement) {
    const row = selectElement.closest('tr');
    const paymentAmountInput = row.querySelector('.payment-amount-input');
    const status = selectElement.value;
    
    if (status === 'paid' || status === 'partial') {
        paymentAmountInput.style.display = 'block';
        if (status === 'paid') {
            // Auto-fill with invoice sum if paid in full
            const invoiceSumInput = row.querySelector('input[name*="[invoice_sum]"]');
            if (invoiceSumInput && invoiceSumInput.value && !paymentAmountInput.value) {
                paymentAmountInput.value = invoiceSumInput.value;
            }
        }
    } else {
        paymentAmountInput.style.display = 'none';
        paymentAmountInput.value = ''; // Clear value when not paid
    }
}

// Calculate CAC on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateCAC();
    
    // Initialize payment amount visibility for existing rows
    document.querySelectorAll('.sales-status-select').forEach(function(select) {
        togglePaymentAmount(select);
    });
});
</script>

@endsection
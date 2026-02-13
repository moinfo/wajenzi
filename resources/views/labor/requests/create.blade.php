@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <i class="fa fa-plus"></i> New Labor Request
            <div class="float-right">
                <a href="{{ route('labor.requests.index') }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Labor Request Details</h3>
            </div>
            <div class="block-content">
                <form action="{{ route('labor.requests.store') }}" method="POST">
                    @csrf

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="project_id">Project <span class="text-danger">*</span></label>
                                <select name="project_id" id="project_id" class="form-control select2" required>
                                    <option value="">Select Project</option>
                                    @foreach($projects as $project)
                                        <option value="{{ $project->id }}" {{ old('project_id', $selectedProject) == $project->id ? 'selected' : '' }}>
                                            {{ $project->project_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="construction_phase_id">Construction Phase</label>
                                <select name="construction_phase_id" id="construction_phase_id" class="form-control select2">
                                    <option value="">Select Phase</option>
                                    @foreach($constructionPhases as $phase)
                                        <option value="{{ $phase->id }}">{{ $phase->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="artisan_id">Artisan</label>
                                <select name="artisan_id" id="artisan_id" class="form-control select2">
                                    <option value="">Select Artisan</option>
                                    @foreach($artisans as $artisan)
                                        <option value="{{ $artisan->id }}" {{ old('artisan_id') == $artisan->id ? 'selected' : '' }}>
                                            {{ $artisan->name }} {{ $artisan->trade_skill ? '(' . $artisan->trade_skill . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Can be assigned later before approval</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="work_location">Work Location</label>
                                <input type="text" name="work_location" id="work_location" class="form-control"
                                    value="{{ old('work_location') }}" placeholder="e.g., Block A, Ground Floor">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="work_description">Work Description <span class="text-danger">*</span></label>
                        <textarea name="work_description" id="work_description" class="form-control" rows="4"
                            required placeholder="Describe the work to be done in detail">{{ old('work_description') }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="estimated_duration_days">Duration (Days)</label>
                                <input type="number" name="estimated_duration_days" id="estimated_duration_days"
                                    class="form-control" value="{{ old('estimated_duration_days') }}" min="1">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="start_date">Expected Start Date</label>
                                <input type="text" name="start_date" id="start_date" class="form-control datepicker"
                                    value="{{ old('start_date') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="end_date">Expected End Date</label>
                                <input type="text" name="end_date" id="end_date" class="form-control datepicker"
                                    value="{{ old('end_date') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="currency">Currency</label>
                                <select name="currency" id="currency" class="form-control">
                                    <option value="TZS" {{ old('currency', 'TZS') == 'TZS' ? 'selected' : '' }}>TZS</option>
                                    <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="proposed_amount">Proposed Amount <span class="text-danger">*</span></label>
                                <input type="text" name="proposed_amount" id="proposed_amount"
                                    class="form-control money-input" value="{{ old('proposed_amount') }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="negotiated_amount">Negotiated Amount</label>
                                <input type="text" name="negotiated_amount" id="negotiated_amount"
                                    class="form-control money-input" value="{{ old('negotiated_amount') }}">
                                <small class="text-muted">Fill after negotiation with artisan</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="d-block">&nbsp;</label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="materials_included"
                                        name="materials_included" value="1" {{ old('materials_included') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="materials_included">Materials Included in Price</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="payment_terms">Payment Terms</label>
                        <textarea name="payment_terms" id="payment_terms" class="form-control" rows="2"
                            placeholder="e.g., 20% mobilization, 30% at 50% completion, 30% at 90%, 20% after final inspection">{{ old('payment_terms') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label for="artisan_assessment">Artisan Assessment Notes</label>
                        <textarea name="artisan_assessment" id="artisan_assessment" class="form-control" rows="3"
                            placeholder="Notes from site visit with artisan, scope clarifications, etc.">{{ old('artisan_assessment') }}</textarea>
                    </div>

                    <hr>
                    <div class="text-right">
                        <a href="{{ route('labor.requests.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Create Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js_after')
<script>
    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });

        // Load construction phases when project changes
        function loadConstructionPhases(projectId, selectedId) {
            var select = $('#construction_phase_id');
            // Destroy Select2 before modifying options
            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }
            select.empty().append('<option value="">Select Phase</option>');
            if (!projectId) {
                select.select2({ width: '100%' });
                return;
            }
            $.get('/ajax/get_construction_phases', { project_id: projectId }, function(data) {
                $.each(data, function(i, phase) {
                    select.append('<option value="' + phase.id + '"' + (phase.id == selectedId ? ' selected' : '') + '>' + phase.name + '</option>');
                });
                select.select2({ width: '100%' });
            });
        }

        $('#project_id').on('change', function() {
            loadConstructionPhases($(this).val());
        });

        // Load phases on page load if project is pre-selected
        var initialProject = $('#project_id').val();
        if (initialProject) {
            loadConstructionPhases(initialProject, '{{ old("construction_phase_id") }}');
        }

        // Format money inputs
        $('.money-input').on('keyup', function() {
            var value = $(this).val().replace(/,/g, '');
            if (!isNaN(value) && value !== '') {
                $(this).val(Number(value).toLocaleString());
            }
        });
    });
</script>
@endsection

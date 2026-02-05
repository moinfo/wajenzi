@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <i class="fa fa-edit"></i> Edit Labor Request
            <div class="float-right">
                <a href="{{ route('labor.requests.show', $request->id) }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">{{ $request->request_number }}</h3>
            </div>
            <div class="block-content">
                <form action="{{ route('labor.requests.update', $request->id) }}" method="POST">
                    @csrf

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="project_id">Project</label>
                                <input type="text" class="form-control" value="{{ $request->project?->project_name }}" disabled>
                                <small class="text-muted">Project cannot be changed</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="construction_phase_id">Construction Phase</label>
                                <select name="construction_phase_id" id="construction_phase_id" class="form-control select2">
                                    <option value="">Select Phase</option>
                                    @foreach($constructionPhases as $phase)
                                        <option value="{{ $phase->id }}" {{ $request->construction_phase_id == $phase->id ? 'selected' : '' }}>
                                            {{ $phase->phase_name }}
                                        </option>
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
                                        <option value="{{ $artisan->id }}" {{ $request->artisan_id == $artisan->id ? 'selected' : '' }}>
                                            {{ $artisan->name }} {{ $artisan->trade_skill ? '(' . $artisan->trade_skill . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="work_location">Work Location</label>
                                <input type="text" name="work_location" id="work_location" class="form-control"
                                    value="{{ old('work_location', $request->work_location) }}" placeholder="e.g., Block A, Ground Floor">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="work_description">Work Description <span class="text-danger">*</span></label>
                        <textarea name="work_description" id="work_description" class="form-control" rows="4"
                            required placeholder="Describe the work to be done in detail">{{ old('work_description', $request->work_description) }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="estimated_duration_days">Duration (Days)</label>
                                <input type="number" name="estimated_duration_days" id="estimated_duration_days"
                                    class="form-control" value="{{ old('estimated_duration_days', $request->estimated_duration_days) }}" min="1">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="start_date">Expected Start Date</label>
                                <input type="text" name="start_date" id="start_date" class="form-control datepicker"
                                    value="{{ old('start_date', $request->start_date?->format('Y-m-d')) }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="end_date">Expected End Date</label>
                                <input type="text" name="end_date" id="end_date" class="form-control datepicker"
                                    value="{{ old('end_date', $request->end_date?->format('Y-m-d')) }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="currency">Currency</label>
                                <select name="currency" id="currency" class="form-control">
                                    <option value="TZS" {{ $request->currency == 'TZS' ? 'selected' : '' }}>TZS</option>
                                    <option value="USD" {{ $request->currency == 'USD' ? 'selected' : '' }}>USD</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="proposed_amount">Proposed Amount <span class="text-danger">*</span></label>
                                <input type="text" name="proposed_amount" id="proposed_amount"
                                    class="form-control money-input" value="{{ old('proposed_amount', number_format($request->proposed_amount, 0)) }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="negotiated_amount">Negotiated Amount</label>
                                <input type="text" name="negotiated_amount" id="negotiated_amount"
                                    class="form-control money-input" value="{{ old('negotiated_amount', $request->negotiated_amount ? number_format($request->negotiated_amount, 0) : '') }}">
                                <small class="text-muted">Fill after negotiation with artisan</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="d-block">&nbsp;</label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="materials_included"
                                        name="materials_included" value="1" {{ $request->materials_included ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="materials_included">Materials Included in Price</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="payment_terms">Payment Terms</label>
                        <textarea name="payment_terms" id="payment_terms" class="form-control" rows="2"
                            placeholder="e.g., 20% mobilization, 30% at 50% completion, 30% at 90%, 20% after final inspection">{{ old('payment_terms', $request->payment_terms) }}</textarea>
                    </div>

                    <div class="form-group">
                        <label for="artisan_assessment">Artisan Assessment Notes</label>
                        <textarea name="artisan_assessment" id="artisan_assessment" class="form-control" rows="3"
                            placeholder="Notes from site visit with artisan, scope clarifications, etc.">{{ old('artisan_assessment', $request->artisan_assessment) }}</textarea>
                    </div>

                    <hr>
                    <div class="text-right">
                        <a href="{{ route('labor.requests.show', $request->id) }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Update Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        $('.select2').select2();
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });

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

@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <i class="fa fa-search-plus"></i> New Labor Inspection
            <div class="float-right">
                <a href="{{ route('labor.contracts.show', $contract->id) }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Back to Contract
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Inspection Details</h3>
                    </div>
                    <div class="block-content">
                        <form action="{{ route('labor.inspections.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="labor_contract_id" value="{{ $contract->id }}">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="inspection_type">Inspection Type <span class="text-danger">*</span></label>
                                        <select name="inspection_type" id="inspection_type" class="form-control" required>
                                            <option value="progress">Progress Inspection</option>
                                            <option value="milestone">Milestone Inspection</option>
                                            <option value="final">Final Inspection</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_phase_id">Associated Payment Phase</label>
                                        <select name="payment_phase_id" id="payment_phase_id" class="form-control">
                                            <option value="">None</option>
                                            @foreach($contract->paymentPhases as $phase)
                                                <option value="{{ $phase->id }}">
                                                    Phase {{ $phase->phase_number }}: {{ $phase->phase_name }} ({{ $phase->status }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="completion_percentage">Completion % <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" name="completion_percentage" id="completion_percentage"
                                                class="form-control" value="{{ old('completion_percentage', $latestProgress) }}"
                                                min="0" max="100" step="0.1" required>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="work_quality">Work Quality <span class="text-danger">*</span></label>
                                        <select name="work_quality" id="work_quality" class="form-control" required>
                                            <option value="excellent">Excellent</option>
                                            <option value="good" selected>Good</option>
                                            <option value="acceptable">Acceptable</option>
                                            <option value="poor">Poor</option>
                                            <option value="unacceptable">Unacceptable</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="result">Result <span class="text-danger">*</span></label>
                                        <select name="result" id="result" class="form-control" required>
                                            <option value="pass">Pass</option>
                                            <option value="conditional">Conditional Pass</option>
                                            <option value="fail">Fail</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="d-block">Scope Compliance</label>
                                        <div class="custom-control custom-checkbox custom-control-inline">
                                            <input type="checkbox" class="custom-control-input" id="scope_compliance"
                                                name="scope_compliance" value="1" checked>
                                            <label class="custom-control-label" for="scope_compliance">
                                                Work complies with scope of work
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="d-block">Rectification Required</label>
                                        <div class="custom-control custom-checkbox custom-control-inline">
                                            <input type="checkbox" class="custom-control-input" id="rectification_required"
                                                name="rectification_required" value="1">
                                            <label class="custom-control-label" for="rectification_required">
                                                Rectification work needed
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="defects_found">Defects Found</label>
                                <textarea name="defects_found" id="defects_found" class="form-control" rows="3"
                                    placeholder="List any defects observed...">{{ old('defects_found') }}</textarea>
                            </div>

                            <div class="form-group" id="rectification-notes-group" style="display: none;">
                                <label for="rectification_notes">Rectification Notes</label>
                                <textarea name="rectification_notes" id="rectification_notes" class="form-control" rows="3"
                                    placeholder="Describe required rectification work...">{{ old('rectification_notes') }}</textarea>
                            </div>

                            <div class="form-group">
                                <label for="notes">Inspector Notes</label>
                                <textarea name="notes" id="notes" class="form-control" rows="3"
                                    placeholder="Additional observations or notes...">{{ old('notes') }}</textarea>
                            </div>

                            <div class="form-group">
                                <label for="photos">Photos</label>
                                <input type="file" name="photos[]" id="photos" class="form-control" multiple
                                    accept="image/*">
                                <small class="text-muted">Upload inspection photos (max 5MB each)</small>
                            </div>

                            <hr>
                            <div class="text-right">
                                <a href="{{ route('labor.contracts.show', $contract->id) }}" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> Create & Submit Inspection
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Contract Info</h3>
                    </div>
                    <div class="block-content">
                        <p><strong>Contract #:</strong> {{ $contract->contract_number }}</p>
                        <p><strong>Project:</strong> {{ $contract->project?->project_name }}</p>
                        <p><strong>Artisan:</strong> {{ $contract->artisan?->name }}</p>
                        <p><strong>Start:</strong> {{ $contract->start_date?->format('Y-m-d') }}</p>
                        <p><strong>End:</strong> {{ $contract->end_date?->format('Y-m-d') }}</p>

                        <hr>
                        <h6>Scope of Work</h6>
                        <p class="small bg-light p-2 rounded">{{ Str::limit($contract->scope_of_work, 200) }}</p>
                    </div>
                </div>

                @if($contract->workLogs->count() > 0)
                    <div class="block">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">Recent Work Logs</h3>
                        </div>
                        <div class="block-content">
                            @foreach($contract->workLogs as $log)
                                <div class="mb-2 pb-2 border-bottom">
                                    <strong>{{ $log->log_date->format('Y-m-d') }}</strong>
                                    <br>
                                    <small>{{ Str::limit($log->work_done, 100) }}</small>
                                    <br>
                                    <span class="badge badge-info">{{ $log->progress_percentage }}%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        // Show/hide rectification notes
        $('#rectification_required').change(function() {
            if ($(this).is(':checked')) {
                $('#rectification-notes-group').show();
            } else {
                $('#rectification-notes-group').hide();
            }
        });

        // Auto-set result based on quality
        $('#work_quality').change(function() {
            var quality = $(this).val();
            if (quality === 'unacceptable') {
                $('#result').val('fail');
            } else if (quality === 'poor') {
                $('#result').val('conditional');
            } else {
                $('#result').val('pass');
            }
        });
    });
</script>
@endsection

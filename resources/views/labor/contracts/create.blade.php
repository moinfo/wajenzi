@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <i class="fa fa-file-contract"></i> Create Labor Contract
            <div class="float-right">
                <a href="{{ route('labor.contracts.index') }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Contract Details</h3>
                    </div>
                    <div class="block-content">
                        <form action="{{ route('labor.contracts.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="labor_request_id" value="{{ $request->id }}">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Project</label>
                                        <input type="text" class="form-control" value="{{ $request->project?->project_name }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Artisan</label>
                                        <input type="text" class="form-control" value="{{ $request->artisan?->name }} ({{ $request->artisan?->trade_skill }})" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="supervisor_id">Site Supervisor</label>
                                        <select name="supervisor_id" id="supervisor_id" class="form-control select2">
                                            <option value="">Select Supervisor</option>
                                            @foreach($supervisors as $supervisor)
                                                <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="start_date">Start Date <span class="text-danger">*</span></label>
                                        <input type="text" name="start_date" id="start_date" class="form-control datepicker"
                                            value="{{ old('start_date', $request->start_date?->format('Y-m-d')) }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="end_date">End Date <span class="text-danger">*</span></label>
                                        <input type="text" name="end_date" id="end_date" class="form-control datepicker"
                                            value="{{ old('end_date', $request->end_date?->format('Y-m-d')) }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="total_amount">Total Contract Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="total_amount" id="total_amount" class="form-control money-input"
                                        value="{{ old('total_amount', number_format($request->final_amount, 0)) }}" required>
                                    <span class="input-group-text">{{ $request->currency }}</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="scope_of_work">Scope of Work <span class="text-danger">*</span></label>
                                <textarea name="scope_of_work" id="scope_of_work" class="form-control" rows="4" required>{{ old('scope_of_work', $request->work_description) }}</textarea>
                            </div>

                            <div class="form-group">
                                <label for="terms_conditions">Terms & Conditions</label>
                                <textarea name="terms_conditions" id="terms_conditions" class="form-control" rows="4">{{ old('terms_conditions', $request->payment_terms) }}</textarea>
                            </div>

                            <hr>
                            <h5>Payment Phases</h5>
                            <p class="text-muted">Define payment milestones. Percentages must total 100%.</p>

                            <div id="payment-phases">
                                @foreach($defaultPhases as $index => $phase)
                                    <div class="row payment-phase-row mb-2">
                                        <div class="col-md-1">
                                            <input type="number" name="phases[{{ $index }}][phase_number]" class="form-control"
                                                value="{{ $phase['phase_number'] }}" readonly>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" name="phases[{{ $index }}][phase_name]" class="form-control"
                                                value="{{ $phase['phase_name'] }}" placeholder="Phase Name" required>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="input-group">
                                                <input type="number" name="phases[{{ $index }}][percentage]" class="form-control phase-percentage"
                                                    value="{{ $phase['percentage'] }}" min="0" max="100" required>
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" name="phases[{{ $index }}][milestone_description]" class="form-control"
                                                value="{{ $phase['milestone_description'] }}" placeholder="Milestone description">
                                        </div>
                                        <div class="col-md-2">
                                            <span class="phase-amount badge badge-info" style="font-size: 1rem;">0</span>
                                        </div>
                                        <div class="col-md-1">
                                            @if($index > 0)
                                                <button type="button" class="btn btn-sm btn-danger remove-phase">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12 text-right">
                                    <strong>Total: <span id="total-percentage">100</span>%</strong>
                                </div>
                            </div>

                            <hr>
                            <div class="text-right">
                                <a href="{{ route('labor.contracts.index') }}" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> Create Contract
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Request Summary</h3>
                    </div>
                    <div class="block-content">
                        <p><strong>Request #:</strong> {{ $request->request_number }}</p>
                        <p><strong>Work Location:</strong> {{ $request->work_location ?? 'N/A' }}</p>
                        <p><strong>Duration:</strong> {{ $request->estimated_duration_days }} days</p>
                        <p><strong>Materials Included:</strong> {{ $request->materials_included ? 'Yes' : 'No' }}</p>

                        <hr>
                        <h6>Financial</h6>
                        <p><strong>Proposed:</strong> {{ number_format($request->proposed_amount, 0) }} {{ $request->currency }}</p>
                        @if($request->negotiated_amount)
                            <p><strong>Negotiated:</strong> {{ number_format($request->negotiated_amount, 0) }} {{ $request->currency }}</p>
                        @endif
                        <p class="text-success"><strong>Approved:</strong> {{ number_format($request->approved_amount ?? $request->final_amount, 0) }} {{ $request->currency }}</p>
                    </div>
                </div>

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Artisan Details</h3>
                    </div>
                    <div class="block-content">
                        <p><strong>Name:</strong> {{ $request->artisan?->name }}</p>
                        <p><strong>Trade:</strong> {{ $request->artisan?->trade_skill ?? 'N/A' }}</p>
                        <p><strong>Phone:</strong> {{ $request->artisan?->phone ?? 'N/A' }}</p>
                        <p><strong>ID Number:</strong> {{ $request->artisan?->id_number ?? 'N/A' }}</p>
                    </div>
                </div>
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

        // Calculate phase amounts when total or percentage changes
        function calculatePhaseAmounts() {
            var total = parseFloat($('#total_amount').val().replace(/,/g, '')) || 0;
            var totalPercentage = 0;

            $('.payment-phase-row').each(function() {
                var percentage = parseFloat($(this).find('.phase-percentage').val()) || 0;
                var amount = (percentage / 100) * total;
                $(this).find('.phase-amount').text(amount.toLocaleString());
                totalPercentage += percentage;
            });

            $('#total-percentage').text(totalPercentage);
            if (totalPercentage !== 100) {
                $('#total-percentage').addClass('text-danger').removeClass('text-success');
            } else {
                $('#total-percentage').addClass('text-success').removeClass('text-danger');
            }
        }

        $('#total_amount, .phase-percentage').on('keyup change', calculatePhaseAmounts);
        calculatePhaseAmounts();

        // Format money input
        $('#total_amount').on('keyup', function() {
            var value = $(this).val().replace(/,/g, '');
            if (!isNaN(value) && value !== '') {
                $(this).val(Number(value).toLocaleString());
            }
        });

        // Remove phase
        $(document).on('click', '.remove-phase', function() {
            $(this).closest('.payment-phase-row').remove();
            calculatePhaseAmounts();
        });
    });
</script>
@endsection

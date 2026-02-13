@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <i class="fa fa-clipboard-check"></i> Add Work Log
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
                        <h3 class="block-title">Work Log Entry</h3>
                    </div>
                    <div class="block-content">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <form action="{{ route('labor.logs.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="labor_contract_id" value="{{ $contract->id }}">

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="log_date">Log Date <span class="text-danger">*</span></label>
                                        <input type="text" name="log_date" id="log_date" class="form-control datepicker"
                                            value="{{ old('log_date', date('Y-m-d')) }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="workers_present">Workers Present <span class="text-danger">*</span></label>
                                        <input type="number" name="workers_present" id="workers_present" class="form-control"
                                            value="{{ old('workers_present', 1) }}" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="hours_worked">Hours Worked</label>
                                        <input type="number" name="hours_worked" id="hours_worked" class="form-control"
                                            value="{{ old('hours_worked') }}" min="0" max="24" step="0.5">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="progress_percentage">Overall Progress %</label>
                                        <div class="input-group">
                                            <input type="number" name="progress_percentage" id="progress_percentage"
                                                class="form-control" value="{{ old('progress_percentage', $lastLog?->progress_percentage) }}"
                                                min="0" max="100" step="0.1">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        @if($lastLog)
                                            <small class="text-muted">Last recorded: {{ $lastLog->progress_percentage }}%</small>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="weather_conditions">Weather</label>
                                        <select name="weather_conditions" id="weather_conditions" class="form-control">
                                            <option value="">Select weather</option>
                                            <option value="sunny">Sunny</option>
                                            <option value="cloudy">Cloudy</option>
                                            <option value="rainy">Rainy</option>
                                            <option value="stormy">Stormy</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="work_done">Work Done Today <span class="text-danger">*</span></label>
                                <textarea name="work_done" id="work_done" class="form-control" rows="4"
                                    required placeholder="Describe the work completed today...">{{ old('work_done') }}</textarea>
                            </div>

                            <div class="form-group">
                                <label for="challenges">Challenges/Issues</label>
                                <textarea name="challenges" id="challenges" class="form-control" rows="2"
                                    placeholder="Any challenges or issues encountered...">{{ old('challenges') }}</textarea>
                            </div>

                            <div class="form-group">
                                <label for="notes">Additional Notes</label>
                                <textarea name="notes" id="notes" class="form-control" rows="2"
                                    placeholder="Any other observations...">{{ old('notes') }}</textarea>
                            </div>

                            <div class="form-group">
                                <label for="photos">Photos</label>
                                <input type="file" name="photos[]" id="photos" class="form-control" multiple
                                    accept="image/*">
                                <small class="text-muted">Upload work progress photos (max 5MB each)</small>
                            </div>

                            <hr>
                            <div class="text-right">
                                <a href="{{ route('labor.contracts.show', $contract->id) }}" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> Save Work Log
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
                        <p><strong>Contract:</strong> {{ $contract->contract_number }}</p>
                        <p><strong>Artisan:</strong> {{ $contract->artisan?->name }}</p>
                        <p><strong>Project:</strong> {{ $contract->project?->project_name }}</p>
                        <p><strong>Start:</strong> {{ $contract->start_date?->format('Y-m-d') }}</p>
                        <p><strong>End:</strong> {{ $contract->end_date?->format('Y-m-d') }}</p>
                        <hr>
                        <p><strong>Current Progress:</strong> {{ number_format($contract->latest_progress, 1) }}%</p>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-info" style="width: {{ $contract->latest_progress }}%">
                                {{ number_format($contract->latest_progress, 0) }}%
                            </div>
                        </div>
                    </div>
                </div>

                @if($lastLog)
                    <div class="block">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">Last Log Entry</h3>
                        </div>
                        <div class="block-content">
                            <p><strong>Date:</strong> {{ $lastLog->log_date->format('Y-m-d') }}</p>
                            <p><strong>Work Done:</strong></p>
                            <p class="small bg-light p-2 rounded">{{ Str::limit($lastLog->work_done, 200) }}</p>
                            @if($lastLog->challenges)
                                <p><strong>Challenges:</strong></p>
                                <p class="small bg-light p-2 rounded">{{ Str::limit($lastLog->challenges, 100) }}</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('js_after')
<script>
    $(document).ready(function() {
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });
    });
</script>
@endsection

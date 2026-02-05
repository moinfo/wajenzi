@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <i class="fa fa-edit"></i> Edit Work Log
            <div class="float-right">
                <a href="{{ route('labor.contracts.show', $log->labor_contract_id) }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Back to Contract
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Edit Log Entry - {{ $log->log_date->format('Y-m-d') }}</h3>
                    </div>
                    <div class="block-content">
                        <form action="{{ route('labor.logs.update', $log->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Log Date</label>
                                        <input type="text" class="form-control" value="{{ $log->log_date->format('Y-m-d') }}" disabled>
                                        <small class="text-muted">Date cannot be changed</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="workers_present">Workers Present <span class="text-danger">*</span></label>
                                        <input type="number" name="workers_present" id="workers_present" class="form-control"
                                            value="{{ old('workers_present', $log->workers_present) }}" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="hours_worked">Hours Worked</label>
                                        <input type="number" name="hours_worked" id="hours_worked" class="form-control"
                                            value="{{ old('hours_worked', $log->hours_worked) }}" min="0" max="24" step="0.5">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="progress_percentage">Overall Progress %</label>
                                        <div class="input-group">
                                            <input type="number" name="progress_percentage" id="progress_percentage"
                                                class="form-control" value="{{ old('progress_percentage', $log->progress_percentage) }}"
                                                min="0" max="100" step="0.1">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="weather_conditions">Weather</label>
                                        <select name="weather_conditions" id="weather_conditions" class="form-control">
                                            <option value="">Select weather</option>
                                            <option value="sunny" {{ $log->weather_conditions == 'sunny' ? 'selected' : '' }}>Sunny</option>
                                            <option value="cloudy" {{ $log->weather_conditions == 'cloudy' ? 'selected' : '' }}>Cloudy</option>
                                            <option value="rainy" {{ $log->weather_conditions == 'rainy' ? 'selected' : '' }}>Rainy</option>
                                            <option value="stormy" {{ $log->weather_conditions == 'stormy' ? 'selected' : '' }}>Stormy</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="work_done">Work Done Today <span class="text-danger">*</span></label>
                                <textarea name="work_done" id="work_done" class="form-control" rows="4"
                                    required placeholder="Describe the work completed today...">{{ old('work_done', $log->work_done) }}</textarea>
                            </div>

                            <div class="form-group">
                                <label for="challenges">Challenges/Issues</label>
                                <textarea name="challenges" id="challenges" class="form-control" rows="2"
                                    placeholder="Any challenges or issues encountered...">{{ old('challenges', $log->challenges) }}</textarea>
                            </div>

                            <div class="form-group">
                                <label for="notes">Additional Notes</label>
                                <textarea name="notes" id="notes" class="form-control" rows="2"
                                    placeholder="Any other observations...">{{ old('notes', $log->notes) }}</textarea>
                            </div>

                            @if($log->photos && count($log->photos) > 0)
                                <div class="form-group">
                                    <label>Existing Photos</label>
                                    <div class="row">
                                        @foreach($log->photos as $photo)
                                            <div class="col-md-3 mb-2">
                                                <img src="{{ $photo }}" class="img-fluid rounded" alt="Work Log Photo">
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="form-group">
                                <label for="photos">Add More Photos</label>
                                <input type="file" name="photos[]" id="photos" class="form-control" multiple
                                    accept="image/*">
                                <small class="text-muted">Upload additional work progress photos (max 5MB each)</small>
                            </div>

                            <hr>
                            <div class="text-right">
                                <a href="{{ route('labor.contracts.show', $log->labor_contract_id) }}" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> Update Work Log
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
                        <p><strong>Contract:</strong> {{ $log->contract?->contract_number }}</p>
                        <p><strong>Artisan:</strong> {{ $log->contract?->artisan?->name }}</p>
                        <p><strong>Project:</strong> {{ $log->contract?->project?->project_name }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

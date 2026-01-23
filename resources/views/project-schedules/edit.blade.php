@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <!-- Header -->
        <div class="content-heading d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2><i class="fa fa-edit text-warning mr-2"></i> Edit Project Schedule</h2>
                <small class="text-muted">{{ $projectSchedule->lead->lead_number ?? 'Lead' }} - {{ $projectSchedule->lead->name ?? '' }}</small>
            </div>
            <div>
                <a href="{{ route('project-schedules.show', $projectSchedule) }}" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Back to Schedule
                </a>
            </div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                {{ session('error') }}
            </div>
        @endif

        <div class="row">
            <div class="col-md-6">
                <!-- Edit Form -->
                <div class="block block-themed">
                    <div class="block-header bg-warning">
                        <h3 class="block-title">Modify Schedule Start Date</h3>
                    </div>
                    <div class="block-content">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle mr-2"></i>
                            Changing the start date will automatically recalculate all activity dates based on working days (excluding weekends and public holidays).
                        </div>

                        <form action="{{ route('project-schedules.update', $projectSchedule) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="form-group">
                                <label><strong>Current Start Date</strong></label>
                                <p class="form-control-static">{{ $projectSchedule->start_date->format('d/m/Y (l)') }}</p>
                            </div>

                            <div class="form-group">
                                <label><strong>Current End Date</strong></label>
                                <p class="form-control-static">{{ $projectSchedule->end_date ? $projectSchedule->end_date->format('d/m/Y (l)') : 'N/A' }}</p>
                            </div>

                            <hr>

                            <div class="form-group">
                                <label for="start_date"><strong>New Start Date</strong> <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror"
                                       value="{{ old('start_date', $projectSchedule->start_date->format('Y-m-d')) }}"
                                       min="{{ date('Y-m-d') }}" required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">All activity dates will be recalculated from this date.</small>
                            </div>

                            <div class="form-group">
                                <label for="notes"><strong>Notes</strong> (Optional)</label>
                                <textarea name="notes" id="notes" class="form-control" rows="3"
                                          placeholder="Add any notes about this schedule change...">{{ old('notes', $projectSchedule->notes) }}</textarea>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fa fa-calculator mr-1"></i> Recalculate Dates
                                </button>
                                <a href="{{ route('project-schedules.show', $projectSchedule) }}" class="btn btn-secondary">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <!-- Current Schedule Preview -->
                <div class="block block-themed">
                    <div class="block-header bg-primary">
                        <h3 class="block-title">Current Activity Schedule</h3>
                    </div>
                    <div class="block-content">
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Activity</th>
                                        <th>Start</th>
                                        <th>End</th>
                                        <th>Days</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($projectSchedule->activities as $activity)
                                        <tr>
                                            <td><strong>{{ $activity->activity_code }}</strong></td>
                                            <td>
                                                <small>{{ Str::limit($activity->name, 30) }}</small>
                                            </td>
                                            <td>{{ $activity->start_date->format('d/m') }}</td>
                                            <td>{{ $activity->end_date->format('d/m') }}</td>
                                            <td>{{ $activity->duration_days }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Holiday Info -->
                <div class="block block-themed">
                    <div class="block-header bg-info">
                        <h3 class="block-title"><i class="fa fa-calendar-times mr-2"></i> Excluded Holidays</h3>
                    </div>
                    <div class="block-content">
                        <p class="text-muted">The following public holidays are excluded from working day calculations:</p>
                        @php
                            $holidays = \App\Models\ProjectHoliday::where('year', date('Y'))
                                ->orWhere('year', date('Y') + 1)
                                ->orderBy('date')
                                ->get();
                        @endphp
                        <div class="row">
                            @foreach($holidays->chunk(ceil($holidays->count() / 2)) as $chunk)
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        @foreach($chunk as $holiday)
                                            <li><small>{{ $holiday->date->format('d/m/Y') }} - {{ $holiday->name }}</small></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <i class="fa fa-clipboard-check"></i> Work Log Details
            <div class="float-right">
                <a href="{{ route('labor.logs.index') }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Log Entry - {{ $log->log_date->format('Y-m-d') }}</h3>
                    </div>
                    <div class="block-content">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td class="text-muted" width="40%">Log Date:</td>
                                        <td><strong>{{ $log->log_date->format('l, F j, Y') }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Logged By:</td>
                                        <td>{{ $log->logger?->name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Workers Present:</td>
                                        <td><strong>{{ $log->workers_present }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Hours Worked:</td>
                                        <td>{{ $log->hours_worked ?? '-' }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td class="text-muted" width="40%">Progress:</td>
                                        <td>
                                            @if($log->progress_percentage)
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-info" style="width: {{ $log->progress_percentage }}%">
                                                        {{ number_format($log->progress_percentage, 0) }}%
                                                    </div>
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Weather:</td>
                                        <td>
                                            @if($log->weather_conditions)
                                                @php
                                                    $weatherIcons = [
                                                        'sunny' => 'fa-sun text-warning',
                                                        'cloudy' => 'fa-cloud text-secondary',
                                                        'rainy' => 'fa-cloud-rain text-info',
                                                        'stormy' => 'fa-bolt text-danger'
                                                    ];
                                                @endphp
                                                <i class="fa {{ $weatherIcons[$log->weather_conditions] ?? 'fa-question' }}"></i>
                                                {{ ucfirst($log->weather_conditions) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Created:</td>
                                        <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="text-muted">Work Done</h6>
                            <div class="bg-light p-3 rounded">
                                {{ $log->work_done }}
                            </div>
                        </div>

                        @if($log->challenges)
                            <div class="mb-4">
                                <h6 class="text-muted">Challenges/Issues</h6>
                                <div class="bg-warning-light p-3 rounded border-left border-warning" style="border-left-width: 4px !important;">
                                    {{ $log->challenges }}
                                </div>
                            </div>
                        @endif

                        @if($log->notes)
                            <div class="mb-4">
                                <h6 class="text-muted">Additional Notes</h6>
                                <div class="bg-light p-3 rounded">
                                    {{ $log->notes }}
                                </div>
                            </div>
                        @endif

                        @if($log->materials_used && count($log->materials_used) > 0)
                            <div class="mb-4">
                                <h6 class="text-muted">Materials Used</h6>
                                <ul class="list-group">
                                    @foreach($log->materials_used as $material)
                                        <li class="list-group-item">{{ $material }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($log->photos && count($log->photos) > 0)
                            <div class="mb-4">
                                <h6 class="text-muted">Photos</h6>
                                <div class="row">
                                    @foreach($log->photos as $photo)
                                        <div class="col-md-4 mb-2">
                                            <a href="{{ $photo }}" target="_blank">
                                                <img src="{{ $photo }}" class="img-fluid rounded" alt="Work Log Photo">
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Contract Info</h3>
                    </div>
                    <div class="block-content">
                        <p><strong>Contract #:</strong>
                            <a href="{{ route('labor.contracts.show', $log->labor_contract_id) }}">
                                {{ $log->contract?->contract_number }}
                            </a>
                        </p>
                        <p><strong>Project:</strong> {{ $log->contract?->project?->project_name }}</p>
                        <p><strong>Artisan:</strong> {{ $log->contract?->artisan?->name }}</p>
                        <p><strong>Trade:</strong> {{ $log->contract?->artisan?->trade_skill }}</p>
                        <hr>
                        <p><strong>Contract Status:</strong>
                            <span class="badge badge-{{ $log->contract?->status_badge_class }}">
                                {{ ucfirst($log->contract?->status) }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Actions</h3>
                    </div>
                    <div class="block-content">
                        @if($log->log_date->diffInDays(now()) <= 3)
                            <a href="{{ route('labor.logs.edit', $log->id) }}"
                                class="btn btn-warning btn-block mb-2">
                                <i class="fa fa-edit"></i> Edit Log
                            </a>
                        @endif

                        <a href="{{ route('labor.contracts.show', $log->labor_contract_id) }}"
                            class="btn btn-primary btn-block mb-2">
                            <i class="fa fa-file-contract"></i> View Contract
                        </a>

                        <a href="{{ route('labor.logs.index') }}" class="btn btn-secondary btn-block">
                            <i class="fa fa-list"></i> All Logs
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

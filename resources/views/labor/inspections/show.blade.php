@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <i class="fa fa-search-plus"></i> Inspection Details
            <div class="float-right">
                <a href="{{ route('labor.inspections.index') }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">{{ $inspection->inspection_number }}</h3>
                        <div class="block-options">
                            <span class="badge badge-{{ $inspection->status_badge_class }} badge-lg">
                                {{ ucfirst($inspection->status) }}
                            </span>
                        </div>
                    </div>
                    <div class="block-content">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td class="text-muted" width="40%">Inspection Type:</td>
                                        <td><strong>{{ ucfirst($inspection->inspection_type) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Inspection Date:</td>
                                        <td>{{ $inspection->inspection_date?->format('Y-m-d') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Inspector:</td>
                                        <td>{{ $inspection->inspector?->name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Result:</td>
                                        <td>
                                            @if($inspection->result === 'pass')
                                                <span class="badge badge-success">Pass</span>
                                            @elseif($inspection->result === 'conditional')
                                                <span class="badge badge-warning">Conditional Pass</span>
                                            @else
                                                <span class="badge badge-danger">Fail</span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td class="text-muted" width="40%">Completion %:</td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-info" style="width: {{ $inspection->completion_percentage }}%">
                                                    {{ number_format($inspection->completion_percentage, 0) }}%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Work Quality:</td>
                                        <td>
                                            @php
                                                $qualityColors = [
                                                    'excellent' => 'success',
                                                    'good' => 'info',
                                                    'acceptable' => 'warning',
                                                    'poor' => 'danger',
                                                    'unacceptable' => 'danger'
                                                ];
                                            @endphp
                                            <span class="badge badge-{{ $qualityColors[$inspection->work_quality] ?? 'secondary' }}">
                                                {{ ucfirst($inspection->work_quality) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Scope Compliance:</td>
                                        <td>
                                            @if($inspection->scope_compliance)
                                                <span class="text-success"><i class="fa fa-check-circle"></i> Compliant</span>
                                            @else
                                                <span class="text-danger"><i class="fa fa-times-circle"></i> Non-compliant</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Rectification:</td>
                                        <td>
                                            @if($inspection->rectification_required)
                                                <span class="text-warning"><i class="fa fa-exclamation-triangle"></i> Required</span>
                                            @else
                                                <span class="text-success"><i class="fa fa-check"></i> Not Required</span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        @if($inspection->defects_found)
                            <div class="mb-4">
                                <h6 class="text-muted">Defects Found</h6>
                                <div class="bg-light p-3 rounded">
                                    {{ $inspection->defects_found }}
                                </div>
                            </div>
                        @endif

                        @if($inspection->rectification_notes)
                            <div class="mb-4">
                                <h6 class="text-muted">Rectification Notes</h6>
                                <div class="bg-warning-light p-3 rounded">
                                    {{ $inspection->rectification_notes }}
                                </div>
                            </div>
                        @endif

                        @if($inspection->notes)
                            <div class="mb-4">
                                <h6 class="text-muted">Inspector Notes</h6>
                                <div class="bg-light p-3 rounded">
                                    {{ $inspection->notes }}
                                </div>
                            </div>
                        @endif

                        @if($inspection->photos && count($inspection->photos) > 0)
                            <div class="mb-4">
                                <h6 class="text-muted">Inspection Photos</h6>
                                <div class="row">
                                    @foreach($inspection->photos as $photo)
                                        <div class="col-md-4 mb-2">
                                            <a href="{{ $photo }}" target="_blank">
                                                <img src="{{ $photo }}" class="img-fluid rounded" alt="Inspection Photo">
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($inspection->status === 'verified')
                            <div class="alert alert-success">
                                <i class="fa fa-check-circle"></i>
                                <strong>Verified by:</strong> {{ $inspection->verifier?->name }}
                                on {{ $inspection->verified_at?->format('Y-m-d H:i') }}
                            </div>
                        @endif
                    </div>
                </div>

                @if($inspection->paymentPhase)
                    <div class="block">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">Associated Payment Phase</h3>
                        </div>
                        <div class="block-content">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="30%">Phase:</td>
                                    <td><strong>{{ $inspection->paymentPhase->phase_name }}</strong> ({{ $inspection->paymentPhase->percentage }}%)</td>
                                </tr>
                                <tr>
                                    <td>Amount:</td>
                                    <td>{{ number_format($inspection->paymentPhase->amount, 0) }} {{ $inspection->contract?->currency }}</td>
                                </tr>
                                <tr>
                                    <td>Milestone:</td>
                                    <td>{{ $inspection->paymentPhase->milestone_description }}</td>
                                </tr>
                                <tr>
                                    <td>Status:</td>
                                    <td>
                                        <span class="badge badge-{{ $inspection->paymentPhase->status_badge_class }}">
                                            {{ ucfirst($inspection->paymentPhase->status) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-md-4">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Contract Info</h3>
                    </div>
                    <div class="block-content">
                        <p><strong>Contract #:</strong>
                            <a href="{{ route('labor.contracts.show', $inspection->labor_contract_id) }}">
                                {{ $inspection->contract?->contract_number }}
                            </a>
                        </p>
                        <p><strong>Project:</strong> {{ $inspection->contract?->project?->project_name }}</p>
                        <p><strong>Artisan:</strong> {{ $inspection->contract?->artisan?->name }}</p>
                        <p><strong>Trade:</strong> {{ $inspection->contract?->artisan?->trade_skill }}</p>
                        <hr>
                        <p><strong>Contract Amount:</strong><br>
                            {{ number_format($inspection->contract?->total_amount, 0) }} {{ $inspection->contract?->currency }}
                        </p>
                        <p><strong>Amount Paid:</strong><br>
                            <span class="text-success">{{ number_format($inspection->contract?->amount_paid, 0) }} {{ $inspection->contract?->currency }}</span>
                        </p>
                        <p><strong>Balance:</strong><br>
                            {{ number_format($inspection->contract?->balance_amount, 0) }} {{ $inspection->contract?->currency }}
                        </p>
                    </div>
                </div>

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Actions</h3>
                    </div>
                    <div class="block-content">
                        @if($inspection->status === 'pending')
                            <a href="{{ route('labor.contracts.show', $inspection->labor_contract_id) }}"
                                class="btn btn-primary btn-block mb-2">
                                <i class="fa fa-eye"></i> View Contract
                            </a>
                            @if($inspection->log_date && $inspection->log_date->diffInDays(now()) <= 3)
                                <a href="{{ route('labor.inspections.edit', $inspection->id) }}"
                                    class="btn btn-warning btn-block mb-2">
                                    <i class="fa fa-edit"></i> Edit Inspection
                                </a>
                            @endif
                        @endif

                        <a href="{{ route('labor.inspections.index') }}" class="btn btn-secondary btn-block">
                            <i class="fa fa-list"></i> All Inspections
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

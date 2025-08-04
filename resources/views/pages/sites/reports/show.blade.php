@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">Site Daily Report</h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item">Projects</li>
                    <li class="breadcrumb-item"><a href="{{ route('site-daily-reports.index') }}">Site Daily Reports</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $report->report_date->format('M d, Y') }}</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    @include('partials.alerts')

    <!-- Report Header -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">WAJENZI PROFESSIONAL CO LTD - Site Daily Report</h3>
            <div class="block-options">
                @can('Export Site Reports')
                    <a href="{{ route('site-daily-reports.export', $report) }}" class="btn btn-sm btn-info">
                        <i class="fa fa-download"></i> Export
                    </a>
                @endcan
                @can('Share Site Reports')
                    <a href="{{ route('site-daily-reports.share', $report) }}" class="btn btn-sm btn-success" target="_blank">
                        <i class="fa fa-share"></i> Share WhatsApp
                    </a>
                @endcan
                @if($report->canEdit() && (Auth::user()->can('Edit All Site Reports') || Auth::user()->id == $report->prepared_by))
                    <a href="{{ route('site-daily-reports.edit', $report) }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-pencil"></i> Edit
                    </a>
                @endif
            </div>
        </div>
        <div class="block-content">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>🗓️ Date (Tarehe):</strong></td>
                            <td>{{ $report->report_date->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td><strong>📌 Site:</strong></td>
                            <td>{{ $report->site->name }}</td>
                        </tr>
                        <tr>
                            <td><strong>📍 Location:</strong></td>
                            <td>{{ $report->site->location }}</td>
                        </tr>
                        <tr>
                            <td><strong>👷🏽‍♂️ Site Supervisor:</strong></td>
                            <td>{{ $report->supervisor->name }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>📊 Progress (Maendeleo):</strong></td>
                            <td>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                                         role="progressbar" style="width: {{ $report->progress_percentage }}%"
                                         aria-valuenow="{{ $report->progress_percentage }}"
                                         aria-valuemin="0" aria-valuemax="100">
                                        {{ number_format($report->progress_percentage, 1) }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>👤 Prepared By:</strong></td>
                            <td>{{ $report->preparedBy->name }}</td>
                        </tr>
                        <tr>
                            <td><strong>📅 Created:</strong></td>
                            <td>{{ $report->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                @php
                                    $statusClass = [
                                        'DRAFT' => 'secondary',
                                        'PENDING' => 'warning',
                                        'APPROVED' => 'success',
                                        'REJECTED' => 'danger'
                                    ][$report->status] ?? 'secondary';
                                @endphp
                                <span class="badge badge-{{ $statusClass }} badge-pill">{{ $report->status }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- RingleSoft Approval Actions -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Approval Actions</h3>
        </div>
        <div class="block-content">
            <x-ringlesoft-approval-actions :model="$report" />
        </div>
    </div>

    <!-- Work Activities -->
    @if($report->workActivities->count() > 0)
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">🛠️ Work Activities (Kazi)</h3>
            </div>
            <div class="block-content">
                <ol class="list-group list-group-flush">
                    @foreach($report->workActivities as $activity)
                        <li class="list-group-item">{{ $activity->work_description }}</li>
                    @endforeach
                </ol>
            </div>
        </div>
    @endif

    <!-- Materials Used -->
    @if($report->materialsUsed->count() > 0)
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">📦 Materials Used (Vifaa)</h3>
            </div>
            <div class="block-content">
                <ul class="list-group list-group-flush">
                    @foreach($report->materialsUsed as $material)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>{{ $material->material_name }}</span>
                            @if($material->quantity || $material->unit)
                                <span class="badge badge-info badge-pill">
                                    @if($material->quantity){{ $material->quantity }}@endif
                                    @if($material->unit) {{ $material->unit }}@endif
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Payments -->
    @if($report->payments->count() > 0)
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">💰 Payments (Malipo)</h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Amount (TSH)</th>
                                <th>Payment To</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_description }}</td>
                                    <td class="text-right">{{ number_format($payment->amount, 2) }}</td>
                                    <td>{{ $payment->payment_to ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-warning">
                                <th>Total</th>
                                <th class="text-right">{{ number_format($report->getTotalPayments(), 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Labor Needed -->
    @if($report->laborNeeded->count() > 0)
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">🧑🏾‍🔧 Labor Needed</h3>
            </div>
            <div class="block-content">
                <ul class="list-group list-group-flush">
                    @foreach($report->laborNeeded as $labor)
                        <li class="list-group-item">
                            <strong>{{ $labor->labor_type }}</strong>
                            @if($labor->description)
                                <br><small class="text-muted">{{ $labor->description }}</small>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Challenges -->
    @if($report->challenges)
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">⚠️ Challenges (Changamoto)</h3>
            </div>
            <div class="block-content">
                <div class="alert alert-warning">
                    {{ $report->challenges }}
                </div>
            </div>
        </div>
    @endif

    <!-- Next Steps -->
    @if($report->next_steps)
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">➡️ Next Steps (Hatua zinazofuata)</h3>
            </div>
            <div class="block-content">
                <div class="alert alert-info">
                    {{ $report->next_steps }}
                </div>
            </div>
        </div>
    @endif

    <!-- Formatted Report Preview -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">📄 Formatted Report Preview</h3>
        </div>
        <div class="block-content">
            <pre class="bg-light p-3" style="white-space: pre-wrap;">{{ $report->getFormattedReport() }}</pre>
        </div>
    </div>
</div>
@endsection

@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <i class="fa fa-file-contract"></i> {{ $contract->contract_number }}
            <div class="float-right">
                <a href="{{ route('labor.contracts.index') }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
                <a href="{{ route('labor.contracts.pdf', $contract->id) }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10" target="_blank">
                    <i class="fa fa-file-pdf"></i> PDF
                </a>
                @if($contract->isDraft())
                    <a href="{{ route('labor.contracts.edit', $contract->id) }}" class="btn btn-rounded btn-warning min-width-100 mb-10">
                        <i class="fa fa-edit"></i> Edit
                    </a>
                    <form action="{{ route('labor.contracts.sign', $contract->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-rounded btn-success min-width-100 mb-10"
                            onclick="return confirm('Sign and activate this contract?')">
                            <i class="fa fa-signature"></i> Sign & Activate
                        </button>
                    </form>
                @elseif($contract->isActive())
                    <a href="{{ route('labor.logs.create', $contract->id) }}" class="btn btn-rounded btn-info min-width-100 mb-10">
                        <i class="fa fa-clipboard-check"></i> Add Log
                    </a>
                    <a href="{{ route('labor.inspections.create', $contract->id) }}" class="btn btn-rounded btn-primary min-width-100 mb-10">
                        <i class="fa fa-search-plus"></i> Inspection
                    </a>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Contract Details</h3>
                        <div class="block-options">
                            <span class="badge badge-{{ $contract->status_badge_class }} badge-lg">
                                {{ ucfirst($contract->status) }}
                            </span>
                        </div>
                    </div>
                    <div class="block-content">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Project:</strong> {{ $contract->project?->project_name }}</p>
                                <p><strong>Contract Date:</strong> {{ $contract->contract_date?->format('Y-m-d') }}</p>
                                <p><strong>Start Date:</strong> {{ $contract->start_date?->format('Y-m-d') }}</p>
                                <p><strong>End Date:</strong> {{ $contract->end_date?->format('Y-m-d') }}</p>
                                @if($contract->actual_end_date)
                                    <p><strong>Actual End:</strong> {{ $contract->actual_end_date->format('Y-m-d') }}</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <p><strong>Supervisor:</strong> {{ $contract->supervisor?->name ?? 'N/A' }}</p>
                                <p><strong>Request #:</strong>
                                    <a href="{{ route('labor.requests.show', $contract->labor_request_id) }}">
                                        {{ $contract->laborRequest?->request_number }}
                                    </a>
                                </p>
                                @if($contract->isActive())
                                    @if($contract->days_remaining > 0)
                                        <p class="text-info"><strong>Days Remaining:</strong> {{ $contract->days_remaining }}</p>
                                    @else
                                        <p class="text-danger"><strong>Days Overdue:</strong> {{ $contract->days_overdue }}</p>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <hr>
                        <h5>Scope of Work</h5>
                        <p class="bg-light p-3 rounded">{{ $contract->scope_of_work }}</p>

                        @if($contract->terms_conditions)
                            <h5>Terms & Conditions</h5>
                            <p class="bg-light p-3 rounded">{{ $contract->terms_conditions }}</p>
                        @endif

                        @if($contract->notes)
                            <h5>Notes</h5>
                            <pre class="bg-light p-3 rounded">{{ $contract->notes }}</pre>
                        @endif
                    </div>
                </div>

                <!-- Payment Phases -->
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><i class="fa fa-money-bill-wave"></i> Payment Phases</h3>
                        <div class="block-options">
                            <a href="{{ route('labor.payments.contract', $contract->id) }}" class="btn btn-sm btn-outline-primary">
                                View All Payments
                            </a>
                        </div>
                    </div>
                    <div class="block-content">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Phase</th>
                                        <th>Milestone</th>
                                        <th class="text-right">Amount</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contract->paymentPhases as $phase)
                                        <tr>
                                            <td>{{ $phase->phase_number }}</td>
                                            <td>{{ $phase->phase_name }} ({{ $phase->percentage }}%)</td>
                                            <td>{{ Str::limit($phase->milestone_description, 50) }}</td>
                                            <td class="text-right">{{ number_format($phase->amount, 0) }}</td>
                                            <td class="text-center">
                                                <span class="badge badge-{{ $phase->status_badge_class }}">
                                                    {{ ucfirst($phase->status) }}
                                                </span>
                                                @if($phase->paid_at)
                                                    <br><small>{{ $phase->paid_at->format('Y-m-d') }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($phase->isDue())
                                                    <form action="{{ route('labor.payments.approve', $phase->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success"
                                                            onclick="return confirm('Approve this payment?')">
                                                            Approve
                                                        </button>
                                                    </form>
                                                @elseif($phase->isApproved())
                                                    <a href="{{ route('labor.payments.process.form', $phase->id) }}"
                                                        class="btn btn-sm btn-primary">Process</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light">
                                        <th colspan="3">Total</th>
                                        <th class="text-right">{{ number_format($contract->total_amount, 0) }}</th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Work Logs -->
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><i class="fa fa-clipboard-check"></i> Recent Work Logs</h3>
                        <div class="block-options">
                            @if($contract->isActive())
                                <a href="{{ route('labor.logs.create', $contract->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-plus"></i> Add Log
                                </a>
                            @endif
                            <a href="{{ route('labor.logs.contract', $contract->id) }}" class="btn btn-sm btn-outline-info">
                                View All
                            </a>
                        </div>
                    </div>
                    <div class="block-content">
                        @if($contract->workLogs->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Work Done</th>
                                            <th>Workers</th>
                                            <th>Progress</th>
                                            <th>Logged By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($contract->workLogs as $log)
                                            <tr>
                                                <td>{{ $log->log_date->format('Y-m-d') }}</td>
                                                <td>{{ Str::limit($log->work_done, 50) }}</td>
                                                <td>{{ $log->workers_present }}</td>
                                                <td>{{ $log->progress_percentage }}%</td>
                                                <td>{{ $log->logger?->name }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center py-3">No work logs yet</p>
                        @endif
                    </div>
                </div>

                <!-- Inspections -->
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><i class="fa fa-search-plus"></i> Inspections</h3>
                        <div class="block-options">
                            @if($contract->isActive())
                                <a href="{{ route('labor.inspections.create', $contract->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-plus"></i> New Inspection
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="block-content">
                        @if($contract->inspections->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Inspection #</th>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Completion</th>
                                            <th>Result</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($contract->inspections as $inspection)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('labor.inspections.show', $inspection->id) }}">
                                                        {{ $inspection->inspection_number }}
                                                    </a>
                                                </td>
                                                <td>{{ $inspection->inspection_date->format('Y-m-d') }}</td>
                                                <td>
                                                    <span class="badge badge-{{ $inspection->type_badge_class }}">
                                                        {{ ucfirst($inspection->inspection_type) }}
                                                    </span>
                                                </td>
                                                <td>{{ $inspection->completion_percentage }}%</td>
                                                <td>
                                                    <span class="badge badge-{{ $inspection->result_badge_class }}">
                                                        {{ ucfirst($inspection->result) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $inspection->status_badge_class }}">
                                                        {{ ucfirst($inspection->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center py-3">No inspections yet</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Artisan Details</h3>
                    </div>
                    <div class="block-content">
                        <p><strong>Name:</strong> {{ $contract->artisan?->name }}</p>
                        <p><strong>Trade:</strong> {{ $contract->artisan?->trade_skill ?? 'N/A' }}</p>
                        <p><strong>Phone:</strong> {{ $contract->artisan?->phone ?? 'N/A' }}</p>
                        <p><strong>ID Number:</strong> {{ $contract->artisan?->id_number ?? 'N/A' }}</p>
                        @if($contract->artisan?->rating)
                            <p><strong>Rating:</strong>
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fa fa-star {{ $i <= $contract->artisan->rating ? 'text-warning' : 'text-muted' }}"></i>
                                @endfor
                            </p>
                        @endif
                    </div>
                </div>

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Financial Summary</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-borderless">
                            <tr>
                                <td>Total Contract:</td>
                                <td class="text-right"><strong>{{ number_format($contract->total_amount, 0) }} {{ $contract->currency }}</strong></td>
                            </tr>
                            <tr class="text-success">
                                <td>Amount Paid:</td>
                                <td class="text-right">{{ number_format($contract->amount_paid, 0) }} {{ $contract->currency }}</td>
                            </tr>
                            <tr class="text-warning">
                                <td>Balance:</td>
                                <td class="text-right">{{ number_format($contract->balance_amount, 0) }} {{ $contract->currency }}</td>
                            </tr>
                        </table>

                        <div class="progress mb-2" style="height: 25px;">
                            <div class="progress-bar bg-success" style="width: {{ $contract->payment_progress }}%">
                                {{ number_format($contract->payment_progress, 0) }}% Paid
                            </div>
                        </div>
                    </div>
                </div>

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Progress</h3>
                    </div>
                    <div class="block-content">
                        <p><strong>Latest Progress:</strong> {{ number_format($contract->latest_progress, 1) }}%</p>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-info" style="width: {{ $contract->latest_progress }}%">
                                {{ number_format($contract->latest_progress, 0) }}%
                            </div>
                        </div>
                    </div>
                </div>

                @if($contract->isActive())
                    <div class="block">
                        <div class="block-header block-header-default bg-danger">
                            <h3 class="block-title text-white">Contract Actions</h3>
                        </div>
                        <div class="block-content">
                            <form action="{{ route('labor.contracts.hold', $contract->id) }}" method="POST" class="mb-2">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-block"
                                    onclick="return confirm('Put this contract on hold?')">
                                    <i class="fa fa-pause"></i> Put On Hold
                                </button>
                            </form>
                            <button type="button" class="btn btn-danger btn-block" data-toggle="modal" data-target="#terminateModal">
                                <i class="fa fa-times"></i> Terminate Contract
                            </button>
                        </div>
                    </div>
                @elseif($contract->isOnHold())
                    <div class="block">
                        <div class="block-header block-header-default bg-warning">
                            <h3 class="block-title">Contract On Hold</h3>
                        </div>
                        <div class="block-content">
                            <form action="{{ route('labor.contracts.resume', $contract->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fa fa-play"></i> Resume Contract
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Terminate Modal -->
<div class="modal fade" id="terminateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('labor.contracts.terminate', $contract->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white">Terminate Contract</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="termination_reason">Termination Reason <span class="text-danger">*</span></label>
                        <textarea name="termination_reason" id="termination_reason" class="form-control" rows="4" required
                            placeholder="Please provide detailed reason for termination..."></textarea>
                    </div>
                    <p class="text-danger">
                        <i class="fa fa-exclamation-triangle"></i>
                        This action cannot be undone. All pending payments will be put on hold.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Terminate Contract</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

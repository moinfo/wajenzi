@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">
                Lead Details - {{ $lead->lead_number ?? $lead->name }}
            </h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item">Projects</li>
                    <li class="breadcrumb-item"><a href="{{ route('leads.index') }}">Leads</a></li>
                    <li class="breadcrumb-item active" aria-current="page">View Lead</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <!-- Lead Status Summary -->
    <div class="row">
        <div class="col-md-3">
            <div class="block block-rounded text-center">
                <div class="block-content block-content-full">
                    <div class="py-3">
                        @if($lead->leadStatus)
                            @php
                                $statusClass = match(strtolower($lead->leadStatus->name)) {
                                    'won' => 'text-success',
                                    'lost' => 'text-danger',
                                    'proposal sent' => 'text-warning',
                                    'new' => 'text-primary',
                                    default => 'text-secondary'
                                };
                            @endphp
                            <p class="h1 {{ $statusClass }} mb-0">{{ $lead->leadStatus->name }}</p>
                        @else
                            <p class="h1 text-muted mb-0">-</p>
                        @endif
                        <p class="text-muted mb-0">Lead Status</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="block block-rounded text-center">
                <div class="block-content block-content-full">
                    <div class="py-3">
                        <p class="h1 text-primary mb-0">
                            {{ $lead->estimated_value ? 'TZS ' . number_format($lead->estimated_value) : '-' }}
                        </p>
                        <p class="text-muted mb-0">Estimated Value</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="block block-rounded text-center">
                <div class="block-content block-content-full">
                    <div class="py-3">
                        <p class="h3 mb-0">{{ $lead->salesperson->name ?? '-' }}</p>
                        <p class="text-muted mb-0">Salesperson</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="block block-rounded text-center">
                <div class="block-content block-content-full">
                    <div class="py-3">
                        @php
                            $pendingFollowup = $lead->leadFollowups->where('status', 'pending')->sortBy('followup_date')->first();
                        @endphp
                        <p class="h3 mb-0">
                            @if($pendingFollowup && $pendingFollowup->followup_date)
                                {{ $pendingFollowup->followup_date->format('d M Y') }}
                            @else
                                <span class="text-muted">Not Set</span>
                            @endif
                        </p>
                        <p class="text-muted mb-0">Next Follow-up</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lead Information -->
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Lead Information</h3>
            <div class="block-options">
                <a href="{{ route('leads.edit', $lead->id) }}" class="btn btn-sm btn-primary">
                    <i class="fa fa-edit"></i> Edit
                </a>
            </div>
        </div>
        <div class="block-content">
            <div class="row">
                <div class="col-md-4">
                    <h6 class="text-muted">Lead Number</h6>
                    <p><strong>{{ $lead->lead_number ?? '-' }}</strong></p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Lead Date</h6>
                    <p>{{ $lead->lead_date ? $lead->lead_date->format('d F Y') : '-' }}</p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Record Status</h6>
                    <p>
                        @if($lead->status == 'active')
                            <span class="badge badge-success">Active</span>
                        @elseif($lead->status == 'converted')
                            <span class="badge badge-primary">Converted</span>
                        @else
                            <span class="badge badge-secondary">Inactive</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Client Details -->
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Client Details</h3>
        </div>
        <div class="block-content">
            <div class="row">
                <div class="col-md-4">
                    <h6 class="text-muted">Client Name</h6>
                    <p><strong>{{ $lead->name }}</strong></p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Phone</h6>
                    <p>
                        @if($lead->phone)
                            <a href="tel:{{ $lead->phone }}"><i class="fa fa-phone mr-1"></i>{{ $lead->phone }}</a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Email</h6>
                    <p>
                        @if($lead->email)
                            <a href="mailto:{{ $lead->email }}"><i class="fa fa-envelope mr-1"></i>{{ $lead->email }}</a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Service & Location -->
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Service & Location</h3>
        </div>
        <div class="block-content">
            <div class="row">
                <div class="col-md-4">
                    <h6 class="text-muted">Lead Source</h6>
                    <p>
                        @if($lead->leadSource)
                            <span class="badge badge-info">{{ $lead->leadSource->name }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Service Interested</h6>
                    <p>
                        @if($lead->serviceInterested)
                            <span class="badge badge-secondary">{{ $lead->serviceInterested->name }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Estimated Value</h6>
                    <p><strong>{{ $lead->estimated_value ? 'TZS ' . number_format($lead->estimated_value, 2) : '-' }}</strong></p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <h6 class="text-muted">Site Location</h6>
                    <p>{{ $lead->site_location ?: '-' }}</p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">City</h6>
                    <p>{{ $lead->city ?: '-' }}</p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Full Address</h6>
                    <p>{{ $lead->address ?: '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Assignment & Notes -->
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Assignment & Notes</h3>
        </div>
        <div class="block-content">
            <div class="row">
                <div class="col-md-4">
                    <h6 class="text-muted">Salesperson</h6>
                    <p><strong>{{ $lead->salesperson->name ?? '-' }}</strong></p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Created By</h6>
                    <p>{{ $lead->createdBy->name ?? '-' }}</p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Created At</h6>
                    <p>{{ $lead->created_at->format('d F Y \a\t H:i') }}</p>
                </div>
            </div>
            @if($lead->notes)
            <div class="row">
                <div class="col-12">
                    <h6 class="text-muted">Notes</h6>
                    <div class="alert alert-light">
                        {!! nl2br(e($lead->notes)) !!}
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Project Schedule Section -->
    @php
        $projectSchedule = \App\Models\ProjectSchedule::where('lead_id', $lead->id)->first();
    @endphp
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title"><i class="fa fa-calendar-alt text-info mr-2"></i>Project Schedule</h3>
            <div class="block-options">
                @if($projectSchedule)
                    <a href="{{ route('project-schedules.show', $projectSchedule) }}" class="btn btn-sm btn-info">
                        <i class="fa fa-eye"></i> View Schedule
                    </a>
                @else
                    <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#createScheduleModal">
                        <i class="fa fa-plus"></i> Create Schedule
                    </button>
                @endif
            </div>
        </div>
        <div class="block-content">
            @if($projectSchedule)
                <div class="row">
                    <div class="col-md-3">
                        <strong>Status:</strong>
                        @php
                            $scheduleStatusColors = [
                                'draft' => 'secondary',
                                'pending_confirmation' => 'warning',
                                'confirmed' => 'info',
                                'in_progress' => 'primary',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                            ];
                        @endphp
                        <span class="badge badge-{{ $scheduleStatusColors[$projectSchedule->status] ?? 'secondary' }}">
                            {{ ucwords(str_replace('_', ' ', $projectSchedule->status)) }}
                        </span>
                    </div>
                    <div class="col-md-3">
                        <strong>Architect:</strong>
                        {{ $projectSchedule->assignedArchitect->name ?? 'Unassigned' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Start:</strong>
                        {{ $projectSchedule->start_date->format('d/m/Y') }}
                    </div>
                    <div class="col-md-3">
                        <strong>End:</strong>
                        {{ $projectSchedule->end_date ? $projectSchedule->end_date->format('d/m/Y') : 'N/A' }}
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <strong>Progress:</strong>
                        <div class="progress mt-1" style="height: 25px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $projectSchedule->progress }}%">
                                {{ $projectSchedule->progress }}% Complete
                            </div>
                        </div>
                    </div>
                </div>
                @if($projectSchedule->status === 'draft' || $projectSchedule->status === 'pending_confirmation')
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="fa fa-exclamation-triangle mr-2"></i>
                        Schedule is not yet confirmed. <a href="{{ route('project-schedules.show', $projectSchedule) }}">Review and confirm</a> to activate activities.
                    </div>
                @endif
            @else
                <div class="text-center py-4">
                    <i class="fa fa-calendar-times fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No project schedule created yet.</p>
                    <p class="text-muted small">A schedule will be automatically created when the first payment is received, or you can create one manually.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Create Schedule Modal -->
    @if(!$projectSchedule)
    <div class="modal fade" id="createScheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('leads.schedule.create', $lead) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Create Project Schedule</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>This will create a project schedule with all activities based on the standard template.</p>
                        <div class="form-group">
                            <label for="schedule_start_date"><strong>Project Start Date</strong></label>
                            <input type="date" name="start_date" id="schedule_start_date" class="form-control"
                                   value="{{ date('Y-m-d', strtotime('+1 day')) }}" min="{{ date('Y-m-d') }}" required>
                            <small class="text-muted">All activity dates will be calculated from this date (excluding weekends and holidays).</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Create Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Billing Documents Section -->
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title"><i class="fa fa-file-invoice text-primary mr-2"></i>Billing Documents</h3>
            <div class="block-options">
                <a href="{{ route('billing.quotations.create', ['lead_id' => $lead->id]) }}" class="btn btn-sm btn-primary mr-1">
                    <i class="fa fa-plus"></i> Quotation
                </a>
                <a href="{{ route('billing.proformas.create', ['lead_id' => $lead->id]) }}" class="btn btn-sm btn-info mr-1">
                    <i class="fa fa-plus"></i> Proforma
                </a>
                <a href="{{ route('billing.invoices.create', ['lead_id' => $lead->id]) }}" class="btn btn-sm btn-success">
                    <i class="fa fa-plus"></i> Invoice
                </a>
            </div>
        </div>
        <div class="block-content">
            @php
                $quotations = $lead->quotations;
                $proformas = $lead->proformas;
                $invoices = $lead->invoices;
                $hasDocs = $quotations->count() > 0 || $proformas->count() > 0 || $invoices->count() > 0;

                // Calculate invoice totals
                $totalInvoiced = $invoices->sum('total_amount');
                $totalPaid = $invoices->sum('paid_amount');
                $totalBalance = $invoices->sum('balance_amount');
                $currency = $invoices->first()?->currency_code ?? 'TZS';
            @endphp

            @if($hasDocs)
                {{-- Invoice Payment Summary --}}
                @if($invoices->count() > 0)
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="bg-light rounded p-3">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <div class="border-right">
                                        <small class="text-muted d-block">Total Invoiced</small>
                                        <h4 class="mb-0 text-dark">{{ $currency }} {{ number_format($totalInvoiced, 2) }}</h4>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border-right">
                                        <small class="text-muted d-block">Total Paid</small>
                                        <h4 class="mb-0 text-success">{{ $currency }} {{ number_format($totalPaid, 2) }}</h4>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div>
                                        <small class="text-muted d-block">Outstanding Balance</small>
                                        <h4 class="mb-0 {{ $totalBalance > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ $currency }} {{ number_format($totalBalance, 2) }}
                                            @if($totalBalance <= 0 && $totalInvoiced > 0)
                                                <i class="fa fa-check-circle ml-1"></i>
                                            @endif
                                        </h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="row">
                    <!-- Quotations -->
                    <div class="col-md-4">
                        <h6 class="text-muted mb-3"><i class="fa fa-file-alt mr-1"></i> Quotations ({{ $quotations->count() }})</h6>
                        @forelse($quotations as $doc)
                            <div class="card mb-2">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <a href="{{ route('billing.quotations.show', $doc) }}" class="font-weight-bold">
                                                {{ $doc->document_number }}
                                            </a>
                                            <br>
                                            <small class="text-muted">{{ $doc->issue_date?->format('d M Y') }}</small>
                                        </div>
                                        <div class="text-right">
                                            <span class="badge badge-{{ $doc->status_color }}">{{ ucfirst($doc->status) }}</span>
                                            <br>
                                            <strong>{{ $doc->currency_code }} {{ number_format($doc->total_amount, 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted"><small>No quotations yet</small></p>
                        @endforelse
                    </div>

                    <!-- Proformas -->
                    <div class="col-md-4">
                        <h6 class="text-muted mb-3"><i class="fa fa-file-invoice mr-1"></i> Proforma Invoices ({{ $proformas->count() }})</h6>
                        @forelse($proformas as $doc)
                            <div class="card mb-2">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <a href="{{ route('billing.proformas.show', $doc) }}" class="font-weight-bold">
                                                {{ $doc->document_number }}
                                            </a>
                                            <br>
                                            <small class="text-muted">{{ $doc->issue_date?->format('d M Y') }}</small>
                                        </div>
                                        <div class="text-right">
                                            <span class="badge badge-{{ $doc->status_color }}">{{ ucfirst($doc->status) }}</span>
                                            <br>
                                            <strong>{{ $doc->currency_code }} {{ number_format($doc->total_amount, 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted"><small>No proformas yet</small></p>
                        @endforelse
                    </div>

                    <!-- Invoices -->
                    <div class="col-md-4">
                        <h6 class="text-muted mb-3"><i class="fa fa-file-invoice-dollar mr-1"></i> Invoices ({{ $invoices->count() }})</h6>
                        @forelse($invoices as $doc)
                            @php
                                $isPaid = $doc->balance_amount <= 0 && $doc->total_amount > 0;
                                $isPartial = $doc->paid_amount > 0 && $doc->balance_amount > 0;
                                $paymentBadge = $isPaid ? 'success' : ($isPartial ? 'warning' : 'danger');
                                $paymentText = $isPaid ? 'PAID' : ($isPartial ? 'PARTIAL' : 'UNPAID');
                            @endphp
                            <div class="card mb-2 {{ $isPaid ? 'border-left border-success' : ($isPartial ? 'border-left border-warning' : 'border-left border-danger') }}" style="border-left-width: 4px !important;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <a href="{{ route('billing.invoices.show', $doc) }}" class="font-weight-bold">
                                                {{ $doc->document_number }}
                                            </a>
                                            <br>
                                            <small class="text-muted">{{ $doc->issue_date?->format('d M Y') }}</small>
                                        </div>
                                        <div class="text-right">
                                            <span class="badge badge-{{ $paymentBadge }}">{{ $paymentText }}</span>
                                        </div>
                                    </div>
                                    <hr class="my-2">
                                    <div class="small">
                                        <div class="d-flex justify-content-between">
                                            <span>Total:</span>
                                            <strong>{{ $doc->currency_code }} {{ number_format($doc->total_amount, 2) }}</strong>
                                        </div>
                                        @if($doc->paid_amount > 0)
                                        <div class="d-flex justify-content-between text-success">
                                            <span>Paid:</span>
                                            <span>{{ $doc->currency_code }} {{ number_format($doc->paid_amount, 2) }}</span>
                                        </div>
                                        @endif
                                        @if($doc->balance_amount > 0)
                                        <div class="d-flex justify-content-between text-danger font-weight-bold">
                                            <span>Balance:</span>
                                            <span>{{ $doc->currency_code }} {{ number_format($doc->balance_amount, 2) }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted"><small>No invoices yet</small></p>
                        @endforelse
                    </div>
                </div>
            @else
                <div class="alert alert-info mb-0">
                    <i class="fa fa-info-circle mr-2"></i>
                    No billing documents linked to this lead yet. Use the buttons above to create a quotation, proforma invoice, or invoice.
                </div>
            @endif
        </div>
    </div>

    <!-- Add New Follow-up -->
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title"><i class="fa fa-plus-circle text-success mr-2"></i>Add New Follow-up</h3>
        </div>
        <div class="block-content">
            <form method="POST" action="{{ route('leads.followup.store', $lead->id) }}">
                @csrf
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="followup_date" class="required">Followup Date</label>
                            <input type="date" class="form-control @error('followup_date') is-invalid @enderror"
                                   id="followup_date" name="followup_date"
                                   value="{{ old('followup_date', now()->format('Y-m-d')) }}" required>
                            @error('followup_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="form-group">
                            <label for="details_discussion" class="required">Followup Remarks</label>
                            <input type="text" class="form-control @error('details_discussion') is-invalid @enderror"
                                   id="details_discussion" name="details_discussion"
                                   value="{{ old('details_discussion') }}"
                                   placeholder="What was discussed with the client?" required>
                            @error('details_discussion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="outcome">Followup Result</label>
                            <input type="text" class="form-control @error('outcome') is-invalid @enderror"
                                   id="outcome" name="outcome"
                                   value="{{ old('outcome') }}"
                                   placeholder="Result of the follow-up">
                            @error('outcome')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label for="next_step">Next Action</label>
                            <input type="text" class="form-control @error('next_step') is-invalid @enderror"
                                   id="next_step" name="next_step"
                                   value="{{ old('next_step') }}"
                                   placeholder="What should be done next?">
                            @error('next_step')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fa fa-save mr-1"></i> Save Follow-up
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lead Follow-ups History -->
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Follow-up History</h3>
            <div class="block-options">
                @php
                    $pendingCount = $lead->leadFollowups->where('status', 'pending')->count();
                    $completedCount = $lead->leadFollowups->where('status', 'completed')->count();
                @endphp
                <span class="badge badge-warning mr-1">{{ $pendingCount }} Pending</span>
                <span class="badge badge-success mr-1">{{ $completedCount }} Completed</span>
                <span class="badge badge-primary">{{ $lead->leadFollowups->count() }} Total</span>
            </div>
        </div>
        <div class="block-content">
            @if($lead->leadFollowups->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 50px;">S/n</th>
                                <th>Followup Date</th>
                                <th>Followup Remarks</th>
                                <th>Followup Result</th>
                                <th>Next Action</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lead->leadFollowups->sortByDesc('followup_date')->sortByDesc('created_at') as $index => $followup)
                                @php
                                    $isOverdue = $followup->status === 'pending' && $followup->followup_date && $followup->followup_date->isPast() && !$followup->followup_date->isToday();
                                    $isToday = $followup->followup_date && $followup->followup_date->isToday();
                                @endphp
                                <tr class="{{ $isOverdue ? 'table-danger' : ($isToday && $followup->status === 'pending' ? 'table-warning' : '') }}">
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        @if($followup->followup_date)
                                            {{ $followup->followup_date->format('d M Y') }}
                                            @if($isOverdue)
                                                <br><small class="text-danger"><i class="fa fa-exclamation-circle"></i> Overdue</small>
                                            @elseif($isToday && $followup->status === 'pending')
                                                <br><small class="text-warning"><i class="fa fa-clock"></i> Today</small>
                                            @endif
                                        @else
                                            {{ $followup->created_at->format('d M Y') }}
                                        @endif
                                    </td>
                                    <td>{{ $followup->details_discussion ?: '-' }}</td>
                                    <td>{{ $followup->outcome ?: '-' }}</td>
                                    <td>{{ $followup->next_step ?: '-' }}</td>
                                    <td>
                                        @switch($followup->status ?? 'pending')
                                            @case('completed')
                                                <span class="badge badge-success"><i class="fa fa-check"></i> Completed</span>
                                                @if($followup->attended_at)
                                                    <br><small class="text-muted">{{ $followup->attended_at->format('d M Y') }}</small>
                                                @endif
                                                @break
                                            @case('cancelled')
                                                <span class="badge badge-danger"><i class="fa fa-times"></i> Cancelled</span>
                                                @break
                                            @case('rescheduled')
                                                <span class="badge badge-info"><i class="fa fa-calendar"></i> Rescheduled</span>
                                                @break
                                            @default
                                                <span class="badge badge-warning"><i class="fa fa-clock"></i> Pending</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        @if(($followup->status ?? 'pending') === 'pending')
                                            <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#attendModal{{ $followup->id }}">
                                                <i class="fa fa-check-circle"></i> Attend
                                            </button>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info">
                    <i class="fa fa-info-circle mr-2"></i> No follow-ups recorded yet for this lead.
                </div>
            @endif
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="block block-rounded">
        <div class="block-content">
            <div class="row">
                <div class="col-12">
                    <a href="{{ route('leads.edit', $lead->id) }}" class="btn btn-primary">
                        <i class="fa fa-edit"></i> Edit Lead
                    </a>

                    <form method="POST" action="{{ route('leads.destroy', $lead->id) }}" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this lead?')">
                            <i class="fa fa-trash"></i> Delete Lead
                        </button>
                    </form>

                    <a href="{{ route('leads.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Leads
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Attend Follow-up Modals -->
@foreach($lead->leadFollowups->where('status', 'pending') as $followup)
<div class="modal fade" id="attendModal{{ $followup->id }}" tabindex="-1" role="dialog" aria-labelledby="attendModalLabel{{ $followup->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('leads.followup.attend', ['leadId' => $lead->id, 'followupId' => $followup->id]) }}">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="attendModalLabel{{ $followup->id }}">
                        <i class="fa fa-check-circle mr-2"></i>Mark Follow-up as Attended
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Follow-up Info -->
                    <div class="alert alert-light mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Client:</strong> {{ $lead->name }}<br>
                                <strong>Scheduled Date:</strong> {{ $followup->followup_date ? $followup->followup_date->format('d M Y') : '-' }}
                            </div>
                            <div class="col-md-6">
                                <strong>Planned Action:</strong> {{ $followup->next_step ?: 'N/A' }}<br>
                                <strong>Remarks:</strong> {{ $followup->details_discussion ?: 'N/A' }}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required">Follow-up Status</label>
                                <select name="status" class="form-control" required>
                                    <option value="completed" selected>✓ COMPLETED - I contacted the client</option>
                                    <option value="rescheduled">↻ RESCHEDULED - Postponed to another date</option>
                                    <option value="cancelled">✗ CANCELLED - No longer needed</option>
                                </select>
                                <small class="form-text text-muted">
                                    <strong>Completed:</strong> You made the call/contact (select this if you spoke to client)<br>
                                    <strong>Rescheduled:</strong> You didn't contact yet, moving to another date
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required">Outcome / Result</label>
                                <select name="outcome" class="form-control" required>
                                    <option value="">-- Select Outcome --</option>
                                    <optgroup label="Positive Outcomes">
                                        <option value="Interested - Hot Lead">🔥 Interested - Hot Lead</option>
                                        <option value="Interested - Warm Lead">👍 Interested - Warm Lead</option>
                                        <option value="Requested Proposal">📄 Requested Proposal</option>
                                        <option value="Meeting Scheduled">📅 Meeting Scheduled</option>
                                        <option value="Site Visit Scheduled">🏗️ Site Visit Scheduled</option>
                                        <option value="Converted to Project">🎉 Converted to Project</option>
                                    </optgroup>
                                    <optgroup label="Neutral Outcomes">
                                        <option value="Call Back Later">📞 Call Back Later</option>
                                        <option value="No Answer">📵 No Answer</option>
                                        <option value="Left Message">💬 Left Message</option>
                                    </optgroup>
                                    <optgroup label="Negative Outcomes">
                                        <option value="Not Interested">👎 Not Interested</option>
                                        <option value="Budget Constraints">💰 Budget Constraints</option>
                                        <option value="Wrong Contact">❌ Wrong Contact</option>
                                    </optgroup>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Additional Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Any additional notes about this follow-up..."></textarea>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label>Update Lead Status</label>
                        <select name="update_lead_status" class="form-control">
                            <option value="">-- Keep Current Status ({{ $lead->leadStatus->name ?? 'N/A' }}) --</option>
                            @foreach($leadStatuses as $status)
                                <option value="{{ $status->id }}">{{ $status->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Optionally update the lead status based on follow-up outcome</small>
                    </div>

                    <hr>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="scheduleNext{{ $followup->id }}" name="schedule_next_followup" value="1" onchange="toggleNextFollowup({{ $followup->id }})">
                            <label class="custom-control-label" for="scheduleNext{{ $followup->id }}">
                                <strong>Schedule Next Follow-up</strong>
                            </label>
                        </div>
                    </div>

                    <div id="nextFollowupFields{{ $followup->id }}" style="display: none;">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Next Follow-up Date</label>
                                    <input type="date" name="next_followup_date" class="form-control" min="{{ now()->addDay()->format('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Next Action</label>
                                    <input type="text" name="next_followup_action" class="form-control" placeholder="What should be done in the next follow-up?">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-check mr-1"></i> Mark as Attended
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@endsection

@section('js_after')
<script>
function toggleNextFollowup(followupId) {
    var checkbox = document.getElementById('scheduleNext' + followupId);
    var fields = document.getElementById('nextFollowupFields' + followupId);
    if (checkbox.checked) {
        fields.style.display = 'block';
    } else {
        fields.style.display = 'none';
    }
}
</script>
@endsection

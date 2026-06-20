{{-- project_site_visit_workflow.blade.php — 6-stage Site Visit Workflow detail --}}
@extends('layouts.backend')

@php
    use Illuminate\Support\Facades\Storage;

    $user = auth()->user();
    $isAdmin       = $user && $user->hasRole('System Administrator');
    $canInvoice    = $user && $user->hasAnyRole(['Accountant', 'Finance', 'System Administrator']);
    $canPay        = $user && $user->hasAnyRole(['Finance', 'Accountant', 'System Administrator']);
    $isCoordinator = $user && $user->hasAnyRole(['Project Manager', 'Sales Manager', 'System Administrator']);
    $onTeam        = $visit->isOnTeam($user?->id);
    $isOwner       = $visit->create_by_id === ($user?->id);

    $subject = $visit->project
        ? $visit->project->project_name
        : ($visit->client ? trim($visit->client->first_name . ' ' . $visit->client->last_name) : 'Client-only visit');

    // Ordered workflow steps for the progress tracker (terminal 'completed' excluded).
    $steps = [
        'initiation'   => 'Initiation',
        'billing'      => 'Billing & Invoice',
        'assignment'   => 'Assignment',
        'confirmation' => 'Confirmation',
        'reporting'    => 'Reporting',
        'integration'  => 'Schedule Link',
    ];
    $currentIndex = $visit->stageIndex(); // 1-based; 7 when completed, 0 when cancelled
@endphp

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">
                Site Visit — {{ $visit->reference_number }}
                <div class="float-right">
                    <a href="{{ route('project_site_visits') }}" class="btn btn-sm btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to list
                    </a>
                </div>
            </div>

            @include('partials.alerts')

            @if($visit->stage === 'cancelled')
                <div class="alert alert-danger">
                    <strong>This site visit was cancelled.</strong>
                    @if($visit->cancel_reason) — {{ $visit->cancel_reason }} @endif
                </div>
            @endif

            {{-- Summary --}}
            <div class="block">
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-3"><small class="text-muted d-block">Project / Client</small>
                            {{ $subject }}
                            @unless($visit->project)<span class="badge badge-light">Client only</span>@endunless
                        </div>
                        <div class="col-md-3"><small class="text-muted d-block">Phone</small>{{ $visit->phone_number ?: '—' }}</div>
                        <div class="col-md-3"><small class="text-muted d-block">Location</small>{{ $visit->location ?: '—' }}</div>
                        <div class="col-md-3"><small class="text-muted d-block">Proposed Visit Date</small>{{ optional($visit->visit_date)->format('Y-m-d') ?: '—' }}</div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-9"><small class="text-muted d-block">Description</small>{{ $visit->description ?: '—' }}</div>
                        <div class="col-md-3"><small class="text-muted d-block">Raised By</small>{{ $visit->user->name ?? '—' }}</div>
                    </div>
                </div>
            </div>

            {{-- Progress tracker --}}
            <div class="block">
                <div class="block-content">
                    <div class="d-flex flex-wrap justify-content-between text-center">
                        @foreach($steps as $key => $label)
                            @php
                                $stepIndex = $loop->iteration;
                                $done    = ($currentIndex > $stepIndex) || $visit->stage === 'completed';
                                $current = ($currentIndex === $stepIndex) && !$visit->isTerminal();
                                $color   = $done ? 'success' : ($current ? 'primary' : 'light');
                                $textCls = $done || $current ? 'text-white' : 'text-muted';
                            @endphp
                            <div class="flex-fill px-1" style="min-width: 90px;">
                                <span class="badge badge-{{ $color }} {{ $textCls }}" style="width:32px;height:32px;border-radius:50%;line-height:24px;display:inline-block;">
                                    @if($done)<i class="fa fa-check"></i>@else {{ $stepIndex }} @endif
                                </span>
                                <div class="font-size-sm mt-1 {{ $current ? 'font-weight-bold' : 'text-muted' }}">{{ $label }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- Stage details --}}
                <div class="col-lg-6">
                    <div class="block">
                        <div class="block-header block-header-default"><h3 class="block-title">Details</h3></div>
                        <div class="block-content">
                            <table class="table table-sm">
                                <tr><th>Reference</th><td>{{ $visit->reference_number }}</td></tr>
                                <tr><th>Current Stage</th><td><span class="badge badge-info">{{ $visit->stageLabel() }}</span></td></tr>
                                @if($visit->invoice_number)
                                    <tr><th>Invoice No.</th><td>{{ $visit->invoice_number }}</td></tr>
                                    <tr><th>Invoice Amount</th><td>{{ number_format((float) $visit->invoice_amount) }} TZS</td></tr>
                                    <tr><th>Billed By</th><td>{{ $visit->billedBy->name ?? '—' }}</td></tr>
                                @endif
                                @if($visit->payment_confirmed_at)
                                    <tr><th>Payment Confirmed</th><td>{{ $visit->payment_confirmed_at->format('Y-m-d H:i') }} by {{ $visit->paymentConfirmedBy->name ?? '—' }}</td></tr>
                                @endif
                                @if($visit->assigned_at)
                                    <tr><th>Architect</th><td>{{ $visit->architect->name ?? '—' }}</td></tr>
                                    <tr><th>Site Engineer</th><td>{{ $visit->siteEngineer->name ?? '—' }}</td></tr>
                                    <tr><th>Site Supervisor</th><td>{{ $visit->siteSupervisor->name ?? '—' }}</td></tr>
                                @endif
                                @if($visit->team_confirmed_at)
                                    <tr><th>Readiness Confirmed</th><td>{{ $visit->team_confirmed_at->format('Y-m-d H:i') }} by {{ $visit->teamConfirmedBy->name ?? '—' }}</td></tr>
                                @endif
                                @if($visit->report_path)
                                    <tr><th>Report</th><td>
                                        <a href="{{ Storage::url($visit->report_path) }}" target="_blank">{{ $visit->report_name ?: 'Download' }}</a>
                                        <div class="text-muted font-size-sm">by {{ $visit->reportUploader->name ?? '—' }} on {{ optional($visit->report_uploaded_at)->format('Y-m-d') }}</div>
                                        @if($visit->report_notes)<div class="mt-1">{{ $visit->report_notes }}</div>@endif
                                    </td></tr>
                                @endif
                                @if($visit->integrated_at && $visit->scheduleActivity)
                                    <tr><th>Linked to Survey</th><td>{{ $visit->scheduleActivity->activity_code }} — {{ $visit->scheduleActivity->name }}</td></tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Action card --}}
                <div class="col-lg-6">
                    <div class="block">
                        <div class="block-header block-header-default"><h3 class="block-title">Next Action</h3></div>
                        <div class="block-content">

                            @if($visit->stage === 'initiation')
                                @if($canInvoice)
                                    <p class="text-muted">Prepare the invoice for this site visit.</p>
                                    <form method="post" action="{{ route('project_site_visit.invoice', $visit->id) }}">
                                        @csrf
                                        <div class="form-group">
                                            <label class="required">Invoice Number</label>
                                            <input type="text" name="invoice_number" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="required">Invoice Amount (TZS)</label>
                                            <input type="number" step="0.01" min="0" name="invoice_amount" class="form-control" required>
                                        </div>
                                        <button class="btn btn-primary"><i class="fa fa-file-invoice"></i> Record Invoice</button>
                                    </form>
                                @else
                                    <p class="text-muted"><i class="fa fa-clock-o"></i> Awaiting billing to prepare the invoice.</p>
                                @endif

                            @elseif($visit->stage === 'billing')
                                @if($canPay)
                                    <p>Invoice <strong>{{ $visit->invoice_number }}</strong> for
                                        <strong>{{ number_format((float) $visit->invoice_amount) }} TZS</strong> is ready.</p>
                                    <form method="post" action="{{ route('project_site_visit.confirm_payment', $visit->id) }}">
                                        @csrf
                                        <button class="btn btn-success"><i class="fa fa-check"></i> Confirm Payment</button>
                                    </form>
                                @else
                                    <p class="text-muted"><i class="fa fa-clock-o"></i> Awaiting Finance to confirm payment.</p>
                                @endif

                            @elseif($visit->stage === 'assignment')
                                @if($isCoordinator)
                                    <p class="text-muted">Assign the field team.</p>
                                    <form method="post" action="{{ route('project_site_visit.assign', $visit->id) }}">
                                        @csrf
                                        <div class="form-group">
                                            <label class="required">Architect</label>
                                            <select name="architect_id" class="form-control" required>
                                                <option value="">Select…</option>
                                                @foreach($architects as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="required">Site Engineer</label>
                                            <select name="site_engineer_id" class="form-control" required>
                                                <option value="">Select…</option>
                                                @foreach($siteEngineers as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="required">Site Supervisor</label>
                                            <select name="site_supervisor_id" class="form-control" required>
                                                <option value="">Select…</option>
                                                @foreach($supervisors as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                                            </select>
                                        </div>
                                        <button class="btn btn-primary"><i class="fa fa-users"></i> Assign Team</button>
                                    </form>
                                @else
                                    <p class="text-muted"><i class="fa fa-clock-o"></i> Awaiting a coordinator to assign the team.</p>
                                @endif

                            @elseif($visit->stage === 'confirmation')
                                @if($onTeam || $isAdmin)
                                    <p class="text-muted">Confirm your readiness to conduct this site visit.</p>
                                    <form method="post" action="{{ route('project_site_visit.confirm_readiness', $visit->id) }}">
                                        @csrf
                                        <button class="btn btn-success"><i class="fa fa-check"></i> Confirm Readiness</button>
                                    </form>
                                @else
                                    <p class="text-muted"><i class="fa fa-clock-o"></i> Awaiting the assigned team to confirm readiness.</p>
                                @endif

                            @elseif($visit->stage === 'reporting')
                                @if($onTeam || $isCoordinator)
                                    <p class="text-muted">Upload the Site Visit Report.</p>
                                    <form method="post" action="{{ route('project_site_visit.report', $visit->id) }}" enctype="multipart/form-data">
                                        @csrf
                                        <div class="form-group">
                                            <label class="required">Report File</label>
                                            <input type="file" name="report" class="form-control-file" required>
                                            <small class="text-muted">PDF, DOC, XLS, image, ZIP or DWG — max 50MB.</small>
                                        </div>
                                        <div class="form-group">
                                            <label>Notes</label>
                                            <textarea name="report_notes" class="form-control" rows="3"></textarea>
                                        </div>
                                        <button class="btn btn-primary"><i class="fa fa-upload"></i> Upload Report</button>
                                    </form>
                                @else
                                    <p class="text-muted"><i class="fa fa-clock-o"></i> Awaiting the assigned team to upload the report.</p>
                                @endif

                            @elseif($visit->stage === 'integration')
                                @if($isCoordinator)
                                    @if($surveyActivity)
                                        <p>Attach the report to the project's Survey Stage
                                            (<strong>{{ $surveyActivity->activity_code }} — {{ $surveyActivity->name }}</strong>).</p>
                                        <form method="post" action="{{ route('project_site_visit.integrate', $visit->id) }}">
                                            @csrf
                                            <button class="btn btn-primary"><i class="fa fa-link"></i> Attach to Survey Stage</button>
                                        </form>
                                    @else
                                        <p class="text-muted">No Survey Stage activity is available for this project.</p>
                                    @endif
                                @else
                                    <p class="text-muted"><i class="fa fa-clock-o"></i> Awaiting a coordinator to link the report to the schedule.</p>
                                @endif

                            @elseif($visit->stage === 'completed')
                                <p class="text-success"><i class="fa fa-check-circle"></i> This site visit workflow is complete.</p>

                            @elseif($visit->stage === 'cancelled')
                                <p class="text-danger"><i class="fa fa-ban"></i> This site visit was cancelled.</p>
                            @endif

                            {{-- Cancel --}}
                            @if(!$visit->isTerminal() && ($isOwner || $isCoordinator))
                                <hr>
                                <form method="post" action="{{ route('project_site_visit.cancel', $visit->id) }}"
                                      onsubmit="return confirm('Cancel this site visit?');">
                                    @csrf
                                    <div class="form-group">
                                        <input type="text" name="cancel_reason" class="form-control form-control-sm" placeholder="Reason (optional)">
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger"><i class="fa fa-times"></i> Cancel Visit</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

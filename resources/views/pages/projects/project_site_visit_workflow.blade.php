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
                            @php
                                $hasWorkflowHistory = $visit->invoice_number || $visit->payment_confirmed_at
                                    || $visit->assigned_at || $visit->team_confirmed_at || $visit->report_path
                                    || $visit->integrated_at;
                            @endphp
                            <table class="table table-sm">
                                {{-- Core --}}
                                <tr><th>Reference</th><td>{{ $visit->reference_number ?: '—' }}</td></tr>
                                <tr><th>Current Stage</th><td><span class="badge badge-info">{{ $visit->stageLabel() }}</span> <span class="text-muted">({{ $visit->stageIndex() }}/{{ $visit->stageCount() }})</span></td></tr>
                                <tr><th>Status</th><td>{{ $visit->status ?: '—' }}</td></tr>
                                @if($visit->document_number)
                                    <tr><th>Document No.</th><td>{{ $visit->document_number }}</td></tr>
                                @endif
                                <tr><th>Project / Client</th><td>
                                    {{ $subject }}
                                    @unless($visit->project)<span class="badge badge-light">Client only</span>@endunless
                                </td></tr>
                                <tr><th>Phone</th><td>{{ $visit->phone_number ?: '—' }}</td></tr>
                                <tr><th>Location</th><td>{{ $visit->location ?: '—' }}</td></tr>
                                @if($visit->siteVisitLocation)
                                    <tr><th>Calculator Location</th><td>{{ $visit->siteVisitLocation->name }} · {{ $visit->visit_days }} day(s)</td></tr>
                                    <tr><th>Estimated Cost</th><td>{{ number_format((float) $visit->estimatedCost()) }} TZS <span class="text-muted font-size-sm">(from calculator presets)</span></td></tr>
                                @endif
                                <tr><th>Proposed Visit Date</th><td>{{ optional($visit->visit_date)->format('Y-m-d') ?: '—' }}</td></tr>
                                <tr><th>Description</th><td>{{ $visit->description ?: '—' }}</td></tr>

                                {{-- Billing --}}
                                @if($visit->invoice_number)
                                    <tr><th>Invoice No.</th><td>{{ $visit->invoice_number }}</td></tr>
                                    <tr><th>Invoice Amount</th><td>{{ number_format((float) $visit->invoice_amount) }} TZS</td></tr>
                                    <tr><th>Billed By</th><td>{{ $visit->billedBy->name ?? '—' }}</td></tr>
                                    <tr><th>Invoice PDF</th><td>
                                        <a href="{{ route('project_site_visit.invoice_pdf', $visit->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-file-pdf-o"></i> View
                                        </a>
                                        <a href="{{ route('project_site_visit.invoice_pdf', ['id' => $visit->id, 'download' => 1]) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="fa fa-download"></i> Download / Share
                                        </a>
                                    </td></tr>
                                @endif
                                @if($visit->payment_confirmed_at)
                                    <tr><th>Payment Confirmed</th><td>{{ $visit->payment_confirmed_at->format('Y-m-d H:i') }} by {{ $visit->paymentConfirmedBy->name ?? '—' }}</td></tr>
                                @endif

                                {{-- Assignment --}}
                                @if($visit->assigned_at)
                                    <tr><th>Architect</th><td>{{ $visit->architect->name ?? '—' }}</td></tr>
                                    <tr><th>Site Engineer</th><td>{{ $visit->siteEngineer->name ?? '—' }}</td></tr>
                                    <tr><th>Site Supervisor</th><td>{{ $visit->siteSupervisor->name ?? '—' }}</td></tr>
                                    <tr><th>Assigned</th><td>{{ $visit->assigned_at->format('Y-m-d H:i') }}</td></tr>
                                @endif
                                @if($visit->team_confirmed_at)
                                    <tr><th>Readiness Confirmed</th><td>{{ $visit->team_confirmed_at->format('Y-m-d H:i') }} by {{ $visit->teamConfirmedBy->name ?? '—' }}</td></tr>
                                @endif

                                {{-- Reporting & integration --}}
                                @if($visit->report_path)
                                    <tr><th>Report</th><td>
                                        <a href="{{ Storage::url($visit->report_path) }}" target="_blank">{{ $visit->report_name ?: 'Download' }}</a>
                                        <div class="text-muted font-size-sm">by {{ $visit->reportUploader->name ?? '—' }} on {{ optional($visit->report_uploaded_at)->format('Y-m-d') }}</div>
                                        @if($visit->report_notes)<div class="mt-1">{{ $visit->report_notes }}</div>@endif
                                    </td></tr>
                                @endif
                                @if($visit->integrated_at && $visit->scheduleActivity)
                                    <tr><th>Linked to Survey</th><td>{{ $visit->scheduleActivity->activity_code }} — {{ $visit->scheduleActivity->name }} <span class="text-muted font-size-sm">({{ $visit->integrated_at->format('Y-m-d') }})</span></td></tr>
                                @endif

                                {{-- Legacy fields (older visits) --}}
                                @if($visit->inspector_id)
                                    <tr><th>Inspector (legacy)</th><td>{{ $visit->inspector->name ?? '—' }}</td></tr>
                                @endif
                                @if($visit->findings)
                                    <tr><th>Findings</th><td>{{ $visit->findings }}</td></tr>
                                @endif
                                @if($visit->recommendations)
                                    <tr><th>Recommendations</th><td>{{ $visit->recommendations }}</td></tr>
                                @endif

                                {{-- Cancellation --}}
                                @if($visit->cancelled_at)
                                    <tr><th>Cancelled</th><td>{{ $visit->cancelled_at->format('Y-m-d H:i') }}@if($visit->cancel_reason) — {{ $visit->cancel_reason }}@endif</td></tr>
                                @endif

                                {{-- Audit --}}
                                <tr><th>Raised By</th><td>{{ $visit->user->name ?? '—' }}</td></tr>
                                <tr><th>Created</th><td>{{ optional($visit->created_at)->format('Y-m-d H:i') ?: '—' }}</td></tr>
                                <tr><th>Last Updated</th><td>{{ optional($visit->updated_at)->format('Y-m-d H:i') ?: '—' }}</td></tr>
                            </table>

                            @if($visit->stage === 'completed' && !$hasWorkflowHistory)
                                <div class="alert alert-info mb-0 font-size-sm">
                                    <i class="fa fa-info-circle"></i> This visit predates the staged workflow, so no
                                    billing, assignment, or report history was recorded. It was migrated as
                                    <strong>Completed</strong> from its previous status (<strong>{{ $visit->status }}</strong>).
                                </div>
                            @endif
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
                                    <p class="text-muted">Prepare the invoice. Pick the calculator location and days — the amount is computed from the location's cost presets and stays editable.</p>
                                    <form method="post" action="{{ route('project_site_visit.invoice', $visit->id) }}">
                                        @csrf
                                        <div class="form-group">
                                            <label class="required">Calculator Location</label>
                                            <select name="site_visit_location_id" id="sv-loc" class="form-control" onchange="svRecalc()">
                                                <option value="" data-base="0" data-perday="0">— Select location —</option>
                                                @foreach(($siteVisitLocations ?? []) as $loc)
                                                    @php $perDay = (float)$loc->preset_travel_tzs + (float)$loc->preset_local_tzs + (float)$loc->preset_allowance_tzs + (float)$loc->preset_food_tzs + (float)$loc->preset_accommodation_tzs; @endphp
                                                    <option value="{{ $loc->id }}" data-base="{{ (float)$loc->base_cost_tzs }}" data-perday="{{ $perDay }}"
                                                        {{ $loc->id == $visit->site_visit_location_id ? 'selected' : '' }}>
                                                        {{ $loc->name }} (base {{ number_format((float)$loc->base_cost_tzs) }}{{ $perDay > 0 ? ' + '.number_format($perDay).'/day' : '' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted">Manage locations in the <a href="{{ route('calculators.site-visit') }}" target="_blank">Site Visit Calculator</a>.</small>
                                        </div>
                                        <div class="form-group">
                                            <label>Days</label>
                                            <input type="number" min="1" max="365" id="sv-days" name="visit_days" class="form-control" value="{{ $visit->visit_days ?: 1 }}" oninput="svRecalc()">
                                        </div>
                                        <div class="form-group">
                                            <label class="required">Invoice Amount (TZS)</label>
                                            <input type="number" step="0.01" min="0" id="sv-amount" name="invoice_amount" class="form-control"
                                                   value="{{ $visit->estimatedCost() ? round($visit->estimatedCost(), 2) : '' }}" required>
                                            <small class="text-muted" id="sv-amount-hint">Auto-computed from the location; you can override it.</small>
                                        </div>
                                        <div class="form-group">
                                            <label>Invoice Number</label>
                                            <input type="text" class="form-control" value="{{ $visit->invoice_number ?: 'Auto-generated on save (SV-INV-…)' }}" readonly disabled>
                                        </div>
                                        <button class="btn btn-primary"><i class="fa fa-file-invoice"></i> Record Invoice</button>
                                    </form>
                                    <script>
                                        function svRecalc() {
                                            var sel = document.getElementById('sv-loc');
                                            var opt = sel.options[sel.selectedIndex];
                                            var days = parseInt(document.getElementById('sv-days').value) || 1;
                                            var base = parseFloat(opt.getAttribute('data-base')) || 0;
                                            var perDay = parseFloat(opt.getAttribute('data-perday')) || 0;
                                            var hint = document.getElementById('sv-amount-hint');
                                            if (opt.value) {
                                                var amount = base + perDay * days;
                                                document.getElementById('sv-amount').value = amount;
                                                hint.textContent = 'Auto-computed: ' + base.toLocaleString() + ' base'
                                                    + (perDay > 0 ? ' + ' + perDay.toLocaleString() + '/day × ' + days : '')
                                                    + ' = ' + amount.toLocaleString() + ' TZS. You can override it.';
                                            } else {
                                                hint.textContent = 'Select a location to auto-compute, or enter the amount manually.';
                                            }
                                        }
                                    </script>
                                @else
                                    <p class="text-muted"><i class="fa fa-clock-o"></i> Awaiting billing to prepare the invoice.</p>
                                @endif

                            @elseif($visit->stage === 'billing')
                                <p>Invoice <strong>{{ $visit->invoice_number }}</strong> for
                                    <strong>{{ number_format((float) $visit->invoice_amount) }} TZS</strong> is ready.</p>
                                <p>
                                    <a href="{{ route('project_site_visit.invoice_pdf', $visit->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fa fa-file-pdf-o"></i> View Invoice PDF
                                    </a>
                                    <a href="{{ route('project_site_visit.invoice_pdf', ['id' => $visit->id, 'download' => 1]) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fa fa-download"></i> Download / Share
                                    </a>
                                </p>
                                @if($canPay)
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

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
        : ($visit->client
            ? trim($visit->client->first_name . ' ' . $visit->client->last_name)
            : ($visit->lead ? $visit->lead->name . ' (Lead)' : 'Client-only visit'));

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
                            @if($visit->lead)<span class="badge badge-light">Lead</span>@elseif(!$visit->project)<span class="badge badge-light">Client only</span>@endif
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
                                    @if($visit->lead)<span class="badge badge-light">Lead</span>@elseif(!$visit->project)<span class="badge badge-light">Client only</span>@endif
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
                                    <tr><th>Invoice</th><td>
                                        @if($visit->billing_document_id)
                                            <a href="{{ route('billing.invoices.show', $visit->billing_document_id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fa fa-file-invoice"></i> Open Invoice
                                            </a>
                                            <a href="{{ route('billing.invoices.pdf', $visit->billing_document_id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                <i class="fa fa-file-pdf-o"></i> PDF
                                            </a>
                                        @else
                                            <a href="{{ route('project_site_visit.invoice_pdf', $visit->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fa fa-file-pdf-o"></i> View PDF
                                            </a>
                                        @endif
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
                                    <p class="text-muted">Prepare the invoice. Pick a calculator location and days — its cost components are itemised into line items that add up to the total. Lines stay editable, and you can add more.</p>
                                    <form method="post" action="{{ route('project_site_visit.invoice', $visit->id) }}">
                                        @csrf
                                        <div class="form-row">
                                            <div class="form-group col-md-7">
                                                <label>Calculator Location</label>
                                                <select id="sv-loc" name="site_visit_location_id" class="form-control" onchange="svRebuild()">
                                                    <option value="" data-name="">— None (enter lines manually) —</option>
                                                    @foreach(($siteVisitLocations ?? []) as $loc)
                                                        <option value="{{ $loc->id }}" data-name="{{ $loc->name }}"
                                                            data-base="{{ (float)$loc->base_cost_tzs }}"
                                                            data-travel="{{ (float)$loc->preset_travel_tzs }}"
                                                            data-local="{{ (float)$loc->preset_local_tzs }}"
                                                            data-allowance="{{ (float)$loc->preset_allowance_tzs }}"
                                                            data-food="{{ (float)$loc->preset_food_tzs }}"
                                                            data-accommodation="{{ (float)$loc->preset_accommodation_tzs }}"
                                                            {{ $loc->id == $visit->site_visit_location_id ? 'selected' : '' }}>
                                                            {{ $loc->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group col-md-5">
                                                <label>Days</label>
                                                <input type="number" min="1" max="365" id="sv-days" name="visit_days" class="form-control" value="{{ $visit->visit_days ?: 1 }}" oninput="svRebuild()">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label class="required">Issue Date</label>
                                                <input type="date" name="issue_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label>Due Date</label>
                                                <input type="date" name="due_date" class="form-control">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="required">Payment Terms</label>
                                                <select name="payment_terms" class="form-control" required>
                                                    <option value="immediate">Immediate</option>
                                                    <option value="net_7">Net 7</option>
                                                    <option value="net_15">Net 15</option>
                                                    <option value="net_30" selected>Net 30</option>
                                                    <option value="net_45">Net 45</option>
                                                    <option value="net_60">Net 60</option>
                                                    <option value="net_90">Net 90</option>
                                                </select>
                                            </div>
                                        </div>

                                        <label class="required">Line Items</label>
                                        <table class="table table-sm mb-1" id="sv-items">
                                            <thead>
                                                <tr><th>Description</th><th style="width:80px;">Qty</th><th style="width:120px;">Unit Price</th><th style="width:110px;" class="text-right">Amount</th><th style="width:30px;"></th></tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><input type="text" name="items[0][item_name]" class="form-control form-control-sm sv-desc" placeholder="Item name" required></td>
                                                    <td><input type="number" min="0.01" step="0.01" name="items[0][quantity]" class="form-control form-control-sm sv-qty" value="1" oninput="svTotal()" required></td>
                                                    <td><input type="number" min="0" step="0.01" name="items[0][unit_price]" class="form-control form-control-sm sv-price" value="0" oninput="svTotal()" required></td>
                                                    <td class="text-right align-middle sv-amt">0</td>
                                                    <td><button type="button" class="btn btn-sm btn-link text-danger" onclick="svDel(this)">&times;</button></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <div class="d-flex justify-content-between mb-2">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="svAdd()"><i class="fa fa-plus"></i> Add line</button>
                                            <span class="font-weight-bold">Total: <span id="sv-grand">0</span> TZS</span>
                                        </div>

                                        <h4 class="font-size-base font-weight-bold mt-3 mb-2">Notes &amp; Terms</h4>
                                        <div class="form-group">
                                            <label>Service Description <small class="text-muted">(shown as “Service Includes” on the invoice PDF)</small></label>
                                            <textarea name="service_description" id="sv-service-editor" class="form-control">{!! old('service_description', \App\Models\InvoiceSetting::getDefaultServiceDescriptionHtml()) !!}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Internal Notes</label>
                                            <textarea name="notes" id="sv-notes-editor" class="form-control" placeholder="Internal notes (not shown to client)"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Terms &amp; Conditions</label>
                                            <textarea name="terms_conditions" id="sv-terms-editor" class="form-control">{!! old('terms_conditions', \App\Models\InvoiceSetting::getDefaultTermsHtml()) !!}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Footer Text</label>
                                            <textarea name="footer_text" class="form-control" rows="2">{{ old('footer_text', \App\Models\BillingDocumentSetting::where('setting_key', 'invoice_footer')->value('setting_value') ?: 'Thank you for your business!') }}</textarea>
                                        </div>
                                        <div class="text-muted font-size-sm mb-2">Invoice number is generated automatically (INV-{{ date('Y') }}-…).</div>
                                        <button class="btn btn-primary"><i class="fa fa-file-invoice"></i> Create Invoice</button>
                                    </form>
                                    <script>
                                        var svIdx = 0;
                                        function svRowHtml(name, qty, price) {
                                            var i = svIdx++;
                                            return '<tr>'
                                                + '<td><input type="text" name="items['+i+'][item_name]" class="form-control form-control-sm sv-desc" value="'+ (name||'').replace(/"/g,'&quot;') +'" placeholder="Item name" required></td>'
                                                + '<td><input type="number" min="0.01" step="0.01" name="items['+i+'][quantity]" class="form-control form-control-sm sv-qty" value="'+ (qty||1) +'" oninput="svTotal()" required></td>'
                                                + '<td><input type="number" min="0" step="0.01" name="items['+i+'][unit_price]" class="form-control form-control-sm sv-price" value="'+ (price||0) +'" oninput="svTotal()" required></td>'
                                                + '<td class="text-right align-middle sv-amt">0</td>'
                                                + '<td><button type="button" class="btn btn-sm btn-link text-danger" onclick="svDel(this)">&times;</button></td>'
                                                + '</tr>';
                                        }
                                        function svTotal() {
                                            var t = 0;
                                            document.querySelectorAll('#sv-items tbody tr').forEach(function(tr){
                                                var q = parseFloat(tr.querySelector('.sv-qty').value)||0;
                                                var p = parseFloat(tr.querySelector('.sv-price').value)||0;
                                                var amt = q*p;
                                                tr.querySelector('.sv-amt').textContent = amt.toLocaleString();
                                                t += amt;
                                            });
                                            document.getElementById('sv-grand').textContent = t.toLocaleString();
                                        }
                                        function svRebuild() {
                                            var sel = document.getElementById('sv-loc');
                                            var opt = sel.options[sel.selectedIndex];
                                            var days = parseInt(document.getElementById('sv-days').value)||1;
                                            if (!opt || !opt.value) { svTotal(); return; }
                                            var name = opt.getAttribute('data-name');
                                            var parts = [
                                                ['Base fee - ' + name, 1, parseFloat(opt.getAttribute('data-base'))||0, true],
                                                ['Travel (' + days + ' day(s))', days, parseFloat(opt.getAttribute('data-travel'))||0, false],
                                                ['Local transport (' + days + ' day(s))', days, parseFloat(opt.getAttribute('data-local'))||0, false],
                                                ['Allowance (' + days + ' day(s))', days, parseFloat(opt.getAttribute('data-allowance'))||0, false],
                                                ['Food (' + days + ' day(s))', days, parseFloat(opt.getAttribute('data-food'))||0, false],
                                                ['Accommodation (' + days + ' day(s))', days, parseFloat(opt.getAttribute('data-accommodation'))||0, false]
                                            ];
                                            svIdx = 0;
                                            var html = '';
                                            parts.forEach(function(p){ if (p[3] || p[2] > 0) html += svRowHtml(p[0], p[1], p[2]); });
                                            document.querySelector('#sv-items tbody').innerHTML = html;
                                            svTotal();
                                        }
                                        function svAdd() {
                                            document.querySelector('#sv-items tbody').insertAdjacentHTML('beforeend', svRowHtml('', 1, 0));
                                        }
                                        function svDel(btn) {
                                            if (document.querySelectorAll('#sv-items tbody tr').length > 1) { btn.closest('tr').remove(); svTotal(); }
                                        }
                                        svRebuild();
                                    </script>
                                @else
                                    <p class="text-muted"><i class="fa fa-clock-o"></i> Awaiting billing to prepare the invoice.</p>
                                @endif

                            @elseif($visit->stage === 'billing')
                                <p>Invoice <strong>{{ $visit->invoice_number }}</strong> for
                                    <strong>{{ number_format((float) $visit->invoice_amount) }} TZS</strong> is ready.</p>
                                <p>
                                    @if($visit->billing_document_id)
                                        <a href="{{ route('billing.invoices.show', $visit->billing_document_id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-file-invoice"></i> Open Invoice
                                        </a>
                                        <a href="{{ route('billing.invoices.pdf', $visit->billing_document_id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                            <i class="fa fa-file-pdf-o"></i> Invoice PDF
                                        </a>
                                    @else
                                        <a href="{{ route('project_site_visit.invoice_pdf', $visit->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-file-pdf-o"></i> View Invoice PDF
                                        </a>
                                    @endif
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
                                    <p class="text-muted">Assign the field team — pick any one, two, or all three.</p>
                                    <form method="post" action="{{ route('project_site_visit.assign', $visit->id) }}">
                                        @csrf
                                        <div class="form-group">
                                            <label>Architect</label>
                                            <select name="architect_id" class="form-control">
                                                <option value="">— None —</option>
                                                @foreach($architects as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Site Engineer</label>
                                            <select name="site_engineer_id" class="form-control">
                                                <option value="">— None —</option>
                                                @foreach($siteEngineers as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Site Supervisor</label>
                                            <select name="site_supervisor_id" class="form-control">
                                                <option value="">— None —</option>
                                                @foreach($supervisors as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                                            </select>
                                        </div>
                                        <small class="text-muted d-block mb-2">Assign at least one.</small>
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

@section('css_after')
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
@endsection

@section('js_after')
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script>
        $(function () {
            var basic = [
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link']],
                ['view', ['fullscreen', 'codeview']]
            ];
            if ($('#sv-service-editor').length) {
                $('#sv-service-editor').summernote({ height: 180, toolbar: basic, placeholder: 'Service description…' });
            }
            if ($('#sv-notes-editor').length) {
                $('#sv-notes-editor').summernote({ height: 120, toolbar: basic, placeholder: 'Internal notes…' });
            }
            if ($('#sv-terms-editor').length) {
                $('#sv-terms-editor').summernote({ height: 260, toolbar: basic, placeholder: 'Terms & conditions…' });
            }
        });
    </script>
@endsection

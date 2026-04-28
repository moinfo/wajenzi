@extends('layouts.backend')

@section('css_before')
<style>
    .datepicker-dropdown { z-index: 1300 !important; }
</style>
@endsection

@section('content')
<div class="content">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div>
    @endif

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0 font-w700">
            <i class="fab fa-whatsapp text-success mr-2"></i>WhatsApp Marketing
        </h2>
        <div>
            @if($tab === 'contacts')
                @can('Add WhatsApp Contact')
                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addContactModal">
                    <i class="fa fa-plus mr-1"></i> Add Contact
                </button>
                @endcan
            @else
                @can('Manage WhatsApp Campaigns')
                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addCampaignModal">
                    <i class="fa fa-plus mr-1"></i> New Campaign
                </button>
                @endcan
            @endif
        </div>
    </div>

    {{-- Stats Tiles --}}
    <div class="row mb-3">
        <div class="col-6 col-md-3 mb-2">
            <div class="block block-rounded mb-0 h-100">
                <div class="block-content d-flex align-items-center py-3">
                    <div class="mr-3"><i class="fa fa-users fa-2x text-primary opacity-50"></i></div>
                    <div>
                        <div class="text-muted" style="font-size:12px">Total Contacts</div>
                        <div class="font-w700 h4 mb-0">{{ $totalContacts }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="block block-rounded mb-0 h-100">
                <div class="block-content d-flex align-items-center py-3">
                    <div class="mr-3"><i class="fa fa-user-check fa-2x text-success opacity-50"></i></div>
                    <div>
                        <div class="text-muted" style="font-size:12px">Converted to Clients</div>
                        <div class="font-w700 h4 mb-0 text-success">{{ $converted }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="block block-rounded mb-0 h-100">
                <div class="block-content d-flex align-items-center py-3">
                    <div class="mr-3"><i class="fab fa-whatsapp fa-2x text-success opacity-50"></i></div>
                    <div>
                        <div class="text-muted" style="font-size:12px">From WhatsApp Ads</div>
                        <div class="font-w700 h4 mb-0">{{ $fromAds }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="block block-rounded mb-0 h-100">
                <div class="block-content d-flex align-items-center py-3">
                    <div class="mr-3"><i class="fa fa-chart-pie fa-2x text-warning opacity-50"></i></div>
                    <div>
                        <div class="text-muted" style="font-size:12px">Conversion Rate</div>
                        <div class="font-w700 h4 mb-0 text-warning">{{ $conversionRate }}%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-0 border-bottom-0">
        <li class="nav-item">
            <a href="{{ route('whatsapp_marketing.index', ['tab' => 'contacts']) }}"
               class="nav-link {{ $tab === 'contacts' ? 'active' : '' }}">
                <i class="fa fa-address-book mr-1"></i> Contacts
            </a>
        </li>
        @can('Manage WhatsApp Campaigns')
        <li class="nav-item">
            <a href="{{ route('whatsapp_marketing.index', ['tab' => 'campaigns']) }}"
               class="nav-link {{ $tab === 'campaigns' ? 'active' : '' }}">
                <i class="fa fa-bullhorn mr-1"></i> Ad Campaigns
                @if($campaignCount > 0)
                    <span class="badge badge-primary ml-1">{{ $campaignCount }}</span>
                @endif
            </a>
        </li>
        @endcan
        @can('View WhatsApp Reports')
        <li class="nav-item">
            <a href="{{ route('whatsapp_marketing.index', ['tab' => 'reports']) }}"
               class="nav-link {{ $tab === 'reports' ? 'active' : '' }}">
                <i class="fa fa-chart-bar mr-1"></i> Reports
            </a>
        </li>
        @endcan
    </ul>

    <div class="block block-rounded border-top-0" style="border-radius: 0 0 .25rem .25rem">
        <div class="block-content block-content-full">

        {{-- ══════ TAB: CONTACTS ══════ --}}
        @if($tab === 'contacts')

            {{-- Stage filter pills --}}
            @php
                $allStages = \App\Models\WhatsAppContact::STAGES;
                $totalAll = $stageCounts->sum();
            @endphp
            <div class="d-flex flex-wrap mb-3" style="gap:6px">
                <a id="stage-pill-all"
                   href="{{ route('whatsapp_marketing.index', array_merge(request()->except('stage'), ['tab'=>'contacts'])) }}"
                   class="btn btn-sm {{ !request('stage') ? 'btn-dark' : 'btn-outline-secondary' }}" style="border-radius:20px">
                    ALL ({{ $totalAll }})
                </a>
                @foreach($allStages as $key => $meta)
                <a id="stage-pill-{{ $key }}"
                   href="{{ route('whatsapp_marketing.index', array_merge(request()->except('stage'), ['tab'=>'contacts','stage'=>$key])) }}"
                   class="btn btn-sm {{ request('stage') === $key ? 'btn-primary' : 'btn-outline-primary' }}" style="border-radius:20px">
                    {{ strtoupper($meta['label']) }} ({{ $stageCounts[$key] ?? 0 }})
                </a>
                @endforeach
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('whatsapp_marketing.index') }}" class="mb-3">
                <input type="hidden" name="tab" value="contacts">
                @if(request('stage'))<input type="hidden" name="stage" value="{{ request('stage') }}">@endif
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-search"></i></span></div>
                            <input type="text" name="search" class="form-control" placeholder="Search name or phone..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="source" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">All Sources</option>
                            @foreach(\App\Models\WhatsAppContact::SOURCES as $k => $label)
                                <option value="{{ $k }}" {{ request('source') === $k ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-filter mr-1"></i>Filter</button>
                        @if(request()->hasAny(['search','source','stage']))
                        <a href="{{ route('whatsapp_marketing.index', ['tab'=>'contacts']) }}" class="btn btn-sm btn-outline-secondary ml-1">Clear</a>
                        @endif
                    </div>
                    <div class="col-md-5 mb-2 text-right">
                        <small class="text-muted">{{ $contacts->count() }} contact{{ $contacts->count() !== 1 ? 's' : '' }}</small>
                    </div>
                </div>
            </form>

            {{-- Contacts table --}}
            <div class="table-responsive">
                <table class="table table-hover table-vcenter table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th width="40">#</th>
                            <th width="30"></th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Services</th>
                            <th>Labels</th>
                            <th>Stage</th>
                            <th>Source</th>
                            <th>Campaign</th>
                            <th>Follow-up</th>
                            <th>Client</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contacts as $i => $contact)
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td>
                                <i class="fa fa-star {{ $contact->is_important ? 'text-warning' : 'text-muted' }}"
                                   style="opacity:{{ $contact->is_important ? 1 : 0.3 }}"></i>
                            </td>
                            <td class="font-w600">{{ $contact->name }}</td>
                            <td>
                                <a href="https://wa.me/{{ preg_replace('/\D/', '', $contact->phone) }}" target="_blank" class="text-success">
                                    {{ $contact->phone }}
                                </a>
                            </td>
                            <td>
                                @foreach($contact->services as $svc)
                                    <span class="badge badge-light border text-primary mr-1" style="font-size:10px;border-radius:20px">{{ $svc->name }}</span>
                                @endforeach
                            </td>
                            <td>
                                @php $lblKeys = $contact->getRelation('_labels') ?? []; @endphp
                                <div class="d-flex align-items-center flex-wrap" style="gap:4px">
                                    <div id="lbl-dots-{{ $contact->id }}" class="d-flex flex-wrap" style="gap:4px">
                                        @foreach($lblKeys as $lbl)
                                            @php $lmeta = \App\Models\WhatsAppContact::LABELS[$lbl] ?? null; @endphp
                                            @if($lmeta)
                                            <span title="{{ $lmeta['label'] }}"
                                                  style="width:10px;height:10px;border-radius:50%;display:inline-block;background:{{ $lmeta['hex'] }};flex-shrink:0;
                                                         box-shadow:0 0 0 1px #fff,0 0 0 2px {{ $lmeta['hex'] }}88"></span>
                                            @endif
                                        @endforeach
                                    </div>
                                    <button type="button"
                                            class="btn btn-xs btn-light border"
                                            style="padding:1px 5px;font-size:10px;line-height:1.5;border-radius:10px;flex-shrink:0"
                                            title="Edit labels"
                                            onclick="openLabelPicker(this, {{ $contact->id }}, {{ json_encode($lblKeys) }}, '{{ $contact->stage }}')">
                                        <i class="fa fa-tag" style="font-size:9px"></i>
                                    </button>
                                </div>
                            </td>
                            <td>
                                <span id="stage-badge-{{ $contact->id }}"
                                      class="badge {{ $contact->stage_badge_class }}" style="border-radius:20px;font-size:11px">
                                    {{ strtoupper($contact->stage_label) }}
                                </span>
                            </td>
                            <td class="text-muted small">{{ $contact->source_label }}</td>
                            <td class="text-muted small">{{ $contact->campaign?->name ?? '—' }}</td>
                            <td class="text-muted small">{{ $contact->next_followup_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>
                                @if($contact->client)
                                    <span class="text-success font-w600 small">{{ $contact->client->first_name }} {{ $contact->client->last_name }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-right text-nowrap">
                                @can('WhatsApp Marketing')
                                <a href="https://wa.me/{{ preg_replace('/\D/', '', $contact->phone) }}" target="_blank"
                                   class="btn btn-xs btn-alt-success" title="Chat on WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                                @endcan
                                @can('Log WhatsApp Call')
                                <button class="btn btn-xs btn-alt-info" title="Log Call / Follow-up"
                                    onclick="openCallPanel({{ $contact->id }})">
                                    <i class="fa fa-phone"></i>
                                </button>
                                @endcan
                                @can('Edit WhatsApp Contact')
                                <button class="btn btn-xs btn-alt-secondary" title="Edit"
                                    onclick="openEditContact({{ json_encode([
                                        'id'                 => $contact->id,
                                        'name'               => $contact->name,
                                        'phone'              => $contact->phone,
                                        'stage'              => $contact->stage,
                                        'source'             => $contact->source,
                                        'campaign_id'        => $contact->campaign_id,
                                        'client_id'          => $contact->client_id,
                                        'next_followup_date' => $contact->next_followup_date?->format('Y-m-d'),
                                        'assigned_to'        => $contact->assigned_to,
                                        'notes'              => $contact->notes,
                                        'is_important'       => $contact->is_important,
                                        'service_ids'        => $contact->services->pluck('id'),
                                        'labels'             => $contact->getRelation('_labels') ?? [],
                                        'deal_value'         => $contact->deal_value,
                                    ]) }})">
                                    <i class="fa fa-pencil-alt"></i>
                                </button>
                                @endcan
                                @can('Delete WhatsApp Contact')
                                <form action="{{ route('whatsapp_marketing.contacts.destroy', $contact->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-alt-danger" onclick="return confirm('Delete this contact?')">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="12" class="text-center py-5 text-muted">
                                <i class="fab fa-whatsapp fa-2x mb-2 d-block text-success opacity-50"></i>
                                No contacts found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        {{-- ══════ TAB: CAMPAIGNS ══════ --}}
        @elseif($tab === 'campaigns')
        @cannot('Manage WhatsApp Campaigns')
            <div class="alert alert-warning"><i class="fa fa-lock mr-1"></i> You do not have permission to view Campaigns.</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover table-vcenter">
                    <thead class="thead-light">
                        <tr>
                            <th>Campaign</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Budget</th>
                            <th class="text-center">Leads</th>
                            <th class="text-center">Converted</th>
                            <th>Cost / Lead</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($campaignRows as $camp)
                        <tr class="{{ $camp->status === 'closed' ? 'text-muted' : '' }}">
                            <td class="font-w600">{{ $camp->name }}</td>
                            <td>{{ $camp->start_date->format('d M Y') }}</td>
                            <td>{{ $camp->end_date?->format('d M Y') ?? '—' }}</td>
                            <td>{{ $camp->budget ? 'TZS ' . number_format($camp->budget, 2) : '—' }}</td>
                            <td class="text-center">
                                <span class="badge badge-primary px-2">{{ $camp->contacts_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-success px-2">{{ $camp->converted_count }}</span>
                            </td>
                            <td class="text-muted">
                                @if($camp->budget && $camp->contacts_count > 0)
                                    TZS {{ number_format($camp->budget / $camp->contacts_count, 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($camp->status === 'closed')
                                    <span class="badge badge-secondary">Closed</span>
                                @else
                                    <span class="badge badge-success">Active</span>
                                @endif
                            </td>
                            <td class="text-right text-nowrap">
                                @if($camp->status === 'active')
                                <button class="btn btn-xs btn-alt-secondary" title="Edit"
                                    onclick="openEditCampaign({{ json_encode([
                                        'id'         => $camp->id,
                                        'name'       => $camp->name,
                                        'start_date' => $camp->start_date->format('Y-m-d'),
                                        'end_date'   => $camp->end_date?->format('Y-m-d'),
                                        'budget'     => $camp->budget,
                                        'notes'      => $camp->notes,
                                    ]) }})">
                                    <i class="fa fa-pencil-alt"></i>
                                </button>
                                <form action="{{ route('whatsapp_marketing.campaigns.close', $camp->id) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-xs btn-alt-warning" title="Close campaign" onclick="return confirm('Close this campaign? It will no longer appear in the contact form.')">
                                        <i class="fa fa-times-circle"></i>
                                    </button>
                                </form>
                                @endif
                                <form action="{{ route('whatsapp_marketing.campaigns.destroy', $camp->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-alt-danger" onclick="return confirm('Delete this campaign?')">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">No campaigns yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endcannot

        {{-- ══════ TAB: REPORTS ══════ --}}
        @elseif($tab === 'reports')
            @include('pages.whatsapp_marketing._reports')

        @endif

        </div>
    </div>
</div>

{{-- ══════════════════════════ MODALS ══════════════════════════ --}}

{{-- Add Contact Modal --}}
<div class="modal fade" id="addContactModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('whatsapp_marketing.contacts.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fab fa-whatsapp mr-1"></i> Add WhatsApp Contact</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    @include('pages.whatsapp_marketing._contact_form', ['prefix' => 'add', 'contact' => null])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Contact</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Contact Modal --}}
<div class="modal fade" id="editContactModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editContactForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fa fa-pencil-alt mr-1"></i> Edit Contact</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    @include('pages.whatsapp_marketing._contact_form', ['prefix' => 'edit', 'contact' => null, 'formCampaigns' => $allCampaigns])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add Campaign Modal --}}
<div class="modal fade" id="addCampaignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('whatsapp_marketing.campaigns.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fa fa-bullhorn mr-1"></i> New Campaign</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    @include('pages.whatsapp_marketing._campaign_form', ['prefix' => 'add'])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Create Campaign</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Campaign Modal --}}
<div class="modal fade" id="editCampaignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editCampaignForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fa fa-pencil-alt mr-1"></i> Edit Campaign</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    @include('pages.whatsapp_marketing._campaign_form', ['prefix' => 'edit'])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══════════════════════════ LABEL PICKER ══════════════════════════ --}}
<div id="labelPickerPopover"
     style="display:none;position:fixed;z-index:1060;background:#fff;
            border:1px solid #dee2e6;border-radius:10px;
            box-shadow:0 4px 20px rgba(0,0,0,.18);padding:14px;min-width:200px">
    <p class="mb-2 font-w600" style="font-size:11px;text-transform:uppercase;letter-spacing:.6px;color:#6c757d">Labels</p>
    @foreach(\App\Models\WhatsAppContact::LABELS as $key => $meta)
    <label class="d-flex align-items-center mb-1" style="cursor:pointer;gap:8px;font-weight:normal;font-size:13px;margin:0">
        <input type="checkbox" class="lbl-picker-cb" value="{{ $key }}" style="display:none">
        <span class="lbl-picker-dot" style="width:13px;height:13px;border-radius:50%;display:inline-block;flex-shrink:0;
              background:{{ $meta['hex'] }};transition:transform .15s;
              box-shadow:0 0 0 2px #fff,0 0 0 3px {{ $meta['hex'] }}88"></span>
        <span>{{ $meta['label'] }}</span>
    </label>
    @endforeach
    <div class="d-flex mt-3" style="gap:6px">
        <button class="btn btn-sm btn-light flex-fill border" onclick="closeLabelPicker()">Cancel</button>
        <button class="btn btn-sm btn-success flex-fill" onclick="saveLabelPicker()">Apply</button>
    </div>
</div>

{{-- ══════════════════════════ CALL PANEL ══════════════════════════ --}}
<div id="callPanelOverlay" onclick="closeCallPanel()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:1040"></div>

<div id="callPanel"
     style="position:fixed;top:0;right:0;width:420px;max-width:100vw;height:100%;
            background:#fff;z-index:1041;box-shadow:-4px 0 24px rgba(0,0,0,.15);
            transform:translateX(100%);transition:transform .25s ease;
            display:flex;flex-direction:column;overflow:hidden">

    {{-- Panel header --}}
    <div id="callPanelHeader" style="padding:16px 20px;border-bottom:1px solid #e4e7ed;flex-shrink:0">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h5 id="callPanelTitle" class="mb-1 font-w700" style="font-size:15px"></h5>
                <div class="d-flex align-items-center" style="gap:8px">
                    <span id="callPanelPhone" class="text-muted small"></span>
                    <span id="callPanelBadge" class="badge" style="font-size:10px;border-radius:20px"></span>
                </div>
            </div>
            <button onclick="closeCallPanel()" class="btn btn-sm btn-light" style="padding:4px 8px">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <button id="logCallBtn" class="btn btn-success btn-sm mt-2 w-100" onclick="toggleCallForm(true)">
            <i class="fa fa-plus mr-1"></i> Log a Call
        </button>
    </div>

    {{-- Inline call log form --}}
    <div id="callLogForm" style="display:none;padding:16px 20px;border-bottom:1px solid #e4e7ed;background:#f9fafb;flex-shrink:0">
        <p class="font-w600 mb-3" style="font-size:13px">New Call Log</p>
        <div class="form-row">
            <div class="col-6">
                <div class="form-group mb-2">
                    <label class="small font-w600">Call Date <span class="text-danger">*</span></label>
                    <input type="text" id="callDateInput" class="form-control form-control-sm datepicker"
                           autocomplete="off" placeholder="yyyy-mm-dd">
                </div>
            </div>
            <div class="col-6">
                <div class="form-group mb-2">
                    <label class="small font-w600">Outcome <span class="text-danger">*</span></label>
                    <select id="callOutcomeInput" class="form-control form-control-sm">
                        @foreach(\App\Models\WhatsAppContactCall::OUTCOMES as $key => $meta)
                            <option value="{{ $key }}">{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="form-group mb-2">
            <label class="small font-w600">Next Follow-up Date</label>
            <input type="text" id="callFollowupInput" class="form-control form-control-sm datepicker"
                   autocomplete="off" placeholder="yyyy-mm-dd">
        </div>
        <div class="form-group mb-2">
            <label class="small font-w600">Notes</label>
            <textarea id="callNotesInput" class="form-control form-control-sm" rows="2"
                      placeholder="What was discussed..."></textarea>
        </div>
        <div class="d-flex" style="gap:8px">
            <button class="btn btn-secondary btn-sm flex-fill" onclick="toggleCallForm(false)">Cancel</button>
            <button id="saveCallBtn" class="btn btn-success btn-sm flex-fill" onclick="saveCall()">Save Log</button>
        </div>
        <div id="callFormError" class="text-danger small mt-1" style="display:none"></div>
    </div>

    {{-- Call history --}}
    <div style="flex:1;overflow-y:auto;padding:16px 20px">
        <p class="text-muted font-w600 small mb-2" style="text-transform:uppercase;letter-spacing:.5px">Call History</p>
        <div id="callHistoryList">
            <p class="text-muted text-center py-4 small">Loading...</p>
        </div>
    </div>
</div>

@endsection

@section('js_after')
<script>
// ── Label Picker Popover ─────────────────────────────────────────────────────
var _lpContactId = null, _lpTriggerBtn = null;
var _allLabels = @json(\App\Models\WhatsAppContact::LABELS);

function openLabelPicker(btn, contactId, currentLabels, currentStage) {
    _lpContactId  = contactId;
    _lpTriggerBtn = btn;

    // Pre-check the current stage label so picker reflects reality
    var effective = currentLabels.slice();
    if (currentStage && _allLabels[currentStage] && effective.indexOf(currentStage) === -1) {
        effective.push(currentStage);
    }

    var popover = document.getElementById('labelPickerPopover');
    popover.querySelectorAll('.lbl-picker-cb').forEach(function(cb) {
        cb.checked = effective.indexOf(cb.value) !== -1;
        var dot = cb.closest('label').querySelector('.lbl-picker-dot');
        dot.style.transform = cb.checked ? 'scale(1.3)' : 'scale(1)';
        dot.style.opacity   = cb.checked ? '1' : '0.4';
    });

    popover.style.display = 'block';
    var rect = btn.getBoundingClientRect();
    var pw = popover.offsetWidth || 200;
    var left = rect.right - pw;
    if (left < 8) left = 8;
    popover.style.top  = (rect.bottom + 4) + 'px';
    popover.style.left = left + 'px';

    setTimeout(function() {
        document.addEventListener('click', _lpOutsideClick);
    }, 0);
}

function _lpOutsideClick(e) {
    var popover = document.getElementById('labelPickerPopover');
    if (!popover.contains(e.target) && !_lpTriggerBtn.contains(e.target)) {
        closeLabelPicker();
    }
}

function closeLabelPicker() {
    document.getElementById('labelPickerPopover').style.display = 'none';
    document.removeEventListener('click', _lpOutsideClick);
    _lpContactId  = null;
    _lpTriggerBtn = null;
}

function saveLabelPicker() {
    var labels = [];
    document.querySelectorAll('.lbl-picker-cb:checked').forEach(function(cb) { labels.push(cb.value); });
    var contactId = _lpContactId;
    closeLabelPicker();

    $.ajax({
        url:  '/whatsapp-marketing/contacts/' + contactId + '/labels',
        type: 'POST',
        data: { _token: $('meta[name="csrf-token"]').attr('content'), _method: 'PATCH', label_ids: labels },
        success: function(res) {
            _refreshLabelDots(contactId, labels);

            // Update stage badge in the row
            var badge = document.getElementById('stage-badge-' + contactId);
            if (badge && res.stage_label) {
                badge.textContent = res.stage_label.toUpperCase();
                badge.className   = 'badge ' + res.stage_badge;
                badge.style.cssText = 'border-radius:20px;font-size:11px';
            }

            // Update stage filter pill counts
            if (res.stage_counts) {
                var allStages = @json(array_keys(\App\Models\WhatsAppContact::STAGES));
                allStages.forEach(function(key) {
                    var pill = document.getElementById('stage-pill-' + key);
                    if (!pill) return;
                    var count = res.stage_counts[key] || 0;
                    pill.textContent = pill.textContent.replace(/\(\d+\)/, '(' + count + ')');
                });
                var allPill = document.getElementById('stage-pill-all');
                if (allPill) allPill.textContent = allPill.textContent.replace(/\(\d+\)/, '(' + (res.total_all || 0) + ')');
            }
        }
    });
}

function _refreshLabelDots(contactId, labels) {
    var container = document.getElementById('lbl-dots-' + contactId);
    if (!container) return;
    while (container.firstChild) container.removeChild(container.firstChild);
    labels.forEach(function(key) {
        var meta = _allLabels[key];
        if (!meta) return;
        var span = document.createElement('span');
        span.title = meta.label;
        span.style.cssText = 'width:10px;height:10px;border-radius:50%;display:inline-block;background:' +
            meta.hex + ';flex-shrink:0;box-shadow:0 0 0 1px #fff,0 0 0 2px ' + meta.hex + '88';
        container.appendChild(span);
    });
}

$(document).on('change', '.lbl-picker-cb', function() {
    var dot = this.closest('label').querySelector('.lbl-picker-dot');
    if (!dot) return;
    dot.style.transform = this.checked ? 'scale(1.3)' : 'scale(1)';
    dot.style.opacity   = this.checked ? '1' : '0.4';
});

// ── Call Panel ───────────────────────────────────────────────────────────────
var _callContactId = null;

function openCallPanel(contactId) {
    _callContactId = contactId;
    toggleCallForm(false);
    var list = document.getElementById('callHistoryList');
    list.innerHTML = '';
    var loading = document.createElement('p');
    loading.className = 'text-muted text-center py-4 small';
    loading.textContent = 'Loading...';
    list.appendChild(loading);

    document.getElementById('callPanelOverlay').style.display = 'block';
    document.getElementById('callPanel').style.transform = 'translateX(0)';

    $.getJSON('/whatsapp-marketing/contacts/' + contactId + '/calls', function(data) {
        document.getElementById('callPanelTitle').textContent = 'Follow-ups — ' + data.contact.name;
        document.getElementById('callPanelPhone').textContent = data.contact.phone;
        var badge = document.getElementById('callPanelBadge');
        badge.textContent = data.contact.stage_label.toUpperCase();
        badge.className   = 'badge ' + data.contact.stage_badge;
        renderCallHistory(data.calls);
    });
}

function closeCallPanel() {
    document.getElementById('callPanel').style.transform = 'translateX(100%)';
    document.getElementById('callPanelOverlay').style.display = 'none';
    _callContactId = null;
}

function toggleCallForm(show) {
    document.getElementById('callLogForm').style.display   = show ? '' : 'none';
    document.getElementById('logCallBtn').style.display    = show ? 'none' : '';
    document.getElementById('callFormError').style.display = 'none';
    if (show) {
        var today = new Date();
        var yy = today.getFullYear(), mm = String(today.getMonth()+1).padStart(2,'0'), dd = String(today.getDate()).padStart(2,'0');
        $('#callDateInput').datepicker('update', yy + '-' + mm + '-' + dd);
        $('#callFollowupInput').datepicker('update', '');
        document.getElementById('callNotesInput').value = '';
        document.getElementById('callOutcomeInput').value = 'answered';
    }
}

function saveCall() {
    var dateVal = document.getElementById('callDateInput').value.trim();
    var errEl   = document.getElementById('callFormError');
    if (!dateVal) {
        errEl.textContent = 'Call date is required.';
        errEl.style.display = '';
        return;
    }
    var btn = document.getElementById('saveCallBtn');
    btn.disabled = true;
    btn.textContent = 'Saving...';

    $.ajax({
        url:  '/whatsapp-marketing/contacts/' + _callContactId + '/calls',
        type: 'POST',
        data: {
            _token:             $('meta[name="csrf-token"]').attr('content'),
            call_date:          dateVal,
            outcome:            document.getElementById('callOutcomeInput').value,
            next_followup_date: document.getElementById('callFollowupInput').value.trim(),
            notes:              document.getElementById('callNotesInput').value.trim(),
        },
        success: function(res) {
            toggleCallForm(false);
            var list = document.getElementById('callHistoryList');
            var placeholder = list.querySelector('p.text-muted');
            if (placeholder && placeholder.textContent === 'No calls logged yet.') placeholder.remove();
            list.insertBefore(buildCallCard(res), list.firstChild);
        },
        error: function(xhr) {
            var msg = 'Error saving. Please try again.';
            try { msg = Object.values(xhr.responseJSON.errors)[0][0]; } catch(e) {}
            errEl.textContent = msg;
            errEl.style.display = '';
        },
        complete: function() { btn.disabled = false; btn.textContent = 'Save Log'; }
    });
}

function renderCallHistory(calls) {
    var list = document.getElementById('callHistoryList');
    list.innerHTML = '';
    if (!calls.length) {
        var p = document.createElement('p');
        p.className = 'text-muted text-center py-4 small';
        p.textContent = 'No calls logged yet.';
        list.appendChild(p);
        return;
    }
    calls.forEach(function(call) { list.appendChild(buildCallCard(call)); });
}

function buildCallCard(call) {
    var div = document.createElement('div');
    div.style.cssText = 'padding:10px 0;border-bottom:1px solid #f0f0f0';

    var header = document.createElement('div');
    header.className = 'd-flex justify-content-between align-items-center';

    var dateSpan = document.createElement('span');
    dateSpan.className = 'font-w600 small';
    dateSpan.textContent = call.call_date;
    header.appendChild(dateSpan);

    var badge = document.createElement('span');
    badge.className = 'badge badge-' + call.outcome_color;
    badge.style.cssText = 'font-size:10px;border-radius:20px';
    badge.textContent = call.outcome_label;
    header.appendChild(badge);
    div.appendChild(header);

    if (call.notes) {
        var notesDiv = document.createElement('div');
        notesDiv.className = 'small text-muted mt-1';
        notesDiv.style.whiteSpace = 'pre-wrap';
        notesDiv.textContent = call.notes;
        div.appendChild(notesDiv);
    }

    if (call.next_followup_date) {
        var fDiv = document.createElement('div');
        fDiv.className = 'small text-muted mt-1';
        var icon = document.createElement('i');
        icon.className = 'fa fa-calendar-alt mr-1';
        fDiv.appendChild(icon);
        fDiv.appendChild(document.createTextNode('Follow-up: ' + call.next_followup_date));
        div.appendChild(fDiv);
    }

    if (call.logged_by) {
        var byDiv = document.createElement('div');
        byDiv.className = 'mt-1';
        var bySpan = document.createElement('span');
        bySpan.className = 'text-muted';
        bySpan.style.fontSize = '11px';
        bySpan.textContent = 'by ' + call.logged_by;
        byDiv.appendChild(bySpan);
        div.appendChild(byDiv);
    }

    return div;
}

// ── Label dot visual sync ────────────────────────────────────────────────────
function _syncLabelDots(scope) {
    (scope || document).querySelectorAll('.lbl-cb').forEach(function(cb) {
        var dot = cb.closest('label') ? cb.closest('label').querySelector('.lbl-dot') : null;
        if (!dot) return;
        dot.style.transform = cb.checked ? 'scale(1.3)' : 'scale(1)';
        dot.style.opacity   = cb.checked ? '1' : '0.4';
    });
}
// Init dots on page load (add-contact form is already in the DOM)
_syncLabelDots();

// Toggle dot appearance when checkbox changes
$(document).on('change', '.lbl-cb', function() {
    var dot = this.closest('label') ? this.closest('label').querySelector('.lbl-dot') : null;
    if (!dot) return;
    dot.style.transform = this.checked ? 'scale(1.3)' : 'scale(1)';
    dot.style.opacity   = this.checked ? '1' : '0.4';
});

// ── Contact modal ────────────────────────────────────────────────────────────
function openEditContact(data) {
    var form = document.getElementById('editContactForm');
    form.action = '/whatsapp-marketing/contacts/' + data.id;

    form.querySelector('[name="name"]').value           = data.name || '';
    form.querySelector('[name="phone"]').value          = data.phone || '';
    form.querySelector('[name="stage"]').value          = data.stage || 'lead';
    form.querySelector('[name="source"]').value         = data.source || 'whatsapp_ad';
    form.querySelector('[name="campaign_id"]').value    = data.campaign_id || '';
    form.querySelector('[name="client_id"]').value      = data.client_id || '';
    form.querySelector('[name="assigned_to"]').value    = data.assigned_to || '';
    form.querySelector('[name="notes"]').value          = data.notes || '';
    form.querySelector('[name="deal_value"]').value     = data.deal_value || '';
    form.querySelector('[name="is_important"]').checked = !!data.is_important;

    // Restore service pills
    form.querySelectorAll('.svc-cb').forEach(function(cb) {
        var selected = data.service_ids && data.service_ids.indexOf(parseInt(cb.dataset.id)) !== -1;
        cb.checked = selected;
        var label = cb.closest('label');
        if (label) label.classList.toggle('active', selected);
    });

    // Restore labels
    form.querySelectorAll('.lbl-cb').forEach(function(cb) {
        cb.checked = !!(data.labels && data.labels.indexOf(cb.dataset.key) !== -1);
    });
    _syncLabelDots(form);

    $('#edit-followup-date').datepicker('update', data.next_followup_date || '');
    $('#editContactModal').modal('show');
}

// Reset labels when add-contact modal opens
$('#addContactModal').on('show.bs.modal', function() {
    this.querySelectorAll('.lbl-cb').forEach(function(cb) { cb.checked = false; });
    _syncLabelDots(this);
});

// ── Campaign modal ───────────────────────────────────────────────────────────
function openEditCampaign(data) {
    var form = document.getElementById('editCampaignForm');
    form.action = '/whatsapp-marketing/campaigns/' + data.id;

    form.querySelector('[name="name"]').value   = data.name || '';
    form.querySelector('[name="budget"]').value = data.budget || '';
    form.querySelector('[name="notes"]').value  = data.notes || '';

    $('#edit-campaign-start').datepicker('update', data.start_date || '');
    $('#edit-campaign-end').datepicker('update', data.end_date || '');
    $('#editCampaignModal').modal('show');
}
</script>
@endsection

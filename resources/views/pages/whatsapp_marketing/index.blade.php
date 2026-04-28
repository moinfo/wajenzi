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
                <a href="{{ route('whatsapp_marketing.index', array_merge(request()->except('stage'), ['tab'=>'contacts'])) }}"
                   class="btn btn-sm {{ !request('stage') ? 'btn-dark' : 'btn-outline-secondary' }}" style="border-radius:20px">
                    ALL ({{ $totalAll }})
                </a>
                @foreach($allStages as $key => $meta)
                <a href="{{ route('whatsapp_marketing.index', array_merge(request()->except('stage'), ['tab'=>'contacts','stage'=>$key])) }}"
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
                                <span class="badge {{ $contact->stage_badge_class }}" style="border-radius:20px;font-size:11px">
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
                                <a href="https://wa.me/{{ preg_replace('/\D/', '', $contact->phone) }}" target="_blank"
                                   class="btn btn-xs btn-alt-success" title="Chat on WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
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
                            <td colspan="11" class="text-center py-5 text-muted">
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
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($campaignRows as $camp)
                        <tr>
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
                            <td class="text-right text-nowrap">
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
                    @include('pages.whatsapp_marketing._contact_form', ['prefix' => 'edit', 'contact' => null])
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

@endsection

@section('js_after')
<script>
(function() {
    var bodyTop = $('body').offset().top;
    $('.modal .datepicker').each(function() {
        try { $(this).datepicker('destroy'); } catch(e) {}
        $(this).datepicker({ format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true, container: 'body', orientation: 'bottom auto' });
        var dp = $(this).data('datepicker');
        if (dp) {
            var _orig = dp.place;
            dp.place = function() {
                _orig.call(this);
                if (bodyTop > 0) { this.picker.css('top', parseFloat(this.picker.css('top')) + bodyTop); }
                this.picker[0].style.setProperty('z-index', '9999', 'important');
                return this;
            };
        }
    });
})();

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
    form.querySelector('[name="is_important"]').checked = !!data.is_important;

    // Restore service pills
    form.querySelectorAll('.svc-cb').forEach(function(cb) {
        var selected = data.service_ids && data.service_ids.indexOf(parseInt(cb.dataset.id)) !== -1;
        cb.checked = selected;
        var label = cb.closest('label');
        if (label) label.classList.toggle('active', selected);
    });

    $('#edit-followup-date').datepicker('update', data.next_followup_date || '');
    $('#editContactModal').modal('show');
}

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

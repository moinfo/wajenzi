@extends('layouts.backend')

@section('css_before')
<style>.datepicker-dropdown { z-index: 1300 !important; }</style>
@endsection

@section('content')
<div class="content">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div>
    @endif

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0 font-w700">Field Marketing</h2>
        <div class="d-flex align-items-center">
            <!-- Month Filter -->
            <form method="GET" action="{{ route('field_marketing.index') }}" class="d-flex align-items-center mr-2" id="monthForm">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="input-group input-group-sm">
                    <input type="month" name="month" class="form-control" value="{{ $month }}" onchange="document.getElementById('monthForm').submit()">
                    @if($month !== now()->format('Y-m'))
                    <div class="input-group-append">
                        <a href="{{ route('field_marketing.index', ['tab' => $tab]) }}" class="btn btn-outline-secondary">
                            <i class="fa fa-times"></i>
                        </a>
                    </div>
                    @endif
                </div>
            </form>
            <!-- Tab-specific action button -->
            @if($tab === 'sessions')
                @can('Add Field Marketing Session')
                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#newSessionModal">
                    <i class="fa fa-plus mr-1"></i> New Session
                </button>
                @endcan
            @elseif($tab === 'targets')
                @can('Set Field Marketing Target')
                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#setTargetModal">
                    <i class="fa fa-bullseye mr-1"></i> Set Target
                </button>
                @endcan
            @elseif($tab === 'services')
                @can('Manage Field Marketing Services')
                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addServiceModal">
                    <i class="fa fa-plus mr-1"></i> Add Service
                </button>
                @endcan
            @endif
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-0 border-bottom-0">
        {{-- Sessions: everyone with FM menu access --}}
        <li class="nav-item">
            <a href="{{ route('field_marketing.index', ['tab' => 'sessions', 'month' => $month]) }}"
               class="nav-link {{ $tab === 'sessions' ? 'active' : '' }}">
                <i class="fa fa-map-marker-alt mr-1"></i> Sessions
            </a>
        </li>
        {{-- Targets: requires Set Field Marketing Target --}}
        @can('Set Field Marketing Target')
        <li class="nav-item">
            <a href="{{ route('field_marketing.index', ['tab' => 'targets', 'month' => $month]) }}"
               class="nav-link {{ $tab === 'targets' ? 'active' : '' }}">
                <i class="fa fa-bullseye mr-1"></i> Targets
            </a>
        </li>
        @endcan
        {{-- Stats: requires View Field Marketing Stats --}}
        @can('View Field Marketing Stats')
        <li class="nav-item">
            <a href="{{ route('field_marketing.index', ['tab' => 'stats', 'month' => $month]) }}"
               class="nav-link {{ $tab === 'stats' ? 'active' : '' }}">
                <i class="fa fa-chart-bar mr-1"></i> Stats
            </a>
        </li>
        @endcan
        {{-- All Visits: everyone with FM menu access --}}
        <li class="nav-item">
            <a href="{{ route('field_marketing.index', ['tab' => 'visits', 'month' => $month]) }}"
               class="nav-link {{ $tab === 'visits' ? 'active' : '' }}">
                <i class="fa fa-list mr-1"></i> All Visits
            </a>
        </li>
        {{-- Services: requires Manage Field Marketing Services --}}
        @can('Manage Field Marketing Services')
        <li class="nav-item">
            <a href="{{ route('field_marketing.index', ['tab' => 'services', 'month' => $month]) }}"
               class="nav-link {{ $tab === 'services' ? 'active' : '' }}">
                <i class="fa fa-tags mr-1"></i> Services
            </a>
        </li>
        @endcan
    </ul>

    <div class="block block-rounded border-top-0" style="border-radius: 0 0 .25rem .25rem">
        <div class="block-content block-content-full">

            {{-- ══════════════════════════════════════════════ --}}
            {{-- TAB: SESSIONS                                  --}}
            {{-- ══════════════════════════════════════════════ --}}
            @if($tab === 'sessions')
            <form method="GET" action="{{ route('field_marketing.index') }}" class="mb-3">
                <input type="hidden" name="tab" value="sessions">
                <input type="hidden" name="month" value="{{ $month }}">
                <div class="row">
                    <div class="col-md-3">
                        <select name="officer_id" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">All Officers</option>
                            @foreach($officers as $officer)
                                <option value="{{ $officer->id }}" {{ request('officer_id') == $officer->id ? 'selected' : '' }}>
                                    {{ $officer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover table-vcenter">
                    <thead class="thead-light">
                        <tr>
                            <th width="40">#</th>
                            <th>Date</th>
                            <th>Officer</th>
                            <th>Area</th>
                            <th class="text-center">Visits</th>
                            <th class="text-center">Interested</th>
                            <th class="text-center">Converted</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sessions as $i => $session)
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td>{{ $session->date->format('Y-m-d') }}</td>
                            <td class="font-w600">{{ $session->officer?->name }}</td>
                            <td>{{ $session->area ?? '—' }}</td>
                            <td class="text-center">{{ $session->visits_count }}</td>
                            <td class="text-center">
                                <span class="badge badge-info px-2">{{ $session->interested_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-success px-2">{{ $session->converted_count }}</span>
                            </td>
                            <td class="text-right text-nowrap">
                                <a href="{{ route('field_marketing.sessions.show', $session->id) }}" class="btn btn-sm btn-alt-primary" title="View"><i class="fa fa-eye"></i></a>
                                @can('Edit Field Marketing Session')
                                <button type="button" class="btn btn-sm btn-alt-secondary" title="Edit"
                                    onclick="openEditSession({{ $session->id }}, '{{ $session->officer_id }}', '{{ $session->area }}', '{{ $session->date->format('Y-m-d') }}', '{{ $session->status }}', {{ json_encode($session->notes) }})">
                                    <i class="fa fa-pencil-alt"></i>
                                </button>
                                @endcan
                                @can('Delete Field Marketing Session')
                                <form action="{{ route('field_marketing.sessions.destroy', $session->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-alt-danger" onclick="return confirm('Delete this session and all its visits?')"><i class="fa fa-trash"></i></button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa fa-map-marker-alt fa-2x mb-2 d-block"></i>
                                No sessions for {{ \Carbon\Carbon::createFromDate($year, $mon, 1)->format('F Y') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ══════════════════════════════════════════════ --}}
            {{-- TAB: TARGETS                                   --}}
            {{-- ══════════════════════════════════════════════ --}}
            @elseif($tab === 'targets')
            @cannot('Set Field Marketing Target')
                <div class="alert alert-warning"><i class="fa fa-lock mr-1"></i> You do not have permission to view Targets.</div>
            @else
            @php $monthLabel = \Carbon\Carbon::createFromDate($year, $mon, 1)->format('M Y'); @endphp

            @if($officers->isEmpty())
                <p class="text-muted">No officers found.</p>
            @else
            <div class="row">
                @foreach($officers as $officer)
                @php
                    $target    = $targets[$officer->id] ?? null;
                    $visits    = $visitCounts[$officer->id] ?? 0;
                    $converted = $convertedCounts[$officer->id] ?? 0;
                    $targetConv  = $target?->target_conversions ?? 0;
                    $progress  = $targetConv > 0 ? min(100, round($converted / $targetConv * 100)) : 0;
                @endphp
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="block block-rounded mb-0 h-100 border">
                        <div class="block-content py-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="font-w700 mb-1">{{ $officer->name }}</p>
                                    <p class="text-muted small mb-2">{{ $monthLabel }}</p>
                                    <p class="mb-1">
                                        Won: <span class="font-w700 text-success">{{ $converted }}</span>
                                        / <span class="text-muted">{{ $targetConv ?: '—' }}</span>
                                        <span class="ml-3 text-muted">{{ $visits }} visits</span>
                                    </p>
                                </div>
                                <button class="btn btn-xs btn-alt-secondary"
                                    onclick="openSetTarget('{{ $officer->id }}', '{{ $officer->name }}', '{{ $month }}', '{{ $target?->target_visits ?? 0 }}', '{{ $target?->target_conversions ?? 0 }}')"
                                    data-toggle="modal" data-target="#setTargetModal">
                                    <i class="fa fa-pencil-alt"></i>
                                </button>
                            </div>
                            <div class="progress mt-2" style="height:5px">
                                <div class="progress-bar bg-{{ $progress >= 100 ? 'success' : 'primary' }}" style="width:{{ $progress }}%"></div>
                            </div>
                            <small class="text-muted">{{ $progress }}%</small>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            @endcannot
            {{-- ══════════════════════════════════════════════ --}}
            {{-- TAB: STATS                                     --}}
            {{-- ══════════════════════════════════════════════ --}}
            @elseif($tab === 'stats')
            @cannot('View Field Marketing Stats')
                <div class="alert alert-warning"><i class="fa fa-lock mr-1"></i> You do not have permission to view Stats.</div>
            @else
            <div class="row mb-4">
                @php
                    $statTiles = [
                        ['label' => 'Total Visits',    'value' => $total,         'class' => 'text-dark'],
                        ['label' => 'Converted',       'value' => $converted,     'class' => 'text-success'],
                        ['label' => 'Interested',      'value' => $interested,    'class' => 'text-info'],
                        ['label' => 'Not Interested',  'value' => $notInterested, 'class' => 'text-danger'],
                        ['label' => 'Follow Up',       'value' => $followUp,      'class' => 'text-warning'],
                    ];
                @endphp
                @foreach($statTiles as $tile)
                <div class="col-6 col-md-3 mb-3">
                    <div class="block block-rounded border text-center mb-0">
                        <div class="block-content py-3">
                            <div class="font-size-h2 font-w700 {{ $tile['class'] }}">{{ $tile['value'] }}</div>
                            <div class="text-muted small">{{ $tile['label'] }}</div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <h5 class="font-w600 mb-3">By Officer</h5>
            <div class="table-responsive">
                <table class="table table-hover table-vcenter">
                    <thead class="thead-light">
                        <tr>
                            <th width="40">#</th>
                            <th>Officer</th>
                            <th class="text-center">Visits</th>
                            <th class="text-center">Clients Won</th>
                            <th>Conversion Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($byOfficer as $i => $row)
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td class="font-w600">{{ $row['officer']?->name }}</td>
                            <td class="text-center">{{ $row['visits'] }}</td>
                            <td class="text-center">
                                <span class="badge badge-success px-2">{{ $row['converted'] }}</span>
                            </td>
                            <td>
                                @php $rate = $row['visits'] > 0 ? round($row['converted'] / $row['visits'] * 100) : 0; @endphp
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 mr-2" style="height:6px">
                                        <div class="progress-bar bg-success" style="width:{{ $rate }}%"></div>
                                    </div>
                                    <span class="text-muted small">{{ $rate }}%</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">No data for this period</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @endcannot
            {{-- ══════════════════════════════════════════════ --}}
            {{-- TAB: ALL VISITS                                --}}
            {{-- ══════════════════════════════════════════════ --}}
            @elseif($tab === 'visits')
            <form method="GET" action="{{ route('field_marketing.index') }}" class="mb-3">
                <input type="hidden" name="tab" value="visits">
                <input type="hidden" name="month" value="{{ $month }}">
                <div class="row">
                    <div class="col-md-2 mb-2">
                        <input type="text" name="from_date" class="form-control form-control-sm datepicker" value="{{ request('from_date') }}" placeholder="From date" autocomplete="off">
                    </div>
                    <div class="col-md-2 mb-2">
                        <input type="text" name="to_date" class="form-control form-control-sm datepicker" value="{{ request('to_date') }}" placeholder="To date" autocomplete="off">
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="officer_id" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">All Officers</option>
                            @foreach($officers as $o)
                                <option value="{{ $o->id }}" {{ request('officer_id') == $o->id ? 'selected' : '' }}>{{ $o->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="interested" {{ request('status') == 'interested' ? 'selected' : '' }}>Interested</option>
                            <option value="not_interested" {{ request('status') == 'not_interested' ? 'selected' : '' }}>Not Interested</option>
                            <option value="follow_up" {{ request('status') == 'follow_up' ? 'selected' : '' }}>Follow Up</option>
                            <option value="converted" {{ request('status') == 'converted' ? 'selected' : '' }}>Converted</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button class="btn btn-secondary btn-sm" type="submit"><i class="fa fa-search mr-1"></i>Filter</button>
                        @if(request()->hasAny(['from_date','to_date','officer_id','status']))
                            <a href="{{ route('field_marketing.index', ['tab'=>'visits','month'=>$month]) }}" class="btn btn-outline-secondary btn-sm ml-1">Clear</a>
                        @endif
                    </div>
                    <div class="col-md-2 mb-2 text-right text-muted small pt-2">{{ $visitCount }} visits</div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover table-vcenter table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th width="40">#</th>
                            <th>Date</th>
                            <th>Officer</th>
                            <th>Area</th>
                            <th>Business</th>
                            <th>Location</th>
                            <th>Phone</th>
                            <th>Services</th>
                            <th>Status</th>
                            <th>Next Follow-up</th>
                            <th>Client</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($allVisits as $i => $visit)
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td class="text-nowrap">{{ $visit->session?->date->format('Y-m-d') }}</td>
                            <td>{{ $visit->session?->officer?->name }}</td>
                            <td class="text-muted">{{ $visit->session?->area ?? '—' }}</td>
                            <td class="font-w600">{{ $visit->business_name }}</td>
                            <td>{{ $visit->location ?? '—' }}</td>
                            <td>{{ $visit->phone ?? '—' }}</td>
                            <td>
                                @foreach($visit->services as $svc)
                                    <span class="badge badge-light border text-primary mr-1 mb-1" style="font-size:10px">{{ $svc->name }}</span>
                                @endforeach
                            </td>
                            <td class="text-nowrap">
                                <span class="badge badge-{{ $visit->status_badge_class }}">{{ $visit->status_label }}</span>
                            </td>
                            <td>{{ $visit->next_followup_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>
                                @if($visit->lead)
                                    <a href="{{ route('leads.show', $visit->lead->id) }}" class="text-primary font-w600">{{ $visit->lead->name }}</a>
                                @else —
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">No visits found for this period</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ══════════════════════════════════════════════ --}}
            {{-- TAB: SERVICES                                  --}}
            {{-- ══════════════════════════════════════════════ --}}
            @elseif($tab === 'services')
            @cannot('Manage Field Marketing Services')
                <div class="alert alert-warning"><i class="fa fa-lock mr-1"></i> You do not have permission to manage Services.</div>
            @else
            <p class="text-muted mb-3">Manage the list of services shown in field visit forms. Changes apply to all officers.</p>

            <div class="table-responsive">
                <table class="table table-hover table-vcenter">
                    <thead class="thead-light">
                        <tr>
                            <th width="40">#</th>
                            <th>Service Name</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($services as $i => $service)
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td>
                                <span class="badge border text-primary px-3 py-1" style="font-size:12px;border-radius:20px">
                                    {{ $service->name }}
                                </span>
                            </td>
                            <td class="text-right">
                                <button class="btn btn-sm btn-alt-secondary"
                                    onclick="openEditService({{ $service->id }}, '{{ addslashes($service->name) }}', '{{ $month }}')"
                                    data-toggle="modal" data-target="#editServiceModal">
                                    <i class="fa fa-pencil-alt"></i>
                                </button>
                                <form action="{{ route('field_marketing.services.destroy', $service->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-alt-danger" onclick="return confirm('Delete service?')"><i class="fa fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center py-4 text-muted">No services yet. Add one above.</td></tr>
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

<!-- New Session Modal -->
<div class="modal fade" id="newSessionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('field_marketing.sessions.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">New Session</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Officer</label>
                        @if($isFieldOfficer)
                            {{-- Field officer sees themselves, no choice --}}
                            <input type="hidden" name="officer_id" value="{{ auth()->id() }}">
                            <input type="text" class="form-control" value="{{ auth()->user()->name }}" disabled>
                        @else
                            {{-- Admin/managers can pick any officer --}}
                            <select name="officer_id" class="form-control" required>
                                <option value="">— Select Officer —</option>
                                @foreach($officers as $o)
                                    <option value="{{ $o->id }}">{{ $o->name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Area</label>
                        <input type="text" name="area" class="form-control" placeholder="e.g. Kimara, General">
                    </div>
                    <div class="form-group">
                        <label>Date <span class="text-danger">*</span></label>
                        <input type="text" name="date" class="form-control datepicker" value="{{ now()->format('Y-m-d') }}" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Session</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Session Modal -->
<div class="modal fade" id="editSessionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editSessionForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-header"><h5 class="modal-title">Edit Session</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Officer <span class="text-danger">*</span></label>
                        <select name="officer_id" id="editOfficerId" class="form-control" required>
                            @foreach($officers as $o)
                                <option value="{{ $o->id }}">{{ $o->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Area</label>
                        <input type="text" name="area" id="editArea" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Date <span class="text-danger">*</span></label>
                        <input type="text" name="date" id="editDate" class="form-control datepicker" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="editStatus" class="form-control">
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" id="editNotes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Set Target Modal -->
<div class="modal fade" id="setTargetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('field_marketing.targets.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Set Monthly Target</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Officer <span class="text-danger">*</span></label>
                        <select name="officer_id" id="targetOfficerId" class="form-control" required>
                            <option value="">— Select Officer —</option>
                            @foreach($officers as $o)
                                <option value="{{ $o->id }}">{{ $o->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Month <span class="text-danger">*</span></label>
                        <input type="month" name="month" id="targetMonth" class="form-control" value="{{ $month }}" required>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Target Visits</label>
                                <input type="number" name="target_visits" id="targetVisits" class="form-control" min="0" value="0">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Target Conversions</label>
                                <input type="number" name="target_conversions" id="targetConversions" class="form-control" min="0" value="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Target</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form action="{{ route('field_marketing.services.store') }}" method="POST">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <div class="modal-header"><h5 class="modal-title">Add Service</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    <div class="form-group mb-0">
                        <label>Service Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. HOSTING">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Service Modal -->
<div class="modal fade" id="editServiceModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form id="editServiceForm" method="POST">
                @csrf @method('PUT')
                <input type="hidden" name="month" id="editServiceMonth" value="{{ $month }}">
                <div class="modal-header"><h5 class="modal-title">Edit Service</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    <div class="form-group mb-0">
                        <label>Service Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editServiceName" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
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

function openEditSession(id, officerId, area, date, status, notes) {
    document.getElementById('editSessionForm').action = '/field-marketing/sessions/' + id;
    document.getElementById('editOfficerId').value = officerId;
    document.getElementById('editArea').value = area;
    document.getElementById('editStatus').value = status;
    document.getElementById('editNotes').value = notes || '';
    $('#editDate').datepicker('update', date);
    $('#editSessionModal').modal('show');
}

function openSetTarget(officerId, officerName, month, targetVisits, targetConversions) {
    document.getElementById('targetOfficerId').value = officerId;
    document.getElementById('targetMonth').value = month;
    document.getElementById('targetVisits').value = targetVisits;
    document.getElementById('targetConversions').value = targetConversions;
}

function openEditService(id, name, month) {
    document.getElementById('editServiceForm').action = '/field-marketing/services/' + id;
    document.getElementById('editServiceName').value = name;
    document.getElementById('editServiceMonth').value = month;
}
</script>
@endsection

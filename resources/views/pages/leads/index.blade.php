@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">Lead Management</h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item">Projects</li>
                    <li class="breadcrumb-item active" aria-current="page">Leads</li>
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

    <!-- Leads List -->
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Leads <small class="text-muted">({{ $leads->total() }} total)</small></h3>
            <div class="block-options">
                <a href="{{ route('leads.create') }}" class="btn btn-success btn-sm">
                    <i class="fa fa-plus mr-1"></i> New Lead
                </a>
            </div>
        </div>
        <div class="block-content block-content-full">
            <!-- Search & Filters -->
            <form method="GET" id="filterForm">
                <div class="row mb-3">
                    <div class="col-md-4 col-lg-3 mb-2">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-search"></i></span>
                            </div>
                            <input type="text" name="search" class="form-control" id="searchInput"
                                   value="{{ request('search') }}"
                                   placeholder="Search name, phone, city...">
                            @if(request('search'))
                            <div class="input-group-append">
                                <a href="{{ route('leads.index', request()->except('search', 'page')) }}" class="btn btn-secondary" title="Clear search">
                                    <i class="fa fa-times"></i>
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="lead_status_id" class="form-control" onchange="document.getElementById('filterForm').submit()">
                            <option value="">All Statuses</option>
                            @foreach($leadStatuses as $status)
                                <option value="{{ $status->id }}" {{ request('lead_status_id') == $status->id ? 'selected' : '' }}>
                                    {{ $status->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="lead_source_id" class="form-control" onchange="document.getElementById('filterForm').submit()">
                            <option value="">All Sources</option>
                            @foreach($leadSources as $source)
                                <option value="{{ $source->id }}" {{ request('lead_source_id') == $source->id ? 'selected' : '' }}>
                                    {{ $source->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="salesperson_id" class="form-control" onchange="document.getElementById('filterForm').submit()">
                            <option value="">All Salespeople</option>
                            @foreach($salespeople as $person)
                                <option value="{{ $person->id }}" {{ request('salesperson_id') == $person->id ? 'selected' : '' }}>
                                    {{ $person->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-lg-1 mb-2">
                        @if(request()->hasAny(['search', 'lead_status_id', 'lead_source_id', 'salesperson_id']))
                            <a href="{{ route('leads.index') }}" class="btn btn-alt-secondary btn-block" title="Clear all filters">
                                <i class="fa fa-undo mr-1"></i> Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-vcenter">
                    <thead>
                        <tr>
                            <th>Lead ID</th>
                            <th>Lead Date</th>
                            <th>Client Name</th>
                            <th>Phone</th>
                            <th>Lead Source</th>
                            <th>Service Interested</th>
                            <th>Site Location</th>
                            <th>Est. Value (TZS)</th>
                            <th>Lead Status</th>
                            <th>City</th>
                            <th>Salesperson</th>
                            <th>Followup Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leads as $lead)
                            <tr>
                                <td><strong>{{ $lead->lead_number }}</strong></td>
                                <td>{{ $lead->lead_date ? $lead->lead_date->format('d-M-Y') : '-' }}</td>
                                <td>{{ $lead->name }}</td>
                                <td>{{ $lead->phone ?: '-' }}</td>
                                <td>
                                    @if($lead->leadSource)
                                        <span class="badge badge-info">{{ $lead->leadSource->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $lead->serviceInterested->name ?? '-' }}</td>
                                <td>{{ $lead->site_location ?: '-' }}</td>
                                <td class="text-right">{{ $lead->estimated_value ? number_format($lead->estimated_value) : '-' }}</td>
                                <td>
                                    @if($lead->leadStatus)
                                        @php
                                            $statusClass = match(strtolower($lead->leadStatus->name)) {
                                                'won' => 'badge-success',
                                                'lost' => 'badge-danger',
                                                'proposal sent' => 'badge-warning',
                                                'new' => 'badge-primary',
                                                default => 'badge-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">{{ $lead->leadStatus->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $lead->city ?: '-' }}</td>
                                <td>{{ $lead->salesperson->name ?? '-' }}</td>
                                <td>
                                    @if($lead->latestFollowup && $lead->latestFollowup->followup_date)
                                        {{ $lead->latestFollowup->followup_date->format('d-M-Y') }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        @if($lead->phone)
                                            <a href="https://wa.me/{{ preg_replace('/\D/', '', $lead->phone) }}" target="_blank"
                                               class="btn btn-sm btn-alt-success" title="Chat on WhatsApp">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>
                                            <a href="tel:{{ $lead->phone }}" class="btn btn-sm btn-alt-secondary" title="Call {{ $lead->phone }}">
                                                <i class="fa fa-phone"></i>
                                            </a>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-alt-info" title="Log Call / Follow-up"
                                                data-action="{{ route('leads.followup.store', $lead->id) }}"
                                                data-lead="{{ $lead->name }}"
                                                onclick="openLogCall(this)">
                                            <i class="fa fa-phone-volume"></i>
                                        </button>
                                        <a href="{{ route('leads.show', $lead->id) }}" class="btn btn-sm btn-alt-secondary" title="View">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="{{ route('leads.edit', $lead->id) }}" class="btn btn-sm btn-alt-secondary" title="Edit">
                                            <i class="fa fa-pencil-alt"></i>
                                        </a>
                                        <form method="POST" action="{{ route('leads.destroy', $lead->id) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-alt-danger" title="Delete" onclick="return confirm('Delete this lead?')">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fa fa-inbox fa-2x mb-2 d-block"></i>
                                        No leads found
                                        @if(request()->hasAny(['search', 'lead_status_id', 'lead_source_id', 'salesperson_id']))
                                            — <a href="{{ route('leads.index') }}">clear filters</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($leads->hasPages())
            <div class="row align-items-center mt-3">
                <div class="col-sm-5 text-muted">
                    Showing {{ $leads->firstItem() }}–{{ $leads->lastItem() }} of {{ $leads->total() }} leads
                </div>
                <div class="col-sm-7">
                    <nav class="d-flex justify-content-end">
                        {{ $leads->appends(request()->query())->links() }}
                    </nav>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Shared Log Call / Follow-up modal — reused for every row; its form action
     is rewritten per lead via openLogCall(). Posts to leads.followup.store so
     logged calls land in the lead's existing Follow-up History. --}}
<div class="modal fade" id="logCallModal" tabindex="-1" aria-labelledby="logCallModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="logCallForm" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="logCallModalLabel">
                        <i class="fa fa-phone-volume text-info mr-1"></i> Log Call / Follow-up
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3" style="font-size:13px;">
                        Lead: <strong id="logCallLeadName"></strong>
                    </p>
                    <div class="form-group mb-3">
                        <label class="font-w600">Call / Follow-up Date <span class="text-danger">*</span></label>
                        <input type="date" name="followup_date" id="logCallDate" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-w600">Call Remarks <span class="text-danger">*</span></label>
                        <input type="text" name="details_discussion" class="form-control"
                               placeholder="What was discussed on the call?" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-w600">Outcome / Result</label>
                        <input type="text" name="outcome" class="form-control"
                               placeholder="e.g. Interested, No answer, Call back later">
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-w600">Next Action</label>
                        <input type="text" name="next_step" class="form-control"
                               placeholder="What should be done next?">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-alt-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fa fa-save mr-1"></i> Save Call Log</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js_after')
<script>
    // Debounced search: submit form after user stops typing for 500ms
    (function() {
        let timer;
        document.getElementById('searchInput').addEventListener('keyup', function(e) {
            clearTimeout(timer);
            if (e.key === 'Enter') {
                document.getElementById('filterForm').submit();
                return;
            }
            timer = setTimeout(function() {
                document.getElementById('filterForm').submit();
            }, 500);
        });
    })();

    // Log Call / Follow-up: point the shared modal's form at the clicked lead,
    // default the date to today, then open it (Bootstrap 5 → jQuery fallback).
    function openLogCall(btn) {
        var form = document.getElementById('logCallForm');
        form.action = btn.dataset.action;
        document.getElementById('logCallLeadName').textContent = btn.dataset.lead || '';
        document.getElementById('logCallDate').value = new Date().toISOString().slice(0, 10);

        var modalEl = document.getElementById('logCallModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl)).show();
        } else if (typeof $ !== 'undefined' && $.fn.modal) {
            $(modalEl).modal('show');
        }
    }
</script>
@endsection
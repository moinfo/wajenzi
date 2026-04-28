@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">
                Session: {{ $session->session_number }}
                <small class="text-muted ml-2">{{ $session->date->format('d M Y') }}</small>
            </h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item">
                        <a href="{{ route('field_marketing.index', ['tab' => 'sessions', 'month' => $session->date->format('Y-m')]) }}">Field Marketing</a>
                    </li>
                    <li class="breadcrumb-item active">{{ $session->session_number }}</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div>
    @endif

    <div class="row">
        <!-- Session info sidebar -->
        <div class="col-md-3">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Session Info</h3>
                </div>
                <div class="block-content">
                    <table class="table table-borderless table-sm">
                        <tr><td class="text-muted font-w500">Officer</td><td class="font-w600">{{ $session->officer?->name }}</td></tr>
                        <tr><td class="text-muted font-w500">Area</td><td>{{ $session->area ?? '—' }}</td></tr>
                        <tr><td class="text-muted font-w500">Date</td><td>{{ $session->date->format('d M Y') }}</td></tr>
                        <tr><td class="text-muted font-w500">Status</td>
                            <td><span class="badge badge-{{ $session->status === 'open' ? 'success' : 'secondary' }}">{{ ucfirst($session->status) }}</span></td>
                        </tr>
                        <tr><td class="text-muted font-w500">Visits</td><td class="font-w700 text-primary">{{ $session->visits->count() }}</td></tr>
                    </table>
                    @if($session->notes)
                        <p class="text-muted mt-2 small">{{ $session->notes }}</p>
                    @endif

                    <!-- Quick stats -->
                    <div class="row text-center mt-3 border-top pt-3">
                        @php
                            $interested   = $session->visits->where('status','interested')->count();
                            $converted    = $session->visits->where('status','converted')->count();
                            $followUp     = $session->visits->where('status','follow_up')->count();
                            $notInterested = $session->visits->where('status','not_interested')->count();
                        @endphp
                        <div class="col-6 mb-2">
                            <div class="font-w700 text-info">{{ $interested }}</div>
                            <div class="text-muted" style="font-size:11px">Interested</div>
                        </div>
                        <div class="col-6 mb-2">
                            <div class="font-w700 text-success">{{ $converted }}</div>
                            <div class="text-muted" style="font-size:11px">Converted</div>
                        </div>
                        <div class="col-6">
                            <div class="font-w700 text-warning">{{ $followUp }}</div>
                            <div class="text-muted" style="font-size:11px">Follow Up</div>
                        </div>
                        <div class="col-6">
                            <div class="font-w700 text-danger">{{ $notInterested }}</div>
                            <div class="text-muted" style="font-size:11px">Not Interested</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visits panel -->
        <div class="col-md-9">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Visits <small class="text-muted">({{ $session->visits->count() }})</small></h3>
                    <div class="block-options">
                        <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addVisitModal">
                            <i class="fa fa-plus mr-1"></i> Add Visit
                        </button>
                    </div>
                </div>
                <div class="block-content block-content-full">
                    @if($session->visits->isEmpty())
                        <p class="text-muted text-center py-4">No visits yet. Click "Add Visit" to log the first contact.</p>
                    @else
                    <div class="table-responsive">
                        <table class="table table-hover table-vcenter table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Business</th>
                                    <th>Location</th>
                                    <th>Phone</th>
                                    <th>Services</th>
                                    <th>Status</th>
                                    <th>Next Follow-up</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($session->visits as $i => $visit)
                                <tr>
                                    <td class="text-muted">{{ $i + 1 }}</td>
                                    <td class="font-w600">{{ $visit->business_name }}</td>
                                    <td>{{ $visit->location ?? '—' }}</td>
                                    <td>{{ $visit->phone ?? '—' }}</td>
                                    <td>
                                        @foreach($visit->services as $svc)
                                            <span class="badge badge-light border text-primary mr-1" style="font-size:10px;border-radius:20px">{{ $svc->name }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $visit->status_badge_class }}">{{ $visit->status_label }}</span>
                                    </td>
                                    <td>{{ $visit->next_followup_date?->format('Y-m-d') ?? '—' }}</td>
                                    <td class="text-right text-nowrap">
                                        <button class="btn btn-xs btn-alt-secondary"
                                            onclick="openEditVisit({{ $visit->id }}, {{ json_encode($visit->business_name) }}, {{ json_encode($visit->location) }}, {{ json_encode($visit->phone) }}, '{{ $visit->status }}', {{ json_encode($visit->next_followup_date?->format('Y-m-d')) }}, {{ json_encode($visit->notes) }}, [{{ $visit->services->pluck('id')->implode(',') }}])"
                                            data-toggle="modal" data-target="#editVisitModal">
                                            <i class="fa fa-pencil-alt"></i>
                                        </button>
                                        <form action="{{ route('field_marketing.visits.destroy', $visit->id) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-alt-danger" onclick="return confirm('Delete this visit?')"><i class="fa fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Visit Modal --}}
<div class="modal fade" id="addVisitModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('field_marketing.visits.store', $session->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-plus mr-1"></i> Add Visit</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Business / Prospect Name <span class="text-danger">*</span></label>
                                <input type="text" name="business_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Location</label>
                                <input type="text" name="location" class="form-control" placeholder="e.g. MD">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="d-block mb-2">Services Pitched</label>
                                @if($services->isEmpty())
                                    <p class="text-muted small">No services configured. Add them in the <a href="{{ route('field_marketing.index', ['tab' => 'services']) }}" target="_blank">Services tab</a>.</p>
                                @else
                                {{-- data-toggle="buttons" makes Bootstrap 4 handle active state automatically --}}
                                <div class="btn-group flex-wrap" data-toggle="buttons" id="addServicesContainer" style="gap:6px">
                                    @foreach($services as $svc)
                                    <label class="btn btn-sm btn-outline-primary m-1" style="border-radius:20px">
                                        <input type="checkbox" name="service_ids[]" value="{{ $svc->id }}"
                                               class="add-svc-cb" data-id="{{ $svc->id }}" autocomplete="off">
                                        {{ $svc->name }}
                                    </label>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-control" id="addVisitStatus" required onchange="toggleFollowupDate('addFollowupDate', this)">
                                    <option value="follow_up">Follow Up</option>
                                    <option value="interested">Interested</option>
                                    <option value="not_interested">Not Interested</option>
                                    <option value="converted">Converted</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4" id="addFollowupDate">
                            <div class="form-group">
                                <label>Next Follow-up Date</label>
                                <input type="text" name="next_followup_date" class="form-control datepicker" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Visit</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Visit Modal --}}
<div class="modal fade" id="editVisitModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editVisitForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-pencil-alt mr-1"></i> Edit Visit</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Business / Prospect Name <span class="text-danger">*</span></label>
                                <input type="text" name="business_name" id="evBusiness" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Location</label>
                                <input type="text" name="location" id="evLocation" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" id="evPhone" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="d-block mb-2">Services Pitched</label>
                                <div class="btn-group flex-wrap" data-toggle="buttons" id="evServicesContainer">
                                    @foreach($services as $svc)
                                    <label class="btn btn-sm btn-outline-primary m-1" style="border-radius:20px">
                                        <input type="checkbox" name="service_ids[]" value="{{ $svc->id }}"
                                               class="ev-svc-cb" data-id="{{ $svc->id }}" autocomplete="off">
                                        {{ $svc->name }}
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Status <span class="text-danger">*</span></label>
                                <select name="status" id="evStatus" class="form-control" required onchange="toggleFollowupDate('editFollowupDate', this)">
                                    <option value="follow_up">Follow Up</option>
                                    <option value="interested">Interested</option>
                                    <option value="not_interested">Not Interested</option>
                                    <option value="converted">Converted</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4" id="editFollowupDate">
                            <div class="form-group">
                                <label>Next Follow-up Date</label>
                                <input type="text" name="next_followup_date" id="evFollowup" class="form-control datepicker" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea name="notes" id="evNotes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
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
@endsection

@section('js_after')
<script>
$('.datepicker').datepicker({ format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true });

function toggleFollowupDate(containerId, select) {
    document.getElementById(containerId).style.display = select.value === 'follow_up' ? '' : 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    toggleFollowupDate('addFollowupDate', document.getElementById('addVisitStatus'));
});

function openEditVisit(id, business, location, phone, status, followup, notes, serviceIds) {
    document.getElementById('editVisitForm').action = '/field-marketing/visits/' + id;
    document.getElementById('evBusiness').value = business || '';
    document.getElementById('evLocation').value = location || '';
    document.getElementById('evPhone').value    = phone    || '';
    document.getElementById('evStatus').value   = status;
    $('#evFollowup').datepicker('update', followup || '');
    document.getElementById('evNotes').value    = notes    || '';

    // Restore service pill state — Bootstrap 4 btn-group uses `active` class on label
    document.querySelectorAll('#evServicesContainer .ev-svc-cb').forEach(function(cb) {
        var selected = serviceIds.indexOf(parseInt(cb.dataset.id)) !== -1;
        cb.checked = selected;
        var label = cb.closest('label');
        if (label) label.classList.toggle('active', selected);
    });

    toggleFollowupDate('editFollowupDate', document.getElementById('evStatus'));
}
</script>
@endsection

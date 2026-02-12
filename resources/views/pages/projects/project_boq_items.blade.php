{{-- project_boq_items.blade.php - Hierarchical BOQ Items View --}}
@extends('layouts.backend')

@section('content')
    @php $boqApproved = strtolower($boq->approvalStatus?->status ?? '') === 'approved'; @endphp
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">
                BOQ Items for {{ $boq->project->project_name }}
                <small class="text-muted">v{{ $boq->version }} ({{ ucfirst($boq->type) }})</small>
                @if($boqApproved)
                    <span class="badge badge-success" style="font-size: 12px; vertical-align: middle; margin-left: 8px;">APPROVED</span>
                @endif
                <div class="float-right">
                    @if(!$boqApproved)
                        @can('Add BOQ Item')
                            <button type="button"
                                onclick="loadFormModal('boq_section_form', {className: 'ProjectBoqSection', boq_id: {{ $boq->id }}}, 'Add Section', 'modal-md');"
                                class="btn btn-rounded min-width-125 mb-10 btn-alt-info">
                                <i class="si si-layers">&nbsp;</i>Add Section
                            </button>
                            <button type="button"
                                onclick="loadFormModal('project_boq_item_form', {className: 'ProjectBoqItem', boq_id: {{ $boq->id }}}, 'Add BOQ Item', 'modal-md');"
                                class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                                <i class="si si-plus">&nbsp;</i>Add Item
                            </button>
                        @endcan
                    @endif
                    <a href="{{ route('project_boq.pdf', $boq->id) }}" class="btn btn-rounded min-width-125 mb-10 btn-alt-danger" target="_blank">
                        <i class="si si-doc">&nbsp;</i>PDF
                    </a>
                    <a href="{{ route('project_boq.csv', $boq->id) }}" class="btn btn-rounded min-width-125 mb-10 btn-alt-success">
                        <i class="si si-cloud-download">&nbsp;</i>Export CSV
                    </a>
                    @if(!$boqApproved)
                        <button type="button" class="btn btn-rounded min-width-125 mb-10 btn-alt-warning"
                            onclick="$('#csv-import-modal').modal('show');">
                            <i class="si si-cloud-upload">&nbsp;</i>Import CSV
                        </button>
                        <div class="btn-group mb-10">
                            <button type="button" class="btn btn-rounded btn-alt-secondary dropdown-toggle" data-toggle="dropdown">
                                <i class="si si-layers">&nbsp;</i>Templates
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" href="javascript:void(0)"
                                    onclick="loadFormModal('boq_save_template_form', {className: 'ProjectBoq', boq_id: {{ $boq->id }}, id: {{ $boq->id }}}, 'Save BOQ as Template', 'modal-md');">
                                    <i class="si si-layers text-info">&nbsp;</i>Save as Template
                                </a>
                                <a class="dropdown-item" href="javascript:void(0)"
                                    onclick="loadFormModal('boq_apply_template_form', {className: 'ProjectBoq', boq_id: {{ $boq->id }}, id: {{ $boq->id }}}, 'Apply Template to BOQ', 'modal-md');">
                                    <i class="si si-cloud-upload text-primary">&nbsp;</i>Apply Template
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('project_boq_templates') }}">
                                    <i class="si si-folder text-muted">&nbsp;</i>View All Templates
                                </a>
                            </div>
                        </div>
                    @endif
                    <button type="button" class="btn btn-rounded mb-10 btn-alt-warning" data-toggle="collapse" data-target="#pending-requests-panel">
                        <i class="fa fa-clock">&nbsp;</i>Pending Requests
                        @if($pendingRequests->count() > 0)
                            <span class="badge badge-danger" style="margin-left: 4px;">{{ $pendingRequests->count() }}</span>
                        @endif
                    </button>
                </div>
            </div>

            {{-- Pending Material Requests Panel --}}
            <div class="collapse" id="pending-requests-panel">
                <div class="block block-rounded mb-3" style="border-left: 3px solid #f0ad4e;">
                    <div class="block-header block-header-default" style="background: #fff8ed;">
                        <h3 class="block-title" style="font-size: 13px;">
                            <i class="fa fa-clock text-warning"></i>&nbsp;Material Requests Pending Approval
                        </h3>
                    </div>
                    <div class="block-content p-0">
                        @if($pendingRequests->count() > 0)
                        <table class="table table-sm table-hover mb-0" style="font-size: 12px;">
                            <thead>
                                <tr style="background: #fafafa;">
                                    <th style="padding: 6px 10px;">Request No.</th>
                                    <th style="padding: 6px 10px;">Items</th>
                                    <th style="padding: 6px 10px;">Priority</th>
                                    <th style="padding: 6px 10px;">Requester</th>
                                    <th style="padding: 6px 10px;">Date</th>
                                    <th style="padding: 6px 10px;">Approvals</th>
                                    <th class="text-center" style="padding: 6px 10px; width: 60px;">View</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingRequests as $req)
                                    <tr>
                                        <td style="padding: 5px 10px;" class="font-w600">{{ $req->request_number }}</td>
                                        <td style="padding: 5px 10px;">
                                            @foreach($req->items as $rItem)
                                                <div style="font-size: 11px;">
                                                    {{ $rItem->boqItem->item_code ?? '-' }} — {{ Str::limit($rItem->boqItem->description ?? $rItem->description ?? '', 25) }}
                                                    <span class="text-muted">({{ number_format($rItem->quantity_requested, 1) }} {{ $rItem->unit }})</span>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td style="padding: 5px 10px;">
                                            @php $pColors = ['low'=>'secondary','medium'=>'info','high'=>'warning','urgent'=>'danger']; @endphp
                                            <span class="badge badge-{{ $pColors[$req->priority] ?? 'secondary' }}">{{ ucfirst($req->priority) }}</span>
                                        </td>
                                        <td style="padding: 5px 10px;">{{ $req->requester->name ?? '-' }}</td>
                                        <td style="padding: 5px 10px;">{{ $req->created_at ? $req->created_at->format('d M') : '-' }}</td>
                                        <td style="padding: 5px 10px;">
                                            <x-ringlesoft-approval-status-summary :model="$req" />
                                        </td>
                                        <td style="padding: 5px 10px;" class="text-center">
                                            <a href="{{ route('project_material_request', ['id' => $req->id, 'document_type_id' => 0]) }}" class="btn btn-xs btn-success" title="View / Approve">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        <div class="p-3 text-center text-muted" style="font-size: 13px;">
                            No pending material requests for this project.
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            Bill of Quantities
                            @if($boq->rootSections->count() > 0)
                                <small class="text-muted">({{ $boq->rootSections->count() }} sections)</small>
                            @endif
                        </h3>
                    </div>
                    <div class="block-content block-content-full">
                        <div class="table-responsive">
                            <table class="table table-bordered table-vcenter table-sm" id="boq-items-table" style="font-size: 12px;">
                                <thead>
                                    <tr style="background-color: #4a9ad4; color: #fff;">
                                        <th class="text-center" style="width: 35px; padding: 6px;">
                                            @can('Add Material Request')
                                                <input type="checkbox" id="select-all-materials" title="Select all material items">
                                            @endcan
                                        </th>
                                        <th class="text-center" style="width: 90px; padding: 6px;">Item Code</th>
                                        <th style="padding: 6px;">Description</th>
                                        <th class="text-right" style="width: 80px; padding: 6px;">Qty</th>
                                        <th class="text-center" style="width: 80px; padding: 6px;">Requested</th>
                                        <th style="width: 60px; padding: 6px;">Unit</th>
                                        <th class="text-right" style="width: 110px; padding: 6px;">Unit Price</th>
                                        <th class="text-right" style="width: 120px; padding: 6px;">Total</th>
                                        <th class="text-center" style="width: 110px; padding: 6px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Render hierarchical sections --}}
                                    @foreach($boq->rootSections as $section)
                                        @include('partials.boq_section_rows', ['section' => $section, 'depth' => 0, 'boqApproved' => $boqApproved])
                                    @endforeach

                                    {{-- Render unsectioned items (backward compatibility) --}}
                                    @if($boq->unsectionedItems->count() > 0)
                                        @if($boq->rootSections->count() > 0)
                                            <tr style="background-color: rgba(108, 117, 125, 0.1);">
                                                <td colspan="9" style="padding: 5px 8px; font-weight: bold;">Unsectioned Items</td>
                                            </tr>
                                        @endif
                                        @foreach($boq->unsectionedItems as $item)
                                            <tr id="boq-item-tr-{{ $item->id }}">
                                                <td class="text-center" style="padding: 4px 6px;">
                                                    @if($item->item_type == 'material')
                                                        @can('Add Material Request')
                                                            @if(in_array($item->id, $pendingBoqItemIds ?? []))
                                                                <span class="badge badge-warning" style="font-size: 8px; padding: 2px 4px;" title="Has pending request">PENDING</span>
                                                            @else
                                                                <input type="checkbox" class="boq-item-checkbox" value="{{ $item->id }}"
                                                                    data-code="{{ $item->item_code }}"
                                                                    data-description="{{ $item->description }}"
                                                                    data-qty="{{ $item->quantity }}"
                                                                    data-requested="{{ $item->quantity_requested ?? 0 }}"
                                                                    data-available="{{ $item->quantity_remaining }}"
                                                                    data-unit="{{ $item->unit }}">
                                                            @endif
                                                        @endcan
                                                    @endif
                                                </td>
                                                <td class="text-center" style="padding: 4px 6px; font-size: 11px; color: #888;">{{ $item->item_code }}</td>
                                                <td style="padding: 4px 8px;">
                                                    {{ $item->description }}
                                                    @if($item->specification) <small class="text-muted">({{ $item->specification }})</small> @endif
                                                    @if($item->item_type == 'labour')
                                                        <span class="badge badge-warning" style="font-size: 9px; padding: 2px 5px; vertical-align: middle;">LABOUR</span>
                                                    @endif
                                                </td>
                                                <td class="text-right" style="padding: 4px 6px;">{{ number_format($item->quantity, 2) }}</td>
                                                <td class="text-center" style="padding: 4px 6px;">
                                                    @if($item->item_type == 'material')
                                                        @php
                                                            $reqQty = $item->quantity_requested ?? 0;
                                                            $totalQty = $item->quantity;
                                                            $pct = $totalQty > 0 ? ($reqQty / $totalQty) * 100 : 0;
                                                            $color = $pct >= 100 ? '#28a745' : ($pct > 0 ? '#f0ad4e' : '#adb5bd');
                                                        @endphp
                                                        <span style="color: {{ $color }}; font-size: 11px; font-weight: 500;" title="{{ number_format($reqQty, 2) }} of {{ number_format($totalQty, 2) }} requested">
                                                            {{ number_format($reqQty, 1) }}/{{ number_format($totalQty, 1) }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td style="padding: 4px 6px;">{{ $item->unit }}</td>
                                                <td class="text-right" style="padding: 4px 6px;">{{ number_format($item->unit_price, 2) }}</td>
                                                <td class="text-right" style="padding: 4px 6px; font-weight: 500;">{{ number_format($item->total_price, 2) }}</td>
                                                <td class="text-center" style="padding: 3px;">
                                                    @if(!$boqApproved)
                                                    <div class="btn-group btn-group-xs">
                                                        @can('Edit BOQ Item')
                                                            <button type="button"
                                                                onclick="loadFormModal('project_boq_item_form', {className: 'ProjectBoqItem', id: {{ $item->id }}}, 'Edit Item', 'modal-md');"
                                                                class="btn btn-xs btn-primary" title="Edit">
                                                                <i class="fa fa-pencil"></i>
                                                            </button>
                                                        @endcan
                                                        @can('Delete BOQ Item')
                                                            <button type="button"
                                                                onclick="deleteModelItem('ProjectBoqItem', {{ $item->id }}, 'boq-item-tr-{{ $item->id }}');"
                                                                class="btn btn-xs btn-danger" title="Delete">
                                                                <i class="fa fa-times"></i>
                                                            </button>
                                                        @endcan
                                                    </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                                <tfoot>
                                    <tr style="background-color: #333; color: #fff; font-weight: bold;">
                                        <td></td>
                                        <td colspan="6" class="text-right" style="padding: 8px;">GRAND TOTAL:</td>
                                        <td class="text-right" style="padding: 8px; font-size: 13px;">{{ number_format($boq->total_amount, 2) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Approval Flow Card --}}
    <div class="block" style="border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); border: 1px solid rgba(0,0,0,0.05); overflow: hidden;">
        <div class="block-header block-header-default" style="background-color: #f8f9fa; border-bottom: 1px solid #e9ecef;">
            <h3 class="block-title" style="color: #0066cc; font-weight: 600;">
                <i class="fas fa-tasks" style="margin-right: 8px;"></i> Approval Flow
            </h3>
            <div class="block-options">
                @php $approvalStatus = $boq->approvalStatus?->status ?? 'Pending'; @endphp
                <span class="badge badge-{{ $approvalStatus === 'Approved' ? 'success' : ($approvalStatus === 'Rejected' ? 'danger' : 'info') }}" style="font-size: 0.9em; padding: 6px 12px;">
                    {{ $approvalStatus }}
                </span>
            </div>
        </div>
        <div class="block-content" style="padding: 20px;">
            <x-ringlesoft-approval-actions :model="$boq" />
        </div>
    </div>

    {{-- Floating "Request Selected" button --}}
    @can('Add Material Request')
    <div id="bulk-request-bar" style="display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 9999;
        background: #fff; border: 2px solid #f0ad4e; border-radius: 50px; padding: 10px 30px; box-shadow: 0 4px 24px rgba(0,0,0,0.25);
        text-align: center; white-space: nowrap;">
        <button type="button" id="btn-request-selected" class="btn btn-warning" onclick="openBulkRequestModal()" style="border-radius: 25px; font-weight: 600;">
            <i class="fa fa-shopping-cart"></i>&nbsp;
            Request Selected (<span id="selected-count">0</span>)
        </button>
        <button type="button" class="btn btn-outline-secondary ml-2" onclick="clearSelection()" style="border-radius: 25px;">Clear</button>
    </div>
    @endcan

    {{-- Bulk Material Request Modal --}}
    <div class="modal fade" id="bulk-request-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ route('project_material_request.bulk', ['project_id' => $boq->project_id]) }}" id="bulk-request-form">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $boq->project_id }}">
                    <div class="block block-themed block-transparent mb-0">
                        <div class="block-header bg-warning">
                            <h3 class="block-title">Bulk Material Request</h3>
                            <div class="block-options">
                                <button type="button" class="btn-block-option" data-dismiss="modal"><i class="si si-close"></i></button>
                            </div>
                        </div>
                        <div class="block-content">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered" style="font-size: 12px;">
                                    <thead>
                                        <tr style="background: #f8f9fa;">
                                            <th style="padding: 6px;">Item Code</th>
                                            <th style="padding: 6px;">Description</th>
                                            <th class="text-right" style="padding: 6px;">BOQ Qty</th>
                                            <th class="text-right" style="padding: 6px;">Available</th>
                                            <th style="padding: 6px; width: 130px;">Request Qty</th>
                                            <th style="padding: 6px; width: 70px;">Unit</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bulk-items-body">
                                        {{-- Populated by JS --}}
                                    </tbody>
                                </table>
                            </div>

                            <hr>
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label class="control-label required">Required Date</label>
                                        <input type="text" class="form-control datepicker" name="required_date"
                                            value="{{ date('Y-m-d', strtotime('+7 days')) }}" required>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label class="control-label required">Priority</label>
                                        <select name="priority" class="form-control" required>
                                            <option value="low">Low</option>
                                            <option value="medium" selected>Medium</option>
                                            <option value="high">High</option>
                                            <option value="urgent">Urgent</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label class="control-label">Purpose <small class="text-muted">(optional)</small></label>
                                        <input type="text" class="form-control" name="purpose" placeholder="Brief reason">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="block-content block-content-full text-right border-top">
                            <button type="button" class="btn btn-alt-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning">
                                <i class="fa fa-shopping-cart">&nbsp;</i>Submit Request
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- CSV Import Modal --}}
    <div class="modal fade" id="csv-import-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <form action="{{ route('project_boq.import_csv', $boq->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="block block-themed block-transparent mb-0">
                        <div class="block-header bg-primary">
                            <h3 class="block-title">Import CSV</h3>
                            <div class="block-options">
                                <button type="button" class="btn-block-option" data-dismiss="modal"><i class="si si-close"></i></button>
                            </div>
                        </div>
                        <div class="block-content">
                            <div class="alert alert-warning" style="font-size: 12px;">
                                <strong>Warning:</strong> This will <strong>replace all existing sections & items</strong> in this BOQ with the contents of the CSV file.
                            </div>
                            <div class="form-group">
                                <label class="control-label">CSV File</label>
                                <input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required>
                                <small class="text-muted">
                                    Use the <strong>Export CSV</strong> button to get the correct format.
                                    Columns: Section, Description, Type, Specification, Unit, Qty, Unit Price
                                </small>
                            </div>
                        </div>
                        <div class="block-content block-content-full text-right border-top">
                            <button type="button" class="btn btn-alt-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-alt-warning">
                                <i class="si si-cloud-upload">&nbsp;</i>Import & Replace
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('js_after')
<script>
    $(document).ready(function() {
        // Track selected items and update floating bar
        $(document).on('change', '.boq-item-checkbox', function() {
            updateSelectionBar();
        });

        $('#select-all-materials').on('change', function() {
            var checked = $(this).is(':checked');
            $('.boq-item-checkbox').prop('checked', checked);
            updateSelectionBar();
        });
    });

    function updateSelectionBar() {
        var count = $('.boq-item-checkbox:checked').length;
        $('#selected-count').text(count);
        if (count > 0) {
            $('#bulk-request-bar').fadeIn(200);
        } else {
            $('#bulk-request-bar').fadeOut(200);
            $('#select-all-materials').prop('checked', false);
        }
    }

    function clearSelection() {
        $('.boq-item-checkbox').prop('checked', false);
        $('#select-all-materials').prop('checked', false);
        updateSelectionBar();
    }

    function openBulkRequestModal() {
        var $body = $('#bulk-items-body');
        $body.empty();

        $('.boq-item-checkbox:checked').each(function(i) {
            var $cb = $(this);
            var id = $cb.val();
            var code = $cb.data('code');
            var desc = $cb.data('description');
            var qty = parseFloat($cb.data('qty'));
            var available = parseFloat($cb.data('available'));
            var unit = $cb.data('unit');

            $body.append(
                '<tr>' +
                    '<td style="padding:5px 6px;">' + code + '</td>' +
                    '<td style="padding:5px 6px;">' + desc + '</td>' +
                    '<td class="text-right" style="padding:5px 6px;">' + qty.toFixed(2) + '</td>' +
                    '<td class="text-right" style="padding:5px 6px;">' + available.toFixed(2) + '</td>' +
                    '<td style="padding:5px 6px;">' +
                        '<input type="hidden" name="items[' + i + '][boq_item_id]" value="' + id + '">' +
                        '<input type="hidden" name="items[' + i + '][unit]" value="' + unit + '">' +
                        '<input type="number" step="0.01" min="0.01" max="' + available.toFixed(2) + '" ' +
                            'name="items[' + i + '][quantity_requested]" class="form-control form-control-sm" ' +
                            'value="' + available.toFixed(2) + '" required style="font-size:12px;">' +
                    '</td>' +
                    '<td style="padding:5px 6px;">' + unit + '</td>' +
                '</tr>'
            );
        });

        $('#bulk-request-modal').modal('show');

        // Init datepicker in modal
        $('#bulk-request-modal .datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });
    }
</script>
@endsection

{{-- project_boq_items.blade.php - Hierarchical BOQ Items View --}}
@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">
                BOQ Items for {{ $boq->project->project_name }}
                <small class="text-muted">v{{ $boq->version }} ({{ ucfirst($boq->type) }})</small>
                <div class="float-right">
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
                    <a href="{{ route('project_boq.pdf', $boq->id) }}" class="btn btn-rounded min-width-125 mb-10 btn-alt-danger" target="_blank">
                        <i class="si si-doc">&nbsp;</i>PDF
                    </a>
                    <a href="{{ route('project_boq.csv', $boq->id) }}" class="btn btn-rounded min-width-125 mb-10 btn-alt-success">
                        <i class="si si-cloud-download">&nbsp;</i>Export CSV
                    </a>
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
                            <table class="table table-bordered table-vcenter table-sm" style="font-size: 12px;">
                                <thead>
                                    <tr style="background-color: #4a9ad4; color: #fff;">
                                        <th class="text-center" style="width: 90px; padding: 6px;">Item Code</th>
                                        <th style="padding: 6px;">Description</th>
                                        <th class="text-right" style="width: 80px; padding: 6px;">Qty</th>
                                        <th style="width: 60px; padding: 6px;">Unit</th>
                                        <th class="text-right" style="width: 110px; padding: 6px;">Unit Price</th>
                                        <th class="text-right" style="width: 120px; padding: 6px;">Total</th>
                                        <th class="text-center" style="width: 90px; padding: 6px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Render hierarchical sections --}}
                                    @foreach($boq->rootSections as $section)
                                        @include('partials.boq_section_rows', ['section' => $section, 'depth' => 0])
                                    @endforeach

                                    {{-- Render unsectioned items (backward compatibility) --}}
                                    @if($boq->unsectionedItems->count() > 0)
                                        @if($boq->rootSections->count() > 0)
                                            <tr style="background-color: rgba(108, 117, 125, 0.1);">
                                                <td colspan="7" style="padding: 5px 8px; font-weight: bold;">Unsectioned Items</td>
                                            </tr>
                                        @endif
                                        @foreach($boq->unsectionedItems as $item)
                                            <tr id="boq-item-tr-{{ $item->id }}">
                                                <td class="text-center" style="padding: 4px 6px; font-size: 11px; color: #888;">{{ $item->item_code }}</td>
                                                <td style="padding: 4px 8px;">
                                                    {{ $item->description }}
                                                    @if($item->specification) <small class="text-muted">({{ $item->specification }})</small> @endif
                                                    @if($item->item_type == 'labour')
                                                        <span class="badge badge-warning" style="font-size: 9px; padding: 2px 5px; vertical-align: middle;">LABOUR</span>
                                                    @endif
                                                </td>
                                                <td class="text-right" style="padding: 4px 6px;">{{ number_format($item->quantity, 2) }}</td>
                                                <td style="padding: 4px 6px;">{{ $item->unit }}</td>
                                                <td class="text-right" style="padding: 4px 6px;">{{ number_format($item->unit_price, 2) }}</td>
                                                <td class="text-right" style="padding: 4px 6px; font-weight: 500;">{{ number_format($item->total_price, 2) }}</td>
                                                <td class="text-center" style="padding: 3px;">
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
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                                <tfoot>
                                    <tr style="background-color: #333; color: #fff; font-weight: bold;">
                                        <td colspan="5" class="text-right" style="padding: 8px;">GRAND TOTAL:</td>
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
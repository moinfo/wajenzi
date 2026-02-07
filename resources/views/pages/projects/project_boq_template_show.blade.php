{{-- project_boq_template_show.blade.php - Template Detail View --}}
@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">
                {{ $template->name }}
                <small class="text-muted">({{ ucfirst($template->type) }} Template)</small>
                <div class="float-right">
                    <a href="{{ route('project_boq_template.csv', $template->id) }}" class="btn btn-rounded min-width-125 mb-10 btn-alt-success">
                        <i class="si si-cloud-download">&nbsp;</i>Export CSV
                    </a>
                    <button type="button" class="btn btn-rounded min-width-125 mb-10 btn-alt-warning"
                        onclick="$('#csv-import-modal').modal('show');">
                        <i class="si si-cloud-upload">&nbsp;</i>Import CSV
                    </button>
                    <a href="{{ route('project_boq_templates') }}" class="btn btn-rounded min-width-125 mb-10 btn-alt-secondary">
                        <i class="si si-arrow-left">&nbsp;</i>Back to Templates
                    </a>
                </div>
            </div>

            {{-- Template info card --}}
            <div class="block">
                <div class="block-content block-content-full">
                    <div class="row" style="font-size: 12px;">
                        <div class="col-md-3">
                            <strong>Source Project:</strong>
                            @if($template->sourceBoq && $template->sourceBoq->project)
                                {{ $template->sourceBoq->project->project_name }} v{{ $template->sourceBoq->version }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                        <div class="col-md-2">
                            <strong>Sections:</strong> {{ $template->sections()->count() }}
                        </div>
                        <div class="col-md-2">
                            <strong>Items:</strong> {{ $template->items()->count() }}
                        </div>
                        <div class="col-md-3">
                            <strong>Total:</strong> TZS {{ number_format($template->total_amount, 2) }}
                        </div>
                        <div class="col-md-2">
                            <strong>Created:</strong> {{ $template->created_at->format('d/m/Y') }}
                            @if($template->creator)
                                <br><small class="text-muted">by {{ $template->creator->name }}</small>
                            @endif
                        </div>
                    </div>
                    @if($template->description)
                        <div class="mt-5" style="font-size: 12px;">
                            <strong>Description:</strong> {{ $template->description }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Items table --}}
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        Template Items
                        @if($template->rootSections->count() > 0)
                            <small class="text-muted">({{ $template->rootSections->count() }} sections)</small>
                        @endif
                    </h3>
                </div>
                <div class="block-content block-content-full">
                    <div class="table-responsive">
                        <table class="table table-bordered table-vcenter table-sm" style="font-size: 12px;">
                            <thead>
                                <tr style="background-color: #4a9ad4; color: #fff;">
                                    <th class="text-center" style="width: 50px; padding: 6px;">S/N</th>
                                    <th style="padding: 6px;">Description</th>
                                    <th style="width: 60px; padding: 6px;">Unit</th>
                                    <th class="text-right" style="width: 80px; padding: 6px;">Qty</th>
                                    <th class="text-right" style="width: 110px; padding: 6px;">Unit Price</th>
                                    <th class="text-right" style="width: 120px; padding: 6px;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $counter = 0; @endphp

                                {{-- Render hierarchical sections --}}
                                @foreach($template->rootSections as $section)
                                    @include('partials.boq_template_section_rows', ['section' => $section, 'depth' => 0])
                                @endforeach

                                {{-- Unsectioned items --}}
                                @if($template->unsectionedItems->count() > 0)
                                    @if($template->rootSections->count() > 0)
                                        <tr style="background-color: rgba(108, 117, 125, 0.1);">
                                            <td colspan="6" style="padding: 5px 8px; font-weight: bold;">Other Items</td>
                                        </tr>
                                    @endif
                                    @foreach($template->unsectionedItems as $item)
                                        @php $counter++; @endphp
                                        <tr @if($item->item_type == 'labour') style="background-color: #fefce8;" @endif>
                                            <td class="text-center" style="padding: 4px 6px; font-size: 11px; color: #888;">{{ $counter }}</td>
                                            <td style="padding: 4px 8px;">
                                                {{ $item->description }}
                                                @if($item->specification) <small class="text-muted">({{ $item->specification }})</small> @endif
                                                @if($item->item_type == 'labour')
                                                    <span class="badge badge-warning" style="font-size: 9px; padding: 2px 5px; vertical-align: middle;">LABOUR</span>
                                                @endif
                                            </td>
                                            <td style="padding: 4px 6px;">{{ $item->unit }}</td>
                                            <td class="text-right" style="padding: 4px 6px;">{{ number_format($item->quantity, 2) }}</td>
                                            <td class="text-right" style="padding: 4px 6px;">{{ number_format($item->unit_price, 2) }}</td>
                                            <td class="text-right" style="padding: 4px 6px; font-weight: 500;">{{ number_format($item->total_price, 2) }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                            <tfoot>
                                <tr style="background-color: #333; color: #fff; font-weight: bold;">
                                    <td colspan="5" class="text-right" style="padding: 8px;">GRAND TOTAL (TZS):</td>
                                    <td class="text-right" style="padding: 8px; font-size: 13px;">{{ number_format($template->total_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Summary --}}
                    @php
                        $materialTotal = $template->items()->where('item_type', 'material')->sum('total_price');
                        $labourTotal = $template->items()->where('item_type', 'labour')->sum('total_price');
                    @endphp
                    <div class="row mt-10">
                        <div class="col-md-4 ml-auto">
                            <table class="table table-sm table-bordered" style="font-size: 12px;">
                                <tr>
                                    <td><strong>Total Materials</strong></td>
                                    <td class="text-right">TZS {{ number_format($materialTotal, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Total Labour</strong></td>
                                    <td class="text-right">TZS {{ number_format($labourTotal, 2) }}</td>
                                </tr>
                                <tr style="background-color: #333; color: #fff;">
                                    <td><strong>Grand Total</strong></td>
                                    <td class="text-right"><strong>TZS {{ number_format($template->total_amount, 2) }}</strong></td>
                                </tr>
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
                <form action="{{ route('project_boq_template.import_csv', $template->id) }}" method="POST" enctype="multipart/form-data">
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
                                <strong>Warning:</strong> This will <strong>replace all existing sections & items</strong> in this template with the contents of the CSV file.
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

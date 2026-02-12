@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">Supplier Receivings
            <div class="float-right">
                <a href="{{ route('procurement_dashboard') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="si si-graph"></i> Dashboard
                </a>
            </div>
        </div>

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">All Receivings</h3>
            </div>
            <div class="block-content">
                <form method="post" id="filter-form" autocomplete="off">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text">Start Date</span>
                                <input type="text" name="start_date" class="form-control datepicker"
                                    value="{{ $start_date ?? date('Y-m-01') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text">End Date</span>
                                <input type="text" name="end_date" class="form-control datepicker"
                                    value="{{ $end_date ?? date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>Receiving #</th>
                                <th>PO Number</th>
                                <th>Project</th>
                                <th>Supplier</th>
                                <th>Delivery Date</th>
                                <th>Delivery Note</th>
                                <th class="text-right">Qty Delivered</th>
                                <th class="text-center">Condition</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($receivings as $receiving)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td><strong>{{ $receiving->receiving_number }}</strong></td>
                                    <td>
                                        @if($receiving->purchase)
                                            <a href="{{ route('purchase_order', ['id' => $receiving->purchase_id, 'document_type_id' => 0]) }}">
                                                {{ $receiving->purchase->document_number ?? 'PO-' . $receiving->purchase_id }}
                                            </a>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>{{ $receiving->project?->name ?? 'N/A' }}</td>
                                    <td>{{ $receiving->supplier?->name ?? 'N/A' }}</td>
                                    <td>{{ $receiving->date?->format('Y-m-d') ?? 'N/A' }}</td>
                                    <td>{{ $receiving->delivery_note_number ?? '-' }}</td>
                                    <td class="text-right">{{ number_format($receiving->quantity_delivered, 2) }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $receiving->condition_badge_class }}">
                                            {{ ucfirst(str_replace('_', ' ', $receiving->condition ?? 'N/A')) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $receiving->status_badge_class }}">
                                            {{ ucfirst($receiving->status ?? 'pending') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="{{ route('supplier_receiving_detail', $receiving->id) }}"
                                                class="btn btn-sm btn-success" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            @if($receiving->needsInspection())
                                                <a href="{{ route('material_inspection.create', $receiving->id) }}"
                                                    class="btn btn-sm btn-primary" title="Inspect">
                                                    <i class="fa fa-clipboard-check"></i>
                                                </a>
                                            @endif
                                            @if($receiving->file)
                                                <a href="{{ asset('storage/' . $receiving->file) }}"
                                                    class="btn btn-sm btn-info" title="View Delivery Note" target="_blank">
                                                    <i class="fa fa-file"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });
    });
</script>
@endsection

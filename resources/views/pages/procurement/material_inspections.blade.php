@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">Material Inspections
            <div class="float-right">
                <a href="{{ route('procurement_dashboard') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="si si-graph"></i> Dashboard
                </a>
            </div>
        </div>

        @if($pendingReceivings->count() > 0)
        <div class="block">
            <div class="block-header block-header-default bg-warning">
                <h3 class="block-title text-white">Deliveries Pending Inspection ({{ $pendingReceivings->count() }})</h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Receiving #</th>
                                <th>Supplier</th>
                                <th>Delivery Date</th>
                                <th>Qty Delivered</th>
                                <th>Condition</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingReceivings as $receiving)
                                <tr>
                                    <td>{{ $receiving->receiving_number ?? $receiving->id }}</td>
                                    <td>{{ $receiving->supplier?->name ?? 'N/A' }}</td>
                                    <td>{{ $receiving->date?->format('Y-m-d') ?? 'N/A' }}</td>
                                    <td>{{ number_format($receiving->quantity_delivered ?? $receiving->amount, 2) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $receiving->condition_badge_class ?? 'secondary' }}">
                                            {{ ucfirst($receiving->condition ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('material_inspection.create', $receiving->id) }}"
                                            class="btn btn-sm btn-primary">
                                            <i class="fa fa-clipboard-check"></i> Inspect
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">All Inspections</h3>
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
                        <div class="col-md-3">
                            <select name="project_id" class="form-control">
                                <option value="">All Projects</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ $selected_project == $project->id ? 'selected' : '' }}>
                                        {{ $project->name }}
                                    </option>
                                @endforeach
                            </select>
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
                                <th>Inspection #</th>
                                <th>Project</th>
                                <th>BOQ Item</th>
                                <th>Supplier</th>
                                <th class="text-right">Delivered</th>
                                <th class="text-right">Accepted</th>
                                <th class="text-center">Condition</th>
                                <th class="text-center">Result</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inspections as $inspection)
                                <tr id="inspection-tr-{{ $inspection->id }}">
                                    <td class="text-center">{{ $loop->index + 1 }}</td>
                                    <td>
                                        <a href="{{ route('material_inspection', ['id' => $inspection->id, 'document_type_id' => 0]) }}">
                                            <strong>{{ $inspection->inspection_number }}</strong>
                                        </a>
                                        <br>
                                        <small class="text-muted">{{ $inspection->inspection_date?->format('Y-m-d') }}</small>
                                    </td>
                                    <td>{{ $inspection->project?->name ?? 'N/A' }}</td>
                                    <td>{{ Str::limit($inspection->boqItem?->description ?? 'N/A', 30) }}</td>
                                    <td>{{ $inspection->supplierReceiving?->supplier?->name ?? 'N/A' }}</td>
                                    <td class="text-right">{{ number_format($inspection->quantity_delivered, 2) }}</td>
                                    <td class="text-right">
                                        {{ number_format($inspection->quantity_accepted, 2) }}
                                        <br>
                                        <small class="text-muted">({{ number_format($inspection->acceptance_rate, 1) }}%)</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $inspection->condition_badge_class }}">
                                            {{ ucfirst($inspection->overall_condition) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $inspection->result_badge_class }}">
                                            {{ ucfirst($inspection->overall_result) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $inspection->status_badge_class }}">
                                            {{ ucfirst($inspection->status) }}
                                        </span>
                                        @if($inspection->stock_updated)
                                            <br><small class="text-success">Stock Updated</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="{{ route('material_inspection', ['id' => $inspection->id, 'document_type_id' => 0]) }}"
                                                class="btn btn-sm btn-success" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
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

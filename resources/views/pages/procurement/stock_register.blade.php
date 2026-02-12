@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">Site Stock Register — {{ $project->name }}
            <div class="float-right">
                <a href="{{ route('stock_register_select') }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Projects
                </a>
                <a href="{{ route('procurement_dashboard') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="si si-graph"></i> Dashboard
                </a>
            </div>
        </div>

        {{-- Stat cards --}}
        <div class="row">
            <div class="col-md-3">
                <div class="block block-rounded text-center">
                    <div class="block-content block-content-full">
                        <div class="font-size-h2 font-w700">{{ $stats['total'] }}</div>
                        <div class="font-size-sm text-muted">Total Items</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded text-center">
                    <div class="block-content block-content-full bg-success-light">
                        <div class="font-size-h2 font-w700 text-success">{{ $stats['in_stock'] }}</div>
                        <div class="font-size-sm text-muted">In Stock</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded text-center">
                    <div class="block-content block-content-full bg-warning-light">
                        <div class="font-size-h2 font-w700 text-warning">{{ $stats['low_stock'] }}</div>
                        <div class="font-size-sm text-muted">Low Stock</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded text-center">
                    <div class="block-content block-content-full bg-danger-light">
                        <div class="font-size-h2 font-w700 text-danger">{{ $stats['out_of_stock'] }}</div>
                        <div class="font-size-sm text-muted">Out of Stock</div>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                {{ session('success') }}
            </div>
        @endif

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Inventory</h3>
                <div class="block-options">
                    <a href="{{ route('stock_register.issue', $project->id) }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-share"></i> Issue Materials
                    </a>
                    <a href="{{ route('stock_register.movements', $project->id) }}" class="btn btn-sm btn-info ml-1">
                        <i class="fa fa-history"></i> Movements
                    </a>
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>Item Code</th>
                                <th>Description</th>
                                <th class="text-center">Unit</th>
                                <th class="text-right">Qty Received</th>
                                <th class="text-right">Qty Used</th>
                                <th class="text-right">Qty Available</th>
                                <th class="text-right">Min Stock</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inventories as $inv)
                                @php
                                    $rowClass = match($inv->stock_status) {
                                        'out_of_stock' => 'table-danger',
                                        'low_stock'    => 'table-warning',
                                        default        => ''
                                    };
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td><strong>{{ $inv->boqItem?->item_code ?? '-' }}</strong></td>
                                    <td>{{ Str::limit($inv->boqItem?->description ?? $inv->material?->name ?? '-', 50) }}</td>
                                    <td class="text-center">{{ $inv->boqItem?->unit ?? '-' }}</td>
                                    <td class="text-right">{{ number_format($inv->quantity, 2) }}</td>
                                    <td class="text-right">{{ number_format($inv->quantity_used, 2) }}</td>
                                    <td class="text-right">
                                        <strong>{{ number_format($inv->quantity_available, 2) }}</strong>
                                    </td>
                                    <td class="text-right">{{ number_format($inv->minimum_stock_level, 2) }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $inv->stock_status_badge_class }}">
                                            {{ $inv->stock_status_label }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="{{ route('stock_register.adjust', [$project->id, $inv->id]) }}"
                                                class="btn btn-sm btn-warning" title="Adjust Stock">
                                                <i class="fa fa-sliders-h"></i>
                                            </a>
                                            <a href="{{ route('stock_register.movements', $project->id) }}?boq_item_id={{ $inv->boq_item_id }}"
                                                class="btn btn-sm btn-info" title="View History">
                                                <i class="fa fa-history"></i>
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

@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">Record Deliveries
            <div class="float-right">
                <a href="{{ route('procurement_dashboard') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="si si-graph"></i> Dashboard
                </a>
            </div>
        </div>

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Approved Purchase Orders Awaiting Delivery</h3>
            </div>
            <div class="block-content">
                @if($purchaseOrders->isEmpty())
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle mr-1"></i> All approved purchase orders have been fully delivered.
                    </div>
                @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>PO Number</th>
                                <th>Project</th>
                                <th>Supplier</th>
                                <th>Material Request</th>
                                <th class="text-center">Items</th>
                                <th class="text-center">Delivery Progress</th>
                                <th class="text-center" style="width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchaseOrders as $po)
                                @php
                                    $totalItems = $po->purchaseItems->count();
                                    $fullyReceived = $po->purchaseItems->filter(fn($i) => $i->isFullyReceived())->count();
                                    $partiallyReceived = $po->purchaseItems->filter(fn($i) => $i->isPartiallyReceived())->count();
                                    $pending = $totalItems - $fullyReceived;
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <a href="{{ route('purchase_order', ['id' => $po->id, 'document_type_id' => 0]) }}">
                                            <strong>{{ $po->document_number ?? 'PO-' . $po->id }}</strong>
                                        </a>
                                    </td>
                                    <td>{{ $po->project?->name ?? 'N/A' }}</td>
                                    <td>{{ $po->supplier?->name ?? 'N/A' }}</td>
                                    <td>{{ $po->materialRequest?->request_number ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-info">{{ $totalItems }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success">{{ $fullyReceived }} received</span>
                                        @if($partiallyReceived > 0)
                                            <span class="badge badge-warning">{{ $partiallyReceived }} partial</span>
                                        @endif
                                        <span class="badge badge-secondary">{{ $pending }} pending</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('purchase_order.record_delivery', $po->id) }}"
                                            class="btn btn-sm btn-info">
                                            <i class="fa fa-truck mr-1"></i> Record Delivery
                                        </a>
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
@endsection

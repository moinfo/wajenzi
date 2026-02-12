@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">Supplier Receiving: {{ $receiving->receiving_number }}
            <div class="float-right">
                <a href="{{ route('supplier_receivings_procurement') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        {{-- Status Banner --}}
        @php
            $statusLabel = match($receiving->status) {
                'pending' => 'Awaiting Inspection',
                'inspected' => 'Inspected',
                'received' => 'Received',
                'rejected' => 'Rejected',
                default => ucfirst($receiving->status)
            };
            $statusClass = match($receiving->status) {
                'pending' => 'warning',
                'inspected' => 'success',
                'received' => 'info',
                'rejected' => 'danger',
                default => 'secondary'
            };
        @endphp

        {{-- Receiving Details --}}
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Delivery Details</h3>
                <div class="block-options">
                    <span class="badge badge-{{ $statusClass }}" style="font-size: 14px; padding: 8px 16px;">
                        {{ $statusLabel }}
                    </span>
                </div>
            </div>
            <div class="block-content">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Receiving #:</strong><br>{{ $receiving->receiving_number }}
                    </div>
                    <div class="col-md-3">
                        <strong>Delivery Note:</strong><br>{{ $receiving->delivery_note_number ?? 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Delivery Date:</strong><br>{{ $receiving->date?->format('Y-m-d') }}
                    </div>
                    <div class="col-md-3">
                        <strong>Condition:</strong><br>
                        <span class="badge badge-{{ $receiving->condition_badge_class }}">
                            {{ ucfirst(str_replace('_', ' ', $receiving->condition ?? 'N/A')) }}
                        </span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Supplier:</strong><br>{{ $receiving->supplier?->name ?? 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Project:</strong><br>{{ $receiving->project?->name ?? 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Purchase Order:</strong><br>
                        @if($receiving->purchase)
                            <a href="{{ route('purchase_order', ['id' => $receiving->purchase_id, 'document_type_id' => 0]) }}">
                                {{ $receiving->purchase->document_number ?? 'PO-' . $receiving->purchase_id }}
                            </a>
                        @else
                            N/A
                        @endif
                    </div>
                    <div class="col-md-3">
                        <strong>Received By:</strong><br>{{ $receiving->receivedBy?->name ?? 'N/A' }}
                    </div>
                </div>
                @if($receiving->description)
                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong>Notes:</strong><br>{{ $receiving->description }}
                    </div>
                </div>
                @endif
                @if($receiving->file)
                <div class="row mb-3">
                    <div class="col-md-12">
                        <a href="{{ asset('storage/' . $receiving->file) }}" class="btn btn-sm btn-info" target="_blank">
                            <i class="fa fa-file mr-1"></i> View Delivery Note
                        </a>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- PO Items --}}
        @if($receiving->purchase && $receiving->purchase->purchaseItems->count())
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Purchase Order Items</h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Description</th>
                                <th>BOQ Item</th>
                                <th class="text-center">Unit</th>
                                <th class="text-right">Qty Ordered</th>
                                <th class="text-right">Qty Received</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($receiving->purchase->purchaseItems as $pItem)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $pItem->description }}</td>
                                    <td>{{ $pItem->boqItem?->item_code ?? '-' }}</td>
                                    <td class="text-center">{{ $pItem->unit }}</td>
                                    <td class="text-right">{{ number_format($pItem->quantity, 2) }}</td>
                                    <td class="text-right">{{ number_format($pItem->quantity_received, 2) }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $pItem->status_badge_class }}">
                                            {{ ucfirst($pItem->status ?? 'pending') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-right"><strong>Totals</strong></td>
                                <td class="text-right"><strong>{{ number_format($receiving->quantity_ordered, 2) }}</strong></td>
                                <td class="text-right"><strong>{{ number_format($receiving->quantity_delivered, 2) }}</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- Inspections --}}
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Inspections</h3>
            </div>
            <div class="block-content">
                @if($receiving->inspections->count())
                    <div class="table-responsive">
                        <table class="table table-bordered table-vcenter">
                            <thead>
                                <tr>
                                    <th>Inspection #</th>
                                    <th>Date</th>
                                    <th class="text-right">Qty Accepted</th>
                                    <th class="text-center">Result</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($receiving->inspections as $inspection)
                                    <tr>
                                        <td>{{ $inspection->inspection_number }}</td>
                                        <td>{{ $inspection->inspection_date?->format('Y-m-d') ?? 'N/A' }}</td>
                                        <td class="text-right">{{ number_format($inspection->quantity_accepted, 2) }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-{{ $inspection->result_badge_class ?? 'secondary' }}">
                                                {{ ucfirst($inspection->overall_result ?? 'N/A') }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-{{ $inspection->status_badge_class ?? 'secondary' }}">
                                                {{ ucfirst($inspection->status) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('material_inspection', ['id' => $inspection->id, 'document_type_id' => 0]) }}"
                                                class="btn btn-sm btn-success">
                                                <i class="fa fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle mr-1"></i>
                        No inspection recorded yet.
                        @if($receiving->needsInspection())
                            <a href="{{ route('material_inspection.create', $receiving->id) }}" class="btn btn-sm btn-primary ml-2">
                                <i class="fa fa-clipboard-check mr-1"></i> Create Inspection
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

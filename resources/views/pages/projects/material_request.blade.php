@extends('layouts.backend')

@section('content')
    @inject('approvalService', 'App\Services\ApprovalService')

    @if($approval_data == null)
        @php
            header("Location: " . URL::to('/404'), true, 302);
            exit();
        @endphp
    @endif

    <!-- Main Container -->
    <div class="container-fluid">
        <div class="content">
            <!-- Header Section -->
            @include('approvals._header', ['page_name' => $page_name, 'approval_data_name' => $approval_data_name])

            <!-- Request Details Card -->
            @include('approvals._payment_details', ['approval_data' => $approval_data, 'details' => $details])

            <!-- Request Items Table -->
            @if($request->items->count() > 0)
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Requested Items ({{ $request->items->count() }})</h3>
                </div>
                <div class="block-content p-0">
                    <table class="table table-sm table-bordered table-hover mb-0" style="font-size: 12px;">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th style="padding: 8px; width: 40px;">#</th>
                                <th style="padding: 8px;">Item Code</th>
                                <th style="padding: 8px;">Description</th>
                                <th class="text-right" style="padding: 8px;">BOQ Qty</th>
                                <th class="text-right" style="padding: 8px;">Requested</th>
                                @if(strtoupper($request->status) === 'APPROVED')
                                    <th class="text-right" style="padding: 8px;">Approved</th>
                                @endif
                                <th style="padding: 8px;">Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($request->items as $item)
                                <tr>
                                    <td style="padding: 6px 8px;" class="text-center text-muted">{{ $loop->iteration }}</td>
                                    <td style="padding: 6px 8px;" class="font-w600">{{ $item->boqItem->item_code ?? '-' }}</td>
                                    <td style="padding: 6px 8px;">
                                        {{ $item->boqItem->description ?? $item->description ?? '-' }}
                                        @if($item->specification)
                                            <small class="text-muted">({{ $item->specification }})</small>
                                        @endif
                                    </td>
                                    <td style="padding: 6px 8px;" class="text-right">
                                        {{ $item->boqItem ? number_format($item->boqItem->quantity, 2) : '-' }}
                                    </td>
                                    <td style="padding: 6px 8px;" class="text-right font-w600">
                                        {{ number_format($item->quantity_requested, 2) }}
                                    </td>
                                    @if(strtoupper($request->status) === 'APPROVED')
                                        <td style="padding: 6px 8px;" class="text-right text-success font-w600">
                                            {{ number_format($item->quantity_approved ?? $item->quantity_requested, 2) }}
                                        </td>
                                    @endif
                                    <td style="padding: 6px 8px;">{{ $item->unit }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- BOQ Item Summary Cards (for items linked to BOQ) -->
            @php $boqItems = $request->items->filter(fn($i) => $i->boqItem); @endphp
            @if($boqItems->count() > 0)
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">BOQ Procurement Status</h3>
                </div>
                <div class="block-content">
                    @foreach($boqItems as $item)
                    <div class="row mb-2" style="font-size: 12px;">
                        <div class="col-md-3">
                            <strong>{{ $item->boqItem->item_code }}</strong> — {{ Str::limit($item->boqItem->description, 30) }}
                        </div>
                        <div class="col-md-2 text-center">
                            <small class="text-muted">BOQ Qty</small><br>
                            <strong>{{ number_format($item->boqItem->quantity, 2) }}</strong>
                        </div>
                        <div class="col-md-2 text-center">
                            <small class="text-muted">Requested</small><br>
                            <strong>{{ number_format($item->boqItem->quantity_requested ?? 0, 2) }}</strong>
                        </div>
                        <div class="col-md-2 text-center">
                            <small class="text-muted">Received</small><br>
                            <strong>{{ number_format($item->boqItem->quantity_received ?? 0, 2) }}</strong>
                        </div>
                        <div class="col-md-3 text-center">
                            <small class="text-muted">Remaining</small><br>
                            <strong>{{ number_format($item->boqItem->quantity_remaining ?? 0, 2) }}</strong>
                        </div>
                    </div>
                    @if(!$loop->last) <hr class="my-1"> @endif
                    @endforeach
                </div>
            </div>
            @endif

            @if($request->purpose)
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Purpose / Justification</h3>
                </div>
                <div class="block-content">
                    <p class="p-3 bg-light rounded">{{ $request->purpose }}</p>
                </div>
            </div>
            @endif

            <!-- Approvals Section -->
            <div class="approvals-section">
                <style>
                    .approvals-section {
                        background-color: #fff;
                        border-radius: 10px;
                        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
                        margin-bottom: 30px;
                        overflow: hidden;
                        border: 1px solid rgba(0, 0, 0, 0.05);
                    }

                    .section-header {
                        background-color: #f8f9fa;
                        padding: 15px 25px;
                        border-bottom: 1px solid #e9ecef;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    }

                    .section-title {
                        margin: 0;
                        color: #0066cc;
                        font-weight: 600;
                        font-size: 18px;
                        display: flex;
                        align-items: center;
                    }

                    .section-title i {
                        margin-right: 10px;
                        color: #0066cc;
                    }

                    .section-body {
                        padding: 25px;
                    }
                </style>

                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-tasks"></i> Approval Flow
                    </h2>
                    <div class="flow-status">
                        <span class="badge bg-info">In Progress</span>
                    </div>
                </div>

                <div class="section-body">
                    <x-ringlesoft-approval-actions :model="$approval_data" />
                </div>
            </div>

            <!-- Actions for Approved Requests -->
            @if(strtoupper($request->status) === 'APPROVED')
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Next Steps</h3>
                </div>
                <div class="block-content">
                    <a href="{{ route('supplier_quotations.by_request', ['id' => $request->id]) }}" class="btn btn-primary">
                        <i class="fa fa-file-invoice-dollar"></i> Manage Quotations
                    </a>
                    @if($request->quotations && $request->quotations->count() >= 3)
                        <a href="{{ route('quotation_comparison.create', ['material_request_id' => $request->id]) }}" class="btn btn-success ml-2">
                            <i class="fa fa-balance-scale"></i> Create Comparison
                        </a>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection

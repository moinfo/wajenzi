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

            <!-- BOQ Details Card -->
            @include('approvals._payment_details', ['approval_data' => $approval_data, 'details' => $details])

            <!-- BOQ Items Summary -->
            @if($boq->items->count() > 0)
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">BOQ Items ({{ $boq->items->count() }})</h3>
                    <div class="block-options">
                        <a href="{{ route('project_boq.show', ['id' => $boq->id]) }}" class="btn btn-sm btn-alt-primary">
                            <i class="fa fa-eye"></i> View Full BOQ
                        </a>
                    </div>
                </div>
                <div class="block-content p-0">
                    <table class="table table-sm table-bordered table-hover mb-0" style="font-size: 12px;">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th style="padding: 8px; width: 40px;">#</th>
                                <th style="padding: 8px;">Item Code</th>
                                <th style="padding: 8px;">Description</th>
                                <th class="text-right" style="padding: 8px;">Qty</th>
                                <th style="padding: 8px;">Unit</th>
                                <th class="text-right" style="padding: 8px;">Unit Price</th>
                                <th class="text-right" style="padding: 8px;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($boq->items->take(20) as $item)
                                <tr>
                                    <td style="padding: 6px 8px;" class="text-center text-muted">{{ $loop->iteration }}</td>
                                    <td style="padding: 6px 8px;" class="font-w600">{{ $item->item_code ?? '-' }}</td>
                                    <td style="padding: 6px 8px;">
                                        {{ $item->description }}
                                        @if($item->specification)
                                            <small class="text-muted">({{ $item->specification }})</small>
                                        @endif
                                        @if($item->item_type == 'labour')
                                            <span class="badge badge-warning" style="font-size: 9px;">LABOUR</span>
                                        @endif
                                    </td>
                                    <td style="padding: 6px 8px;" class="text-right">{{ number_format($item->quantity, 2) }}</td>
                                    <td style="padding: 6px 8px;">{{ $item->unit }}</td>
                                    <td style="padding: 6px 8px;" class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                                    <td style="padding: 6px 8px;" class="text-right font-w600">{{ number_format($item->total_price, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            @if($boq->items->count() > 20)
                                <tr>
                                    <td colspan="7" class="text-center text-muted" style="padding: 8px;">
                                        ... and {{ $boq->items->count() - 20 }} more items.
                                        <a href="{{ route('project_boq.show', ['id' => $boq->id]) }}">View all</a>
                                    </td>
                                </tr>
                            @endif
                            <tr style="background: #f8f9fa;">
                                <td colspan="6" class="text-right" style="padding: 8px;"><strong>Grand Total:</strong></td>
                                <td class="text-right" style="padding: 8px;"><strong>{{ number_format($boq->total_amount, 2) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
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
        </div>
    </div>
@endsection

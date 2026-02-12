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
            @include('approvals._header', ['page_name'=> $page_name,'approval_data_name'=> $approval_data_name ])

            <!-- Payment Details Card -->
            @include('approvals._payment_details', ['approval_data' => $approval_data])

            @if(isset($purchaseItems) && $purchaseItems->count())
            <div class="details-card" style="margin-bottom: 25px;">
                <div class="card-header" style="background-color: #f8f9fa; padding: 15px 25px; border-bottom: 1px solid #e9ecef;">
                    <h3 style="margin: 0; color: #0066cc; font-weight: 600; font-size: 18px;">
                        <i class="fas fa-boxes me-2"></i> Order Items
                    </h3>
                </div>
                <div class="card-body" style="padding: 25px;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Description</th>
                                    <th>BOQ Item</th>
                                    <th class="text-center">Unit</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Unit Price</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $itemsTotal = 0; @endphp
                                @foreach($purchaseItems as $pItem)
                                    @php $itemsTotal += $pItem->total_price; @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $pItem->description }}</td>
                                        <td>{{ $pItem->boqItem?->item_code ?? '-' }}</td>
                                        <td class="text-center">{{ $pItem->unit }}</td>
                                        <td class="text-right">{{ number_format($pItem->quantity, 2) }}</td>
                                        <td class="text-right">{{ number_format($pItem->unit_price, 2) }}</td>
                                        <td class="text-right">{{ number_format($pItem->total_price, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="text-right"><strong>Subtotal</strong></td>
                                    <td class="text-right"><strong>{{ number_format($itemsTotal, 2) }}</strong></td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-right">VAT (18%)</td>
                                    <td class="text-right">{{ number_format($itemsTotal * 0.18, 2) }}</td>
                                </tr>
                                <tr style="font-size: 1.1em;">
                                    <td colspan="6" class="text-right"><strong>Grand Total</strong></td>
                                    <td class="text-right"><strong>{{ number_format($itemsTotal * 1.18, 2) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            @if(isset($inspectionItems) && $inspectionItems && $inspectionItems->count())
            <div class="details-card" style="margin-bottom: 25px;">
                <div class="card-header" style="background-color: #f8f9fa; padding: 15px 25px; border-bottom: 1px solid #e9ecef;">
                    <h3 style="margin: 0; color: #0066cc; font-weight: 600; font-size: 18px;">
                        <i class="fas fa-boxes me-2"></i> Delivered Items
                    </h3>
                </div>
                <div class="card-body" style="padding: 25px;">
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
                                @foreach($inspectionItems as $pItem)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $pItem->description }}</td>
                                        <td><code>{{ $pItem->boqItem?->item_code ?? '-' }}</code></td>
                                        <td class="text-center">{{ $pItem->unit }}</td>
                                        <td class="text-right">{{ number_format($pItem->quantity, 2) }}</td>
                                        <td class="text-right">{{ number_format($pItem->quantity_received, 2) }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-{{ $pItem->status_badge_class ?? 'secondary' }}">
                                                {{ ucfirst($pItem->status ?? 'pending') }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if(isset($criteria_checklist) && is_array($criteria_checklist) && count($criteria_checklist))
                    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e9ecef;">
                        <h5 style="color: #0066cc; font-weight: 600; margin-bottom: 12px;">
                            <i class="fas fa-clipboard-check me-1"></i> Inspection Checklist
                        </h5>
                        <div class="row">
                            @foreach($criteria_checklist as $key => $passed)
                                <div class="col-md-4 mb-2">
                                    <span style="color: {{ $passed ? '#16a34a' : '#dc2626' }};">
                                        <i class="fa {{ $passed ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                                    </span>
                                    {{ ucfirst(str_replace('_', ' ', $key)) }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(isset($inspection_notes) && $inspection_notes)
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e9ecef;">
                        <h5 style="color: #0066cc; font-weight: 600; margin-bottom: 8px;">
                            <i class="fas fa-sticky-note me-1"></i> Inspector Notes
                        </h5>
                        <p class="text-muted mb-0">{{ $inspection_notes }}</p>
                    </div>
                    @endif

                    @if(isset($rejection_reason) && $rejection_reason)
                    <div style="margin-top: 15px; padding: 12px 15px; background: #fef2f2; border-radius: 8px; border-left: 4px solid #dc2626;">
                        <h5 style="color: #dc2626; font-weight: 600; margin-bottom: 4px;">
                            <i class="fas fa-exclamation-triangle me-1"></i> Rejection Reason
                        </h5>
                        <p class="mb-0">{{ $rejection_reason }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            @if(isset($model) && $model === 'Purchase' && strtoupper($approval_data->status ?? '') === 'APPROVED' && $approval_data->material_request_id && isset($purchaseItems) && $purchaseItems->contains(fn($i) => !$i->isFullyReceived()))
            <div class="text-center mb-4">
                <a href="{{ route('purchase_order.record_delivery', $approval_data->id) }}"
                    class="btn btn-lg btn-info">
                    <i class="fa fa-truck mr-1"></i> Record Delivery
                </a>
            </div>
            @endif

            {{-- Quotation Comparison Items --}}
            @if(isset($quotations) && $quotations->count())
            <div class="details-card" style="margin-bottom: 25px;">
                <div class="card-header" style="background-color: #f8f9fa; padding: 15px 25px; border-bottom: 1px solid #e9ecef;">
                    <h3 style="margin: 0; color: #0066cc; font-weight: 600; font-size: 18px;">
                        <i class="fas fa-balance-scale me-2"></i> Supplier Quotation Comparison
                    </h3>
                </div>
                <div class="card-body" style="padding: 25px;">
                    @if(isset($recommendation_reason) && $recommendation_reason)
                    <div style="background: #e8f5e9; border-left: 4px solid #4CAF50; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px;">
                        <strong style="color: #2e7d32;"><i class="fas fa-thumbs-up me-1"></i> Recommendation:</strong>
                        <span style="color: #333;">{{ $recommendation_reason }}</span>
                    </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter" style="font-size: 14px;">
                            <thead style="background: #f1f3f5;">
                                <tr>
                                    <th style="width: 35px;">#</th>
                                    <th>Item Description</th>
                                    <th class="text-center">Unit</th>
                                    <th class="text-center">Qty</th>
                                    @foreach($quotations as $q)
                                        <th class="text-right" style="min-width: 120px; {{ $approval_data->selected_quotation_id == $q->id ? 'background: #e8f5e9;' : '' }}">
                                            {{ $q->supplier?->name ?? 'Supplier' }}
                                            @if($approval_data->selected_quotation_id == $q->id)
                                                <br><span class="badge bg-success" style="font-size: 10px;">Selected</span>
                                            @endif
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    // Build lookup: material_request_item_id => [quotation_id => item]
                                    $itemMatrix = [];
                                    $mrItems = $approval_data->materialRequest?->items ?? collect();

                                    foreach ($quotations as $q) {
                                        foreach ($q->items as $qi) {
                                            $key = $qi->material_request_item_id ?? $qi->boq_item_id ?? $qi->description;
                                            $itemMatrix[$key][$q->id] = $qi;
                                        }
                                    }
                                    $rowNum = 0;
                                @endphp

                                @if($mrItems->count())
                                    @foreach($mrItems as $mrItem)
                                        @php $rowNum++; $key = $mrItem->id; @endphp
                                        <tr>
                                            <td>{{ $rowNum }}</td>
                                            <td>{{ $mrItem->boqItem?->description ?? $mrItem->description ?? '-' }}</td>
                                            <td class="text-center">{{ $mrItem->unit ?? $mrItem->boqItem?->unit ?? '-' }}</td>
                                            <td class="text-center">{{ number_format($mrItem->quantity_requested, 2) }}</td>
                                            @foreach($quotations as $q)
                                                @php $qi = $itemMatrix[$key][$q->id] ?? null; @endphp
                                                <td class="text-right" style="{{ $approval_data->selected_quotation_id == $q->id ? 'background: #f1f8e9;' : '' }}">
                                                    @if($qi)
                                                        {{ number_format($qi->unit_price, 2) }}
                                                        <br><small class="text-muted">= {{ number_format($qi->total_price, 2) }}</small>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @else
                                    {{-- Fallback: use first quotation's items as rows --}}
                                    @foreach($quotations->first()?->items ?? [] as $qi)
                                        @php $rowNum++; @endphp
                                        <tr>
                                            <td>{{ $rowNum }}</td>
                                            <td>{{ $qi->description ?? $qi->boqItem?->description ?? '-' }}</td>
                                            <td class="text-center">{{ $qi->unit ?? '-' }}</td>
                                            <td class="text-center">{{ number_format($qi->quantity, 2) }}</td>
                                            @foreach($quotations as $q)
                                                @php
                                                    $matchItem = $q->items->first(function($i) use ($qi) {
                                                        return ($i->material_request_item_id && $i->material_request_item_id == $qi->material_request_item_id)
                                                            || ($i->boq_item_id && $i->boq_item_id == $qi->boq_item_id);
                                                    });
                                                @endphp
                                                <td class="text-right" style="{{ $approval_data->selected_quotation_id == $q->id ? 'background: #f1f8e9;' : '' }}">
                                                    @if($matchItem)
                                                        {{ number_format($matchItem->unit_price, 2) }}
                                                        <br><small class="text-muted">= {{ number_format($matchItem->total_price, 2) }}</small>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                            <tfoot style="font-weight: 600; background: #f8f9fa;">
                                <tr>
                                    <td colspan="4" class="text-right">Subtotal</td>
                                    @foreach($quotations as $q)
                                        <td class="text-right" style="{{ $approval_data->selected_quotation_id == $q->id ? 'background: #e8f5e9;' : '' }}">
                                            {{ number_format($q->total_amount ?? 0, 2) }}
                                        </td>
                                    @endforeach
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-right">VAT</td>
                                    @foreach($quotations as $q)
                                        <td class="text-right" style="{{ $approval_data->selected_quotation_id == $q->id ? 'background: #e8f5e9;' : '' }}">
                                            {{ number_format($q->vat_amount ?? 0, 2) }}
                                        </td>
                                    @endforeach
                                </tr>
                                <tr style="font-size: 1.05em;">
                                    <td colspan="4" class="text-right"><strong>Grand Total</strong></td>
                                    @foreach($quotations as $q)
                                        <td class="text-right" style="{{ $approval_data->selected_quotation_id == $q->id ? 'background: #c8e6c9; font-weight: 700;' : '' }}">
                                            <strong>{{ number_format($q->grand_total ?? 0, 2) }}</strong>
                                        </td>
                                    @endforeach
                                </tr>
                            </tfoot>
                        </table>
                    </div>
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

                    .approval-steps {
                        position: relative;
                        margin-bottom: 20px;
                    }

                    .approval-timeline {
                        position: absolute;
                        top: 0;
                        bottom: 0;
                        left: 20px;
                        width: 2px;
                        background-color: #dee2e6;
                        z-index: 1;
                    }

                    .approval-submit-container {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        background-color: #f8f9fa;
                        padding: 15px 25px;
                        border-radius: 8px;
                        margin-top: 20px;
                    }

                    .submit-message {
                        color: #6c757d;
                        font-size: 15px;
                    }

                    .btn-submit {
                        background-color: #4CAF50;
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        border-radius: 6px;
                        font-weight: 600;
                        transition: all 0.3s ease;
                        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                    }

                    .btn-submit:hover {
                        background-color: #3e8e41;
                        transform: translateY(-2px);
                        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
                    }

                    /* Status indicators */
                    .status-indicator {
                        display: inline-block;
                        width: 12px;
                        height: 12px;
                        border-radius: 50%;
                        margin-right: 8px;
                    }

                    .status-pending {
                        background-color: #ffc107;
                    }

                    .status-approved {
                        background-color: #4CAF50;
                    }

                    .status-rejected {
                        background-color: #dc3545;
                    }

                    .status-waiting {
                        background-color: #6c757d;
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
                    <!-- Approval Component -->
                    <x-ringlesoft-approval-actions :model="$approval_data" />
                </div>
            </div>
        </div>
    </div>
@endsection

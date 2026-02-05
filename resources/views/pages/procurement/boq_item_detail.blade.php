@extends('layouts.backend')

@section('css')
<style>
    .item-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    .item-header h2 {
        margin: 0 0 10px;
    }
    .quantity-card {
        text-align: center;
        padding: 20px;
        border-radius: 10px;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .quantity-card h3 {
        margin: 0;
        font-size: 2rem;
    }
    .quantity-card p {
        margin: 5px 0 0;
        color: #6c757d;
    }
    .flow-diagram {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    .flow-step {
        text-align: center;
        flex: 1;
    }
    .flow-step .value {
        font-size: 1.5rem;
        font-weight: bold;
    }
    .flow-arrow {
        font-size: 1.5rem;
        color: #6c757d;
    }
    .history-table th {
        background: #f8f9fa;
    }
    .tab-content {
        padding: 20px 0;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            BOQ Item Procurement Details
            <div class="float-right">
                <a href="{{ route('procurement_dashboard.project', $boqItem->project_id ?? $boqItem->boq->project_id) }}"
                    class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Back to Project
                </a>
            </div>
        </div>

        <!-- Item Header -->
        <div class="item-header">
            <div class="row">
                <div class="col-md-8">
                    <small class="opacity-75">{{ $boqItem->item_code ?? 'N/A' }}</small>
                    <h2>{{ $boqItem->description }}</h2>
                    <p class="mb-0 opacity-75">
                        <i class="fa fa-building"></i> {{ $boqItem->project?->name ?? $boqItem->boq?->project?->name ?? 'N/A' }}
                        @if($boqItem->constructionPhase)
                            | <i class="fa fa-layer-group"></i> {{ $boqItem->constructionPhase->name }}
                        @endif
                    </p>
                </div>
                <div class="col-md-4 text-right">
                    <span class="badge badge-{{ $boqItem->procurement_status === 'complete' ? 'success' : ($boqItem->procurement_status === 'in_progress' ? 'warning' : 'light') }} badge-lg p-3">
                        {{ ucfirst(str_replace('_', ' ', $boqItem->procurement_status ?? 'not started')) }}
                    </span>
                    <p class="mt-2 mb-0">
                        <strong>{{ number_format($boqItem->procurement_percentage ?? 0, 1) }}%</strong> Complete
                    </p>
                </div>
            </div>
        </div>

        <!-- Quantity Flow -->
        <div class="flow-diagram">
            <div class="flow-step">
                <div class="value text-primary">{{ number_format($boqItem->quantity, 2) }}</div>
                <p>BOQ Quantity</p>
            </div>
            <div class="flow-arrow"><i class="fa fa-arrow-right"></i></div>
            <div class="flow-step">
                <div class="value text-info">{{ number_format($boqItem->quantity_requested ?? 0, 2) }}</div>
                <p>Requested</p>
            </div>
            <div class="flow-arrow"><i class="fa fa-arrow-right"></i></div>
            <div class="flow-step">
                <div class="value text-warning">{{ number_format($boqItem->quantity_ordered ?? 0, 2) }}</div>
                <p>Ordered</p>
            </div>
            <div class="flow-arrow"><i class="fa fa-arrow-right"></i></div>
            <div class="flow-step">
                <div class="value text-success">{{ number_format($boqItem->quantity_received ?? 0, 2) }}</div>
                <p>Received</p>
            </div>
            <div class="flow-arrow"><i class="fa fa-arrow-right"></i></div>
            <div class="flow-step">
                <div class="value text-secondary">{{ number_format($boqItem->quantity_used ?? 0, 2) }}</div>
                <p>Used</p>
            </div>
        </div>

        <!-- Quantity Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="quantity-card">
                    <h3 class="text-primary">{{ number_format($boqItem->quantity, 2) }}</h3>
                    <p>Total Required</p>
                    <small class="text-muted">{{ $boqItem->unit ?? 'units' }}</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="quantity-card">
                    <h3 class="text-success">{{ number_format($boqItem->quantity_received ?? 0, 2) }}</h3>
                    <p>Total Received</p>
                    <small class="text-muted">{{ number_format(($boqItem->quantity_received ?? 0) / max($boqItem->quantity, 1) * 100, 1) }}% of required</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="quantity-card">
                    <h3 class="{{ ($boqItem->quantity_remaining ?? $boqItem->quantity) > 0 ? 'text-warning' : 'text-success' }}">
                        {{ number_format($boqItem->quantity_remaining ?? ($boqItem->quantity - ($boqItem->quantity_received ?? 0)), 2) }}
                    </h3>
                    <p>Remaining</p>
                    <small class="text-muted">To procure</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="quantity-card">
                    <h3 class="text-info">{{ number_format(($boqItem->quantity_received ?? 0) - ($boqItem->quantity_used ?? 0), 2) }}</h3>
                    <p>In Stock</p>
                    <small class="text-muted">Available for use</small>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs" id="itemTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#requests">
                    Material Requests <span class="badge badge-info">{{ $materialRequests->count() }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#quotations">
                    Quotations <span class="badge badge-secondary">{{ $quotations->count() }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#purchases">
                    Purchases <span class="badge badge-secondary">{{ $purchases->count() }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#receivings">
                    Deliveries <span class="badge badge-secondary">{{ $receivings->count() }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#movements">
                    Stock Movements <span class="badge badge-secondary">{{ $movements->count() }}</span>
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Material Requests Tab -->
            <div class="tab-pane fade show active" id="requests">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped history-table">
                        <thead>
                            <tr>
                                <th>Request #</th>
                                <th>Date</th>
                                <th>Quantity</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Requested By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($materialRequests as $request)
                            <tr>
                                <td>
                                    <a href="{{ route('project_material_request', ['id' => $request->id, 'document_type_id' => 0]) }}">
                                        <strong>{{ $request->request_number }}</strong>
                                    </a>
                                </td>
                                <td>{{ $request->created_at?->format('Y-m-d') }}</td>
                                <td>{{ number_format($request->quantity_requested, 2) }} {{ $request->unit }}</td>
                                <td>
                                    <span class="badge badge-{{ $request->priority === 'urgent' ? 'danger' : ($request->priority === 'high' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst($request->priority ?? 'normal') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $request->status_badge_class ?? 'secondary' }}">
                                        {{ ucfirst($request->status) }}
                                    </span>
                                </td>
                                <td>{{ $request->user?->name ?? 'N/A' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No material requests for this item</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quotations Tab -->
            <div class="tab-pane fade" id="quotations">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped history-table">
                        <thead>
                            <tr>
                                <th>Quotation #</th>
                                <th>Supplier</th>
                                <th>Date</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($quotations as $quotation)
                            <tr class="{{ $quotation->status === 'selected' ? 'table-success' : '' }}">
                                <td><strong>{{ $quotation->quotation_number }}</strong></td>
                                <td>{{ $quotation->supplier?->name ?? 'N/A' }}</td>
                                <td>{{ $quotation->quotation_date?->format('Y-m-d') }}</td>
                                <td class="text-right">{{ number_format($quotation->unit_price, 2) }}</td>
                                <td class="text-right"><strong>{{ number_format($quotation->grand_total, 2) }}</strong></td>
                                <td>
                                    <span class="badge badge-{{ $quotation->status === 'selected' ? 'success' : ($quotation->status === 'rejected' ? 'danger' : 'info') }}">
                                        {{ ucfirst($quotation->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No quotations for this item</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Purchases Tab -->
            <div class="tab-pane fade" id="purchases">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped history-table">
                        <thead>
                            <tr>
                                <th>Purchase #</th>
                                <th>Supplier</th>
                                <th>Date</th>
                                <th>Quantity</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchases as $purchase)
                            <tr>
                                <td>
                                    <a href="{{ route('purchase', ['id' => $purchase->id, 'document_type_id' => 0]) }}">
                                        <strong>{{ $purchase->purchase_number ?? $purchase->id }}</strong>
                                    </a>
                                </td>
                                <td>{{ $purchase->supplier?->name ?? 'N/A' }}</td>
                                <td>{{ $purchase->date?->format('Y-m-d') }}</td>
                                <td>{{ number_format($purchase->items->where('boq_item_id', $boqItem->id)->sum('quantity'), 2) }}</td>
                                <td class="text-right">{{ number_format($purchase->amount, 2) }}</td>
                                <td>
                                    <span class="badge badge-{{ $purchase->status_badge_class ?? 'secondary' }}">
                                        {{ ucfirst($purchase->status ?? 'pending') }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No purchases for this item</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Receivings Tab -->
            <div class="tab-pane fade" id="receivings">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped history-table">
                        <thead>
                            <tr>
                                <th>Receiving #</th>
                                <th>Supplier</th>
                                <th>Date</th>
                                <th>Qty Delivered</th>
                                <th>Qty Accepted</th>
                                <th>Condition</th>
                                <th>Inspection</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($receivings as $receiving)
                            <tr>
                                <td><strong>{{ $receiving->receiving_number ?? $receiving->id }}</strong></td>
                                <td>{{ $receiving->supplier?->name ?? 'N/A' }}</td>
                                <td>{{ $receiving->date?->format('Y-m-d') }}</td>
                                <td>{{ number_format($receiving->quantity_delivered ?? $receiving->amount, 2) }}</td>
                                <td>{{ number_format($receiving->inspection?->quantity_accepted ?? 0, 2) }}</td>
                                <td>
                                    <span class="badge badge-{{ $receiving->condition_badge_class ?? 'secondary' }}">
                                        {{ ucfirst($receiving->condition ?? 'N/A') }}
                                    </span>
                                </td>
                                <td>
                                    @if($receiving->inspection)
                                        <a href="{{ route('material_inspection', ['id' => $receiving->inspection->id, 'document_type_id' => 0]) }}">
                                            {{ $receiving->inspection->inspection_number }}
                                        </a>
                                    @else
                                        <span class="text-muted">Pending</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No deliveries for this item</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Stock Movements Tab -->
            <div class="tab-pane fade" id="movements">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped history-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Reference</th>
                                <th>Performed By</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($movements as $movement)
                            <tr>
                                <td>{{ $movement->date?->format('Y-m-d H:i') ?? $movement->created_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    <span class="badge badge-{{ $movement->movement_type === 'received' ? 'success' : ($movement->movement_type === 'issued' ? 'warning' : 'info') }}">
                                        {{ ucfirst($movement->movement_type) }}
                                    </span>
                                </td>
                                <td class="{{ $movement->movement_type === 'received' ? 'text-success' : 'text-danger' }}">
                                    {{ $movement->movement_type === 'received' ? '+' : '-' }}{{ number_format($movement->quantity, 2) }}
                                </td>
                                <td>{{ $movement->reference_number ?? ($movement->reference_type . ' #' . $movement->reference_id) }}</td>
                                <td>{{ $movement->performedBy?->name ?? 'System' }}</td>
                                <td>{{ Str::limit($movement->notes, 30) ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No stock movements recorded</td>
                            </tr>
                            @endforelse
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
        // Handle tab persistence
        const hash = window.location.hash;
        if (hash) {
            $('a[href="' + hash + '"]').tab('show');
        }

        $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
            history.replaceState(null, null, e.target.hash);
        });
    });
</script>
@endsection

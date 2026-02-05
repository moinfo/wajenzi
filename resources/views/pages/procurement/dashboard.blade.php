@extends('layouts.backend')

@section('css')
<style>
    .stat-card {
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        color: white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .stat-card h3 {
        margin: 0;
        font-size: 2.5rem;
        font-weight: bold;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
    }
    .stat-card p {
        margin: 10px 0 5px;
        font-size: 1rem;
        font-weight: 600;
    }
    .stat-card small {
        opacity: 0.9;
        font-size: 0.85rem;
    }
    .action-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
    }
    .action-item:last-child {
        border-bottom: none;
    }
    .action-count {
        font-size: 1.25rem;
        font-weight: bold;
    }
    .block-title i {
        margin-right: 8px;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            Procurement Dashboard
            <div class="float-right">
                <a href="{{ route('supplier_quotations') }}" class="btn btn-rounded btn-outline-primary min-width-100 mb-10">
                    <i class="fa fa-file-invoice"></i> Quotations
                </a>
                <a href="{{ route('quotation_comparisons') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="fa fa-balance-scale"></i> Comparisons
                </a>
                <a href="{{ route('material_inspections') }}" class="btn btn-rounded btn-outline-success min-width-100 mb-10">
                    <i class="fa fa-clipboard-check"></i> Inspections
                </a>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card bg-primary">
                    <h3>{{ $stats['total_requests'] }}</h3>
                    <p>Total Requests</p>
                    <small>{{ $stats['pending_requests'] }} pending</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info">
                    <h3>{{ $stats['total_quotations'] }}</h3>
                    <p>Total Quotations</p>
                    <small>{{ $stats['pending_comparisons'] }} comparisons pending</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning">
                    <h3>{{ $stats['pending_deliveries'] }}</h3>
                    <p>Pending Deliveries</p>
                    <small>Awaiting inspection</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success">
                    <h3>{{ $stats['approved_inspections'] }}</h3>
                    <p>Completed Inspections</p>
                    <small>{{ $stats['pending_inspections'] }} pending approval</small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Pending Actions -->
            <div class="col-md-4">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><i class="fa fa-exclamation-circle text-danger"></i> Actions Required</h3>
                    </div>
                    <div class="block-content">
                        <div class="action-item">
                            <span>Requests Pending Approval</span>
                            <span class="action-count text-warning">{{ $pendingActions['requests_pending_approval'] }}</span>
                        </div>
                        <div class="action-item">
                            <span>Requests Needing Quotations</span>
                            <span class="action-count text-info">{{ $pendingActions['requests_needing_quotations'] }}</span>
                        </div>
                        <div class="action-item">
                            <span>Ready for Comparison</span>
                            <span class="action-count text-primary">{{ $pendingActions['requests_ready_for_comparison'] }}</span>
                        </div>
                        <div class="action-item">
                            <span>Comparisons Pending Approval</span>
                            <span class="action-count text-warning">{{ $pendingActions['comparisons_pending_approval'] }}</span>
                        </div>
                        <div class="action-item">
                            <span>Deliveries Pending Inspection</span>
                            <span class="action-count text-danger">{{ $pendingActions['deliveries_pending_inspection'] }}</span>
                        </div>
                        <div class="action-item">
                            <span>Inspections Pending Approval</span>
                            <span class="action-count text-warning">{{ $pendingActions['inspections_pending_approval'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Projects -->
            <div class="col-md-4">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Active Projects</h3>
                    </div>
                    <div class="block-content">
                        @forelse($activeProjects as $project)
                            <div class="action-item">
                                <div>
                                    <a href="{{ route('procurement_dashboard.project', $project->id) }}">
                                        {{ Str::limit($project->name, 25) }}
                                    </a>
                                    <br>
                                    <small class="text-muted">{{ $project->material_requests_count }} requests</small>
                                </div>
                                <span class="badge badge-info">{{ $project->boqs_count }} BOQs</span>
                            </div>
                        @empty
                            <p class="text-muted text-center py-3">No active projects</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Low Stock Alerts -->
            <div class="col-md-4">
                <div class="block">
                    <div class="block-header block-header-default bg-warning">
                        <h3 class="block-title">Low Stock Alerts</h3>
                    </div>
                    <div class="block-content">
                        @forelse($lowStockItems as $item)
                            <div class="action-item">
                                <div>
                                    {{ Str::limit($item->boqItem?->description ?? $item->material?->name ?? 'Unknown', 20) }}
                                    <br>
                                    <small class="text-muted">{{ $item->project?->name ?? 'N/A' }}</small>
                                </div>
                                <span class="badge badge-{{ $item->stock_status_badge_class }}">
                                    {{ number_format($item->quantity_available, 2) }}
                                </span>
                            </div>
                        @empty
                            <p class="text-success text-center py-3">
                                <i class="fa fa-check-circle"></i> No low stock alerts
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Requests -->
            <div class="col-md-4">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Recent Requests</h3>
                    </div>
                    <div class="block-content">
                        @foreach($recentRequests as $request)
                            <div class="action-item">
                                <div>
                                    <strong>{{ $request->request_number }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $request->project?->name ?? 'N/A' }}</small>
                                </div>
                                <span class="badge badge-{{ $request->priority_badge_class ?? 'secondary' }}">
                                    {{ ucfirst($request->priority ?? $request->status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Recent Comparisons -->
            <div class="col-md-4">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Recent Comparisons</h3>
                    </div>
                    <div class="block-content">
                        @foreach($recentComparisons as $comparison)
                            <div class="action-item">
                                <div>
                                    <a href="{{ route('quotation_comparison', ['id' => $comparison->id, 'document_type_id' => 0]) }}">
                                        <strong>{{ $comparison->comparison_number }}</strong>
                                    </a>
                                    <br>
                                    <small class="text-muted">
                                        {{ $comparison->selectedQuotation?->supplier?->name ?? 'No supplier' }}
                                    </small>
                                </div>
                                <span class="badge badge-{{ $comparison->status_badge_class ?? 'secondary' }}">
                                    {{ ucfirst($comparison->status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Recent Inspections -->
            <div class="col-md-4">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Recent Inspections</h3>
                    </div>
                    <div class="block-content">
                        @foreach($recentInspections as $inspection)
                            <div class="action-item">
                                <div>
                                    <a href="{{ route('material_inspection', ['id' => $inspection->id, 'document_type_id' => 0]) }}">
                                        <strong>{{ $inspection->inspection_number }}</strong>
                                    </a>
                                    <br>
                                    <small class="text-muted">{{ $inspection->project?->name ?? 'N/A' }}</small>
                                </div>
                                <span class="badge badge-{{ $inspection->result_badge_class ?? 'secondary' }}">
                                    {{ ucfirst($inspection->overall_result ?? $inspection->status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

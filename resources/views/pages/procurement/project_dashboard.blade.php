@extends('layouts.backend')

@section('css')
<style>
    .progress-card {
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .progress-card h5 {
        margin-bottom: 15px;
        color: #333;
    }
    .progress-bar-thick {
        height: 25px;
        border-radius: 5px;
    }
    .boq-item-card {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        transition: all 0.2s;
    }
    .boq-item-card:hover {
        border-color: #007bff;
        box-shadow: 0 2px 8px rgba(0,123,255,0.15);
    }
    .boq-item-card.status-complete {
        border-left: 4px solid #28a745;
    }
    .boq-item-card.status-in-progress {
        border-left: 4px solid #ffc107;
    }
    .boq-item-card.status-not-started {
        border-left: 4px solid #6c757d;
    }
    .stat-mini {
        text-align: center;
        padding: 10px;
    }
    .stat-mini h4 {
        margin: 0;
        font-size: 1.5rem;
    }
    .stat-mini p {
        margin: 5px 0 0;
        color: #6c757d;
        font-size: 0.85rem;
    }
    .phase-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        border-radius: 8px 8px 0 0;
        margin-bottom: 0;
    }
    .timeline-item {
        position: relative;
        padding-left: 30px;
        padding-bottom: 20px;
        border-left: 2px solid #dee2e6;
    }
    .timeline-item:last-child {
        border-left: 2px solid transparent;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -8px;
        top: 0;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #007bff;
        border: 2px solid white;
    }
    .timeline-item.completed::before {
        background: #28a745;
    }
    .timeline-item.pending::before {
        background: #ffc107;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            Procurement: {{ $project->name }}
            <div class="float-right">
                <a href="{{ route('procurement_dashboard') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="si si-graph"></i> Dashboard
                </a>
                <a href="{{ route('project', ['id' => $project->id, 'document_type_id' => 0]) }}"
                    class="btn btn-rounded btn-outline-primary min-width-100 mb-10">
                    <i class="fa fa-building"></i> Project Details
                </a>
            </div>
        </div>

        <!-- Overall Progress -->
        <div class="row">
            <div class="col-md-12">
                <div class="progress-card">
                    <div class="row">
                        <div class="col-md-3 stat-mini">
                            <h4 class="text-primary">{{ $stats['total_boq_items'] }}</h4>
                            <p>BOQ Items</p>
                        </div>
                        <div class="col-md-3 stat-mini">
                            <h4 class="text-info">{{ $stats['total_requests'] }}</h4>
                            <p>Material Requests</p>
                        </div>
                        <div class="col-md-3 stat-mini">
                            <h4 class="text-warning">{{ $stats['pending_requests'] }}</h4>
                            <p>Pending Approval</p>
                        </div>
                        <div class="col-md-3 stat-mini">
                            <h4 class="text-success">{{ number_format($stats['overall_progress'], 1) }}%</h4>
                            <p>Overall Progress</p>
                        </div>
                    </div>
                    <div class="progress progress-bar-thick mt-3">
                        <div class="progress-bar bg-success" style="width: {{ $stats['complete_percentage'] }}%"
                            title="Complete: {{ $stats['complete_percentage'] }}%"></div>
                        <div class="progress-bar bg-warning" style="width: {{ $stats['in_progress_percentage'] }}%"
                            title="In Progress: {{ $stats['in_progress_percentage'] }}%"></div>
                        <div class="progress-bar bg-secondary" style="width: {{ $stats['not_started_percentage'] }}%"
                            title="Not Started: {{ $stats['not_started_percentage'] }}%"></div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-4">
                            <small><span class="badge badge-success">●</span> Complete: {{ $stats['complete_count'] }}</small>
                        </div>
                        <div class="col-md-4">
                            <small><span class="badge badge-warning">●</span> In Progress: {{ $stats['in_progress_count'] }}</small>
                        </div>
                        <div class="col-md-4">
                            <small><span class="badge badge-secondary">●</span> Not Started: {{ $stats['not_started_count'] }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Budget Overview -->
        <div class="row">
            <div class="col-md-3">
                <div class="block text-center">
                    <div class="block-content">
                        <h4 class="text-primary">{{ number_format($budget['total_budget'], 2) }}</h4>
                        <p class="text-muted">Total BOQ Budget</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block text-center">
                    <div class="block-content">
                        <h4 class="text-warning">{{ number_format($budget['total_ordered'], 2) }}</h4>
                        <p class="text-muted">Total Ordered</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block text-center">
                    <div class="block-content">
                        <h4 class="{{ $budget['remaining'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($budget['remaining'], 2) }}
                        </h4>
                        <p class="text-muted">Remaining Budget</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block text-center">
                    <div class="block-content">
                        <h4 class="text-info">{{ number_format($budget['utilization'], 1) }}%</h4>
                        <p class="text-muted">Budget Utilization</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- BOQ Items by Phase -->
            <div class="col-md-8">
                @foreach($phases as $phase)
                <div class="block">
                    <div class="phase-header">
                        <h5 class="mb-0">
                            <i class="fa fa-layer-group"></i> {{ $phase->name }}
                            <span class="float-right">
                                {{ $phase->boq_items_count ?? $phase->boqItems->count() }} items
                            </span>
                        </h5>
                    </div>
                    <div class="block-content">
                        @forelse($phase->boqItems as $item)
                        <div class="boq-item-card status-{{ $item->procurement_status ?? 'not-started' }}">
                            <div class="row">
                                <div class="col-md-6">
                                    <a href="{{ route('procurement_dashboard.boq_item', $item->id) }}">
                                        <strong>{{ $item->item_code ?? 'N/A' }}</strong>
                                    </a>
                                    <p class="mb-0 text-muted">{{ Str::limit($item->description, 50) }}</p>
                                </div>
                                <div class="col-md-2 text-center">
                                    <small class="text-muted">Required</small><br>
                                    <strong>{{ number_format($item->quantity, 2) }}</strong>
                                </div>
                                <div class="col-md-2 text-center">
                                    <small class="text-muted">Received</small><br>
                                    <strong class="text-success">{{ number_format($item->quantity_received ?? 0, 2) }}</strong>
                                </div>
                                <div class="col-md-2 text-center">
                                    <span class="badge badge-{{ $item->procurement_status === 'complete' ? 'success' : ($item->procurement_status === 'in_progress' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst(str_replace('_', ' ', $item->procurement_status ?? 'not started')) }}
                                    </span>
                                    <br>
                                    <small>{{ number_format($item->procurement_percentage ?? 0, 0) }}%</small>
                                </div>
                            </div>
                            @if($item->quantity_remaining > 0)
                            <div class="progress mt-2" style="height: 5px;">
                                <div class="progress-bar bg-success" style="width: {{ $item->procurement_percentage ?? 0 }}%"></div>
                            </div>
                            @endif
                        </div>
                        @empty
                        <p class="text-muted text-center py-3">No BOQ items in this phase</p>
                        @endforelse
                    </div>
                </div>
                @endforeach

                @if($phases->isEmpty())
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> No construction phases defined for this project.
                    BOQ items are shown without phase grouping.
                </div>
                @endif
            </div>

            <!-- Right Sidebar -->
            <div class="col-md-4">
                <!-- Pending Actions -->
                <div class="block">
                    <div class="block-header block-header-default bg-warning">
                        <h3 class="block-title text-white">Pending Actions</h3>
                    </div>
                    <div class="block-content">
                        @if($pendingActions['requests_pending'] > 0)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Requests Pending Approval</span>
                            <span class="badge badge-warning">{{ $pendingActions['requests_pending'] }}</span>
                        </div>
                        @endif
                        @if($pendingActions['comparisons_pending'] > 0)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Comparisons Pending</span>
                            <span class="badge badge-info">{{ $pendingActions['comparisons_pending'] }}</span>
                        </div>
                        @endif
                        @if($pendingActions['deliveries_pending'] > 0)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Deliveries to Inspect</span>
                            <span class="badge badge-danger">{{ $pendingActions['deliveries_pending'] }}</span>
                        </div>
                        @endif
                        @if($pendingActions['inspections_pending'] > 0)
                        <div class="d-flex justify-content-between py-2">
                            <span>Inspections Pending</span>
                            <span class="badge badge-warning">{{ $pendingActions['inspections_pending'] }}</span>
                        </div>
                        @endif
                        @if(array_sum($pendingActions) === 0)
                        <p class="text-success text-center py-3">
                            <i class="fa fa-check-circle"></i> No pending actions
                        </p>
                        @endif
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Recent Activity</h3>
                    </div>
                    <div class="block-content">
                        @forelse($recentActivity as $activity)
                        <div class="timeline-item {{ $activity->status === 'approved' ? 'completed' : ($activity->status === 'pending' ? 'pending' : '') }}">
                            <strong>{{ $activity->type_label ?? $activity->type }}</strong>
                            <p class="mb-1 text-muted small">{{ $activity->description ?? $activity->reference_number }}</p>
                            <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                        </div>
                        @empty
                        <p class="text-muted text-center py-3">No recent activity</p>
                        @endforelse
                    </div>
                </div>

                <!-- Low Stock Items -->
                @if($lowStockItems->isNotEmpty())
                <div class="block">
                    <div class="block-header block-header-default bg-danger">
                        <h3 class="block-title text-white">Low Stock Alerts</h3>
                    </div>
                    <div class="block-content">
                        @foreach($lowStockItems as $item)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>{{ Str::limit($item->boqItem?->description ?? 'Unknown', 20) }}</span>
                            <span class="badge badge-danger">{{ number_format($item->quantity_available, 2) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

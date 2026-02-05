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
            <i class="fa fa-hard-hat"></i> Labor Procurement Dashboard
            <div class="float-right">
                <a href="{{ route('labor.requests.index') }}" class="btn btn-rounded btn-outline-primary min-width-100 mb-10">
                    <i class="fa fa-clipboard-list"></i> Requests
                </a>
                <a href="{{ route('labor.contracts.index') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="fa fa-file-contract"></i> Contracts
                </a>
                <a href="{{ route('labor.payments.index') }}" class="btn btn-rounded btn-outline-success min-width-100 mb-10">
                    <i class="fa fa-money-bill-wave"></i> Payments
                </a>
            </div>
        </div>

        <!-- Project Filter -->
        <div class="row mb-3">
            <div class="col-md-4">
                <form method="get" class="d-flex">
                    <select name="project_id" class="form-control" onchange="this.form.submit()">
                        <option value="">All Projects</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ $selectedProject == $project->id ? 'selected' : '' }}>
                                {{ $project->project_name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card bg-primary">
                    <h3>{{ $activeContracts }}</h3>
                    <p>Active Contracts</p>
                    <small>{{ number_format($activeContractValue, 0) }} TZS</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning">
                    <h3>{{ $pendingRequests }}</h3>
                    <p>Pending Requests</p>
                    <small>Awaiting approval</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info">
                    <h3>{{ $pendingPaymentPhases }}</h3>
                    <p>Payments Due</p>
                    <small>{{ number_format($pendingPaymentAmount, 0) }} TZS</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success">
                    <h3>{{ $completedContracts }}</h3>
                    <p>Completed Contracts</p>
                    <small>{{ number_format($paidAmount, 0) }} TZS paid</small>
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
                        <a href="{{ route('labor.requests.index', ['status' => 'pending']) }}" class="action-item text-decoration-none">
                            <span>Requests Pending Approval</span>
                            <span class="action-count text-warning">{{ $pendingRequests }}</span>
                        </a>
                        <a href="{{ route('labor.inspections.index', ['status' => 'pending']) }}" class="action-item text-decoration-none">
                            <span>Inspections Pending</span>
                            <span class="action-count text-info">{{ $pendingInspections }}</span>
                        </a>
                        <a href="{{ route('labor.payments.index', ['status' => 'due']) }}" class="action-item text-decoration-none">
                            <span>Payments Due</span>
                            <span class="action-count text-primary">{{ $pendingPaymentPhases }}</span>
                        </a>
                    </div>
                </div>

                @if($contractsNearingEnd->count() > 0)
                <div class="block">
                    <div class="block-header block-header-default bg-warning">
                        <h3 class="block-title text-white"><i class="fa fa-clock"></i> Contracts Nearing End</h3>
                    </div>
                    <div class="block-content">
                        @foreach($contractsNearingEnd as $contract)
                            <div class="action-item">
                                <div>
                                    <a href="{{ route('labor.contracts.show', $contract->id) }}">
                                        <strong>{{ $contract->contract_number }}</strong>
                                    </a>
                                    <br>
                                    <small class="text-muted">{{ $contract->artisan?->name }}</small>
                                </div>
                                <span class="badge badge-warning">{{ $contract->days_remaining }} days</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($overdueContracts->count() > 0)
                <div class="block">
                    <div class="block-header block-header-default bg-danger">
                        <h3 class="block-title text-white"><i class="fa fa-exclamation-triangle"></i> Overdue Contracts</h3>
                    </div>
                    <div class="block-content">
                        @foreach($overdueContracts as $contract)
                            <div class="action-item">
                                <div>
                                    <a href="{{ route('labor.contracts.show', $contract->id) }}">
                                        <strong>{{ $contract->contract_number }}</strong>
                                    </a>
                                    <br>
                                    <small class="text-muted">{{ $contract->artisan?->name }}</small>
                                </div>
                                <span class="badge badge-danger">{{ $contract->days_overdue }} days overdue</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Recent Requests -->
            <div class="col-md-4">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><i class="fa fa-clipboard-list"></i> Recent Requests</h3>
                        <div class="block-options">
                            <a href="{{ route('labor.requests.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                    </div>
                    <div class="block-content">
                        @forelse($recentRequests as $request)
                            <div class="action-item">
                                <div>
                                    <a href="{{ route('labor.requests.show', $request->id) }}">
                                        <strong>{{ $request->request_number }}</strong>
                                    </a>
                                    <br>
                                    <small class="text-muted">{{ Str::limit($request->artisan?->name ?? 'No artisan', 25) }}</small>
                                </div>
                                <span class="badge badge-{{ $request->status_badge_class }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </div>
                        @empty
                            <p class="text-muted text-center py-3">No recent requests</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Recent Contracts -->
            <div class="col-md-4">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><i class="fa fa-file-contract"></i> Recent Contracts</h3>
                        <div class="block-options">
                            <a href="{{ route('labor.contracts.index') }}" class="btn btn-sm btn-outline-info">View All</a>
                        </div>
                    </div>
                    <div class="block-content">
                        @forelse($recentContracts as $contract)
                            <div class="action-item">
                                <div>
                                    <a href="{{ route('labor.contracts.show', $contract->id) }}">
                                        <strong>{{ $contract->contract_number }}</strong>
                                    </a>
                                    <br>
                                    <small class="text-muted">{{ Str::limit($contract->artisan?->name, 25) }}</small>
                                </div>
                                <div class="text-right">
                                    <span class="badge badge-{{ $contract->status_badge_class }}">
                                        {{ ucfirst($contract->status) }}
                                    </span>
                                    <br>
                                    <small>{{ number_format($contract->payment_progress, 0) }}% paid</small>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted text-center py-3">No recent contracts</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Inspections -->
        <div class="row">
            <div class="col-md-12">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><i class="fa fa-search-plus"></i> Recent Inspections</h3>
                        <div class="block-options">
                            <a href="{{ route('labor.inspections.index') }}" class="btn btn-sm btn-outline-success">View All</a>
                        </div>
                    </div>
                    <div class="block-content">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Inspection #</th>
                                        <th>Contract</th>
                                        <th>Artisan</th>
                                        <th>Type</th>
                                        <th>Completion</th>
                                        <th>Result</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentInspections as $inspection)
                                        <tr>
                                            <td>
                                                <a href="{{ route('labor.inspections.show', $inspection->id) }}">
                                                    {{ $inspection->inspection_number }}
                                                </a>
                                            </td>
                                            <td>{{ $inspection->contract?->contract_number }}</td>
                                            <td>{{ $inspection->contract?->artisan?->name }}</td>
                                            <td>
                                                <span class="badge badge-{{ $inspection->type_badge_class }}">
                                                    {{ ucfirst($inspection->inspection_type) }}
                                                </span>
                                            </td>
                                            <td>{{ number_format($inspection->completion_percentage, 1) }}%</td>
                                            <td>
                                                <span class="badge badge-{{ $inspection->result_badge_class }}">
                                                    {{ ucfirst($inspection->result) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $inspection->status_badge_class }}">
                                                    {{ ucfirst($inspection->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">No recent inspections</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

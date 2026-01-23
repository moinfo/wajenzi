@extends('layouts.backend')

@section('css')
<style>
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border: none;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        height: 100%;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    .stat-card .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .stat-card.total .stat-icon { background: rgba(52, 152, 219, 0.15); color: #3498db; }
    .stat-card.active .stat-icon { background: rgba(46, 204, 113, 0.15); color: #2ecc71; }
    .stat-card.completed .stat-icon { background: rgba(155, 89, 182, 0.15); color: #9b59b6; }
    .stat-card.delayed .stat-icon { background: rgba(231, 76, 60, 0.15); color: #e74c3c; }
    .stat-card.value .stat-icon { background: rgba(243, 156, 18, 0.15); color: #f39c12; }
    .stat-card .stat-info {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .stat-card .stat-value {
        font-size: 1.35rem;
        font-weight: 700;
        color: #1a202c;
        line-height: 1.2;
    }
    .stat-card .stat-label {
        font-size: 0.65rem;
        color: #718096;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        font-weight: 500;
        white-space: nowrap;
    }
    .project-id {
        font-family: monospace;
        font-size: 0.8rem;
        background: #f1f5f9;
        padding: 0.15rem 0.4rem;
        border-radius: 4px;
    }
    .delay-badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.5rem;
    }
    .delay-badge.positive { background: #fee2e2; color: #dc2626; }
    .delay-badge.negative { background: #d1fae5; color: #059669; }
    .delay-badge.neutral { background: #f3f4f6; color: #6b7280; }
    .contract-value {
        font-weight: 600;
        white-space: nowrap;
    }
    .user-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.75rem;
        background: #f8fafc;
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
    }
    .user-badge i { color: #94a3b8; }
    .table th {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background: #f8fafc;
    }
    .priority-badge {
        font-size: 0.65rem;
        padding: 0.15rem 0.4rem;
        border-radius: 3px;
    }
    .priority-low { background: #e2e8f0; color: #64748b; }
    .priority-normal { background: #dbeafe; color: #3b82f6; }
    .priority-high { background: #fef3c7; color: #d97706; }
    .priority-urgent { background: #fee2e2; color: #dc2626; }
</style>
@endsection

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fa fa-building text-primary mr-2"></i>Projects</h2>
                </div>
                <div>
                    @can('Create Project')
                        <button type="button" onclick="loadFormModal('project_form', {className: 'Project'}, 'Create New Project', 'modal-lg');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Project
                        </button>
                    @endcan
                </div>
            </div>

            <!-- Summary Statistics -->
            <div class="row mb-4">
                <div class="col">
                    <div class="stat-card total">
                        <div class="stat-icon"><i class="fa fa-folder-open"></i></div>
                        <div class="stat-info">
                            <div class="stat-value">{{ $stats['total'] }}</div>
                            <div class="stat-label">Total Projects</div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-card active">
                        <div class="stat-icon"><i class="fa fa-spinner"></i></div>
                        <div class="stat-info">
                            <div class="stat-value">{{ $stats['active'] }}</div>
                            <div class="stat-label">Active</div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-card completed">
                        <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
                        <div class="stat-info">
                            <div class="stat-value">{{ $stats['completed'] }}</div>
                            <div class="stat-label">Completed</div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-card delayed">
                        <div class="stat-icon"><i class="fa fa-exclamation-triangle"></i></div>
                        <div class="stat-info">
                            <div class="stat-value">{{ $stats['delayed'] }}</div>
                            <div class="stat-label">Delayed</div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-card value">
                        <div class="stat-icon"><i class="fa fa-money-bill-wave"></i></div>
                        <div class="stat-info">
                            <div class="stat-value">{{ number_format($stats['total_value'] / 1000000, 1) }}M</div>
                            <div class="stat-label">Total Value (TZS)</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">All Projects</h3>
                    <div class="block-options">
                        <button type="button" class="btn btn-sm btn-secondary" data-toggle="collapse" data-target="#filterSection">
                            <i class="fa fa-filter"></i> Filters
                        </button>
                    </div>
                </div>
                <div class="block-content">
                    <!-- Filters -->
                    <div class="collapse {{ request()->anyFilled(['project_type_id', 'service_type_id', 'status', 'salesperson_id', 'project_manager_id']) ? 'show' : '' }}" id="filterSection">
                        <div class="card card-body bg-light mb-3">
                            <form action="" id="filter-form" method="get" autocomplete="off">
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="small text-muted">Project Type</label>
                                            <select name="project_type_id" class="form-control form-control-sm">
                                                <option value="">All Types</option>
                                                @foreach ($projectTypes as $type)
                                                    <option value="{{ $type->id }}" {{ request('project_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="small text-muted">Service Type</label>
                                            <select name="service_type_id" class="form-control form-control-sm">
                                                <option value="">All Services</option>
                                                @foreach ($serviceTypes as $type)
                                                    <option value="{{ $type->id }}" {{ request('service_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="small text-muted">Status</label>
                                            <select name="status" class="form-control form-control-sm">
                                                <option value="">All Status</option>
                                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="APPROVED" {{ request('status') == 'APPROVED' ? 'selected' : '' }}>Approved</option>
                                                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                                <option value="COMPLETED" {{ request('status') == 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                                                <option value="on_hold" {{ request('status') == 'on_hold' ? 'selected' : '' }}>On Hold</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="small text-muted">Salesperson</label>
                                            <select name="salesperson_id" class="form-control form-control-sm">
                                                <option value="">All</option>
                                                @foreach ($salespersons as $person)
                                                    <option value="{{ $person->id }}" {{ request('salesperson_id') == $person->id ? 'selected' : '' }}>{{ $person->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="small text-muted">Project Manager</label>
                                            <select name="project_manager_id" class="form-control form-control-sm">
                                                <option value="">All</option>
                                                @foreach ($projectManagers as $manager)
                                                    <option value="{{ $manager->id }}" {{ request('project_manager_id') == $manager->id ? 'selected' : '' }}>{{ $manager->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="small text-muted">&nbsp;</label>
                                            <div>
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <i class="fa fa-search"></i> Filter
                                                </button>
                                                <a href="{{ route('projects') }}" class="btn btn-sm btn-secondary">
                                                    <i class="fa fa-times"></i> Clear
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter table-sm js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 60px;">Project ID</th>
                                <th>Project Name</th>
                                <th>Client</th>
                                <th>Category</th>
                                <th>Service Type</th>
                                <th class="text-center">Status</th>
                                <th>Start Date</th>
                                <th>Expected End</th>
                                <th>Actual End</th>
                                <th class="text-center">Planned<br>(Days)</th>
                                <th class="text-center">Actual<br>(Days)</th>
                                <th class="text-center">Delay<br>(Days)</th>
                                <th class="text-right">Contract Value</th>
                                <th>Salesperson</th>
                                <th>Project Manager</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($projects as $project)
                                @php
                                    $delay = $project->delay;
                                    $delayClass = $delay > 0 ? 'positive' : ($delay < 0 ? 'negative' : 'neutral');
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'Pending' => 'warning',
                                        'Submitted' => 'info',
                                        'APPROVED' => 'success',
                                        'Approved' => 'success',
                                        'in_progress' => 'primary',
                                        'COMPLETED' => 'success',
                                        'Completed' => 'success',
                                        'on_hold' => 'secondary',
                                        'Rejected' => 'danger',
                                        'cancelled' => 'danger',
                                    ];
                                    $approvalStatus = $project->approvalStatus?->status ?? $project->status ?? 'pending';
                                @endphp
                                <tr id="project-tr-{{$project->id}}" class="{{ $project->isDelayed() ? 'table-warning' : '' }}">
                                    <td class="text-center">
                                        <span class="project-id">{{ $project->document_number ?? 'PRJ-'.$project->id }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $project->project_name }}</strong>
                                        @if($project->priority && $project->priority !== 'normal')
                                            <span class="priority-badge priority-{{ $project->priority }}">{{ ucfirst($project->priority) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($project->client)
                                            {{ $project->client->first_name }} {{ $project->client->last_name }}
                                            <br><small class="text-muted">ID: {{ $project->client_id }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $project->projectType->name ?? '-' }}</td>
                                    <td>{{ $project->serviceType->name ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $statusColors[$approvalStatus] ?? 'secondary' }}">
                                            {{ ucwords(str_replace('_', ' ', $approvalStatus)) }}
                                        </span>
                                    </td>
                                    <td>{{ $project->start_date ? $project->start_date->format('d/m/Y') : '-' }}</td>
                                    <td>{{ $project->expected_end_date ? $project->expected_end_date->format('d/m/Y') : '-' }}</td>
                                    <td>{{ $project->actual_end_date ? $project->actual_end_date->format('d/m/Y') : '-' }}</td>
                                    <td class="text-center">{{ $project->planned_duration ?? '-' }}</td>
                                    <td class="text-center">{{ $project->actual_duration ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($delay !== null)
                                            <span class="delay-badge {{ $delayClass }}">
                                                @if($delay > 0)
                                                    +{{ $delay }}
                                                @elseif($delay < 0)
                                                    {{ $delay }}
                                                @else
                                                    0
                                                @endif
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if($project->contract_value)
                                            <span class="contract-value">TZS {{ number_format($project->contract_value, 0) }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($project->salesperson)
                                            <span class="user-badge">
                                                <i class="fa fa-user"></i>
                                                {{ Str::limit($project->salesperson->name, 15) }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($project->projectManager)
                                            <span class="user-badge">
                                                <i class="fa fa-user-tie"></i>
                                                {{ Str::limit($project->projectManager->name, 15) }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a class="btn btn-sm btn-success js-tooltip-enabled"
                                               href="{{ route('individual_projects', [$project->id, 10]) }}"
                                               title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @can('Edit Project')
                                                <button type="button"
                                                        onclick="loadFormModal('project_form', {className: 'Project', id: {{$project->id}}}, 'Edit {{$project->project_name}}', 'modal-lg');"
                                                        class="btn btn-sm btn-primary"
                                                        title="Edit">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>
                                            @endcan
                                            @can('Delete Project')
                                                <button type="button"
                                                        onclick="deleteModelItem('Project', {{$project->id}}, 'project-tr-{{$project->id}}');"
                                                        class="btn btn-sm btn-danger"
                                                        title="Delete">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @endcan
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

@section('js')
<script>
    // Initialize DataTable with horizontal scroll
    $(document).ready(function() {
        if ($.fn.DataTable.isDataTable('.js-dataTable-full')) {
            $('.js-dataTable-full').DataTable().destroy();
        }
        $('.js-dataTable-full').DataTable({
            pageLength: 25,
            scrollX: true,
            order: [[0, 'desc']],
            columnDefs: [
                { orderable: false, targets: -1 } // Disable sorting on Actions column
            ]
        });
    });
</script>
@endsection

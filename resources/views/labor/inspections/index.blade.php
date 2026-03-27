@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <i class="fa fa-search-plus"></i> Labor Inspections
            <div class="float-right">
                <a href="{{ route('labor.dashboard') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="fa fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
        </div>

        @if($contractsPendingInspection->count() > 0)
        <div class="block">
            <div class="block-header block-header-default bg-warning">
                <h3 class="block-title text-white">
                    <i class="fa fa-clock"></i> Contracts Ready for Inspection ({{ $contractsPendingInspection->count() }})
                </h3>
            </div>
            <div class="block-content">
                <!-- Desktop table view -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Contract #</th>
                                <th>Project</th>
                                <th>Artisan</th>
                                <th>Current Progress</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contractsPendingInspection as $contract)
                                <tr>
                                    <td>{{ $contract->contract_number }}</td>
                                    <td>{{ $contract->project?->project_name }}</td>
                                    <td>{{ $contract->artisan?->name }}</td>
                                    <td>{{ number_format($contract->latest_progress, 1) }}%</td>
                                    <td class="text-center">
                                        <a href="{{ route('labor.inspections.create', $contract->id) }}"
                                            class="btn btn-sm btn-primary">
                                            <i class="fa fa-clipboard-check"></i> Create Inspection
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile card view -->
                <div class="d-md-none">
                    @foreach($contractsPendingInspection as $contract)
                        <div class="card mb-3 border-warning">
                            <div class="card-header bg-warning text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $contract->contract_number }}</strong>
                                        <br>
                                        <small>{{ $contract->project?->project_name }}</small>
                                    </div>
                                    <div class="text-right">
                                        <strong>{{ number_format($contract->latest_progress, 1) }}%</strong>
                                        <br>
                                        <small>Complete</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12">
                                        <strong>Artisan:</strong> {{ $contract->artisan?->name }}
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="{{ route('labor.inspections.create', $contract->id) }}"
                                    class="btn btn-primary btn-block">
                                    <i class="fa fa-clipboard-check"></i> Create Inspection
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">All Inspections</h3>
            </div>
            <div class="block-content">
                <form method="get" id="filter-form" autocomplete="off">
                    <div class="row mb-3">
                        <!-- Mobile-optimized filter layout -->
                        <div class="col-6 col-md-2 mb-2">
                            <input type="date" name="start_date" class="form-control"
                                value="{{ $start_date }}" placeholder="Start Date">
                        </div>
                        <div class="col-6 col-md-2 mb-2">
                            <input type="date" name="end_date" class="form-control"
                                value="{{ $end_date }}" placeholder="End Date">
                        </div>
                        <div class="col-12 col-md-3 mb-2">
                            <select name="project_id" class="form-control">
                                <option value="">All Projects</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ $selected_project == $project->id ? 'selected' : '' }}>
                                        {{ $project->project_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2 mb-2">
                            <select name="status" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="draft" {{ $selected_status == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="pending" {{ $selected_status == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="verified" {{ $selected_status == 'verified' ? 'selected' : '' }}>Verified</option>
                                <option value="approved" {{ $selected_status == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ $selected_status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2 mb-2">
                            <select name="inspection_type" class="form-control">
                                <option value="">All Types</option>
                                <option value="routine" {{ $selected_type == 'routine' ? 'selected' : '' }}>Routine</option>
                                <option value="progress" {{ $selected_type == 'progress' ? 'selected' : '' }}>Progress</option>
                                <option value="milestone" {{ $selected_type == 'milestone' ? 'selected' : '' }}>Milestone</option>
                                <option value="quality" {{ $selected_type == 'quality' ? 'selected' : '' }}>Quality</option>
                                <option value="final" {{ $selected_type == 'final' ? 'selected' : '' }}>Final</option>
                                <option value="safety" {{ $selected_type == 'safety' ? 'selected' : '' }}>Safety</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2 mb-2">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Mobile-first table with responsive design -->
                <div class="table-responsive">
                    <div class="d-none d-md-block">
                        <!-- Desktop table view -->
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px;">#</th>
                                    <th>Inspection #</th>
                                    <th>Contract</th>
                                    <th>Artisan</th>
                                    <th class="text-center">Type</th>
                                    <th class="text-center">Completion</th>
                                    <th class="text-center">Quality</th>
                                    <th class="text-center">Result</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($inspections as $inspection)
                                    <tr>
                                        <td class="text-center">{{ $loop->index + 1 }}</td>
                                        <td>
                                            <a href="{{ route('labor.inspections.show', $inspection->id) }}">
                                                <strong>{{ $inspection->inspection_number }}</strong>
                                            </a>
                                            <br>
                                            <small class="text-muted">{{ $inspection->inspection_date?->format('Y-m-d') }}</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('labor.contracts.show', $inspection->labor_contract_id) }}">
                                                {{ $inspection->contract?->contract_number }}
                                            </a>
                                        </td>
                                        <td>{{ $inspection->contract?->artisan?->name }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-{{ $inspection->type_badge_class }}">
                                                {{ ucfirst($inspection->inspection_type) }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ number_format($inspection->completion_percentage, 1) }}%</td>
                                        <td class="text-center">
                                            <span class="badge badge-{{ $inspection->quality_badge_class }}">
                                                {{ ucfirst($inspection->work_quality) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-{{ $inspection->result_badge_class }}">
                                                {{ ucfirst($inspection->result) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-{{ $inspection->status_badge_class }}">
                                                {{ ucfirst($inspection->status) }}
                                            </span>
                                            @if(!$inspection->isDraft())
                                                <br>
                                                <x-ringlesoft-approval-status-summary :model="$inspection" />
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('labor.inspections.show', $inspection->id) }}"
                                                class="btn btn-sm btn-info" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile card view -->
                    <div class="d-md-none">
                        @foreach($inspections as $inspection)
                            <div class="card mb-3">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $inspection->inspection_number }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $inspection->inspection_date?->format('Y-m-d') }}</small>
                                    </div>
                                    <div class="text-right">
                                        <span class="badge badge-{{ $inspection->status_badge_class }}">
                                            {{ ucfirst($inspection->status) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-2">
                                        <div class="col-6">
                                            <strong>Contract:</strong><br>
                                            <a href="{{ route('labor.contracts.show', $inspection->labor_contract_id) }}">
                                                {{ $inspection->contract?->contract_number }}
                                            </a>
                                        </div>
                                        <div class="col-6">
                                            <strong>Artisan:</strong><br>
                                            {{ $inspection->contract?->artisan?->name }}
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-4">
                                            <span class="badge badge-{{ $inspection->type_badge_class }}">
                                                {{ ucfirst($inspection->inspection_type) }}
                                            </span>
                                        </div>
                                        <div class="col-4 text-center">
                                            <strong>{{ number_format($inspection->completion_percentage, 1) }}%</strong>
                                            <br>
                                            <small class="text-muted">Complete</small>
                                        </div>
                                        <div class="col-4 text-right">
                                            <span class="badge badge-{{ $inspection->quality_badge_class }}">
                                                {{ ucfirst($inspection->work_quality) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-12 text-center">
                                            <span class="badge badge-{{ $inspection->result_badge_class }}">
                                                Result: {{ ucfirst($inspection->result) }}
                                            </span>
                                        </div>
                                    </div>
                                    @if(!$inspection->isDraft())
                                        <div class="row mb-2">
                                            <div class="col-12">
                                                <x-ringlesoft-approval-status-summary :model="$inspection" />
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="card-footer">
                                    <a href="{{ route('labor.inspections.show', $inspection->id) }}"
                                        class="btn btn-primary btn-block">
                                        <i class="fa fa-eye"></i> View Details
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js_after')
<script>
    $(document).ready(function() {
        // Initialize DataTables for desktop only
        if (window.innerWidth >= 768) {
            $('.js-dataTable-full').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[1, 'desc']], // Sort by inspection date
                language: {
                    search: 'Search inspections:',
                    lengthMenu: 'Show _MENU_ inspections',
                    zeroRecords: 'No inspections found',
                    info: 'Showing _START_ to _END_ of _TOTAL_ inspections',
                    infoEmpty: 'No inspections available',
                    infoFiltered: '(filtered from _MAX_ total inspections)'
                }
            });
        }

        // Mobile-friendly date inputs (native HTML5 date picker)
        if (window.innerWidth < 768) {
            $('input[type="date"]').each(function() {
                // Add mobile-friendly styling
                $(this).addClass('form-control');
            });
        }

        // Handle filter form submission
        $('#filter-form').on('submit', function(e) {
            // Show loading state on mobile
            if (window.innerWidth < 768) {
                $('button[type="submit"]').html('<i class="fa fa-spinner fa-spin"></i> Loading...').prop('disabled', true);
            }
        });
    });

    // Handle window resize
    $(window).on('resize', function() {
        // Reinitialize DataTables if switching from mobile to desktop
        if (window.innerWidth >= 768 && !$.fn.dataTable.isDataTable('.js-dataTable-full')) {
            location.reload(); // Simple reload for proper initialization
        }
    });
</script>

<style>
/* Mobile-specific styles */
@media (max-width: 767.98px) {
    .card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border: 1px solid #dee2e6;
    }
    
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        padding: 0.75rem 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .badge {
        font-size: 0.75em;
        padding: 0.25em 0.5em;
    }
    
    .btn-block {
        width: 100%;
    }
    
    /* Touch-friendly spacing */
    .form-control {
        min-height: 44px; /* iOS touch target size */
    }
    
    .btn {
        min-height: 44px; /* iOS touch target size */
        margin-bottom: 0.5rem;
    }
    
    /* Better mobile table scrolling */
    .table-responsive {
        -webkit-overflow-scrolling: touch;
    }
}

/* Desktop optimizations */
@media (min-width: 768px) {
    .js-dataTable-full {
        font-size: 0.875rem;
    }
    
    .js-dataTable-full .badge {
        font-size: 0.7em;
    }
}
</style>
@endsection

@extends('layouts.backend')

@section('css_after')
    <style>
        .positions-summary-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            padding: 1rem 1.25rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
            height: 100%;
        }

        .positions-summary-label {
            color: #64748b;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .positions-summary-value {
            color: #0f172a;
            font-size: 1.8rem;
            font-weight: 800;
            line-height: 1.1;
            margin-top: 0.5rem;
        }

        .positions-mobile-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #fff;
            padding: 1rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .positions-mobile-card + .positions-mobile-card {
            margin-top: 1rem;
        }

        .positions-mobile-meta {
            color: #64748b;
            font-size: 0.85rem;
        }

        .positions-description {
            color: #334155;
            font-size: 0.92rem;
            line-height: 1.5;
        }

        @media (max-width: 767.98px) {
            .positions-page-header {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
            }

            .positions-page-header .float-right {
                float: none !important;
                width: 100%;
            }

            .positions-page-header .btn {
                width: 100%;
            }

            .positions-table-wrap {
                display: none;
            }
        }

        @media (min-width: 768px) {
            .positions-mobile-list {
                display: none;
            }
        }
    </style>
@endsection

@section('js_after')
    <script>
        function deletePositionItem(id) {
            Utility.swalConfirm('Are you sure you want to delete this Position?', 'Delete Position', {type: 'question'}, function(res) {
                if (res) {
                    Utility.deleteModelObject('Position', id, function() {
                        Swal.fire('Deleted!', 'Position deleted successfully', 'success');
                        $('#position-row-' + id).hide();
                        $('#position-mobile-' + id).hide();
                    }, function() {
                        Utility.swal('Error', 'Something went wrong!', 'error');
                    }, false);
                }
            });
        }
    </script>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading positions-page-header">Settings
                <div class="float-right">
                    @can('Add Position')
                        <button
                            type="button"
                            onclick="loadFormModal('settings_position_form', {className: 'Position', metadata: {positions: 'Position'}}, 'Create New Position', 'modal-md');"
                            class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"
                        >
                            <i class="si si-plus">&nbsp;</i>New Position
                        </button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Positions</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <div class="row mb-4">
                            <div class="col-12 col-md-4 mb-3">
                                <div class="positions-summary-card">
                                    <div class="positions-summary-label">Total Positions</div>
                                    <div class="positions-summary-value">{{ $positions->count() }}</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 mb-3">
                                <div class="positions-summary-card">
                                    <div class="positions-summary-label">Active</div>
                                    <div class="positions-summary-value">{{ $positions->where('status', 'ACTIVE')->count() }}</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 mb-3">
                                <div class="positions-summary-card">
                                    <div class="positions-summary-label">Inactive</div>
                                    <div class="positions-summary-value">{{ $positions->where('status', 'INACTIVE')->count() }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="positions-mobile-list">
                            @forelse($positions as $position)
                                <div class="positions-mobile-card" id="position-mobile-{{ $position->id }}">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <div class="font-w700 text-dark">{{ $position->name }}</div>
                                            <div class="positions-mobile-meta">{{ $position->abbreviation }}</div>
                                        </div>
                                        <span class="badge badge-{{ $position->status === 'ACTIVE' ? 'success' : 'secondary' }}">
                                            {{ $position->status }}
                                        </span>
                                    </div>

                                    <div class="positions-mobile-meta mb-2">
                                        Reports To: {{ $position->reportsTo->name ?? 'Not assigned' }}
                                    </div>

                                    <div class="positions-description mb-3">
                                        {{ $position->description ?: 'No description added for this position yet.' }}
                                    </div>

                                    <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                                        @can('Edit Position')
                                            <button
                                                type="button"
                                                onclick="loadFormModal('settings_position_form', {className: 'Position', id: {{ $position->id }}, metadata: {positions: 'Position'}}, 'Edit {{ addslashes($position->name) }}', 'modal-md');"
                                                class="btn btn-sm btn-primary"
                                            >
                                                <i class="fa fa-pencil"></i> Edit
                                            </button>
                                        @endcan

                                        @can('Delete Position')
                                            <button
                                                type="button"
                                                onclick="deletePositionItem({{ $position->id }})"
                                                class="btn btn-sm btn-danger"
                                            >
                                                <i class="fa fa-times"></i> Delete
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            @empty
                                <div class="alert alert-info">
                                    No positions have been created yet.
                                </div>
                            @endforelse
                        </div>

                        <div class="table-responsive positions-table-wrap">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 80px;">#</th>
                                    <th>Name</th>
                                    <th>Abbreviation</th>
                                    <th>Reports To</th>
                                    <th>Status</th>
                                    <th>Description</th>
                                    <th class="text-center" style="width: 120px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($positions as $position)
                                    <tr id="position-row-{{ $position->id }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td class="font-w600">{{ $position->name }}</td>
                                        <td>{{ $position->abbreviation }}</td>
                                        <td>{{ $position->reportsTo->name ?? 'Not assigned' }}</td>
                                        <td>
                                            <span class="badge badge-{{ $position->status === 'ACTIVE' ? 'success' : 'secondary' }}">
                                                {{ $position->status }}
                                            </span>
                                        </td>
                                        <td>{{ \Illuminate\Support\Str::limit($position->description ?? 'No description', 80) }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                @can('Edit Position')
                                                    <button
                                                        type="button"
                                                        onclick="loadFormModal('settings_position_form', {className: 'Position', id: {{ $position->id }}, metadata: {positions: 'Position'}}, 'Edit {{ addslashes($position->name) }}', 'modal-md');"
                                                        class="btn btn-sm btn-primary js-tooltip-enabled"
                                                        data-toggle="tooltip"
                                                        title="Edit"
                                                    >
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan

                                                @can('Delete Position')
                                                    <button
                                                        type="button"
                                                        onclick="deletePositionItem({{ $position->id }})"
                                                        class="btn btn-sm btn-danger js-tooltip-enabled"
                                                        data-toggle="tooltip"
                                                        title="Delete"
                                                    >
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No positions have been created yet.</td>
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
@endsection

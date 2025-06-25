@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <!-- Page Header -->
            <div class="bg-white rounded shadow-sm p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="font-weight-bold mb-0">Approval Flows Settings</h2>
                    @can('Add Approval Flow')
                        <button type="button"
                                onclick="loadFormModal('settings_process_approval_flow_form', {className: 'ProcessApprovalFlow'}, 'Create New Approval Flow', 'modal-md');"
                                class="btn btn-primary btn-lg rounded-pill">
                            <i class="si si-plus mr-1"></i>New Approval Flow
                        </button>
                    @endcan
                </div>
            </div>

            @include('components.headed_paper_settings')

            <!-- Results Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="font-weight-bold mb-0 text-white">Approval Flows List</h5>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-light" data-toggle="tooltip" title="Export to Excel">
                                <i class="fa fa-file-excel-o"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-light" data-toggle="tooltip" title="Print">
                                <i class="fa fa-print"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 js-dataTable-full">
                            <thead>
                                <tr class="bg-light">
                                    <th class="text-center border-top-0" width="10%">#</th>
                                    <th class="border-top-0" width="40%">Name</th>
                                    <th class="border-top-0" width="35%">Approvable Type</th>
                                    <th class="text-center border-top-0" width="15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($process_approval_flows as $key => $value)
                                    <tr id="process_approval_flow-tr-{{$value->id}}">
                                        <td class="text-center">{{$loop->iteration}}</td>
                                        <td class="font-weight-bold">{{ $value->name }}</td>
                                        <td><span class="badge badge-pill badge-light">{{ $value->approvable_type }}</span></td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                @can('Edit Approval Flow')
                                                    <button type="button"
                                                            onclick="loadFormModal('settings_process_approval_flow_form', {className: 'ProcessApprovalFlow', id: {{$value->id}}}, 'Edit {{$value->name}}', 'modal-md');"
                                                            class="btn btn-info rounded-left"
                                                            data-toggle="tooltip"
                                                            title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan

                                                @can('Delete Approval Flow')
                                                    <button type="button"
                                                            onclick="deleteModelItem('ProcessApprovalFlow', {{$value->id}}, 'process_approval_flow-tr-{{$value->id}}');"
                                                            class="btn btn-danger rounded-right"
                                                            data-toggle="tooltip"
                                                            title="Delete">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                @if(count($process_approval_flows) == 0)
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <div class="my-4">
                                                <i class="fa fa-database fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No approval flows found</p>
                                                @can('Add Approval Flow')
                                                    <button type="button"
                                                            onclick="loadFormModal('settings_process_approval_flow_form', {className: 'ProcessApprovalFlow'}, 'Create New Approval Flow', 'modal-md');"
                                                            class="btn btn-sm btn-primary mt-2">
                                                        <i class="si si-plus mr-1"></i>Add Your First Approval Flow
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer bg-white d-flex justify-content-between">
                    <div class="text-muted small">Showing {{ count($process_approval_flows) }} records</div>
                    <div class="pagination">
                        <!-- Pagination controls would go here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js_after')
    <script>
        $(function() {
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
@endsection


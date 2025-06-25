@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <!-- Page Header -->
            <div class="bg-white rounded shadow-sm p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="font-weight-bold mb-0">Approval Flow Steps Settings</h2>
                    @can('Add Approval Flow Step')
                        <button type="button"
                                onclick="loadFormModal('settings_process_approval_flow_step_form', {className: 'ProcessApprovalFlowStep'}, 'Create New Approval Flow Step', 'modal-md');"
                                class="btn btn-primary btn-lg rounded-pill">
                            <i class="si si-plus mr-1"></i>New Approval Flow Step
                        </button>
                    @endcan
                </div>
            </div>

            @include('components.headed_paper_settings')

            <!-- Results Card -->
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 js-dataTable-full">
                            <thead>
                                <tr class="bg-light">
                                    <th class="text-center border-top-0" width="5%">#</th>
                                    <th class="border-top-0" width="25%">Approval Name</th>
                                    <th class="border-top-0" width="25%">Role</th>
                                    <th class="border-top-0" width="20%">Action</th>
                                    <th class="border-top-0" width="10%">Order</th>
                                    <th class="text-center border-top-0" width="15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($process_approval_flow_steps as $key => $value)
                                    <tr id="process_approval_flow_step-tr-{{$value->id}}">
                                        <td class="text-center">{{$loop->iteration}}</td>
                                        <td class="font-weight-bold">{{ $value->process_approval_flow->name }}</td>
                                        <td>{{ $value->role->name }}</td>
                                        <td><span class="badge badge-pill badge-light">{{ $value->action }}</span></td>
                                        <td class="text-center">{{ $value->order }}</td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                @can('Edit Approval Flow Step')
                                                    <button type="button"
                                                            onclick="loadFormModal('settings_process_approval_flow_step_form', {className: 'ProcessApprovalFlowStep', id: {{$value->id}}}, 'Edit {{$value->name}}', 'modal-md');"
                                                            class="btn btn-info rounded-left"
                                                            data-toggle="tooltip"
                                                            title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan

                                                @can('Delete Approval Flow Step')
                                                    <button type="button"
                                                            onclick="deleteModelItem('ProcessApprovalFlowStep', {{$value->id}}, 'process_approval_flow_step-tr-{{$value->id}}');"
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

                                @if(count($process_approval_flow_steps) == 0)
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="my-4">
                                                <i class="fa fa-database fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No approval flow steps found</p>
                                                @can('Add Approval Flow Step')
                                                    <button type="button"
                                                            onclick="loadFormModal('settings_process_approval_flow_step_form', {className: 'ProcessApprovalFlowStep'}, 'Create New Approval Flow Step', 'modal-md');"
                                                            class="btn btn-sm btn-primary mt-2">
                                                        <i class="si si-plus mr-1"></i>Add Your First Approval Flow Step
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
                    <div class="text-muted small">Showing {{ count($process_approval_flow_steps) }} records</div>
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

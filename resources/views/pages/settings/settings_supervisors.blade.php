@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    @can('Add Supervisor')
                        <button type="button" onclick="loadFormModal('settings_supervisor_form', {className: 'Supervisor'}, 'Create New Supervisor', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Supervisor</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Supervisors</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Name</th>
                                <th>Phone Number</th>
                                <th>Employee_type</th>
                                <th>System</th>
                                <th class="d-none d-sm-table-cell" style="width: 30%;">Other Details</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($supervisors as $supervisor)
                                <?php
                                    if($supervisor->employee_id == 1){
                                        $employee_type = 'Supervisor';
                                    }else{
                                        $employee_type = 'Driver';
                                    }
                                ?>
                                <tr id="supervisor-tr-{{$supervisor->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $supervisor->name }}</td>
                                    <td class="font-w600">{{ $supervisor->phone }}</td>
                                    <td class="font-w600">{{ $employee_type }}</td>
                                    <td class="font-w600">{{ $supervisor->system->name }}</td>
                                    <td class="d-none d-sm-table-cell">{{ $supervisor->details }}
                                    </td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit Supervisor')
                                                <button type="button" onclick="loadFormModal('settings_supervisor_form', {className: 'Supervisor', id: {{$supervisor->id}}}, 'Edit {{$supervisor->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                                @can('Delete Supervisor')
                                                    <button type="button" onclick="deleteModelItem('Supervisor', {{$supervisor->id}}, 'supervisor-tr-{{$supervisor->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
                                                        <i class="fa fa-times"></i>
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

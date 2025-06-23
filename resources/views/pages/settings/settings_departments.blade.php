@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Staff Departments
                <div class="float-right">
                    @can('Add Department')
                        <button type="button" onclick="loadFormModal('settings_department_form', {className: 'Department'}, 'Create New Department', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10">
                            <i class="si si-plus">&nbsp;</i>New Department</button>
                    @endcan

                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Staff Departments</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Name</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($departments as $department)
                                <tr id="department-tr-{{$department->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $department->name }}</td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit Department')
                                                <button type="button" onclick="loadFormModal('settings_department_form', {className: 'Department', id: {{$department->id}}}, 'Edit {{$department->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                            @can('Delete Department')
                                                <button type="button" onclick="deleteModelItem('Department', {{$department->id}}, 'department-tr-{{$department->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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

@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Attendance Types
                <div class="float-right">
                    @can('Add Attendance Type')
                        <button type="button" onclick="loadFormModal('settings_attendance_type_form', {className: 'AttendanceType'}, 'Create New Attendance Type', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Attendance Type</button>
                    @endcan

                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Attendance Types</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($attendance_types as $attendance_type)
                                <tr id="attendance_type-tr-{{$attendance_type->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $attendance_type->name }}</td>
                                    <td>{{ $attendance_type->description ?? 'No description' }}</td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit Attendance Type')
                                                <button type="button" onclick="loadFormModal('settings_attendance_type_form', {className: 'AttendanceType', id: {{$attendance_type->id}}}, 'Edit {{$attendance_type->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                            @can('Delete Attendance Type')
                                                <button type="button" onclick="deleteModelItem('AttendanceType', {{$attendance_type->id}}, 'attendance_type-tr-{{$attendance_type->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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
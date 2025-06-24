@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Leave Types
                <div class="float-right">
                    @can('Add Leave Type')
                        <button type="button" onclick="loadFormModal('settings_leave_type_form', {className: 'LeaveType'}, 'Create New LeaveType', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Leave Type</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Leave Types</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Name</th>
                                <th>Days Allowed</th>
                                <th>Description</th>
                                <th>Notice Days</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($leave_types as $leave_type)
                                <tr id="leave_type-tr-{{$leave_type->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $leave_type->name ?? null}}</td>
                                    <td class="font-w600">{{ $leave_type->days_allowed?? null }}</td>
                                    <td class="font-w600">{{ $leave_type->description?? null }}</td>
                                    <td class="font-w600">{{ $leave_type->notice_days?? null }}</td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            @can('Edit Leave Type')
                                                <button type="button" onclick="loadFormModal('settings_leave_type_form', {className: 'LeaveType', id: {{$leave_type->id}}}, 'Edit {{$leave_type->name ?? null}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                                @can('Delete Leave Type')
                                                    <button type="button" onclick="deleteModelItem('LeaveType', {{$leave_type->id}}, 'leave_type-tr-{{$leave_type->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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

@extends('layouts.backend')
@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Permissions
                <div class="float-right">
                    {{--                    @can('Add Permission')--}}
                    <button type="button" onclick="loadFormModal('settings_permission_form', {className: 'Permission'}, 'Create New Permission', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Permission</button>
                    {{--                    @endcan--}}
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Permissions</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Name</th>
                                <th>Permission Type</th>
                                <th>Created at</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($permissions as $permission)
                                <tr id="permission-tr-{{$permission->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $permission->name }}</td>
                                    <td class="font-w600">{{ $permission->permission_type }}</td>
                                    <td class="font-w600">{{ $permission->created_at->diffForHumans() }}</td>
                                    <td class="text-center" >

                                        <div class="btn-group">
                                            {{--                                            @can('Edit Permission')--}}
                                            <button type="button" onclick="loadFormModal('settings_permission_form', {className: 'Permission', id: {{$permission->id}}}, 'Edit {{$permission->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            {{--                                            @endcan--}}
                                            {{--                                            @can('Delete Permission')--}}
                                            <button type="button" onclick="deleteModelItem('Permission', {{$permission->id}}, 'permission-tr-{{$permission->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
                                                <i class="fa fa-times"></i>
                                            </button>
                                            {{--                                            @endcan--}}

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

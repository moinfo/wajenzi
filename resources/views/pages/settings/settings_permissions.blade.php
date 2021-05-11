@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Permissions
                <div class="float-right">
                    @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Add Permission"))
                        <button type="button" onclick="loadFormModal('settings_permission_form', {className: 'Permission'}, 'Create New Permission', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Permission</button>
                    @endif
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Staff Permissions</h3>
                    </div>
                    <div class="block-content">
                        <p>This is a list containing all the possible permissions that staff can be subscribed to</p>
                        <table class="table table-striped table-vcenter">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Name</th>
                                <th class="d-none d-sm-table-cell" style="width: 30%;">Description</th>
                                <th class="d-none d-sm-table-cell" style="width: 30%;">Permission TYpe</th>
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
                                    <td class="d-none d-sm-table-cell">{{ $permission->description }}
                                    <td class="d-none d-sm-table-cell">{{ $permission->permission_type }}
                                    </td>
                                    <td class="text-center" >

                                        <div class="btn-group">
                                            @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Edit Permission"))
                                                <button type="button" onclick="loadFormModal('settings_permission_form', {className: 'Permission', id: {{$permission->id}}}, 'Edit {{$permission->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endif
                                                @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Delete Permission"))
                                                    <button type="button" onclick="deleteModelItem('Permission', {{$permission->id}}, 'permission-tr-{{$permission->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                @endif

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

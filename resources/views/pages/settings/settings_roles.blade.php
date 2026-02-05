@extends('layouts.backend')
@section('content')

    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Roles
                <div class="float-right">
                    @can('Add Role')
                        <button type="button"
                                onclick="loadFormModal('settings_role_form', {className: 'Role'}, 'Create New Role', 'modal-md');"
                                class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New
                            Role
                        </button>
                    @endcan
                </div>
            </div>

            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Roles</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Name</th>
                                <th>Created at</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($roles as $role)
                                <tr id="role-tr-{{$role->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $role->name }}</td>
                                    <td class="font-w600">{{ $role->created_at->diffForHumans() }}</td>
                                    <td class="text-center">

                                        <div class="btn-group">
                                            @can('Assign User To Roles')
                                                <button type="button"
                                                        onclick="loadFormModal('settings_user_roles_form', {className: 'UsersPermission', role_id: {{$role->id}}}, 'Permission For {{$role->name}}', 'modal-lg');"
                                                        class="btn btn-sm btn-primary js-tooltip-enabled"
                                                        data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-users"></i>
                                                </button>
                                            @endcan
                                            @can('Assign Role To Permissions')

                                                <button type="button"
                                                        onclick="loadFormModal('settings_role_permissions_form', {className: 'UsersPermission', role_id: {{$role->id}}}, 'Permission For {{$role->name}}', 'modal-lg');"
                                                        class="btn btn-sm btn-primary js-tooltip-enabled"
                                                        data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-cog"></i>
                                                </button>
                                            @endcan
                                            @can('Edit Role')
                                                <button type="button"
                                                        onclick="loadFormModal('settings_role_form', {className: 'Role', id: {{$role->id}}}, 'Edit {{$role->name}}', 'modal-md');"
                                                        class="btn btn-sm btn-primary js-tooltip-enabled"
                                                        data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan
                                            @can('Delete Role')
                                                <button type="button"
                                                        onclick="deleteModelItem('Role', {{$role->id}}, 'role-tr-{{$role->id}}');"
                                                        class="btn btn-sm btn-danger js-tooltip-enabled"
                                                        data-toggle="tooltip" title="Delete"
                                                        data-original-title="Delete">
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

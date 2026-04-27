@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    @if($showInactive ?? false)
                        <a href="{{ route('hr_settings_users') }}" class="btn btn-rounded min-width-125 mb-10 btn-success">
                            <i class="fa fa-users">&nbsp;</i>Active Users
                        </a>
                    @else
                        <a href="{{ route('hr_settings_users', ['status' => 'INACTIVE']) }}" class="btn btn-rounded min-width-125 mb-10 btn-secondary">
                            <i class="fa fa-user-times">&nbsp;</i>Inactive Users
                        </a>
                    @endif
                    @can('Add User')
                        <button type="button" onclick="loadFormModal('settings_user_form', {className: 'User'}, 'Create New User', 'modal-lg');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New User</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">{{ ($showInactive ?? false) ? 'Inactive Users' : 'Active Users' }}</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 80px;">#</th>
                                    <th style="width: 80px;"></th>
                                    <th style="width: 20%;">Full Name</th>
                                    <th style="width: 25%;">Email</th>
                                    <th style="width: 15%;">Position</th>
                                    <th style="width: 15%;">Department</th>
                                    <th style="width: 10%;">Status</th>
                                    <th class="text-center" style="width: 120px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($users as $user)
                                    <tr id="user-tr-{{$user->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>

                                        <td class="font-w600">
                                            @can('Edit User Permission')
                                                <button type="button" onclick="loadFormModal('settings_user_permission_form', {className: 'UsersPermission', user_id: {{$user->id}}}, 'Permission For {{$user->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit Permissions" data-original-title="Edit Permissions">
                                                    <i class="fa fa-cog"></i>
                                                </button>
                                            @endcan
                                        </td>
                                        <td class="font-w600">
                                            <div class="d-flex align-items-center">
                                                <i class="fa fa-user-circle text-muted mr-2" style="font-size: 1.2em;"></i>
                                                <div>
                                                    <div class="font-weight-bold">{{ $user->name }}</div>
                                                    <small class="text-muted">ID: {{ $user->employee_number ?? 'N/A' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fa fa-envelope text-muted mr-2"></i>
                                                {{ $user->email }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fa fa-briefcase text-muted mr-2"></i>
                                                {{ $user->designation ?? 'N/A' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fa fa-building text-muted mr-2"></i>
                                                {{ $user->department->name ?? 'N/A' }}
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($user->status === 'ACTIVE')
                                                <span class="badge badge-success">ACTIVE</span>
                                            @else
                                                <span class="badge badge-danger">INACTIVE</span>
                                            @endif
                                        </td>
                                        <td class="text-center" >
                                            <div class="btn-group">
                                                @can('Manage User Password')
                                                    <button type="button" onclick="loadFormModal('settings_manage_user_form', {className: 'User', id: {{$user->id}}}, 'Edit {{$user->name}}', 'modal-lg');" class="btn btn-sm btn-success js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Password">
                                                        <i class="fa fa-key"></i>
                                                    </button>
                                                @endcan
                                                @can('Edit User')
                                                    <button type="button" onclick="loadFormModal('settings_user_form', {className: 'User', id: {{$user->id}}}, 'Edit {{$user->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                        <button type="button" onclick="loadFormModal('settings_user_profile_form', {className: 'User', id: {{$user->id}}}, 'Upload Profile {{$user->name}}', 'modal-lg');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Upload Profile" data-original-title="Upload Profile">
                                                            <i class="fa fa-user"></i>
                                                        </button>
                                                @endcan

                                                    @can('Delete User')
                                                        <button type="button" onclick="deleteModelItem('User', {{$user->id}}, 'user-tr-{{$user->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    @endcan
                                                    @can('Edit User')
                                                        <form action="{{ route('hr_settings_users_toggle_status', $user->id) }}" method="POST" style="display:inline;">
                                                            @csrf
                                                            @if($user->status === 'ACTIVE')
                                                                <button type="submit" class="btn btn-sm btn-warning js-tooltip-enabled" data-toggle="tooltip" title="Deactivate" data-original-title="Deactivate" onclick="return confirm('Are you sure you want to deactivate this user?');">
                                                                    <i class="fa fa-ban"></i>
                                                                </button>
                                                            @else
                                                                <button type="submit" class="btn btn-sm btn-info js-tooltip-enabled" data-toggle="tooltip" title="Activate" data-original-title="Activate" onclick="return confirm('Are you sure you want to activate this user?');">
                                                                    <i class="fa fa-check"></i>
                                                                </button>
                                                            @endif
                                                        </form>
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
    </div>
@endsection

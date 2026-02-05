@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Settings
{{--                @include('components.headed_paper_settings')--}}
                <br/>

                <div class="float-right">
                    @can('Add User Group')
                        <button type="button" onclick="loadFormModal('settings_user_group_form', {className: 'UserGroup'}, 'Create New User Group', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New User Group</button> @endcan

                </div>
            </div>
            <div>

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">User Groups</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Date</th>
                                <th>Name</th>
                                <th>Keyword</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($user_groups as $key => $value)
                                <tr>
                                    <th scope="row">
                                        <a href="#">{{$loop->iteration}}</a>
                                    </th>
                                    <td>{{ $value->updated_at }}</td>
                                    <td>{{ $value->name }}</td>
                                    <td>{{ $value->keyword }}</td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit User Group')
                                                <button type="button" onclick="loadFormModal('settings_user_group_form', {className: 'UserGroup', id: {{$value->id}}}, 'Edit {{$value->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                            @can('Delete User Group')
                                                <button type="button" onclick="deleteModelItem('UserGroup', {{$value->id}}, 'user_group-tr-{{$value->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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

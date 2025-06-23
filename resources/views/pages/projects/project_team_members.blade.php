{{-- project_team_members.blade.php --}}
@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Project Team Members
                <div class="float-right">
                    @can('Add Team Member')
                        <button type="button" onclick="loadFormModal('project_team_member_form', {className: 'ProjectTeamMember'}, 'Add Team Member', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>Add Member</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Team Members List</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form name="team_search" action="" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Project</span>
                                                    </div>
                                                    <select name="project_id" id="input-project" class="form-control">
                                                        <option value="">All Projects</option>
                                                        @foreach ($projects as $project)
                                                            <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Role</span>
                                                    </div>
                                                    <select name="role" id="input-role" class="form-control">
                                                        <option value="">All Roles</option>
                                                        <option value="project_manager">Project Manager</option>
                                                        <option value="supervisor">Supervisor</option>
                                                        <option value="engineer">Engineer</option>
                                                        <option value="worker">Worker</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-2">
                                                <div>
                                                    <button type="submit" name="submit" class="btn btn-sm btn-primary">Show</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Project</th>
                                    <th>Member</th>
                                    <th>Role</th>
                                    <th>Assigned Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($teamMembers as $member)
                                    <tr id="member-tr-{{$member->id}}">
                                        <td class="text-center">{{$loop->index + 1}}</td>
                                        <td>{{ $member->project->project_name }}</td>
                                        <td>{{ $member->user->name }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $member->role)) }}</td>
                                        <td>{{ $member->assigned_date }}</td>
                                        <td>{{ $member->end_date ?? '-' }}</td>
                                        <td>
                                            @if($member->status == 'active')
                                                <div class="badge badge-success">{{ $member->status}}</div>
                                            @elseif($member->status == 'inactive')
                                                <div class="badge badge-warning">{{ $member->status}}</div>
                                            @elseif($member->status == 'completed')
                                                <div class="badge badge-info">{{ $member->status}}</div>
                                            @else
                                                <div class="badge badge-secondary">{{ $member->status}}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                @can('Edit Team Member')
                                                    <button type="button"
                                                            onclick="loadFormModal('project_team_member_form', {className: 'ProjectTeamMember', id: {{$member->id}}}, 'Edit Team Member', 'modal-md');"
                                                            class="btn btn-sm btn-primary">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan
                                                @can('Delete Team Member')
                                                    <button type="button"
                                                            onclick="deleteModelItem('ProjectTeamMember', {{$member->id}}, 'member-tr-{{$member->id}}');"
                                                            class="btn btn-sm btn-danger">
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
    </div>
@endsection

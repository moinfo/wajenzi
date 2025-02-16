{{-- projects.blade.php --}}
@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Projects
                <div class="float-right">
                    @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Create Project"))
                        <button type="button" onclick="loadFormModal('project_form', {className: 'Project'}, 'Create New Project', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Project</button>
                    @endif
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Projects</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form name="project_search" action="" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Start Date</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form  datepicker" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon2">End Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form  datepicker" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon3">Type</span>
                                                    </div>
                                                    <select name="project_type_id" id="input-project-type" class="form-control">
                                                        <option value="">All Types</option>
                                                        @foreach ($projectTypes as $type)
                                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                        @endforeach
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
                                    <th>Project Name</th>
                                    <th>Client</th>
                                    <th>Type</th>
                                    <th>Start Date</th>
                                    <th>Expected End Date</th>
                                    <th>Approvals</th>
                                    <th>Status</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($projects as $project)
                                    @php
                                        $approval_document_types_id = 10;
                                        $approvals = \App\Models\ApprovalLevel::getUsersApprovals($approval_document_types_id);
                                    @endphp
                                    <tr id="project-tr-{{$project->id}}">
                                        <td class="text-center">{{$loop->index + 1}}</td>
                                        <td class="font-w600">{{ $project->project_name }}</td>
                                        <td>{{ $project->client->first_name ?? null }} {{ $project->client->last_name ?? null}}</td>
                                        <td>{{ $project->projectType->name ?? null }}</td>
                                        <td>{{ $project->start_date }}</td>
                                        <td>{{ $project->expected_end_date }}</td>
                                        <td class="approvals-cell">
                                            <div class="approval-badges">
                                                @foreach($approvals as $approval)
                                                    @php
                                                        $approval_level_id = $approval->id;
                                                        $document_id = $project->id;
                                                        $group_name = \App\Models\ApprovalLevel::getUserGroupName($approval_level_id);
                                                        $approves = \App\Models\Approval::getApproved($approval_level_id,$document_id);
                                                    @endphp
                                                    @if(count($approves))
                                                        @foreach($approves as $approve)
                                                            @if($approve->user_group_id == $approval->user_group_id)
                                                                <span class="approval-badge approved">
                            <i class="fa fa-check"></i>{{$group_name ?? null}}
                        </span>
                                                            @else
                                                                <span class="approval-badge pending">
                            <i class="fa fa-clock-o"></i>{{$group_name ?? null}}
                        </span>
                                                            @endif
                                                        @endforeach
                                                    @else
                                                        <span class="approval-badge pending">
                    <i class="fa fa-clock-o"></i>{{$group_name ?? null}}
                </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>
                                            @if($project->status == 'PENDING')
                                                <div class="badge badge-warning">{{ $project->status}}</div>
                                            @elseif($project->status == 'APPROVED')
                                                <div class="badge badge-primary">{{ $project->status}}</div>
                                            @elseif($project->status == 'REJECTED')
                                                <div class="badge badge-danger">{{ $project->status}}</div>
                                            @elseif($project->status == 'PAID')
                                                <div class="badge badge-primary">{{ $project->status}}</div>
                                            @elseif($project->status == 'COMPLETED')
                                                <div class="badge badge-success">{{ $project->status}}</div>
                                            @else
                                                <div class="badge badge-secondary">{{ $project->status}}</div>
                                            @endif
                                        </td>                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success js-tooltip-enabled"
                                                   href="{{ route('individual_projects', [$project->id, 10]) }}">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Edit Project"))
                                                    <button type="button"
                                                            onclick="loadFormModal('project_form', {className: 'Project', id: {{$project->id}}}, 'Edit {{$project->project_name}}', 'modal-md');"
                                                            class="btn btn-sm btn-primary">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endif
                                                @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Delete Project"))
                                                    <button type="button"
                                                            onclick="deleteModelItem('Project', {{$project->id}}, 'project-tr-{{$project->id}}');"
                                                            class="btn btn-sm btn-danger">
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
    </div>
@endsection

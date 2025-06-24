{{-- project_site_visits.blade.php --}}
@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Site Visits
                <div class="float-right">
                    @can('Add Visit')
                        <button type="button" onclick="loadFormModal('project_site_visit_form', {className: 'ProjectSiteVisit'}, 'Create New Visit', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Visit</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Site Visits</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form name="visit_search" action="" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Start Date</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form  datepicker" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">End Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form  datepicker" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
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
                                    <th>Location</th>
                                    <th>Description</th>
                                    <th>Visit Date</th>
                                    <th>Status</th>
                                    <th>Approvals</th>
{{--                                    <th>Inspector</th>--}}
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($visits as $visit)
                                    @php
                                        $approval_document_types_id = 11;
                                        $approvals = \App\Models\ApprovalLevel::getUsersApprovals($approval_document_types_id);
                                    @endphp

                                    <tr id="visit-tr-{{$visit->id}}">
                                        <td class="text-center">{{$loop->index + 1}}</td>
                                        <td>{{ $visit->project->project_name ?? null }} - {{ $visit->project->client->first_name ?? null }} {{ $visit->project->client->last_name ?? null }}</td>
                                        <td>{{ $visit->location ?? null }}</td>
                                        <td>{{ $visit->description ?? null }}</td>
                                        <td>{{ $visit->visit_date }}</td>
                                        <td class="approvals-cell">
                                            <div class="approval-badges">
                                                @foreach($approvals as $approval)
                                                    @php
                                                        $approval_level_id = $approval->id;
                                                        $document_id = $visit->id;
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
                                            @if($visit->status == 'PENDING')
                                                <div class="badge badge-warning">{{ $visit->status}}</div>
                                            @elseif($visit->status == 'APPROVED')
                                                <div class="badge badge-primary">{{ $visit->status}}</div>
                                            @elseif($visit->status == 'REJECTED')
                                                <div class="badge badge-danger">{{ $visit->status}}</div>
                                            @elseif($visit->status == 'PAID')
                                                <div class="badge badge-primary">{{ $visit->status}}</div>
                                            @elseif($visit->status == 'COMPLETED')
                                                <div class="badge badge-success">{{ $visit->status}}</div>
                                            @else
                                                <div class="badge badge-secondary">{{ $visit->status}}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success" href="{{route('individual_project_site_visits',['id' => $visit->id,'document_type_id'=>11])}}">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                @can('Edit Visit')
                                                    <button type="button"
                                                            onclick="loadFormModal('project_site_visit_form', {className: 'ProjectSiteVisit', id: {{$visit->id}}}, 'Edit Visit', 'modal-md');"
                                                            class="btn btn-sm btn-primary">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan
                                                @can('Delete Visit')
                                                    <button type="button"
                                                            onclick="deleteModelItem('ProjectSiteVisit', {{$visit->id}}, 'visit-tr-{{$visit->id}}');"
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

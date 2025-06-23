@extends('layouts.backend')

@section('css')
<style>
    .summary-stats {
        display: inline-flex !important;
        border: 1px solid #e9ecef;
        border-radius: 5px;
        overflow: hidden;
    }
    .summary-stats .badge {
        border-radius: 0;
        margin: 0;
        font-size: 0.8rem;
        padding: 0.3rem 0.5rem;
    }
    .summary-stats .badge:hover {
        filter: brightness(0.9);
    }
</style>
@endsection

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Projects
                <div class="float-right">
                    @can('Create Project')
                        <button type="button" onclick="loadFormModal('project_form', {className: 'Project'}, 'Create New Project', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Project</button>
                    @endcan
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
                                    <th class="text-center" style="width: 50px;">#</th>
                                    <th>Document Number</th>
                                    <th>Project Name</th>
                                    <th>Client</th>
                                    <th>Type</th>
                                    <th>Start Date</th>
                                    <th>Expected End Date</th>
                                    <th class="text-center">Approvals</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($projects as $project)
                                    <tr id="project-tr-{{$project->id}}">
                                        <td class="text-center">{{$loop->iteration}}</td>
                                        <td class="text-center">{{$project->document_number}}</td>
                                        <td class="font-w600">{{ $project->project_name }}</td>
                                        <td>{{ $project->client->first_name ?? null }} {{ $project->client->last_name ?? null}}</td>
                                        <td>{{ $project->projectType->name ?? null }}</td>
                                        <td>{{ $project->start_date }}</td>
                                        <td>{{ $project->expected_end_date }}</td>
                                        <td class="text-center">
                                            <!-- Approval status summary component -->
                                            <x-ringlesoft-approval-status-summary :model="$project" />
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $approvalStatus = $project->approvalStatus?->status ?? 'PENDING';
                                                $statusClass = [
                                                    'Pending' => 'warning',
                                                    'Submitted' => 'info',
                                                    'Approved' => 'success',
                                                    'Rejected' => 'danger',
                                                    'Paid' => 'primary',
                                                    'Completed' => 'success',
                                                    'Discarded' => 'danger',
                                                ][$approvalStatus] ?? 'secondary';

                                                $statusIcon = [
                                                    'Pending' => '<i class="fas fa-clock"></i>',
                                                    'Submitted' => '<i class="fas fa-paper-plane"></i>',
                                                    'Approved' => '<i class="fas fa-check"></i>',
                                                    'Rejected' => '<i class="fas fa-times"></i>',
                                                    'Paid' => '<i class="fas fa-money-bill"></i>',
                                                    'Completed' => '<i class="fas fa-check-circle"></i>',
                                                    'Discarded' => '<i class="fas fa-trash"></i>',
                                                ][$approvalStatus] ?? '<i class="fas fa-question-circle"></i>';
                                            @endphp
                                            <span class="badge badge-{{ $statusClass }} badge-pill" style="font-size: 0.9em; padding: 6px 10px;">
                                                {!! $statusIcon !!} {{ $approvalStatus }}
                                            </span>

                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success js-tooltip-enabled"
                                                   href="{{ route('individual_projects', [$project->id, 10]) }}">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @can('Edit Project')
                                                    <button type="button"
                                                            onclick="loadFormModal('project_form', {className: 'Project', id: {{$project->id}}}, 'Edit {{$project->project_name}}', 'modal-md');"
                                                            class="btn btn-sm btn-primary">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </button>
                                                @endcan
                                                @can('Delete Project')
                                                    <button type="button"
                                                            onclick="deleteModelItem('Project', {{$project->id}}, 'project-tr-{{$project->id}}');"
                                                            class="btn btn-sm btn-danger">
                                                        <i class="fas fa-times"></i>
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

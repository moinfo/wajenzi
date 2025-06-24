@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Activities
                <div class="float-right">
                    @can('Add Activity')
                        <button type="button" onclick="loadFormModal('settings_activity_form', {className: 'Activity'}, 'Create New Activity', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Activity</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Construction Activities</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>Name</th>
                                <th>Construction Stage</th>
                                <th>Description</th>
                                <th class="text-center">Sort Order</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($activities as $activity)
                                <tr id="activity-tr-{{$activity->id}}">
                                    <td class="text-center">{{$loop->index + 1}}</td>
                                    <td class="font-w600">{{ $activity->name }}</td>
                                    <td>
                                        <span class="badge badge-info">{{ $activity->constructionStage->name ?? '-' }}</span>
                                    </td>
                                    <td>{{ $activity->description ?? '-' }}</td>
                                    <td class="text-center">{{ $activity->sort_order }}</td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            @can('Edit Activity')
                                                <button type="button" onclick="loadFormModal('settings_activity_form', {className: 'Activity', id: {{$activity->id}}}, 'Edit {{$activity->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan
                                            @can('Delete Activity')
                                                <button type="button" onclick="deleteModelItem('Activity', {{$activity->id}}, 'activity-tr-{{$activity->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete">
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
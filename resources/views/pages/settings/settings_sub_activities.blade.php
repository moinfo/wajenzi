@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Sub-Activities
                <div class="float-right">
                    @can('Add Sub Activity')
                        <button type="button" onclick="loadFormModal('settings_sub_activity_form', {className: 'SubActivity'}, 'Create New Sub-Activity', 'modal-lg');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Sub-Activity</button>
                    @endcan

                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Sub-Activities with Time Tracking</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>Name</th>
                                <th>Activity</th>
                                <th>Stage</th>
                                <th class="text-center">Duration</th>
                                <th class="text-center">Labor</th>
                                <th class="text-center">Skill Level</th>
                                <th class="text-center">Parallel</th>
                                <th class="text-center">Weather</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($sub_activities as $sub_activity)
                                <tr id="sub_activity-tr-{{$sub_activity->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $sub_activity->name }}</td>
                                    <td>{{ $sub_activity->activity->name ?? '-' }}</td>
                                    <td>{{ $sub_activity->activity->constructionStage->name ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($sub_activity->estimated_duration_hours)
                                            {{ $sub_activity->duration_display }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($sub_activity->labor_requirement)
                                            {{ $sub_activity->labor_requirement }} workers
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $sub_activity->skill_level)) }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($sub_activity->can_run_parallel)
                                            <i class="fa fa-check text-success" title="Can run in parallel"></i>
                                        @else
                                            <i class="fa fa-times text-muted" title="Sequential only"></i>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($sub_activity->weather_dependent)
                                            <i class="fa fa-cloud text-warning" title="Weather dependent"></i>
                                        @else
                                            <i class="fa fa-check text-success" title="Weather independent"></i>
                                        @endif
                                    </td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit Sub Activity')
                                                <button type="button" onclick="loadFormModal('settings_sub_activity_form', {className: 'SubActivity', id: {{$sub_activity->id}}}, 'Edit {{$sub_activity->name}}', 'modal-lg');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                            @can('Delete Sub Activity')
                                                <button type="button" onclick="deleteModelItem('SubActivity', {{$sub_activity->id}}, 'sub_activity-tr-{{$sub_activity->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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
@extends('layouts.backend')

@section('content')
    <style>
        .activity-row {
            background-color: #f8f9fa !important;
            border-left: 4px solid #1BC5BD;
            font-weight: 600;
        }
        .sub-activity-row {
            background-color: #ffffff !important;
            border-left: 4px solid #e9ecef;
        }
        .activity-row:hover {
            background-color: #e9ecef !important;
        }
        .sub-activity-row:hover {
            background-color: #f8f9fa !important;
        }
        .badge-light-primary {
            background-color: rgba(27, 197, 189, 0.1);
            color: #1BC5BD;
        }
    </style>

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
                        <h3 class="block-title">Activities and Sub-Activities (Hierarchical)</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 80px;">Number</th>
                                <th>Name</th>
                                <th>Type</th>
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
                            @php
                                $activityCounter = 0;
                                $subActivityCounters = [];
                            @endphp

                            @foreach($activities as $activity)
                                @php
                                    $activityCounter++;
                                    $subActivityCounters[$activity->id] = 0;
                                @endphp

                                {{-- Display Activity Row --}}
                                <tr class="activity-row">
                                    <td class="text-center">
                                        <span class="font-weight-bold" style="color: #1BC5BD; font-size: 1.1em;">
                                            {{ $activityCounter }}
                                        </span>
                                    </td>
                                    <td class="font-w600">
                                        <span style="color: #1BC5BD; font-weight: 700;">
                                            <i class="fas fa-tasks text-primary mr-2"></i>
                                            {{ $activity->name }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-primary">Activity</span>
                                    </td>
                                    <td>{{ $activity->constructionStage->name ?? '-' }}</td>
                                    <td class="text-center"><span class="text-muted">-</span></td>
                                    <td class="text-center"><span class="text-muted">-</span></td>
                                    <td class="text-center"><span class="text-muted">-</span></td>
                                    <td class="text-center"><span class="text-muted">-</span></td>
                                    <td class="text-center"><span class="text-muted">-</span></td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            @can('Edit Activity')
                                                <button type="button" onclick="loadFormModal('settings_activity_form', {className: 'Activity', id: {{$activity->id}}}, 'Edit {{$activity->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit Activity">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>

                                {{-- Display Sub-Activities for this Activity --}}
                                @foreach($activity->subActivities as $sub_activity)
                                    @php
                                        $subActivityCounters[$activity->id]++;
                                        $displayNumber = $activityCounter . '.' . $subActivityCounters[$activity->id];
                                    @endphp

                                    <tr id="sub_activity-tr-{{$sub_activity->id}}" class="sub-activity-row">
                                        <td class="text-center">
                                            <span class="font-weight-bold" style="color: #6c757d; font-size: 0.9em;">
                                                {{ $displayNumber }}
                                            </span>
                                        </td>
                                        <td class="font-w600">
                                            <span style="margin-left: 20px; color: #6c757d;">
                                                <i class="fas fa-level-up-alt fa-rotate-90 text-muted mr-2"></i>
                                                {{ $sub_activity->name }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary">Sub-Activity</span>
                                        </td>
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
                                            @if($sub_activity->skill_level)
                                                <span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $sub_activity->skill_level)) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if(isset($sub_activity->can_run_parallel))
                                                @if($sub_activity->can_run_parallel)
                                                    <i class="fa fa-check text-success" title="Can run in parallel"></i>
                                                @else
                                                    <i class="fa fa-times text-muted" title="Sequential only"></i>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if(isset($sub_activity->weather_dependent))
                                                @if($sub_activity->weather_dependent)
                                                    <i class="fa fa-cloud text-warning" title="Weather dependent"></i>
                                                @else
                                                    <i class="fa fa-check text-success" title="Weather independent"></i>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                @can('Edit Sub Activity')
                                                    <button type="button" onclick="loadFormModal('settings_sub_activity_form', {className: 'SubActivity', id: {{$sub_activity->id}}}, 'Edit {{$sub_activity->name}}', 'modal-lg');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan
                                                @can('Delete Sub Activity')
                                                    <button type="button" onclick="deleteModelItem('SubActivity', {{$sub_activity->id}}, 'sub_activity-tr-{{$sub_activity->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
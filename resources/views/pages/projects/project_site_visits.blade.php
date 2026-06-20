{{-- project_site_visits.blade.php --}}
@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Site Visits
                <div class="float-right">
                    @can('Add Visit')
                        <button type="button" onclick="loadFormModal('project_site_visit_form', {className: 'ProjectSiteVisit'}, 'Create New Visit', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Visit</button>
                    @endcan
                </div>
            </div>
            @include('partials.alerts')
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
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form  datepicker" value="{{ $start_date ?? '' }}">
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">End Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form  datepicker" value="{{ $end_date ?? '' }}">
                                                </div>
                                            </div>
                                            <div class="class col-md-2">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Project</span>
                                                    </div>
                                                    <select name="project_id" id="input-project" class="form-control">
                                                        <option value="">All Projects</option>
                                                        @foreach ($projects as $project)
                                                            <option value="{{ $project->id }}" {{ (string) $project->id === (string) request('project_id') ? 'selected' : '' }}>{{ $project->project_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-2">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Stage</span>
                                                    </div>
                                                    <select name="stage" id="input-stage" class="form-control">
                                                        <option value="">All Stages</option>
                                                        @foreach (($stages ?? []) as $key => $label)
                                                            <option value="{{ $key }}" {{ request('stage') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                        @endforeach
                                                        <option value="cancelled" {{ request('stage') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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
                                    <th>Project / Client</th>
                                    <th>Location</th>
                                    <th>Description</th>
                                    <th>Visit Date</th>
                                    <th>Stage</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php
                                    $stageBadge = [
                                        'initiation'   => 'secondary',
                                        'billing'      => 'warning',
                                        'assignment'   => 'info',
                                        'confirmation' => 'info',
                                        'reporting'    => 'primary',
                                        'integration'  => 'primary',
                                        'completed'    => 'success',
                                        'cancelled'    => 'danger',
                                    ];
                                @endphp
                                @foreach($visits as $visit)
                                    <tr id="visit-tr-{{$visit->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                            <div class="font-size-sm text-muted">{{ $visit->reference_number }}</div>
                                        </td>
                                        <td>
                                            @if($visit->project)
                                                <i class="fa fa-building text-primary mr-1"></i>
                                                {{ $visit->project->project_name }} - {{ $visit->project->client->first_name ?? '' }} {{ $visit->project->client->last_name ?? '' }}
                                            @elseif($visit->client)
                                                <i class="fa fa-user text-info mr-1"></i>
                                                {{ $visit->client->first_name }} {{ $visit->client->last_name }}
                                                <span class="badge badge-light ml-1">Client only</span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>{{ $visit->location ?? null }}</td>
                                        <td>{{ $visit->description ?? null }}</td>
                                        <td>{{ \Carbon\Carbon::parse($visit->visit_date)->format('Y-m-d') }}</td>
                                        <td>
                                            <span class="badge badge-{{ $stageBadge[$visit->stage] ?? 'secondary' }}">
                                                @if($visit->stage === 'cancelled')
                                                    Cancelled
                                                @else
                                                    {{ $visit->stageIndex() }}/{{ $visit->stageCount() }} — {{ $visit->stageLabel() }}
                                                @endif
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success" href="{{ route('project_site_visit.show', $visit->id) }}">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                @if($visit->stage === 'initiation')
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

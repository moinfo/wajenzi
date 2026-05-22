{{-- project_daily_reports.blade.php --}}
@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Daily Reports
                <div class="float-right">
                    <button type="button" onclick="loadFormModal('daily_report_form', {className: 'ProjectDailyReport'}, 'Create New Report', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Report</button>
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <ul class="nav nav-tabs nav-tabs-block" data-toggle="tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" href="#tab-daily">Daily</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#tab-weekly">Weekly per Employee</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#tab-monthly">Monthly per Employee</a>
                            </li>
                        </ul>
                    </div>
                    <div class="block-content tab-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form name="report_search" action="" id="filter-form" method="get" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Start Date</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">End Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
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
                        <div class="tab-pane active" id="tab-daily" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                    <thead>
                                    <tr>
                                        <th class="text-center" style="width: 100px;">#</th>
                                        <th>Project</th>
                                        <th>Report Date</th>
                                        <th>Supervisor</th>
                                        <th>Weather</th>
                                        <th>Labor Hours</th>
                                        <th class="text-center" style="width: 100px;">Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($reports as $report)
                                        <tr id="report-tr-{{$report->id}}">
                                            <td class="text-center">{{$loop->index + 1}}</td>
                                            <td>{{ optional($report->project)->project_name ?? 'N/A' }}</td>
                                            <td>{{ $report->report_date }}</td>
                                            <td>{{ optional($report->supervisor)->name ?? 'N/A' }}</td>
                                            <td>{{ $report->weather_conditions }}</td>
                                            <td class="text-right">{{ $report->labor_hours }}</td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a class="btn btn-sm btn-success" href="{{route('project_daily_report',['id' => $report->id])}}">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    @can('Edit Report')
                                                        <button type="button"
                                                                onclick="loadFormModal('daily_report_form', {className: 'ProjectDailyReport', id: {{$report->id}}}, 'Edit Report', 'modal-md');"
                                                                class="btn btn-sm btn-primary">
                                                            <i class="fa fa-pencil"></i>
                                                        </button>
                                                    @endcan
                                                    @can('Delete Report')
                                                        <button type="button"
                                                                onclick="deleteModelItem('ProjectDailyReport', {{$report->id}}, 'report-tr-{{$report->id}}');"
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

                        <div class="tab-pane" id="tab-weekly" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-vcenter">
                                    <thead>
                                    <tr>
                                        <th style="width:60px;">#</th>
                                        <th>Week</th>
                                        <th>Period</th>
                                        <th>Employee (Supervisor)</th>
                                        <th class="text-right">Reports</th>
                                        <th class="text-right">Labor Hours</th>
                                        <th class="text-right">Projects</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($weekly as $row)
                                        <tr>
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td><span class="badge badge-info">{{ $row->period_label }}</span></td>
                                            <td>{{ $row->period_start }} → {{ $row->period_end }}</td>
                                            <td>{{ $row->supervisor_name }}</td>
                                            <td class="text-right">{{ number_format($row->reports_count) }}</td>
                                            <td class="text-right">{{ number_format($row->labor_hours_total) }}</td>
                                            <td class="text-right">{{ number_format($row->projects_count) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted">No daily reports in the selected range.</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane" id="tab-monthly" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-vcenter">
                                    <thead>
                                    <tr>
                                        <th style="width:60px;">#</th>
                                        <th>Month</th>
                                        <th>Period</th>
                                        <th>Employee (Supervisor)</th>
                                        <th class="text-right">Reports</th>
                                        <th class="text-right">Labor Hours</th>
                                        <th class="text-right">Projects</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($monthly as $row)
                                        <tr>
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td><span class="badge badge-primary">{{ $row->period_label }}</span></td>
                                            <td>{{ $row->period_start }} → {{ $row->period_end }}</td>
                                            <td>{{ $row->supervisor_name }}</td>
                                            <td class="text-right">{{ number_format($row->reports_count) }}</td>
                                            <td class="text-right">{{ number_format($row->labor_hours_total) }}</td>
                                            <td class="text-right">{{ number_format($row->projects_count) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted">No daily reports in the selected range.</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

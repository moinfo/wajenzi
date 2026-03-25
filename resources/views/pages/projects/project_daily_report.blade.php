{{-- project_daily_report.blade.php --}}
@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Daily Report Details
                <div class="float-right">
                    <a href="{{ route('project_daily_reports') }}" class="btn btn-sm btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Report #{{ $report->id }}</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-striped">
                                <tr>
                                    <th width="40%">Project</th>
                                    <td>{{ $report->project?->project_name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Report Date</th>
                                    <td>{{ $report->report_date }}</td>
                                </tr>
                                <tr>
                                    <th>Supervisor</th>
                                    <td>{{ $report->supervisor?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Weather Conditions</th>
                                    <td>{{ $report->weather_conditions ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Labor Hours</th>
                                    <td>{{ $report->labor_hours ?? 0 }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-striped">
                                <tr>
                                    <th width="40%">Work Completed</th>
                                    <td>{!! nl2br(e($report->work_completed ?? '-')) !!}</td>
                                </tr>
                                <tr>
                                    <th>Materials Used</th>
                                    <td>{!! nl2br(e($report->materials_used ?? '-')) !!}</td>
                                </tr>
                                <tr>
                                    <th>Issues Faced</th>
                                    <td>{!! nl2br(e($report->issues_faced ?? '-')) !!}</td>
                                </tr>
                                <tr>
                                    <th>Created At</th>
                                    <td>{{ $report->created_at }}</td>
                                </tr>
                                <tr>
                                    <th>Updated At</th>
                                    <td>{{ $report->updated_at }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

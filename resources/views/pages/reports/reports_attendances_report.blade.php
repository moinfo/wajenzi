@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">Attendance Report
        </div>
        <div>
            <div class="block block-themed">
                <div class="block-content">
                    <div class="row no-print m-t-10">
                        <div class="class col-md-12">
                            @include('components.headed_paper')
                            <br/>
                            <div class="class card-box">
                                <form name="attendance_search" action="" id="filter-form" method="post" autocomplete="off">
                                    @csrf
                                    <div class="row">
                                        <div class="class col-md-3">
                                            <div class="input-group mb-3">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Start Date</span>
                                                </div>
                                                <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $start_date }}">
                                            </div>
                                        </div>
                                        <div class="class col-md-3">
                                            <div class="input-group mb-3">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">End Date</span>
                                                </div>
                                                <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $end_date }}">
                                            </div>
                                        </div>
                                        <div class="class col-md-3">
                                            <div class="input-group mb-3">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Department</span>
                                                </div>
                                                <select name="department_id" id="department_id" class="form-control">
                                                    <option value="">All Departments</option>
                                                    @foreach($departments as $department)
                                                        <option value="{{ $department->id }}" {{ ($department_id == $department->id) ? 'selected' : '' }}>
                                                            {{ $department->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="class col-md-3">
                                            <div class="input-group mb-3">
                                                <input type="text" name="search" id="search" class="form-control" placeholder="Search by name, email, device ID" value="{{ $search }}">
                                                <div class="input-group-append">
                                                    <button type="submit" class="btn btn-primary">Filter</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <button type="submit" name="export" value="excel" class="btn btn-success">
                                                <i class="fa fa-file-excel"></i> Export to Excel
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Overall Statistics -->
                    <div class="row no-print m-t-10">
                        <div class="col-md-12">
                            <div class="card-box">
                                <h4>Overall Statistics</h4>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="badge badge-primary">Total Users: {{ $overallStats['total_users'] }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="badge badge-info">Total Days: {{ $overallStats['total_days'] }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="badge badge-success">Avg Attendance: {{ number_format($overallStats['avg_attendance_rate'], 1) }}%</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="badge badge-warning">Avg Punctuality: {{ number_format($overallStats['avg_punctuality_rate'], 1) }}%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Staff Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Device ID</th>
                                    <th>Present Days</th>
                                    <th>Early Days</th>
                                    <th>Late Days</th>
                                    <th>Absent Days</th>
                                    <th>Attendance Rate</th>
                                    <!-- Date columns -->
                                    @foreach($dates as $date)
                                        <th class="text-center" style="min-width: 60px;">
                                            {{ $date->format('d') }}<br>
                                            <small>{{ $date->format('D') }}</small>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($staffs as $staff)
                                <tr>
                                    <td>{{ $staff->row_number }}</td>
                                    <td>{{ $staff->name }}</td>
                                    <td>{{ $staff->email }}</td>
                                    <td>{{ $staff->department_display }}</td>
                                    <td>{{ $staff->user_device_id ?? 'N/A' }}</td>
                                    <td>{{ $staff->present_days }}</td>
                                    <td class="text-success">{{ $staff->attendance_summary['early_days'] }}</td>
                                    <td class="text-warning">{{ $staff->attendance_summary['late_days'] }}</td>
                                    <td class="text-danger">{{ $staff->attendance_summary['absent_days'] }}</td>
                                    <td>
                                        <span class="badge {{ $staff->attendance_rate_badge_class }}">
                                            {{ $staff->attendance_rate }}%
                                        </span>
                                    </td>
                                    <!-- Daily attendance status -->
                                    @foreach($dates as $date)
                                        @php
                                            $dateStr = $date->format('Y-m-d');
                                            $dayAttendance = $staff->attendance_summary['attendance_details'][$dateStr] ?? null;
                                        @endphp
                                        <td class="text-center">
                                            @if($dayAttendance)
                                                <span class="{{ $dayAttendance['icon_class'] }}" 
                                                      title="{{ $dayAttendance['title'] }}{{ $dayAttendance['display_time'] ? ' - ' . $dayAttendance['display_time'] : '' }}">
                                                </span>
                                                @if($dayAttendance['display_time'])
                                                    <br><small>{{ $dayAttendance['display_time'] }}</small>
                                                @endif
                                            @else
                                                <span class="fa fa-minus text-muted" title="No data"></span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="row">
                        <div class="col-md-12">
                            {{ $users->appends(request()->query())->links() }}
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="row no-print">
                        <div class="col-md-12">
                            <div class="card-box">
                                <h5>Legend:</h5>
                                <div class="row">
                                    <div class="col-md-3">
                                        <i class="fa fa-check text-success"></i> Early/On-time
                                    </div>
                                    <div class="col-md-3">
                                        <i class="fa fa-times text-danger"></i> Late
                                    </div>
                                    <div class="col-md-3">
                                        <i class="fa fa-minus text-muted"></i> Absent
                                    </div>
                                    <div class="col-md-3">
                                        Late time threshold: {{ $staffs->first()->late_in_time ?? '09:00:00' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js_after')
<script>
$(document).ready(function() {
    // Auto-submit form when date or department changes
    $('#start_date, #end_date, #department_id').change(function() {
        $('#filter-form').submit();
    });
    
    // Handle search with enter key
    $('#search').keypress(function(e) {
        if(e.which == 13) {
            $('#filter-form').submit();
        }
    });
});
</script>
@endsection
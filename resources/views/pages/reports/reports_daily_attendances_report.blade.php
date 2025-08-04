@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading">Daily Attendance Report
        </div>
        <div>
            <div class="block block-themed">
                <div class="block-content">
                    <div class="row no-print m-t-10">
                        <div class="class col-md-12">
                            @include('components.headed_paper')
                            <br/>
                            <div class="class card-box">
                                <form name="daily_attendance_search" action="" id="filter-form" method="post" autocomplete="off">
                                    @csrf
                                    <div class="row">
                                        <div class="class col-md-3">
                                            <div class="input-group mb-3">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Date</span>
                                                </div>
                                                <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $start_date }}">
                                            </div>
                                        </div>
                                        <div class="class col-md-3">
                                            <div class="input-group mb-3">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Attendance Type</span>
                                                </div>
                                                <select name="attendance_type_id" id="attendance_type_id" class="form-control">
                                                    <option value="">All Types</option>
                                                    @foreach($attendanceTypes as $type)
                                                        <option value="{{ $type->id }}" {{ ($attendance_type_id == $type->id) ? 'selected' : '' }}>
                                                            {{ $type->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="class col-md-4">
                                            <div class="input-group mb-3">
                                                <input type="text" name="search" id="search" class="form-control" placeholder="Search by name, email, device ID" value="{{ $search }}">
                                                <div class="input-group-append">
                                                    <button type="submit" class="btn btn-primary">Filter</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="class col-md-2">
                                            <button type="submit" name="export" value="excel" class="btn btn-success">
                                                <i class="fa fa-file-excel"></i> Export
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
                                <h4>Daily Statistics for {{ \Carbon\Carbon::parse($start_date)->format('F d, Y') }}</h4>
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="badge badge-primary">Total Users: {{ $overallStats['total_users'] }}</div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="badge badge-info">Attendance Types: {{ $overallStats['attendance_types_count'] }}</div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="badge badge-success">Present: {{ $overallStats['present_today'] }}</div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="badge badge-success">On Time: {{ $overallStats['on_time_today'] }}</div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="badge badge-warning">Late: {{ $overallStats['late_today'] }}</div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="badge badge-secondary">Absent: {{ $overallStats['absent_today'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance by Type -->
                    @foreach($staffs as $attendanceType => $typeStaffs)
                    <div class="card-box mb-4">
                        <h5 class="text-primary">{{ $attendanceType }} ({{ $typeStaffs->count() }} users)</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 40px;">#</th>
                                        <th>Staff Name</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Device ID</th>
                                        <th>Check-in Time</th>
                                        <th>Status</th>
                                        <th>Comment</th>
                                        <th>Attachment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($typeStaffs as $staff)
                                    <tr>
                                        <td>{{ $staff->row_number }}</td>
                                        <td>{{ $staff->name }}</td>
                                        <td>{{ $staff->email }}</td>
                                        <td>{{ $staff->department_display }}</td>
                                        <td>{{ $staff->user_device_id ?? 'N/A' }}</td>
                                        <td>
                                            @if($staff->in_time)
                                                <strong>{{ $staff->in_time }}</strong>
                                            @else
                                                <span class="text-muted">Not checked in</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $staff->status_badge_class }}">
                                                <i class="{{ $staff->status_icon_class }}"></i>
                                                {{ $staff->status_title }}
                                            </span>
                                        </td>
                                        <td>{{ $staff->comment ?? '-' }}</td>
                                        <td>
                                            @if($staff->attachment)
                                                <a href="{{ asset('storage/'.$staff->attachment) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                                    <i class="fa fa-file"></i> View
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endforeach

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
                                        <span class="badge badge-success"><i class="fa fa-check"></i> On Time</span>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="badge badge-warning"><i class="fa fa-times"></i> Late</span>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="badge badge-secondary"><i class="fa fa-minus"></i> Absent</span>
                                    </div>
                                    <div class="col-md-3">
                                        Late time threshold: {{ $staffs->flatten()->first()->late_in_time ?? '09:00:00' }}
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
    // Auto-submit form when date or attendance type changes
    $('#start_date, #attendance_type_id').change(function() {
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
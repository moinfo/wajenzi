@extends('layouts.backend')

@section('content')
    <!-- Page Content -->
    <div class="content">
        <h2 class="content-heading">Timesheet <small>dashboard</small></h2>
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Staff Timesheet Data
                    <span class="pull-right">
                        <select class="form-control form-control-sm col-12">
                            <option value="MONTHLY" selected>Monthly</option>
                        </select>
                    </span>
                </h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-condensed">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Staff Name</th>
                            <th>Employee Number</th>
                            @foreach($months as $month)
                                <th>{{ $month }}</th>
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                            @foreach($staff_data as $staff)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $staff->name }}</td>
                                    <td>{{ $staff->employee_number }}</td>
                                    @foreach($months as $month)
                                        <td></td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- END Page Content -->
@endsection

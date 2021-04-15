@extends('layouts.backend')

@section('content')
    <!-- Page Content -->
    <div class="content">
        <h2 class="content-heading">Staff List <small>All</small>
            <div class="float-right">
                <button type="button" onclick="loadFormModal('staff_form', {metadata: {positions: 'Position', departments : 'Department', supervisors: 'Staff'}}, 'Create New Employee', 'modal-lg');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>Create New Staff</button>
            </div>
        </h2>
        <div class="table-responsive">
            <table class="table table-condensed table-bordered table-striped">
                <thead><tr><th>#</th><th>Name</th><th>Employee No.</th><th>Gender</th><th>Email</th><th>Phone</th><th>Department</th><th>Designation</th><th>Employment Type</th><th></th><th></th></tr></thead>
                <tbody>
                    @foreach($staffs as $staff)
                        <tr>
                            <td>{{ $loop->index + 1 }}</td>
                            <td>{{ $staff['name'] }}</td>
                            <td>{{ $staff['employee_number'] }}</td>
                            <td>{{ $staff['gender'] }}</td>
                            <td>{{ $staff['email'] }}</td>
                            <td>{{ $staff['phone'] }}</td>
                            <td>{{ $staff->department->name ?? '' }}</td>
                            <td>{{ $staff->position->name ?? '' }}</td>
                            <td>{{ $staff->employment_type ?? '' }}</td>
                            <td></td>
                            <td></td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <!-- END Page Content -->
@endsection

@section('js_after')

@endsection

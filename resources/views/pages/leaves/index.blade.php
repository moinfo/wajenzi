@extends('layouts.backend')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="float-left">My Leave Requests</h3>
                        <button type="button" class="btn btn-primary float-right" data-toggle="modal" data-target="#newLeaveModal">
                            New Leave Request
                        </button>
                    </div>

                    <div class="card-body">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Days</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($leaveRequests as $leave)
                                <tr>
                                    <td>{{ $leave->leaveType->name }}</td>
                                    <td>{{ $leave->start_date->format('M d, Y') }}</td>
                                    <td>{{ $leave->end_date->format('M d, Y') }}</td>
                                    <td>{{ $leave->total_days }}</td>
                                    <td>
                                    <span class="badge badge-{{ $leave->status == 'approved' ? 'success' : ($leave->status == 'rejected' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($leave->status) }}
                                    </span>
                                    </td>
                                    <td>{{ $leave->admin_remarks }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Leave Request Modal -->
    <div class="modal fade" id="newLeaveModal" tabindex="-1" role="dialog" aria-labelledby="newLeaveModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('leaves.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="newLeaveModalLabel">New Leave Request</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Leave Type</label>
                            <select name="leave_type_id" class="form-control" required>
                                @foreach($leaveTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Reason</label>
                            <textarea name="reason" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <div id="notice-info" class="alert alert-info">
                                Please select a leave type to see notice requirements.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const leaveTypeSelect = document.querySelector('select[name="leave_type_id"]');
                const startDateInput = document.querySelector('input[name="start_date"]');
                const noticeDays = {
                    @foreach($leaveTypes as $type)
                        {{ $type->id }}: {{ $type->notice_days }},
                    @endforeach
                };

                leaveTypeSelect.addEventListener('change', function() {
                    const selectedTypeId = this.value;
                    const requiredDays = noticeDays[selectedTypeId];

                    if (requiredDays > 0) {
                        const minDate = new Date();
                        minDate.setDate(minDate.getDate() + requiredDays);
                        startDateInput.min = minDate.toISOString().split('T')[0];

                        document.getElementById('notice-info').textContent =
                            `This leave type requires ${requiredDays} days advance notice.`;
                    } else {
                        startDateInput.min = new Date().toISOString().split('T')[0];
                        document.getElementById('notice-info').textContent =
                            'No advance notice required for this leave type.';
                    }
                });
            });
        </script>
@endsection

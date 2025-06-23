@extends('layouts.backend')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Leave Requests</div>

                    <div class="card-body">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Date Range</th>
                                <th>Days</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($leaveRequests as $leave)
                                <tr>
                                    <td>{{ $leave->user->name }}</td>
                                    <td>{{ $leave->leaveType->name }}</td>
                                    <td>
                                        {{ $leave->start_date->format('M d, Y') }} -
                                        {{ $leave->end_date->format('M d, Y') }}
                                    </td>
                                    <td>{{ $leave->total_days }}</td>
                                    <td>{{ $leave->reason }}</td>
                                    <td>
                                    <span class="badge badge-{{ $leave->status == 'approved' ? 'success' : ($leave->status == 'rejected' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($leave->status) }}
                                    </span>
                                    </td>
                                    <td>
                                        @if($leave->status == 'pending')
                                            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#updateModal{{ $leave->id }}">
                                                Update
                                            </button>
                                        @endif
                                    </td>
                                </tr>

                                <!-- Update Modal -->
                                <div class="modal fade" id="updateModal{{ $leave->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <form action="{{ route('admin.leaves.update', $leave) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Update Leave Request</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label>Status</label>
                                                        <select name="status" class="form-control" required>
                                                            <option value="approved">Approve</option>
                                                            <option value="rejected">Reject</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Remarks</label>
                                                        <textarea name="admin_remarks" class="form-control" rows="3" required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Update</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            </tbody>
                        </table>

                        {{ $leaveRequests->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

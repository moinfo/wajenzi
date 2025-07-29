@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">Sales Daily Reports</h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item">Projects</li>
                    <li class="breadcrumb-item active" aria-current="page">Sales Daily Reports</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Filters -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Filters</h3>
        </div>
        <div class="block-content">
            <form method="GET" class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="text" name="start_date" class="form-control datepicker"
                               value="{{ request('start_date') ? \Carbon\Carbon::parse(request('start_date'))->format('d/m/Y') : '' }}"
                               placeholder="Select start date">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="text" name="end_date" class="form-control datepicker"
                               value="{{ request('end_date') ? \Carbon\Carbon::parse(request('end_date'))->format('d/m/Y') : '' }}"
                               placeholder="Select end date">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="DRAFT" {{ request('status') == 'DRAFT' ? 'selected' : '' }}>Draft</option>
                            <option value="PENDING" {{ request('status') == 'PENDING' ? 'selected' : '' }}>Pending</option>
                            <option value="APPROVED" {{ request('status') == 'APPROVED' ? 'selected' : '' }}>Approved</option>
                            <option value="REJECTED" {{ request('status') == 'REJECTED' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Prepared By</label>
                        <select name="user_id" class="form-control">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('sales_daily_reports') }}" class="btn btn-secondary">Clear</a>
                    <a href="{{ route('sales_daily_report.create') }}" class="btn btn-success float-right">
                        <i class="fa fa-plus"></i> New Report
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Reports List -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Reports List</h3>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-vcenter">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Prepared By</th>
                            <th>Department</th>
                            <th>Lead Follow-ups</th>
                            <th>Sales Activities</th>
                            <th>Client Concerns</th>
                            <th>Approvals</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $report)
                            <tr id="report-tr-{{ $report->id }}">
                                <td>{{ $report->report_date->format('M d, Y') }}</td>
                                <td>{{ $report->preparedBy->name }}</td>
                                <td>{{ $report->department()->first()?->name ?? '-' }}</td>
                                <td>
                                    <span class="badge badge-info">{{ $report->leadFollowups->count() }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-primary">{{ $report->salesActivities->count() }}</span>
                                    <br><small>Total: {{ number_format($report->getTotalSalesAmount(), 2) }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-warning">{{ $report->clientConcerns->count() }}</span>
                                </td>
                                <td class="text-center">
                                    <!-- Approval status summary component -->
                                    <x-ringlesoft-approval-status-summary :model="$report" />
                                </td>
                                <td class="text-center">
                                    @php
                                        $approvalStatus = $report->approvalStatus?->status ?? 'PENDING';
                                        $statusClass = [
                                            'Pending' => 'warning',
                                            'Submitted' => 'info',
                                            'Approved' => 'success',
                                            'Rejected' => 'danger',
                                            'Paid' => 'primary',
                                            'Completed' => 'success',
                                            'Discarded' => 'danger',
                                        ][$approvalStatus] ?? 'secondary';

                                        $statusIcon = [
                                            'Pending' => '<i class="fas fa-clock"></i>',
                                            'Submitted' => '<i class="fas fa-paper-plane"></i>',
                                            'Approved' => '<i class="fas fa-check"></i>',
                                            'Rejected' => '<i class="fas fa-times"></i>',
                                            'Paid' => '<i class="fas fa-money-bill"></i>',
                                            'Completed' => '<i class="fas fa-check-circle"></i>',
                                            'Discarded' => '<i class="fas fa-trash"></i>',
                                        ][$approvalStatus] ?? '<i class="fas fa-question-circle"></i>';
                                    @endphp
                                    <span class="badge badge-{{ $statusClass }} badge-pill" style="font-size: 0.9em; padding: 6px 10px;">
                                        {!! $statusIcon !!} {{ $approvalStatus }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a class="btn btn-sm btn-success js-tooltip-enabled" href="{{ route('sales_daily_report.show', ['id' => $report->id, 'document_type_id' => 14]) }}">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        @can('Edit Sales')
                                            <a class="btn btn-sm btn-primary js-tooltip-enabled" href="{{ route('sales_daily_report.edit', $report->id) }}" 
                                               data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        @endcan
                                        @can('Delete Sales')
                                            <button type="button"
                                                    onclick="deleteModelItem('SalesDailyReport', {{ $report->id }}, 'report-tr-{{ $report->id }}');"
                                                    class="btn btn-sm btn-danger js-tooltip-enabled"
                                                    data-toggle="tooltip" title="Delete"
                                                    data-original-title="Delete">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">No reports found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $reports->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.datepicker').datepicker({
        autoclose: true,
        format: 'dd/mm/yyyy',
        todayHighlight: true,
        defaultViewDate: new Date()
    });
});
</script>

@endsection

@extends('layouts.backend')

@section('content')
    <div class="container py-4">
        <!-- Dashboard Header -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h2 class="font-weight-bold text-dark mb-0">Leave Dashboard</h2>
            </div>
        </div>

        <!-- Leave Balance Cards -->
        <div class="row">
            @foreach($leaveBalances as $balance)
                <div class="col-md-3 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title text-muted mb-3">{{ $balance['name'] }}</h5>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <p class="text-muted mb-1 small">Total</p>
                                    <h3 class="mb-0 font-weight-bold">{{ $balance['total'] }}</h3>
                                </div>
                                <div class="text-right">
                                    <p class="text-muted mb-1 small">Remaining</p>
                                    <h3 class="mb-0 font-weight-bold text-{{ $balance['remaining'] < 5 ? 'danger' : 'success' }}">
                                        {{ $balance['remaining'] }}
                                    </h3>
                                </div>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-{{ $balance['remaining'] < 5 ? 'danger' : 'success' }}"
                                     role="progressbar"
                                     style="width: {{ ($balance['used'] / $balance['total']) * 100 }}%">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Recent Leave Requests -->
        <div class="row mt-2">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0">
                        <h5 class="mb-0 font-weight-bold text-dark">Recent Leave Requests</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                <tr>
                                    <th class="border-top-0 px-4">Type</th>
                                    <th class="border-top-0">Dates</th>
                                    <th class="border-top-0">Days</th>
                                    <th class="border-top-0">Status</th>
                                    <th class="border-top-0">Submitted</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($recentRequests as $request)
                                    <tr>
                                        <td class="px-4">
                                            <span class="font-weight-medium">{{ $request->leaveType->name }}</span>
                                        </td>
                                        <td>
                                            <span class="text-muted">
                                                {{ $request->start_date->format('M d') }} -
                                                {{ $request->end_date->format('M d, Y') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="font-weight-medium">{{ $request->total_days }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-pill px-3 py-2 {{
                                                $request->status == 'approved' ? 'badge-success-soft text-success' :
                                                ($request->status == 'rejected' ? 'badge-danger-soft text-danger' : 'badge-warning-soft text-warning')
                                            }}">
                                                {{ ucfirst($request->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $request->created_at->diffForHumans() }}</span>
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

    <style>
        .badge-success-soft {
            background-color: rgba(40, 167, 69, 0.1);
        }
        .badge-danger-soft {
            background-color: rgba(220, 53, 69, 0.1);
        }
        .badge-warning-soft {
            background-color: rgba(255, 193, 7, 0.1);
        }
        .progress {
            background-color: #f5f5f5;
            border-radius: 10px;
        }
        .progress-bar {
            border-radius: 10px;
        }
        .card {
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
        }
        .table td, .table th {
            vertical-align: middle;
        }
    </style>
@endsection

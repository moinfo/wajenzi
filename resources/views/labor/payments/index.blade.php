@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <i class="fa fa-money-bill-wave"></i> Labor Payments
            <div class="float-right">
                <a href="{{ route('labor.dashboard') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="fa fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="block block-rounded">
                    <div class="block-content block-content-full d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-0">Pending</p>
                            <h3 class="mb-0">{{ $stats['pending_count'] }}</h3>
                        </div>
                        <div class="text-secondary">
                            <i class="fa fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded bg-warning">
                    <div class="block-content block-content-full d-flex justify-content-between align-items-center text-white">
                        <div>
                            <p class="mb-0">Due for Approval</p>
                            <h3 class="mb-0">{{ $stats['due_count'] }}</h3>
                            <small>{{ number_format($stats['due_amount'], 0) }} TZS</small>
                        </div>
                        <div>
                            <i class="fa fa-exclamation-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded bg-info">
                    <div class="block-content block-content-full d-flex justify-content-between align-items-center text-white">
                        <div>
                            <p class="mb-0">Approved</p>
                            <h3 class="mb-0">{{ $stats['approved_count'] }}</h3>
                            <small>{{ number_format($stats['approved_amount'], 0) }} TZS</small>
                        </div>
                        <div>
                            <i class="fa fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded bg-success">
                    <div class="block-content block-content-full d-flex justify-content-between align-items-center text-white">
                        <div>
                            <p class="mb-0">Paid</p>
                            <h3 class="mb-0">{{ $stats['paid_count'] }}</h3>
                            <small>{{ number_format($stats['paid_amount'], 0) }} TZS</small>
                        </div>
                        <div>
                            <i class="fa fa-money-bill-wave fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Payment Phases</h3>
            </div>
            <div class="block-content">
                <form method="get" id="filter-form" autocomplete="off">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select name="project_id" class="form-control">
                                <option value="">All Projects</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ $selected_project == $project->id ? 'selected' : '' }}>
                                        {{ $project->project_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="contract_id" class="form-control">
                                <option value="">All Contracts</option>
                                @foreach($contracts as $contract)
                                    <option value="{{ $contract->id }}" {{ $selected_contract == $contract->id ? 'selected' : '' }}>
                                        {{ $contract->contract_number }} - {{ $contract->artisan?->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="pending" {{ $selected_status == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="due" {{ $selected_status == 'due' ? 'selected' : '' }}>Due</option>
                                <option value="approved" {{ $selected_status == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="paid" {{ $selected_status == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="held" {{ $selected_status == 'held' ? 'selected' : '' }}>Held</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>Contract</th>
                                <th>Project</th>
                                <th>Artisan</th>
                                <th>Phase</th>
                                <th class="text-right">Amount</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($phases as $phase)
                                <tr>
                                    <td class="text-center">{{ $loop->index + 1 }}</td>
                                    <td>
                                        <a href="{{ route('labor.contracts.show', $phase->labor_contract_id) }}">
                                            {{ $phase->contract?->contract_number }}
                                        </a>
                                    </td>
                                    <td>{{ Str::limit($phase->contract?->project?->project_name, 20) }}</td>
                                    <td>{{ $phase->contract?->artisan?->name }}</td>
                                    <td>
                                        <strong>{{ $phase->phase_name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $phase->percentage }}% - {{ Str::limit($phase->milestone_description, 30) }}</small>
                                    </td>
                                    <td class="text-right">
                                        {{ number_format($phase->amount, 0) }}
                                        <br>
                                        <small class="text-muted">{{ $phase->contract?->currency }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $phase->status_badge_class }}">
                                            {{ ucfirst($phase->status) }}
                                        </span>
                                        @if($phase->paid_at)
                                            <br><small>{{ $phase->paid_at->format('Y-m-d') }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($phase->isDue())
                                            <form action="{{ route('labor.payments.approve', $phase->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success"
                                                    onclick="return confirm('Approve this payment for processing?')">
                                                    <i class="fa fa-check"></i> Approve
                                                </button>
                                            </form>
                                        @elseif($phase->isApproved())
                                            <a href="{{ route('labor.payments.process.form', $phase->id) }}"
                                                class="btn btn-sm btn-primary">
                                                <i class="fa fa-money-bill"></i> Process
                                            </a>
                                        @elseif($phase->isHeld())
                                            <form action="{{ route('labor.payments.release', $phase->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-warning">
                                                    <i class="fa fa-play"></i> Release
                                                </button>
                                            </form>
                                        @elseif($phase->isPaid())
                                            <span class="text-success">
                                                <i class="fa fa-check-circle"></i> Paid
                                            </span>
                                            @if($phase->payment_reference)
                                                <br><small>{{ $phase->payment_reference }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">Awaiting</span>
                                        @endif
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
@endsection

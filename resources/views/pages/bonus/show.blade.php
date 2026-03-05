@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">
                <i class="fa fa-trophy text-warning mr-2"></i>{{ $task->task_number }}
            </h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item"><a href="{{ route('architect-bonus.index') }}">Architect Bonus</a></li>
                    <li class="breadcrumb-item active">{{ $task->task_number }}</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @php
        $statusColors = [
            'pending' => 'secondary', 'in_progress' => 'primary', 'completed' => 'info',
            'scored' => 'success', 'paid' => 'success', 'no_bonus' => 'danger',
        ];
    @endphp

    <!-- Status & Bonus Summary -->
    <div class="row">
        <div class="col-md-3">
            <div class="block block-rounded text-center">
                <div class="block-content block-content-full py-4">
                    <span class="badge badge-{{ $statusColors[$task->status] ?? 'secondary' }} px-3 py-2" style="font-size: 1rem;">
                        {{ ucwords(str_replace('_', ' ', $task->status)) }}
                    </span>
                    <p class="text-muted mb-0 mt-2">Status</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="block block-rounded text-center">
                <div class="block-content block-content-full py-4">
                    <p class="h2 {{ $task->bonus_amount > 0 ? 'text-success' : 'text-muted' }} mb-0">
                        TZS {{ number_format($task->bonus_amount) }}
                    </p>
                    <p class="text-muted mb-0">Bonus Amount</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="block block-rounded text-center">
                <div class="block-content block-content-full py-4">
                    <p class="h2 text-primary mb-0">
                        {{ $task->final_units !== null ? $task->final_units : '-' }}
                        @if($isAdmin)<small class="text-muted">/ {{ $task->max_units }}</small>@endif
                    </p>
                    <p class="text-muted mb-0">Units Earned</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="block block-rounded text-center">
                <div class="block-content block-content-full py-4">
                    <p class="h2 mb-0">
                        @if($task->performance_score !== null)
                            {{ round($task->performance_score * 100) }}%
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </p>
                    <p class="text-muted mb-0">Performance Score</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Details -->
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Task Details</h3>
            <div class="block-options">
                @if($isAdmin && $task->status === 'pending')
                    <form action="{{ route('architect-bonus.start', $task->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fa fa-play mr-1"></i> Start Task
                        </button>
                    </form>
                @endif
                @if($isAdmin && in_array($task->status, ['in_progress', 'completed']))
                    <a href="{{ route('architect-bonus.score', $task->id) }}" class="btn btn-sm btn-warning">
                        <i class="fa fa-star mr-1"></i> Score Task
                    </a>
                @endif
                @if($isAdmin && $task->status === 'scored')
                    <form action="{{ route('architect-bonus.paid', $task->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Mark this bonus as paid?')">
                            <i class="fa fa-money-bill mr-1"></i> Mark Paid
                        </button>
                    </form>
                @endif
            </div>
        </div>
        <div class="block-content">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="text-muted" style="width:40%">Project Name</td>
                            <td><strong>{{ $task->project_name }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Architect</td>
                            <td>{{ $task->architect->name ?? '-' }}</td>
                        </tr>
                        @if($isAdmin)
                        <tr>
                            <td class="text-muted">Project Budget</td>
                            <td><strong>TZS {{ number_format($task->project_budget) }}</strong></td>
                        </tr>
                        @endif
                        @if($task->lead)
                        <tr>
                            <td class="text-muted">Linked Lead</td>
                            <td>
                                @if($isAdmin)
                                    <a href="{{ route('leads.show', $task->lead_id) }}">{{ $task->lead->lead_number }}</a>
                                @else
                                    {{ $task->lead->lead_number }}
                                @endif
                            </td>
                        </tr>
                        @endif
                        @if($task->notes)
                        <tr>
                            <td class="text-muted">Notes</td>
                            <td>{{ $task->notes }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="text-muted" style="width:40%">Start Date</td>
                            <td>{{ $task->start_date->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Scheduled Completion</td>
                            <td>{{ $task->scheduled_completion_date->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Scheduled Duration</td>
                            <td>{{ $task->scheduled_days }} working days</td>
                        </tr>
                        @if($task->actual_completion_date)
                        <tr>
                            <td class="text-muted">Actual Completion</td>
                            <td>
                                {{ $task->actual_completion_date->format('d M Y') }}
                                @if($task->actual_days > $task->scheduled_days)
                                    <span class="text-danger ml-1">({{ $task->actual_days - $task->scheduled_days }} days late)</span>
                                @elseif($task->actual_days < $task->scheduled_days)
                                    <span class="text-success ml-1">({{ $task->scheduled_days - $task->actual_days }} days early)</span>
                                @else
                                    <span class="text-info ml-1">(on time)</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Actual Duration</td>
                            <td>{{ $task->actual_days }} working days</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-muted">Created By</td>
                            <td>{{ $task->creator->name ?? '-' }} on {{ $task->created_at->format('d M Y') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Breakdown (admin or after scoring) -->
    @if($isAdmin && $task->performance_score !== null)
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title"><i class="fa fa-chart-line text-info mr-2"></i>Performance Breakdown</h3>
        </div>
        <div class="block-content">
            <div class="row text-center">
                <div class="col-md-4">
                    <div class="p-3 border rounded mb-3">
                        <h5 class="text-muted mb-1">Schedule Performance (SP)</h5>
                        <p class="h2 mb-1">{{ $task->schedule_performance }}</p>
                        <small class="text-muted">
                            Weight: {{ ($weights['schedule'] ?? 0.4) * 100 }}%
                            | Contribution: {{ round(($weights['schedule'] ?? 0.4) * $task->schedule_performance, 3) }}
                        </small>
                        <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar bg-primary" style="width: {{ min($task->schedule_performance * 100, 110) }}%"></div>
                        </div>
                        <small class="text-muted">{{ $task->scheduled_days }} sched / {{ $task->actual_days }} actual days (cap 1.1)</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border rounded mb-3">
                        <h5 class="text-muted mb-1">Design Quality (DQ)</h5>
                        <p class="h2 mb-1">{{ $task->design_quality_score }}</p>
                        <small class="text-muted">
                            Weight: {{ ($weights['quality'] ?? 0.4) * 100 }}%
                            | Contribution: {{ round(($weights['quality'] ?? 0.4) * $task->design_quality_score, 3) }}
                        </small>
                        <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: {{ $task->design_quality_score * 100 }}%"></div>
                        </div>
                        <small class="text-muted">Rated by design manager (0.4 - 1.0)</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border rounded mb-3">
                        <h5 class="text-muted mb-1">Client Approval (CA)</h5>
                        <p class="h2 mb-1">{{ $task->client_approval_efficiency }}</p>
                        <small class="text-muted">
                            Weight: {{ ($weights['client'] ?? 0.2) * 100 }}%
                            | Contribution: {{ round(($weights['client'] ?? 0.2) * $task->client_approval_efficiency, 3) }}
                        </small>
                        <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar bg-warning" style="width: {{ $task->client_approval_efficiency * 100 }}%"></div>
                        </div>
                        <small class="text-muted">{{ $task->client_revisions }} revision(s) (CA = 1/revisions)</small>
                    </div>
                </div>
            </div>

            <div class="alert alert-light border text-center mt-2">
                <strong>PS</strong> = ({{ ($weights['schedule'] ?? 0.4) }} x {{ $task->schedule_performance }})
                + ({{ ($weights['quality'] ?? 0.4) }} x {{ $task->design_quality_score }})
                + ({{ ($weights['client'] ?? 0.2) }} x {{ $task->client_approval_efficiency }})
                = <strong>{{ $task->performance_score }}</strong>
                &nbsp;|&nbsp;
                <strong>Final Units</strong> = {{ $task->max_units }} x {{ $task->performance_score }}
                = <strong>{{ $task->final_units }}</strong>
                &nbsp;|&nbsp;
                <strong>Bonus</strong> = {{ $task->final_units }} x 10,000
                = <strong>TZS {{ number_format($task->bonus_amount) }}</strong>
            </div>
        </div>
    </div>
    @endif

    <a href="{{ route('architect-bonus.index') }}" class="btn btn-secondary mb-4">
        <i class="fa fa-arrow-left mr-1"></i> Back to Tasks
    </a>
</div>
@endsection

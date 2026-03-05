@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">
                <i class="fa fa-chart-bar text-info mr-2"></i>Bonus Report
            </h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item"><a href="{{ route('architect-bonus.index') }}">Architect Bonus</a></li>
                    <li class="breadcrumb-item active">Report</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    <!-- Month Picker -->
    <div class="block block-rounded">
        <div class="block-content">
            <form method="GET" class="row align-items-end">
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label><strong>Report Month</strong></label>
                        <input type="month" name="month" class="form-control" value="{{ $month }}" onchange="this.form.submit()">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-filter mr-1"></i> Generate</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($architectSummary->isNotEmpty())
    <!-- Per-Architect Summary -->
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Architect Summary - {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</h3>
        </div>
        <div class="block-content block-content-full">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-vcenter">
                    <thead>
                        <tr>
                            <th>Architect</th>
                            <th class="text-center">Tasks</th>
                            <th class="text-center">Total Units</th>
                            <th class="text-right">Total Bonus (TZS)</th>
                            <th class="text-center">Avg Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $grandTotal = 0; $grandUnits = 0; @endphp
                        @foreach($architectSummary as $summary)
                            @php $grandTotal += $summary['total_bonus']; $grandUnits += $summary['total_units']; @endphp
                            <tr>
                                <td><strong>{{ $summary['architect']->name ?? '-' }}</strong></td>
                                <td class="text-center">{{ $summary['tasks_count'] }}</td>
                                <td class="text-center">{{ $summary['total_units'] }}</td>
                                <td class="text-right"><strong>{{ number_format($summary['total_bonus']) }}</strong></td>
                                <td class="text-center">
                                    @if($summary['avg_performance'] > 0)
                                        {{ round($summary['avg_performance'] * 100) }}%
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="font-weight-bold bg-body-light">
                            <td>TOTAL</td>
                            <td class="text-center">{{ $tasks->count() }}</td>
                            <td class="text-center">{{ $grandUnits }}</td>
                            <td class="text-right">TZS {{ number_format($grandTotal) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Detailed Tasks -->
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Task Details</h3>
        </div>
        <div class="block-content block-content-full">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-vcenter table-sm">
                    <thead>
                        <tr>
                            <th>Task #</th>
                            <th>Project</th>
                            <th>Architect</th>
                            <th class="text-center">SP</th>
                            <th class="text-center">DQ</th>
                            <th class="text-center">CA</th>
                            <th class="text-center">PS</th>
                            <th class="text-center">Units</th>
                            <th class="text-right">Bonus (TZS)</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tasks as $task)
                            <tr>
                                <td>
                                    <a href="{{ route('architect-bonus.show', $task->id) }}">{{ $task->task_number }}</a>
                                </td>
                                <td>{{ $task->project_name }}</td>
                                <td>{{ $task->architect->name ?? '-' }}</td>
                                <td class="text-center">{{ $task->schedule_performance ?? '-' }}</td>
                                <td class="text-center">{{ $task->design_quality_score ?? '-' }}</td>
                                <td class="text-center">{{ $task->client_approval_efficiency ?? '-' }}</td>
                                <td class="text-center">
                                    <strong>{{ $task->performance_score ? round($task->performance_score * 100) . '%' : '-' }}</strong>
                                </td>
                                <td class="text-center">{{ $task->final_units ?? '-' }}</td>
                                <td class="text-right">
                                    @if($task->bonus_amount > 0)
                                        {{ number_format($task->bonus_amount) }}
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @php
                                        $colors = ['scored' => 'success', 'paid' => 'success', 'no_bonus' => 'danger'];
                                    @endphp
                                    <span class="badge badge-{{ $colors[$task->status] ?? 'secondary' }}">
                                        {{ ucwords(str_replace('_', ' ', $task->status)) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
        <div class="block block-rounded">
            <div class="block-content text-center py-5">
                <i class="fa fa-chart-bar fa-3x text-muted mb-3"></i>
                <p class="text-muted">No scored tasks found for {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}.</p>
            </div>
        </div>
    @endif
</div>
@endsection

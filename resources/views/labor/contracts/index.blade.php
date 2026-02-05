@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <i class="fa fa-file-contract"></i> Labor Contracts
            <div class="float-right">
                <a href="{{ route('labor.dashboard') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="fa fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
        </div>

        @if($availableRequests->count() > 0)
        <div class="block">
            <div class="block-header block-header-default bg-success">
                <h3 class="block-title text-white">
                    <i class="fa fa-check-circle"></i> Approved Requests Ready for Contract ({{ $availableRequests->count() }})
                </h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Request #</th>
                                <th>Project</th>
                                <th>Artisan</th>
                                <th class="text-right">Amount</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($availableRequests as $request)
                                <tr>
                                    <td>{{ $request->request_number }}</td>
                                    <td>{{ $request->project?->project_name }}</td>
                                    <td>{{ $request->artisan?->name }}</td>
                                    <td class="text-right">{{ number_format($request->final_amount, 0) }} {{ $request->currency }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('labor.contracts.create', $request->id) }}"
                                            class="btn btn-sm btn-primary">
                                            <i class="fa fa-file-contract"></i> Create Contract
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">All Contracts</h3>
            </div>
            <div class="block-content">
                <form method="get" id="filter-form" autocomplete="off">
                    <div class="row mb-3">
                        <div class="col-md-4">
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
                            <select name="status" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="draft" {{ $selected_status == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="active" {{ $selected_status == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="on_hold" {{ $selected_status == 'on_hold' ? 'selected' : '' }}>On Hold</option>
                                <option value="completed" {{ $selected_status == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="terminated" {{ $selected_status == 'terminated' ? 'selected' : '' }}>Terminated</option>
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
                                <th>Contract #</th>
                                <th>Project</th>
                                <th>Artisan</th>
                                <th class="text-center">Duration</th>
                                <th class="text-right">Total</th>
                                <th class="text-center">Payment Progress</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contracts as $contract)
                                <tr>
                                    <td class="text-center">{{ $loop->index + 1 }}</td>
                                    <td>
                                        <a href="{{ route('labor.contracts.show', $contract->id) }}">
                                            <strong>{{ $contract->contract_number }}</strong>
                                        </a>
                                        <br>
                                        <small class="text-muted">{{ $contract->contract_date?->format('Y-m-d') }}</small>
                                    </td>
                                    <td>{{ Str::limit($contract->project?->project_name, 25) }}</td>
                                    <td>
                                        {{ $contract->artisan?->name }}
                                        @if($contract->artisan?->trade_skill)
                                            <br><small class="text-muted">{{ $contract->artisan->trade_skill }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{ $contract->start_date?->format('m/d') }} - {{ $contract->end_date?->format('m/d') }}
                                        @if($contract->isActive())
                                            <br>
                                            @if($contract->days_remaining > 0)
                                                <small class="text-info">{{ $contract->days_remaining }} days left</small>
                                            @else
                                                <small class="text-danger">{{ $contract->days_overdue }} days overdue</small>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        {{ number_format($contract->total_amount, 0) }}
                                        <br><small class="text-muted">{{ $contract->currency }}</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success" style="width: {{ $contract->payment_progress }}%">
                                                {{ number_format($contract->payment_progress, 0) }}%
                                            </div>
                                        </div>
                                        <small>{{ number_format($contract->amount_paid, 0) }} / {{ number_format($contract->total_amount, 0) }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $contract->status_badge_class }}">
                                            {{ ucfirst($contract->status) }}
                                        </span>
                                        @if($contract->isSigned())
                                            <br><small class="text-success"><i class="fa fa-signature"></i> Signed</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="{{ route('labor.contracts.show', $contract->id) }}"
                                                class="btn btn-sm btn-info" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            @if($contract->isDraft())
                                                <a href="{{ route('labor.contracts.edit', $contract->id) }}"
                                                    class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                            @endif
                                            @if($contract->isActive() || $contract->isDraft())
                                                <a href="{{ route('labor.contracts.pdf', $contract->id) }}"
                                                    class="btn btn-sm btn-secondary" title="Download PDF" target="_blank">
                                                    <i class="fa fa-file-pdf"></i>
                                                </a>
                                            @endif
                                        </div>
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

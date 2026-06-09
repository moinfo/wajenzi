@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">
                <i class="fa fa-trophy text-warning mr-2"></i>Architect Bonus Scheme
            </h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item">Architect Bonus</li>
                    <li class="breadcrumb-item active">Tasks</li>
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

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-md-4">
            <div class="block block-rounded text-center">
                <div class="block-content block-content-full">
                    <div class="py-3">
                        <p class="h1 text-success mb-0">TZS {{ number_format($totalBonusEarned) }}</p>
                        <p class="text-muted mb-0">Total Bonus Earned</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="block block-rounded text-center">
                <div class="block-content block-content-full">
                    <div class="py-3">
                        <p class="h1 text-primary mb-0">{{ $totalTasksCompleted }}</p>
                        <p class="text-muted mb-0">Tasks Completed</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="block block-rounded text-center">
                <div class="block-content block-content-full">
                    <div class="py-3">
                        <p class="h1 text-warning mb-0">{{ $pendingTasks }}</p>
                        <p class="text-muted mb-0">Pending Tasks</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Task List -->
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Bonus Tasks <small class="text-muted">({{ $tasks->total() }})</small></h3>
            @if($isAdmin)
            <div class="block-options">
                <a href="{{ route('architect-bonus.backfill-suggestions') }}" class="btn btn-outline-secondary btn-sm mr-2"
                   title="Link existing bonus tasks to their project schedules">
                    <i class="fa fa-link mr-1"></i> Backfill Schedule Links
                </a>
                <a href="{{ route('architect-bonus.create') }}" class="btn btn-success btn-sm">
                    <i class="fa fa-plus mr-1"></i> New Task
                </a>
            </div>
            @endif
        </div>
        <div class="block-content block-content-full">
            <!-- Filters -->
            <form method="GET" id="filterForm">
                <div class="row mb-3">
                    <div class="col-md-3 mb-2">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-search"></i></span>
                            </div>
                            <input type="text" name="search" class="form-control"
                                   value="{{ request('search') }}" placeholder="Search task or project...">
                        </div>
                    </div>
                    @if($isAdmin)
                    <div class="col-md-2 mb-2">
                        <select name="architect_id" class="form-control" onchange="this.form.submit()">
                            <option value="">All Architects</option>
                            @foreach($architects as $arch)
                                <option value="{{ $arch->id }}" {{ request('architect_id') == $arch->id ? 'selected' : '' }}>
                                    {{ $arch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-2 mb-2">
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            @foreach(['pending', 'in_progress', 'completed', 'scored', 'paid', 'no_bonus'] as $s)
                                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $s)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1 mb-2">
                        @if(request()->hasAny(['search', 'architect_id', 'status']))
                            <a href="{{ route('architect-bonus.index') }}" class="btn btn-alt-secondary btn-block">
                                <i class="fa fa-undo"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-vcenter">
                    <thead>
                        <tr>
                            <th>Task #</th>
                            <th>Project</th>
                            @if($isAdmin)<th>Architect</th>@endif
                            <th>Deadline</th>
                            @if($isAdmin)<th class="text-right">Budget (TZS)</th>@endif
                            <th class="text-center">Max Units</th>
                            <th class="text-center">Earned Units</th>
                            <th class="text-right">Bonus (TZS)</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tasks as $task)
                            <tr>
                                <td><strong>{{ $task->task_number }}</strong></td>
                                <td>{{ $task->project_name }}</td>
                                @if($isAdmin)<td>{{ $task->architect->name ?? '-' }}</td>@endif
                                <td>{{ $task->scheduled_completion_date->format('d-M-Y') }}</td>
                                @if($isAdmin)
                                <td class="text-right">
                                    {{ number_format($task->project_budget) }}
                                    @if(!in_array($task->status, ['scored', 'paid', 'no_bonus']))
                                        <button type="button" class="btn btn-sm btn-link p-0 ml-1 edit-budget-btn"
                                                data-id="{{ $task->id }}"
                                                data-task="{{ $task->task_number }}"
                                                data-budget="{{ $task->project_budget }}"
                                                title="Edit budget">
                                            <i class="fa fa-pencil-alt"></i>
                                        </button>
                                    @endif
                                </td>
                                @endif
                                <td class="text-center">{{ $isAdmin ? $task->max_units : '-' }}</td>
                                <td class="text-center">
                                    @if($task->final_units !== null)
                                        <strong>{{ $task->final_units }}</strong>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if($task->bonus_amount > 0)
                                        <strong class="text-success">{{ number_format($task->bonus_amount) }}</strong>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @php
                                        $statusColors = [
                                            'pending' => 'badge-secondary',
                                            'in_progress' => 'badge-primary',
                                            'completed' => 'badge-info',
                                            'scored' => 'badge-success',
                                            'paid' => 'badge-success',
                                            'no_bonus' => 'badge-danger',
                                        ];
                                    @endphp
                                    <span class="badge {{ $statusColors[$task->status] ?? 'badge-secondary' }}">
                                        {{ ucwords(str_replace('_', ' ', $task->status)) }}
                                    </span>
                                    @if($task->status === 'pending' && !$task->accepted_at)
                                        <br><small class="text-warning"><i class="fa fa-clock mr-1"></i>Needs acceptance</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="{{ route('architect-bonus.show', $task->id) }}" class="btn btn-sm btn-alt-secondary" title="View">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        @if(!$isAdmin && $task->status === 'pending' && $task->architect_id === auth()->id())
                                            <form action="{{ route('architect-bonus.accept', $task->id) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Accept task {{ $task->task_number }}? The bonus clock will start from today.')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" title="Accept Task">
                                                    <i class="fa fa-check"></i> Accept
                                                </button>
                                            </form>
                                        @endif
                                        @if($isAdmin && in_array($task->status, ['in_progress', 'completed']))
                                            <a href="{{ route('architect-bonus.score', $task->id) }}" class="btn btn-sm btn-alt-primary" title="Score">
                                                <i class="fa fa-star"></i>
                                            </a>
                                        @endif
                                        @can('Delete Bonus Task')
                                            @if($task->status === 'pending')
                                                <form action="{{ route('architect-bonus.destroy', $task->id) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('Delete task {{ $task->task_number }}? This cannot be undone.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-alt-danger" title="Delete">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isAdmin ? 10 : 7 }}" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fa fa-trophy fa-2x mb-2 d-block"></i>
                                        No bonus tasks found
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($tasks->hasPages())
            <div class="row align-items-center mt-3">
                <div class="col-sm-5 text-muted">
                    Showing {{ $tasks->firstItem() }}-{{ $tasks->lastItem() }} of {{ $tasks->total() }}
                </div>
                <div class="col-sm-7">
                    <nav class="d-flex justify-content-end">
                        {{ $tasks->appends(request()->query())->links() }}
                    </nav>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@if($isAdmin)
<!-- Edit Budget Modal -->
<div class="modal fade" id="editBudgetModal" tabindex="-1" role="dialog" aria-labelledby="editBudgetModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" id="editBudgetForm">
                @csrf
                <div class="modal-header">
                    <h3 class="block-title" id="editBudgetModalTitle">Edit Project Budget</h3>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Task <strong id="eb-task"></strong> — updating the budget recomputes the maximum bonus units from the tier table.</p>
                    <div class="form-group">
                        <label class="control-label" for="eb-budget">Project Budget (TZS)</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="eb-budget" name="project_budget" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-alt-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save mr-1"></i> Save Budget</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@section('js_after')
@if($isAdmin)
<script>
    $(function () {
        $('.edit-budget-btn').on('click', function () {
            var $btn = $(this);
            $('#eb-task').text($btn.data('task'));
            $('#eb-budget').val($btn.data('budget'));
            $('#editBudgetForm').attr('action', '{{ url('architect-bonus') }}/' + $btn.data('id') + '/budget');
            $('#editBudgetModal').modal('show');
        });
    });
</script>
@endif
@endsection

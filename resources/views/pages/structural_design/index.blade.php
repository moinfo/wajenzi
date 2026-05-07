@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">Structural Design Dashboard
            <div class="float-right">
                @can('Create Structural Design')
                <button type="button" data-toggle="modal" data-target="#createModal"
                    class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                    <i class="si si-plus">&nbsp;</i>New Structural Design
                </button>
                @endcan
            </div>
        </div>

        {{-- Filters --}}
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">All Structural Designs</h3>
            </div>
            <div class="block-content">
                <form method="GET" class="mb-3">
                    <div class="row">
                        <div class="col-md-3">
                            <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                @foreach(['pending','in_progress','submitted','approved','rejected'] as $s)
                                <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $s)) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>

                <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Project</th>
                            <th>Client</th>
                            <th>Engineer</th>
                            <th>Stages</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($designs as $design)
                        @php
                        $completedStages = $design->stages->where('status', 'completed')->count();
                        $totalStages     = $design->stages->count();
                        $statusColors = [
                            'pending'     => 'warning',
                            'in_progress' => 'primary',
                            'submitted'   => 'info',
                            'approved'    => 'success',
                            'rejected'    => 'danger',
                        ];
                        @endphp
                        <tr>
                            <td>{{ $design->document_number }}</td>
                            <td><strong>{{ $design->project->project_name ?? '—' }}</strong></td>
                            <td>
                                @if($design->project?->client)
                                    {{ $design->project->client->first_name }}
                                    {{ $design->project->client->last_name }}
                                @else —
                                @endif
                            </td>
                            <td>{{ $design->assignedEngineer->name ?? '<span class="text-muted">Unassigned</span>' }}</td>
                            <td>
                                <span class="badge badge-{{ $completedStages === $totalStages && $totalStages > 0 ? 'success' : 'secondary' }}">
                                    {{ $completedStages }}/{{ $totalStages }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-{{ $statusColors[$design->status] ?? 'secondary' }}">
                                    {{ ucwords(str_replace('_', ' ', $design->status)) }}
                                </span>
                            </td>
                            <td>{{ $design->created_at->format('d/m/Y') }}</td>
                            <td>
                                <a href="{{ route('structural_design.show', $design) }}"
                                   class="btn btn-sm btn-alt-primary">
                                    <i class="fa fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No structural designs found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                {{ $designs->links() }}
            </div>
        </div>
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form action="{{ route('structural_design.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create Structural Design</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Project <span class="text-danger">*</span></label>
                        <select name="project_id" class="form-control" required>
                            <option value="">Select Project</option>
                            @foreach(\App\Models\Project::orderBy('project_name')->get() as $p)
                            <option value="{{ $p->id }}">{{ $p->project_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Assign Engineer</label>
                        <select name="assigned_engineer_id" class="form-control">
                            <option value="">— Unassigned —</option>
                            @foreach(\App\Models\User::whereHas('roles', fn($q) => $q->whereIn('name',['Structural Engineer','Engineer']))->get() as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

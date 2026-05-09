@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">BOQ Preparation Plans
            <div class="float-right">
                <button type="button" data-toggle="modal" data-target="#createModal"
                    class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                    <i class="si si-plus">&nbsp;</i>New BOQ Plan
                </button>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }} <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }} <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        @endif

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">All BOQ Preparation Plans</h3>
            </div>
            <div class="block-content">
                <form method="GET" class="mb-3">
                    <div class="row">
                        <div class="col-md-3">
                            <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                @foreach(['draft','submitted','approved','rejected'] as $s)
                                <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>
                                    {{ ucwords($s) }}
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
                            <th>Created By</th>
                            <th>Planned Period</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plans as $plan)
                        @php
                        $statusColors = ['draft'=>'secondary','submitted'=>'info','approved'=>'success','rejected'=>'danger'];
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $plan->project->project_name ?? '—' }}</strong></td>
                            <td>
                                @if($plan->project->client)
                                    {{ $plan->project->client->first_name }} {{ $plan->project->client->last_name }}
                                @else —
                                @endif
                            </td>
                            <td>{{ $plan->creator->name ?? '—' }}</td>
                            <td>
                                @if($plan->planned_start)
                                    {{ $plan->planned_start->format('d/m/Y') }} – {{ $plan->planned_end?->format('d/m/Y') }}
                                @else —
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $statusColors[$plan->status] ?? 'secondary' }}">
                                    {{ ucfirst($plan->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('project-boq-plans.show', $plan) }}" class="btn btn-xs btn-alt-primary">
                                    <i class="fa fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted">No BOQ preparation plans found.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                {{ $plans->links() }}
            </div>
        </div>
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-clipboard-list mr-2"></i>New BOQ Preparation Plan</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('project-boq-plans.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Project <span class="text-danger">*</span></label>
                        <select name="project_id" class="form-control" required>
                            <option value="">— Select Project (must have approved structural design) —</option>
                            @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Only projects with an approved structural design are listed.</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Planned Start <span class="text-danger">*</span></label>
                                <input type="date" name="planned_start" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Planned End <span class="text-danger">*</span></label>
                                <input type="date" name="planned_end" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Scope Description</label>
                        <textarea name="scope_description" class="form-control" rows="4"
                            placeholder="Describe the BOQ scope: what will be quantified, methodology, data sources..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Create Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

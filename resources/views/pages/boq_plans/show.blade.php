@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">

        <div class="content-heading">BOQ Preparation Plan — {{ $plan->project->project_name ?? '' }}
            <div class="float-right">
                <a href="{{ route('project-boq-plans.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
                @if(in_array($plan->status, ['draft','rejected']))
                <form action="{{ route('project-boq-plans.submit', $plan) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success"
                        onclick="return confirm('Submit this BOQ plan for CEO/MD approval?')">
                        <i class="fa fa-paper-plane"></i> Submit for Approval
                    </button>
                </form>
                @endif
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

        <div class="row">

            {{-- Left: Plan Details + Approval --}}
            <div class="col-md-4">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Plan Details</h3>
                    </div>
                    <div class="block-content">
                        @php
                        $statusColors = ['draft'=>'secondary','submitted'=>'info','approved'=>'success','rejected'=>'danger'];
                        @endphp
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th class="text-muted">Reference</th>
                                <td>{{ $plan->document_number }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Project</th>
                                <td>{{ $plan->project->project_name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Status</th>
                                <td>
                                    <span class="badge badge-{{ $statusColors[$plan->status] ?? 'secondary' }}">
                                        {{ ucfirst($plan->status) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted">Created By</th>
                                <td>{{ $plan->creator->name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Planned Start</th>
                                <td>{{ $plan->planned_start?->format('d/m/Y') ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Planned End</th>
                                <td>{{ $plan->planned_end?->format('d/m/Y') ?? '—' }}</td>
                            </tr>
                            @if($plan->submitted_at)
                            <tr>
                                <th class="text-muted">Submitted</th>
                                <td>{{ $plan->submitted_at->format('d/m/Y') }}</td>
                            </tr>
                            @endif
                            @if($plan->approved_at)
                            <tr>
                                <th class="text-muted">Approved</th>
                                <td>{{ $plan->approved_at->format('d/m/Y') }}</td>
                            </tr>
                            @endif
                        </table>

                        @if($plan->scope_description)
                        <div class="alert alert-light border mt-2">
                            <small class="text-muted d-block mb-1">Scope Description:</small>
                            {{ $plan->scope_description }}
                        </div>
                        @endif
                    </div>
                </div>

                {{-- MD/CEO Approval Panel --}}
                @if($plan->approvalStatus)
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Approval</h3>
                    </div>
                    <div class="block-content">
                        @include('partials.approval_status', ['approvable' => $plan])
                    </div>
                </div>
                @endif
            </div>

            {{-- Right: MD/CEO Approval Actions (when pending) --}}
            <div class="col-md-8">
                @if($plan->isPendingApproval())
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-check-circle text-warning mr-2"></i>Approval Required
                        </h3>
                    </div>
                    <div class="block-content">
                        <p class="text-muted mb-3">
                            This BOQ preparation plan has been submitted by the Quantity Surveyor and awaits your approval before BOQ work can begin.
                        </p>
                        <x-ringlesoft-approval-actions :model="$plan" />
                    </div>
                </div>
                @elseif($plan->isApproved())
                <div class="alert alert-success">
                    <i class="fa fa-check-circle mr-2"></i>
                    This BOQ preparation plan has been approved. The QS can now proceed to prepare the Bill of Quantities.
                    <div class="mt-2">
                        <a href="{{ route('project_boq.create') }}" class="btn btn-sm btn-success">
                            <i class="fa fa-plus"></i> Create BOQ for This Project
                        </a>
                    </div>
                </div>
                @elseif($plan->status === 'draft')
                <div class="alert alert-info">
                    <i class="fa fa-info-circle mr-2"></i>
                    This plan is in draft. Review the details and submit for CEO/MD approval when ready.
                </div>
                @elseif($plan->status === 'rejected')
                <div class="alert alert-danger">
                    <i class="fa fa-times-circle mr-2"></i>
                    This plan was rejected. Please revise and resubmit.
                </div>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection

{{-- project_boqs.blade.php --}}
@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Project BOQs
                <div class="float-right">
                    <a href="{{ route('project_boq_templates') }}" class="btn btn-rounded min-width-125 mb-10 btn-alt-info">
                        <i class="si si-layers">&nbsp;</i>BOQ Templates
                    </a>
                    @can('Create BOQ')
                        <button type="button" onclick="loadFormModal('project_boq_form', {className: 'ProjectBoq'}, 'Create New BOQ', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New BOQ</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All BOQs</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form name="boq_search" action="" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Project</span>
                                                    </div>
                                                    <select name="project_id" id="input-project" class="form-control">
                                                        <option value="">All Projects</option>
                                                        @foreach ($projects as $project)
                                                            <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Type</span>
                                                    </div>
                                                    <select name="type" id="input-type" class="form-control">
                                                        <option value="">All Types</option>
                                                        <option value="client">Client</option>
                                                        <option value="internal">Internal</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Status</span>
                                                    </div>
                                                    <select name="status" id="input-status" class="form-control">
                                                        <option value="">All Status</option>
                                                        <option value="draft">Draft</option>
                                                        <option value="submitted">Submitted</option>
                                                        <option value="approved">Approved</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-2">
                                                <div>
                                                    <button type="submit" name="submit" class="btn btn-sm btn-primary">Show</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 80px;">#</th>
                                    <th>Project</th>
                                    <th>Version</th>
                                    <th>Type</th>
                                    <th class="text-right">Total Amount</th>
                                    <th>Approvals</th>
                                    <th>Status</th>
                                    <th class="text-center" style="width: 120px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($boqs as $boq)
                                    <tr id="boq-tr-{{$boq->id}}">
                                        <td class="text-center">{{$loop->index + 1}}</td>
                                        <td>
                                            <a href="{{ route('project_boq', ['id' => $boq->id, 'document_type_id' => 0]) }}" class="font-w600">
                                                {{ $boq->project->project_name }}
                                            </a>
                                        </td>
                                        <td class="text-center">{{ $boq->version }}</td>
                                        <td>{{ ucfirst($boq->type) }}</td>
                                        <td class="text-right">{{ number_format($boq->total_amount, 2) }}</td>
                                        <td class="text-center">
                                            <x-ringlesoft-approval-status-summary :model="$boq" />
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $approvalStatus = $boq->approvalStatus?->status ?? 'Pending';
                                                $statusClass = [
                                                    'Pending' => 'warning',
                                                    'Submitted' => 'info',
                                                    'Approved' => 'success',
                                                    'Rejected' => 'danger',
                                                    'Completed' => 'primary',
                                                    'Discarded' => 'danger',
                                                ][$approvalStatus] ?? 'secondary';
                                            @endphp
                                            <span class="badge badge-{{ $statusClass }} badge-pill" style="font-size: 0.9em; padding: 6px 10px;">
                                                {{ $approvalStatus }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success" href="{{route('project_boq.show',['id' => $boq->id])}}" title="Items">
                                                    <i class="fa fa-list"></i>
                                                </a>
                                                @can('Edit BOQ')
                                                    <button type="button"
                                                            onclick="loadFormModal('project_boq_form', {className: 'ProjectBoq', id: {{$boq->id}}}, 'Edit BOQ', 'modal-md');"
                                                            class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan
                                                @can('Delete BOQ')
                                                    <button type="button"
                                                            onclick="deleteModelItem('ProjectBoq', {{$boq->id}}, 'boq-tr-{{$boq->id}}');"
                                                            class="btn btn-sm btn-danger" title="Delete">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td colspan="4" class="text-right"><strong>Total:</strong></td>
                                    <td class="text-right"><strong>{{ number_format($boqs->sum('total_amount'), 2) }}</strong></td>
                                    <td colspan="3"></td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- project_boqs.blade.php --}}
@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Project BOQs
                <div class="float-right">
                    @can('Create BOQ')
                        <button type="button" onclick="loadFormModal('project_boq_form', {className: 'ProjectBoq'}, 'Create New BOQ', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New BOQ</button>
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
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Project</th>
                                    <th>Version</th>
                                    <th>Type</th>
                                    <th class="text-right">Total Amount</th>
                                    <th>Status</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($boqs as $boq)
                                    <tr id="boq-tr-{{$boq->id}}">
                                        <td class="text-center">{{$loop->index + 1}}</td>
                                        <td>{{ $boq->project->project_name }}</td>
                                        <td class="text-center">{{ $boq->version }}</td>
                                        <td>{{ ucfirst($boq->type) }}</td>
                                        <td class="text-right">{{ number_format($boq->total_amount, 2) }}</td>
                                        <td>
                                            @if($boq->status == 'draft')
                                                <div class="badge badge-warning">{{ $boq->status}}</div>
                                            @elseif($boq->status == 'submitted')
                                                <div class="badge badge-info">{{ $boq->status}}</div>
                                            @elseif($boq->status == 'approved')
                                                <div class="badge badge-success">{{ $boq->status}}</div>
                                            @else
                                                <div class="badge badge-secondary">{{ $boq->status}}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success" href="{{route('project_boq',['id' => $boq->id])}}">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                @can('Edit BOQ')
                                                    <button type="button"
                                                            onclick="loadFormModal('project_boq_form', {className: 'ProjectBoq', id: {{$boq->id}}}, 'Edit BOQ', 'modal-md');"
                                                            class="btn btn-sm btn-primary">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan
                                                @can('Delete BOQ')
                                                    <button type="button"
                                                            onclick="deleteModelItem('ProjectBoq', {{$boq->id}}, 'boq-tr-{{$boq->id}}');"
                                                            class="btn btn-sm btn-danger">
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
                                    <td colspan="2"></td>
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

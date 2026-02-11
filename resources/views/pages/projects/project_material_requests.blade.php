{{-- project_material_requests.blade.php --}}
@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Material Requests
                <div class="float-right">
                    @can('Add Material Request')
                        <button type="button" onclick="loadFormModal('project_material_request_form', {className: 'ProjectMaterialRequest'}, 'Create Material Request', 'modal-lg');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Request</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Material Requests</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form name="request_search" action="" id="filter-form" method="post" autocomplete="off">
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
                                                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Status</span>
                                                    </div>
                                                    <select name="status" id="input-status" class="form-control">
                                                        <option value="">All Statuses</option>
                                                        <option value="pending">Pending</option>
                                                        <option value="APPROVED">Approved</option>
                                                        <option value="rejected">Rejected</option>
                                                        <option value="completed">Completed</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-2">
                                                <div>
                                                    <button type="submit" name="submit" class="btn btn-sm btn-primary">Filter</button>
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
                                    <th>Request No.</th>
                                    <th>Project</th>
                                    <th>BOQ Item</th>
                                    <th class="text-right">Quantity</th>
                                    <th>Priority</th>
                                    <th>Required Date</th>
                                    <th scope="col">Approvals</th>
                                    <th scope="col">Status</th>
                                    <th>Requester</th>
                                    <th class="text-center" style="width: 120px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($requests as $request)
                                    <tr id="request-tr-{{$request->id}}">
                                        <td class="text-center">{{$loop->index + 1}}</td>
                                        <td class="font-w600">
                                            <a href="{{ route('project_material_request', ['id' => $request->id, 'document_type_id' => 0]) }}">
                                                {{ $request->request_number }}
                                            </a>
                                        </td>
                                        <td>{{ $request->project->name ?? '-' }}</td>
                                        <td>{{ $request->boqItem->item_code ?? ($request->material->name ?? '-') }}</td>
                                        <td class="text-right">{{ number_format($request->quantity_requested, 2) }} {{ $request->unit }}</td>
                                        <td>
                                            @php
                                                $priorityColors = [
                                                    'low' => 'secondary',
                                                    'medium' => 'info',
                                                    'high' => 'warning',
                                                    'urgent' => 'danger'
                                                ];
                                            @endphp
                                            <span class="badge badge-{{ $priorityColors[$request->priority] ?? 'secondary' }}">
                                                {{ ucfirst($request->priority ?? 'medium') }}
                                            </span>
                                        </td>
                                        <td>{{ $request->required_date ? \Carbon\Carbon::parse($request->required_date)->format('d M Y') : '-' }}</td>
                                        <td class="text-center">
                                            <x-ringlesoft-approval-status-summary :model="$request" />
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $approvalStatus = $request->approvalStatus?->status ?? 'Pending';
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
                                        <td>{{ $request->requester->name ?? '-' }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success" href="{{ route('project_material_request', ['id' => $request->id, 'document_type_id' => 0]) }}" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                @if(strtoupper($request->status) === 'APPROVED')
                                                    <a class="btn btn-sm btn-info" href="{{ route('supplier_quotations.by_request', ['id' => $request->id]) }}" title="Quotations">
                                                        <i class="fa fa-file-invoice-dollar"></i>
                                                    </a>
                                                @endif
                                                @can('Edit Material Request')
                                                    @if($request->status === 'pending')
                                                        <button type="button"
                                                                onclick="loadFormModal('project_material_request_form', {className: 'ProjectMaterialRequest', id: {{$request->id}}}, 'Edit Request', 'modal-lg');"
                                                                class="btn btn-sm btn-primary" title="Edit">
                                                            <i class="fa fa-pencil"></i>
                                                        </button>
                                                    @endif
                                                @endcan
                                                @can('Delete Material Request')
                                                    @if($request->status === 'pending')
                                                        <button type="button"
                                                                onclick="deleteModelItem('ProjectMaterialRequest', {{$request->id}}, 'request-tr-{{$request->id}}');"
                                                                class="btn btn-sm btn-danger" title="Delete">
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    @endif
                                                @endcan
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
    </div>
@endsection

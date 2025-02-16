{{-- project_clients.blade.php --}}
@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Project Clients
                <div class="float-right">
                    @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Create Client"))
                        <button type="button" onclick="loadFormModal('project_client_form', {className: 'ProjectClient'}, 'Create New Client', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Client</button>
                    @endif
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Clients</h3>
                    </div>
                    <div class="block-content">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Projects</th>
                                    <th>Documents</th>
                                    <th>Approvals</th>
                                    <th>Status</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($clients as $client)
                                    @php
                                        $approval_document_types_id = 9;
                                        $approvals = \App\Models\ApprovalLevel::getUsersApprovals($approval_document_types_id);
                                    @endphp
                                    <tr id="client-tr-{{$client->id}}">
                                        <td class="text-center">{{$loop->index + 1}}</td>
                                        <td class="font-w600">{{ $client->first_name }} {{ $client->last_name }}</td>
                                        <td>{{ $client->email }}</td>
                                        <td>{{ $client->phone_number }}</td>
                                        <td class="text-center">{{ $client->projects_count }}</td>
                                        <td class="text-center">{{ $client->documents_count }}</td>
                                        <td class="approvals-cell">
                                            <div class="approval-badges">
                                                @foreach($approvals as $approval)
                                                    @php
                                                        $approval_level_id = $approval->id;
                                                        $document_id = $client->id;
                                                        $group_name = \App\Models\ApprovalLevel::getUserGroupName($approval_level_id);
                                                        $approves = \App\Models\Approval::getApproved($approval_level_id,$document_id);
                                                    @endphp
                                                    @if(count($approves))
                                                        @foreach($approves as $approve)
                                                            @if($approve->user_group_id == $approval->user_group_id)
                                                                <span class="approval-badge approved">
                            <i class="fa fa-check"></i>{{$group_name ?? null}}
                        </span>
                                                            @else
                                                                <span class="approval-badge pending">
                            <i class="fa fa-clock-o"></i>{{$group_name ?? null}}
                        </span>
                                                            @endif
                                                        @endforeach
                                                    @else
                                                        <span class="approval-badge pending">
                    <i class="fa fa-clock-o"></i>{{$group_name ?? null}}
                </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>
                                            @if($client->status == 'PENDING')
                                                <div class="badge badge-warning">{{ $client->status}}</div>
                                            @elseif($client->status == 'APPROVED')
                                                <div class="badge badge-primary">{{ $client->status}}</div>
                                            @elseif($client->status == 'REJECTED')
                                                <div class="badge badge-danger">{{ $client->status}}</div>
                                            @elseif($client->status == 'PAID')
                                                <div class="badge badge-primary">{{ $client->status}}</div>
                                            @elseif($client->status == 'COMPLETED')
                                                <div class="badge badge-success">{{ $client->status}}</div>
                                            @else
                                                <div class="badge badge-secondary">{{ $client->status}}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success js-tooltip-enabled"
                                                   href="{{ route('individual_project_clients', [$client->id, 9]) }}">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Edit Client"))
                                                    <button type="button"
                                                            onclick="loadFormModal('project_client_form', {className: 'ProjectClient', id: {{$client->id}}}, 'Edit Client', 'modal-md');"
                                                            class="btn btn-sm btn-primary">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endif
                                                @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Delete Client"))
                                                    <button type="button"
                                                            onclick="deleteModelItem('ProjectClient', {{$client->id}}, 'client-tr-{{$client->id}}');"
                                                            class="btn btn-sm btn-danger">
                                                        <i class="fa fa-times"></i>
                                                    </button>
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
    </div>
@endsection

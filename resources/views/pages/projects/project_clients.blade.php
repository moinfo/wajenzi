{{-- project_clients.blade.php --}}
@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Project Clients
                <div class="float-right">
                    @can('Create Client')
                        <button type="button" onclick="loadFormModal('project_client_form', {className: 'ProjectClient'}, 'Create New Client', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Client</button>
                    @endcan
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
                                    <th>Document Number</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Source</th>
                                    <th>Projects</th>
                                    <th>Documents</th>
                                    <th>Approvals</th>
                                    <th>Status</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($clients as $client)
                                    <tr id="client-tr-{{$client->id}}">
                                        <td class="text-center">{{$loop->iteration}}</td>
                                        <td class="text-center">{{$client->document_number}}</td>
                                        <td class="font-w600">{{ $client->first_name }} {{ $client->last_name }}</td>
                                        <td>{{ $client->email }}</td>
                                        <td>{{ $client->phone_number }}</td>
                                        <td>{{ $client->client_source->name ?? null }}</td>
                                        <td class="text-center">{{ $client->projects_count }}</td>
                                        <td class="text-center">{{ $client->documents_count }}</td>
                                        <td class="text-center">
                                            <!-- Approval status summary component -->
                                            <x-ringlesoft-approval-status-summary :model="$client" />
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $approvalStatus = $client->approvalStatus?->status ?? 'PENDING';
                                                $statusClass = [
                                                    'Pending' => 'warning',
                                                    'Submitted' => 'info',
                                                    'Approved' => 'success',
                                                    'Rejected' => 'danger',
                                                    'Paid' => 'primary',
                                                    'Completed' => 'success',
                                                    'Discarded' => 'danger',
                                                ][$approvalStatus] ?? 'secondary';

                                                $statusIcon = [
                                                    'Pending' => '<i class="fas fa-clock"></i>',
                                                    'Submitted' => '<i class="fas fa-paper-plane"></i>',
                                                    'Approved' => '<i class="fas fa-check"></i>',
                                                    'Rejected' => '<i class="fas fa-times"></i>',
                                                    'Paid' => '<i class="fas fa-money-bill"></i>',
                                                    'Completed' => '<i class="fas fa-check-circle"></i>',
                                                    'Discarded' => '<i class="fas fa-trash"></i>',
                                                ][$approvalStatus] ?? '<i class="fas fa-question-circle"></i>';
                                            @endphp
                                            <span class="badge badge-{{ $statusClass }} badge-pill" style="font-size: 0.9em; padding: 6px 10px;">
                                                {!! $statusIcon !!} {{ $approvalStatus }}
                                            </span>

                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success js-tooltip-enabled"
                                                   href="{{ route('individual_project_clients', [$client->id, 9]) }}">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                @can('Edit Client')
                                                    <button type="button"
                                                            onclick="loadFormModal('project_client_form', {className: 'ProjectClient', id: {{$client->id}}}, 'Edit Client', 'modal-md');"
                                                            class="btn btn-sm btn-primary">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan
                                                @can('Delete Client')
                                                    <button type="button"
                                                            onclick="deleteModelItem('ProjectClient', {{$client->id}}, 'client-tr-{{$client->id}}');"
                                                            class="btn btn-sm btn-danger">
                                                        <i class="fa fa-times"></i>
                                                    </button>
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

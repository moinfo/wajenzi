@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    @can('Add Lead Status')
                        <button type="button" onclick="loadFormModal('settings_lead_status_form', {className: 'LeadStatus'}, 'Create New Lead Status', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Lead Status</button>@endcan

                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Lead Statuses</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Name</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($lead_statuses as $lead_status)
                                <tr id="lead-status-tr-{{$lead_status->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $lead_status->name }}</td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit Lead Status')
                                                <button type="button" onclick="loadFormModal('settings_lead_status_form', {className: 'LeadStatus', id: {{$lead_status->id}}}, 'Edit {{$lead_status->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                                @can('Delete Lead Status')
                                                    <button type="button" onclick="deleteModelItem('LeadStatus', {{$lead_status->id}}, 'lead-status-tr-{{$lead_status->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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
@endsection

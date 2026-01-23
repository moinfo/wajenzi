@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    @can('Add Service Interested')
                        <button type="button" onclick="loadFormModal('settings_service_interested_form', {className: 'ServiceInterested'}, 'Create New Service Interested', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Service Interested</button>@endcan

                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Services Interested</h3>
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
                            @foreach($service_interesteds as $service_interested)
                                <tr id="service-interested-tr-{{$service_interested->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $service_interested->name }}</td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit Service Interested')
                                                <button type="button" onclick="loadFormModal('settings_service_interested_form', {className: 'ServiceInterested', id: {{$service_interested->id}}}, 'Edit {{$service_interested->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                                @can('Delete Service Interested')
                                                    <button type="button" onclick="deleteModelItem('ServiceInterested', {{$service_interested->id}}, 'service-interested-tr-{{$service_interested->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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

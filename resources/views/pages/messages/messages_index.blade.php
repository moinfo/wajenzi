@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Messages
                <div class="float-right">
                    @can('Add Message')
                        <button type="button" onclick="loadFormModal('bulk_message_form', {className: 'Message'}, 'Create New Bulk Messages', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Bulk Messages To All Staff</button>
                    @endcan
                    @can('Add Message')
                        <button type="button" onclick="loadFormModal('message_form', {className: 'Message'}, 'Create New Messages', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Messages</button>
                    @endcan

                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Messages</h3>
                    </div>
                    <div class="block-content">
                        <p>This is a list containing all messages</p>
                        <div class="table-responsive">
                            <table id="js-dataTable-full" class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Name</th>
                                    <th>Phone Number</th>
                                    <th>Message</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>

                                @foreach($messages as $message)
                                    <tr id="message-tr-{{$message->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td class="font-w600">{{ $message->name ?? null }}</td>
                                        <td class="font-w600">{{ $message->phone }}</td>
                                        <td class="d-none d-sm-table-cell">{{ $message->message }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                @can('Edit Message')
                                                    <button type="button"
                                                            onclick="loadFormModal('message_form', {className: 'Message', id: {{$message->id}}}, 'Edit {{$message->name}}', 'modal-md');"
                                                            class="btn btn-sm btn-primary js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan


                                                    @can('Delete Message')
                                                        <button type="button"
                                                                onclick="deleteModelItem('Message', {{$message->id}}, 'message-tr-{{$message->id}}');"
                                                                class="btn btn-sm btn-danger js-tooltip-enabled"
                                                                data-toggle="tooltip" title="Delete"
                                                                data-original-title="Delete">
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



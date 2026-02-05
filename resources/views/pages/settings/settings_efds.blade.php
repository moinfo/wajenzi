@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">EFD
                <div class="float-right">
                    @can('Add EFD')
                        <button type="button" onclick="loadFormModal('settings_efd_form', {className: 'Efd'}, 'Create New EFD', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New EFD</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">EFD</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Name</th>
                                <th>System</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($efd as $efd)
                                <tr id="efd-tr-{{$efd->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $efd->name ?? null}}</td>
                                    <td class="font-w600">{{ $efd->system->name ?? null }}</td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            @can('Edit EFD')
                                                <button type="button" onclick="loadFormModal('settings_efd_form', {className: 'Efd', id: {{$efd->id}}}, 'Edit {{$efd->name ?? null}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                                @can('Delete EFD')
                                                    <button type="button" onclick="deleteModelItem('Efd', {{$efd->id}}, 'efd-tr-{{$efd->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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

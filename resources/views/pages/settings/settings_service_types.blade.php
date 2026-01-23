@extends('layouts.backend')

@section('content')
    <!-- Hero -->
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Service Types</h1>
                <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">Settings</li>
                        <li class="breadcrumb-item active" aria-current="page">Service Types</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <!-- END Hero -->

    <!-- Page Content -->
    <div class="content">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Service Types</h3>
                <div class="block-options">
                    @can('Add Service Type')
                        <button type="button" class="btn btn-alt-primary btn-sm"
                            onclick="loadFormModal('settings_service_type_form', {className: 'ServiceType'}, 'New Service Type')">
                            <i class="fa fa-plus"></i> New Service Type
                        </button>
                    @endcan
                </div>
            </div>
            <div class="block-content block-content-full">
                <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 80px;">#</th>
                            <th>Name</th>
                            <th class="text-center" style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($objects as $object)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ $object->name }}</td>
                                <td class="text-center">
                                    @can('Edit Service Type')
                                        <button type="button" class="btn btn-sm btn-alt-secondary"
                                            onclick="loadFormModal('settings_service_type_form', {className: 'ServiceType', id: {{ $object->id }}}, 'Edit Service Type')">
                                            <i class="fa fa-pencil-alt"></i>
                                        </button>
                                    @endcan
                                    @can('Delete Service Type')
                                        <button type="button" class="btn btn-sm btn-alt-danger"
                                            onclick="deleteModelItem('ServiceType', {{ $object->id }})">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- END Page Content -->
@endsection

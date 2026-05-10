@extends('layouts.backend')
@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Design Service Packages</h1>
            <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">Settings</li>
                    <li class="breadcrumb-item active">Design Packages</li>
                </ol>
            </nav>
        </div>
    </div>
</div>
<div class="content">
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Packages</h3>
            <div class="block-options">
                @can('Add Design Package')
                <button type="button" class="btn btn-alt-primary btn-sm"
                    onclick="loadFormModal('design_package_form', {className: 'DesignServicePackage'}, 'New Package', 'modal-lg')">
                    <i class="fa fa-plus"></i> Add Package
                </button>
                @endcan
            </div>
        </div>
        <div class="block-content block-content-full">
            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th class="text-end">Price (USD)</th>
                        <th>Included Services</th>
                        <th class="text-center">Active</th>
                        <th class="text-center" style="width:100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($objects as $obj)
                    <tr>
                        <td>{{ $obj->sort_order }}</td>
                        <td><strong>{{ $obj->name }}</strong></td>
                        <td>
                            @if($obj->rise_type === 'low')
                                <span class="badge bg-info">Low-Rise</span>
                            @else
                                <span class="badge bg-warning text-dark">High-Rise</span>
                            @endif
                        </td>
                        <td class="text-end fw-semibold">${{ number_format($obj->price_usd, 0) }}</td>
                        <td>
                            @foreach($obj->included_services ?? [] as $svc)
                                <span class="badge bg-light text-dark border me-1">{{ $svc }}</span>
                            @endforeach
                        </td>
                        <td class="text-center">
                            @if($obj->is_active)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-danger">No</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @can('Edit Design Package')
                            <button type="button" class="btn btn-sm btn-alt-secondary"
                                onclick="loadFormModal('design_package_form', {className: 'DesignServicePackage', id: {{ $obj->id }}}, 'Edit Package', 'modal-lg')">
                                <i class="fa fa-pencil-alt"></i>
                            </button>
                            @endcan
                            @can('Delete Design Package')
                            <button type="button" class="btn btn-sm btn-alt-danger"
                                onclick="deleteModelItem('DesignServicePackage', {{ $obj->id }})">
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
@endsection

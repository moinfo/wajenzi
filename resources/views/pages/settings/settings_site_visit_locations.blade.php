@extends('layouts.backend')
@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Site Visit Locations</h1>
            <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">Settings</li>
                    <li class="breadcrumb-item active">Site Visit Locations</li>
                </ol>
            </nav>
        </div>
    </div>
</div>
<div class="content">
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Locations &amp; Preset Costs</h3>
            <div class="block-options">
                @can('Add Site Visit Location')
                <button type="button" class="btn btn-alt-primary btn-sm"
                    onclick="loadFormModal('site_visit_location_form', {className: 'SiteVisitLocation'}, 'New Location', 'modal-lg')">
                    <i class="fa fa-plus"></i> Add Location
                </button>
                @endcan
            </div>
        </div>
        <div class="block-content block-content-full">
            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Location</th>
                        <th class="text-end">Base Cost (TZS)</th>
                        <th class="text-end">Preset Travel</th>
                        <th class="text-end">Preset Local</th>
                        <th class="text-end">Preset Allowance</th>
                        <th class="text-center">Active</th>
                        <th class="text-center" style="width:100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($objects as $obj)
                    <tr>
                        <td>{{ $obj->sort_order }}</td>
                        <td><strong>{{ $obj->name }}</strong></td>
                        <td class="text-end">{{ number_format($obj->base_cost_tzs, 0) }}</td>
                        <td class="text-end">{{ number_format($obj->preset_travel_tzs, 0) }}</td>
                        <td class="text-end">{{ number_format($obj->preset_local_tzs, 0) }}</td>
                        <td class="text-end">{{ number_format($obj->preset_allowance_tzs, 0) }}</td>
                        <td class="text-center">
                            @if($obj->is_active) <span class="badge bg-success">Yes</span>
                            @else <span class="badge bg-danger">No</span> @endif
                        </td>
                        <td class="text-center">
                            @can('Edit Site Visit Location')
                            <button type="button" class="btn btn-sm btn-alt-secondary"
                                onclick="loadFormModal('site_visit_location_form', {className: 'SiteVisitLocation', id: {{ $obj->id }}}, 'Edit Location', 'modal-lg')">
                                <i class="fa fa-pencil-alt"></i>
                            </button>
                            @endcan
                            @can('Delete Site Visit Location')
                            <button type="button" class="btn btn-sm btn-alt-danger"
                                onclick="deleteModelItem('SiteVisitLocation', {{ $obj->id }})">
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

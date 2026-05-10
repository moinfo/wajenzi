@extends('layouts.backend')
@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Special Structure Rates</h1>
            <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">Settings</li>
                    <li class="breadcrumb-item active">Special Structure Rates</li>
                </ol>
            </nav>
        </div>
    </div>
</div>
<div class="content">
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Special Structures (per m²)</h3>
            <div class="block-options">
                @can('Add Special Structure')
                <button type="button" class="btn btn-alt-primary btn-sm"
                    onclick="loadFormModal('design_special_structure_form', {className: 'DesignSpecialStructure'}, 'New Structure', 'modal-md')">
                    <i class="fa fa-plus"></i> Add Structure
                </button>
                @endcan
            </div>
        </div>
        <div class="block-content block-content-full">
            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Structure Type</th>
                        <th class="text-end">Rate (TZS / m²)</th>
                        <th class="text-center">Active</th>
                        <th class="text-center" style="width:100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($objects as $obj)
                    <tr>
                        <td>{{ $obj->sort_order }}</td>
                        <td>{{ $obj->name }}</td>
                        <td class="text-end fw-semibold">TZS {{ number_format($obj->rate_tzs_per_sqm, 0) }}</td>
                        <td class="text-center">
                            @if($obj->is_active) <span class="badge bg-success">Yes</span>
                            @else <span class="badge bg-danger">No</span> @endif
                        </td>
                        <td class="text-center">
                            @can('Edit Special Structure')
                            <button type="button" class="btn btn-sm btn-alt-secondary"
                                onclick="loadFormModal('design_special_structure_form', {className: 'DesignSpecialStructure', id: {{ $obj->id }}}, 'Edit Structure', 'modal-md')">
                                <i class="fa fa-pencil-alt"></i>
                            </button>
                            @endcan
                            @can('Delete Special Structure')
                            <button type="button" class="btn btn-sm btn-alt-danger"
                                onclick="deleteModelItem('DesignSpecialStructure', {{ $obj->id }})">
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

@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Building Types
                <div class="float-right">
                    @can('Add Building Type')
                        <button type="button" onclick="loadFormModal('settings_building_type_form', {className: 'BuildingType'}, 'Create New Building Type', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Building Type</button>
                    @endcan

                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Building Types</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th class="text-center" style="width: 100px;">Status</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($building_types as $building_type)
                                <tr id="building_type-tr-{{$building_type->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $building_type->name }}</td>
                                    <td>{{ $building_type->description ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($building_type->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit Building Type')
                                                <button type="button" onclick="loadFormModal('settings_building_type_form', {className: 'BuildingType', id: {{$building_type->id}}}, 'Edit {{$building_type->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                            @can('Delete Building Type')
                                                <button type="button" onclick="deleteModelItem('BuildingType', {{$building_type->id}}, 'building_type-tr-{{$building_type->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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
@extends('layouts.backend')

@section('content')
    <style>
        .parent-building-type {
            background-color: #f8f9fa !important;
            border-left: 4px solid #1BC5BD;
        }
        .child-building-type {
            background-color: #ffffff !important;
            border-left: 4px solid #e9ecef;
        }
        .parent-building-type:hover {
            background-color: #e9ecef !important;
        }
        .child-building-type:hover {
            background-color: #f8f9fa !important;
        }
        .badge-light-primary {
            background-color: rgba(27, 197, 189, 0.1);
            color: #1BC5BD;
        }
        .badge-light-info {
            background-color: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }
    </style>

    <div class="container-fluid">
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
                        <h3 class="block-title">Building Types (Hierarchical)</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 80px;">Number</th>
                                <th>Building Type Name</th>
                                <th>Parent Type</th>
                                <th>Description</th>
                                <th class="text-center">Sort Order</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                // Create a hierarchical sort: parents by ID, then their children by ID
                                $sortedBuildingTypes = collect();
                                
                                // First, get all parent building types (no parent_id) sorted by ID
                                $parents = $building_types->where('parent_id', null)->sortBy('id');
                                
                                // For each parent, add it and then add its children
                                foreach($parents as $parent) {
                                    $sortedBuildingTypes->push($parent);
                                    
                                    // Get children of this parent and sort by sort_order, then by id
                                    $children = $building_types->where('parent_id', $parent->id)
                                                              ->sortBy([['sort_order', 'asc'], ['id', 'asc']]);
                                    
                                    foreach($children as $child) {
                                        $sortedBuildingTypes->push($child);
                                    }
                                }
                                
                                $parentCounter = 0;
                                $childCounters = [];
                            @endphp
                            
                            @foreach($sortedBuildingTypes as $building_type)
                                @php
                                    if ($building_type->parent_id == null) {
                                        // This is a parent building type
                                        $parentCounter++;
                                        $displayNumber = $parentCounter;
                                        $childCounters[$building_type->id] = 0; // Reset child counter for this parent
                                    } else {
                                        // This is a child building type
                                        $parentId = $building_type->parent_id;
                                        if (!isset($childCounters[$parentId])) {
                                            $childCounters[$parentId] = 0;
                                        }
                                        $childCounters[$parentId]++;
                                        
                                        // Find which parent number this belongs to
                                        $parentNumber = 0;
                                        foreach($parents as $index => $parent) {
                                            if ($parent->id == $parentId) {
                                                $parentNumber = $index + 1;
                                                break;
                                            }
                                        }
                                        
                                        $displayNumber = $parentNumber . '.' . $childCounters[$parentId];
                                    }
                                @endphp
                                
                                <tr id="building_type-tr-{{$building_type->id}}" class="@if($building_type->parent_id) child-building-type @else parent-building-type @endif">
                                    <td class="text-center">
                                        <span class="font-weight-bold" style="@if($building_type->parent_id) color: #6c757d; font-size: 0.9em; @else color: #1BC5BD; font-size: 1.1em; @endif">
                                            {{ $displayNumber }}
                                        </span>
                                    </td>
                                    <td class="font-w600">
                                        @if($building_type->parent_id)
                                            <span style="margin-left: 20px; color: #6c757d;">
                                                <i class="fas fa-level-up-alt fa-rotate-90 text-muted mr-2"></i>
                                                {{ $building_type->name }}
                                            </span>
                                        @else
                                            <span style="color: #1BC5BD; font-weight: 700;">
                                                <i class="fas fa-building text-primary mr-2"></i>
                                                {{ $building_type->name }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($building_type->parent)
                                            <span class="badge badge-light-primary">{{ $building_type->parent->name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $building_type->description ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-light-info">{{ $building_type->sort_order ?? 0 }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($building_type->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            @can('Edit Building Type')
                                                <button type="button" onclick="loadFormModal('settings_building_type_form', {className: 'BuildingType', id: {{$building_type->id}}}, 'Edit {{$building_type->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan
                                            @can('Delete Building Type')
                                                <button type="button" onclick="deleteModelItem('BuildingType', {{$building_type->id}}, 'building_type-tr-{{$building_type->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete">
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

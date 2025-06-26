@extends('layouts.backend')

@section('content')
    <style>
        .parent-construction-stage {
            background-color: #f8f9fa !important;
            border-left: 4px solid #1BC5BD;
        }
        .child-construction-stage {
            background-color: #ffffff !important;
            border-left: 4px solid #e9ecef;
        }
        .parent-construction-stage:hover {
            background-color: #e9ecef !important;
        }
        .child-construction-stage:hover {
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

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Construction Stages
                <div class="float-right">
                    @can('Add Construction Stage')
                        <button type="button" onclick="loadFormModal('settings_construction_stage_form', {className: 'ConstructionStage'}, 'Create New Stage', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Stage</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Construction Stages (Hierarchical)</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 80px;">Number</th>
                                <th>Stage Name</th>
                                <th>Parent Stage</th>
                                <th>Description</th>
                                <th class="text-center">Sort Order</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                // Create a hierarchical sort: parents by sort_order, then their children by sort_order
                                $sortedConstructionStages = collect();
                                
                                // First, get all parent construction stages (no parent_id) sorted by sort_order
                                $parents = $construction_stages->where('parent_id', null)->sortBy('sort_order');
                                
                                // For each parent, add it and then add its children
                                foreach($parents as $parent) {
                                    $sortedConstructionStages->push($parent);
                                    
                                    // Get children of this parent and sort by sort_order, then by id
                                    $children = $construction_stages->where('parent_id', $parent->id)
                                                                  ->sortBy([['sort_order', 'asc'], ['id', 'asc']]);
                                    
                                    foreach($children as $child) {
                                        $sortedConstructionStages->push($child);
                                    }
                                }
                                
                                $parentCounter = 0;
                                $childCounters = [];
                                $parentNumberMap = [];
                                
                                // Debug info
                                if(config('app.debug')) {
                                    echo "<!-- Debug: Total stages: " . $construction_stages->count() . " -->";
                                    echo "<!-- Debug: Parents: " . $parents->count() . " -->";
                                    foreach($parents as $p) {
                                        $childrenCount = $construction_stages->where('parent_id', $p->id)->count();
                                        echo "<!-- Debug: Parent '" . $p->name . "' (ID: " . $p->id . ") has " . $childrenCount . " children -->";
                                    }
                                }
                            @endphp
                            
                            @foreach($sortedConstructionStages as $stage)
                                @php
                                    if ($stage->parent_id == null) {
                                        // This is a parent construction stage
                                        $parentCounter++;
                                        $displayNumber = $parentCounter;
                                        $childCounters[$stage->id] = 0; // Reset child counter for this parent
                                        // Store the mapping of parent ID to parent number
                                        $parentNumberMap[$stage->id] = $parentCounter;
                                    } else {
                                        // This is a child construction stage
                                        $parentId = $stage->parent_id;
                                        if (!isset($childCounters[$parentId])) {
                                            $childCounters[$parentId] = 0;
                                        }
                                        $childCounters[$parentId]++;
                                        
                                        // Use the stored parent number mapping
                                        $parentNumber = $parentNumberMap[$parentId] ?? 0;
                                        $displayNumber = $parentNumber . '.' . $childCounters[$parentId];
                                    }
                                @endphp
                                
                                <tr id="stage-tr-{{$stage->id}}" class="@if($stage->parent_id) child-construction-stage @else parent-construction-stage @endif">
                                    <td class="text-center">
                                        <span class="font-weight-bold" style="@if($stage->parent_id) color: #6c757d; font-size: 0.9em; @else color: #1BC5BD; font-size: 1.1em; @endif">
                                            {{ $displayNumber }}
                                        </span>
                                    </td>
                                    <td class="font-w600">
                                        @if($stage->parent_id)
                                            <span style="margin-left: 20px; color: #6c757d;">
                                                <i class="fas fa-level-up-alt fa-rotate-90 text-muted mr-2"></i>
                                                {{ $stage->name }}
                                            </span>
                                        @else
                                            <span style="color: #1BC5BD; font-weight: 700;">
                                                <i class="fas fa-layer-group text-primary mr-2"></i>
                                                {{ $stage->name }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($stage->parent)
                                            <span class="badge badge-light-primary">{{ $stage->parent->name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $stage->description ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-light-info">{{ $stage->sort_order }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            @can('Edit Construction Stage')
                                                <button type="button" onclick="loadFormModal('settings_construction_stage_form', {className: 'ConstructionStage', id: {{$stage->id}}}, 'Edit {{$stage->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan
                                            @can('Delete Construction Stage')
                                                <button type="button" onclick="deleteModelItem('ConstructionStage', {{$stage->id}}, 'stage-tr-{{$stage->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete">
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
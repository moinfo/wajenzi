@extends('layouts.backend')

@section('content')
    <style>
        .parent-category {
            background-color: #f8f9fa !important;
            border-left: 4px solid #1BC5BD;
        }
        .child-category {
            background-color: #ffffff !important;
            border-left: 4px solid #e9ecef;
        }
        .parent-category:hover {
            background-color: #e9ecef !important;
        }
        .child-category:hover {
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
            <div class="content-heading">BOQ Item Categories
                <div class="float-right">
                    @can('Add BOQ Item Category')
                        <button type="button" onclick="loadFormModal('settings_boq_item_category_form', {className: 'BoqItemCategory'}, 'Create New Category', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Category</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">BOQ Item Categories (Hierarchical)</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 80px;">Number</th>
                                <th>Category Name</th>
                                <th>Parent Category</th>
                                <th>Description</th>
                                <th class="text-center">Sort Order</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                // Sort categories: parents first by sort_order, then children by parent's sort_order and their own sort_order
                                $sortedCategories = $categories->sortBy(function($category) {
                                    if ($category->parent_id == null) {
                                        // Parent category: use sort_order directly
                                        return sprintf('%03d.000', $category->sort_order);
                                    } else {
                                        // Child category: use parent's sort_order + own sort_order
                                        $parentSortOrder = $category->parent->sort_order ?? 999;
                                        return sprintf('%03d.%03d', $parentSortOrder, $category->sort_order);
                                    }
                                });
                                
                                $parentCounter = 0;
                                $childCounters = [];
                            @endphp
                            
                            @foreach($sortedCategories as $category)
                                @php
                                    if ($category->parent_id == null) {
                                        // This is a parent category
                                        $parentCounter++;
                                        $categoryNumber = $parentCounter;
                                        $displayNumber = $parentCounter;
                                        $childCounters[$category->id] = 0; // Reset child counter for this parent
                                    } else {
                                        // This is a child category
                                        $parentId = $category->parent_id;
                                        if (!isset($childCounters[$parentId])) {
                                            $childCounters[$parentId] = 0;
                                        }
                                        $childCounters[$parentId]++;
                                        
                                        // Find parent number
                                        $parentCategory = $categories->where('id', $parentId)->first();
                                        $parentNumber = $categories->where('parent_id', null)
                                                                   ->where('sort_order', '<=', $parentCategory->sort_order)
                                                                   ->count();
                                        
                                        $categoryNumber = $parentNumber . '.' . $childCounters[$parentId];
                                        $displayNumber = $parentNumber . '.' . $childCounters[$parentId];
                                    }
                                @endphp
                                
                                <tr id="category-tr-{{$category->id}}" class="@if($category->parent_id) child-category @else parent-category @endif">
                                    <td class="text-center">
                                        <span class="font-weight-bold" style="@if($category->parent_id) color: #6c757d; font-size: 0.9em; @else color: #1BC5BD; font-size: 1.1em; @endif">
                                            {{ $displayNumber }}
                                        </span>
                                    </td>
                                    <td class="font-w600">
                                        @if($category->parent_id)
                                            <span style="margin-left: 20px; color: #6c757d;">
                                                <i class="fas fa-level-up-alt fa-rotate-90 text-muted mr-2"></i>
                                                {{ $category->name }}
                                            </span>
                                        @else
                                            <span style="color: #1BC5BD; font-weight: 700;">
                                                <i class="fas fa-folder text-primary mr-2"></i>
                                                {{ $category->name }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($category->parent)
                                            <span class="badge badge-light-primary">{{ $category->parent->name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $category->description ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-light-info">{{ $category->sort_order }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($category->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            @can('Edit BOQ Item Category')
                                                <button type="button" onclick="loadFormModal('settings_boq_item_category_form', {className: 'BoqItemCategory', id: {{$category->id}}}, 'Edit {{$category->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan
                                            @can('Delete BOQ Item Category')
                                                <button type="button" onclick="deleteModelItem('BoqItemCategory', {{$category->id}}, 'category-tr-{{$category->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete">
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
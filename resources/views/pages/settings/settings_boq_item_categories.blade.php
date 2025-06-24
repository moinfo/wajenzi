@extends('layouts.backend')

@section('content')

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
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>Name</th>
                                <th>Parent Category</th>
                                <th>Description</th>
                                <th class="text-center">Sort Order</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($categories as $category)
                                <tr id="category-tr-{{$category->id}}">
                                    <td class="text-center">{{$loop->index + 1}}</td>
                                    <td class="font-w600">
                                        @if($category->parent_id)
                                            <i class="fa fa-angle-right text-muted"></i>
                                        @endif
                                        {{ $category->name }}
                                    </td>
                                    <td>{{ $category->parent->name ?? '-' }}</td>
                                    <td>{{ $category->description ?? '-' }}</td>
                                    <td class="text-center">{{ $category->sort_order }}</td>
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
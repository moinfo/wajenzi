@extends('layouts.backend')

@section('content')
    <style>
        .category-row {
            background-color: #f8f9fa !important;
            border-left: 4px solid #1BC5BD;
            font-weight: 600;
        }
        .item-row {
            background-color: #ffffff !important;
            border-left: 4px solid #e9ecef;
        }
        .category-row:hover {
            background-color: #e9ecef !important;
        }
        .item-row:hover {
            background-color: #f8f9fa !important;
        }
        .badge-light-primary {
            background-color: rgba(27, 197, 189, 0.1);
            color: #1BC5BD;
        }
    </style>

    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">BOQ Items
                <div class="float-right">
                    @can('Add BOQ Item')
                        <button type="button" onclick="loadFormModal('settings_boq_item_form', {className: 'BoqTemplateItem'}, 'Create New BOQ Item', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Item</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">BOQ Items / Materials (By Category)</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 80px;">Number</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Unit</th>
                                <th class="text-right">Base Price</th>
                                <th>Description</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                $categoryCounter = 0;
                                $itemCounters = [];
                            @endphp

                            @foreach($categories as $category)
                                @php
                                    $categoryCounter++;
                                    $itemCounters[$category->id] = 0;
                                @endphp

                                {{-- Display Category Row --}}
                                <tr class="category-row">
                                    <td class="text-center">
                                        <span class="font-weight-bold" style="color: #1BC5BD; font-size: 1.1em;">
                                            {{ $categoryCounter }}
                                        </span>
                                    </td>
                                    <td class="font-w600">
                                        <span style="color: #1BC5BD; font-weight: 700;">
                                            <i class="fas fa-folder text-primary mr-2"></i>
                                            {{ $category->name }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-primary">Category</span>
                                    </td>
                                    <td class="text-center"><span class="text-muted">-</span></td>
                                    <td class="text-right"><span class="text-muted">-</span></td>
                                    <td>{{ Str::limit($category->description ?? '-', 50) }}</td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            @can('Edit BOQ Item Category')
                                                <button type="button" onclick="loadFormModal('settings_boq_item_category_form', {className: 'BoqItemCategory', id: {{$category->id}}}, 'Edit {{$category->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit Category">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>

                                {{-- Display BOQ Items for this Category --}}
                                @foreach($category->boqItems as $item)
                                    @php
                                        $itemCounters[$category->id]++;
                                        $displayNumber = $categoryCounter . '.' . $itemCounters[$category->id];
                                    @endphp

                                    <tr id="item-tr-{{$item->id}}" class="item-row">
                                        <td class="text-center">
                                            <span class="font-weight-bold" style="color: #6c757d; font-size: 0.9em;">
                                                {{ $displayNumber }}
                                            </span>
                                        </td>
                                        <td class="font-w600">
                                            <span style="margin-left: 20px; color: #6c757d;">
                                                <i class="fas fa-cube text-muted mr-2"></i>
                                                {{ $item->name }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary">BOQ Item</span>
                                        </td>
                                        <td class="text-center">{{ $item->unit ?? '-' }}</td>
                                        <td class="text-right">
                                            @if($item->base_price)
                                                {{ number_format($item->base_price, 2) }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ Str::limit($item->description ?? '-', 50) }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                @can('Edit BOQ Item')
                                                    <button type="button" onclick="loadFormModal('settings_boq_item_form', {className: 'BoqTemplateItem', id: {{$item->id}}}, 'Edit {{$item->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan
                                                @can('Delete BOQ Item')
                                                    <button type="button" onclick="deleteModelItem('BoqTemplateItem', {{$item->id}}, 'item-tr-{{$item->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                {{-- Display uncategorized items for the last category (if this is the last one) --}}
                                @if($loop->last)
                                    @php
                                        $uncategorizedItems = $boq_items->whereNull('category_id');
                                    @endphp
                                    
                                    @if($uncategorizedItems->count() > 0)
                                        {{-- Uncategorized Category Row --}}
                                        @php
                                            $categoryCounter++;
                                            $itemCounters['uncategorized'] = 0;
                                        @endphp
                                        
                                        <tr class="category-row">
                                            <td class="text-center">
                                                <span class="font-weight-bold" style="color: #1BC5BD; font-size: 1.1em;">
                                                    {{ $categoryCounter }}
                                                </span>
                                            </td>
                                            <td class="font-w600">
                                                <span style="color: #1BC5BD; font-weight: 700;">
                                                    <i class="fas fa-folder text-warning mr-2"></i>
                                                    Uncategorized Items
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-warning">Category</span>
                                            </td>
                                            <td class="text-center"><span class="text-muted">-</span></td>
                                            <td class="text-right"><span class="text-muted">-</span></td>
                                            <td>Items without assigned category</td>
                                            <td class="text-center"><span class="text-muted">-</span></td>
                                        </tr>

                                        {{-- Display Uncategorized Items --}}
                                        @foreach($uncategorizedItems as $item)
                                            @php
                                                $itemCounters['uncategorized']++;
                                                $displayNumber = $categoryCounter . '.' . $itemCounters['uncategorized'];
                                            @endphp

                                            <tr id="item-tr-{{$item->id}}" class="item-row">
                                                <td class="text-center">
                                                    <span class="font-weight-bold" style="color: #6c757d; font-size: 0.9em;">
                                                        {{ $displayNumber }}
                                                    </span>
                                                </td>
                                                <td class="font-w600">
                                                    <span style="margin-left: 20px; color: #6c757d;">
                                                        <i class="fas fa-cube text-muted mr-2"></i>
                                                        {{ $item->name }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-secondary">BOQ Item</span>
                                                </td>
                                                <td class="text-center">{{ $item->unit ?? '-' }}</td>
                                                <td class="text-right">
                                                    @if($item->base_price)
                                                        {{ number_format($item->base_price, 2) }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>{{ Str::limit($item->description ?? '-', 50) }}</td>
                                                <td class="text-center">
                                                    <div class="btn-group">
                                                        @can('Edit BOQ Item')
                                                            <button type="button" onclick="loadFormModal('settings_boq_item_form', {className: 'BoqTemplateItem', id: {{$item->id}}}, 'Edit {{$item->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit">
                                                                <i class="fa fa-pencil"></i>
                                                            </button>
                                                        @endcan
                                                        @can('Delete BOQ Item')
                                                            <button type="button" onclick="deleteModelItem('BoqTemplateItem', {{$item->id}}, 'item-tr-{{$item->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete">
                                                                <i class="fa fa-times"></i>
                                                            </button>
                                                        @endcan
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @endif
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
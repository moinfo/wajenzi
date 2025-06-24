@extends('layouts.backend')

@section('content')

    <div class="main-container">
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
                        <h3 class="block-title">BOQ Items / Materials</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th class="text-right">Base Price</th>
                                <th>Description</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($boq_items as $item)
                                <tr id="item-tr-{{$item->id}}">
                                    <td class="text-center">{{$loop->index + 1}}</td>
                                    <td class="font-w600">{{ $item->name }}</td>
                                    <td>
                                        @if($item->category)
                                            <span class="badge badge-secondary">{{ $item->category->name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
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
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
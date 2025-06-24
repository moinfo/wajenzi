{{-- project_boq_items.blade.php --}}
@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">BOQ Items for {{ $boq->project->project_name }}
                <div class="float-right">
                    @can('Add BOQ Item')
                        <button type="button" onclick="loadFormModal('project_boq_item_form', {className: 'ProjectBoqItem', boq_id: {{$boq->id}}}, 'Add BOQ Item', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>Add Item</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">BOQ Items List</h3>
                    </div>
                    <div class="block-content">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Description</th>
                                    <th class="text-right">Quantity</th>
                                    <th>Unit</th>
                                    <th class="text-right">Unit Price</th>
                                    <th class="text-right">Total Price</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($boqItems as $item)
                                    <tr id="boq-item-tr-{{$item->id}}">
                                        <td class="text-center">{{$loop->index + 1}}</td>
                                        <td>{{ $item->description }}</td>
                                        <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                                        <td>{{ $item->unit }}</td>
                                        <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-right">{{ number_format($item->total_price, 2) }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                @can('Edit BOQ Item')
                                                    <button type="button"
                                                            onclick="loadFormModal('project_boq_item_form', {className: 'ProjectBoqItem', id: {{$item->id}}}, 'Edit Item', 'modal-md');"
                                                            class="btn btn-sm btn-primary">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan
                                                @can('Delete BOQ Item')
                                                    <button type="button"
                                                            onclick="deleteModelItem('ProjectBoqItem', {{$item->id}}, 'boq-item-tr-{{$item->id}}');"
                                                            class="btn btn-sm btn-danger">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td colspan="5" class="text-right"><strong>Total:</strong></td>
                                    <td class="text-right"><strong>{{ number_format($boqItems->sum('total_price'), 2) }}</strong></td>
                                    <td></td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Collection
                <div class="float-right">
                    <button type="button" onclick="loadFormModal('collection_form', {className: 'Collection'}, 'Create New Collection', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Collection</button>
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Collections</h3>
                    </div>
                    <div class="block-content">
                        <p>This is a list containing all Collections</p>
                        <div class="table-responsive">
                            <table class="table table-striped table-vcenter">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Date</th>
                                    <th>Supervisor Name</th>
                                    <th>Bank Name</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($collections as $collection)
                                    <tr id="collection-tr-{{$collection->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td>{{ $collection->date }}</td>
                                        <td>{{ $collection->supervisor->name }}</td>
                                        <td>{{ $collection->bank->name }}</td>
                                        <td class="font-w600">{{ $collection->description }}</td>
                                        <td class="text-right">{{ number_format($collection->amount, 2) }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button"
                                                        onclick="loadFormModal('collection_form', {className: 'Collection', id: {{$collection->id}}}, 'Edit {{$collection->name}}', 'modal-md');"
                                                        class="btn btn-sm btn-primary js-tooltip-enabled"
                                                        data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                                <button type="button"
                                                        onclick="deleteModelItem('Collection', {{$collection->id}}, 'collection-tr-{{$collection->id}}');"
                                                        class="btn btn-sm btn-danger js-tooltip-enabled"
                                                        data-toggle="tooltip" title="Delete"
                                                        data-original-title="Delete">
                                                    <i class="fa fa-times"></i>
                                                </button>
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
    </div>

@endsection



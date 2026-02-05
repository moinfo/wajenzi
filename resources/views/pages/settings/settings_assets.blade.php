@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Settings
                @include('components.headed_paper_settings')
                <br/>
                <div class="block-header text-center">
                    <h3 class="block-title">Assets</h3>
                </div>
                <div class="float-right">
                    @can('Add Asset')
                        <button type="button" onclick="loadFormModal('settings_asset_form', {className: 'Asset'}, 'Create New Asset', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Asset</button>
                    @endcan

                </div>
            </div>
            <div>
                <div class="block">

                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>Name</th>
                                <th class="d-none d-sm-table-cell" style="width: 30%;">Description</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($assets as $asset)
                                <tr id="asset-tr-{{$asset->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $asset->name }}</td>
                                    <td class="d-none d-sm-table-cell">{{ $asset->description }}
                                    </td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit Asset')
                                                <button type="button" onclick="loadFormModal('settings_asset_form', {className: 'Asset', id: {{$asset->id}}}, 'Edit {{$asset->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                                @can('Delete Asset')
                                                    <button type="button" onclick="deleteModelItem('Asset', {{$asset->id}}, 'asset-tr-{{$asset->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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

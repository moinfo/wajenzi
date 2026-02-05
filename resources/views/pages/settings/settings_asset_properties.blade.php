@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Settings
                @include('components.headed_paper_settings')
                <br/>
                <div class="block-header text-center">
                    <h3 class="block-title">Asset Properties</h3>
                </div>
                <div class="float-right">
                    @can('Add Asset Property')
                        <button type="button" onclick="loadFormModal('settings_asset_property_form', {className: 'AssetProperty'}, 'Create New AssetProperty', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Asset Property</button>
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
                                <th>Asset</th>
                                <th>Property</th>
                                <th>Staff</th>
                                <th class="d-none d-sm-table-cell" style="width: 30%;">Description</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($asset_properties as $asset_property)
                                <tr id="asset_property-tr-{{$asset_property->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $asset_property->asset->name ?? null}}</td>
                                    <td class="font-w600">{{ $asset_property->name }}</td>
                                    <td class="font-w600">{{ $asset_property->user->name ?? null }}</td>
                                    <td class="d-none d-sm-table-cell">{{ $asset_property->description }}
                                    </td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit Asset Property')
                                                <button type="button" onclick="loadFormModal('settings_asset_property_form', {className: 'AssetProperty', id: {{$asset_property->id}}}, 'Edit {{$asset_property->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                                @can('Delete Asset Property')
                                                    <button type="button" onclick="deleteModelItem('AssetProperty', {{$asset_property->id}}, 'asset_property-tr-{{$asset_property->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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

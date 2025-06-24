{{-- project_material_inventory.blade.php --}}
@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Material Inventory
                <div class="float-right">
                    @can('Create Material Inventory')
                        <button type="button" onclick="loadFormModal('project_material_inventory_form', {className: 'ProjectMaterialInventory'}, 'Add Inventory', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>Add Inventory</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Inventory List</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form name="inventory_search" action="" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Project</span>
                                                    </div>
                                                    <select name="project_id" id="input-project" class="form-control">
                                                        <option value="">All Projects</option>
                                                        @foreach ($projects as $project)
                                                            <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Material</span>
                                                    </div>
                                                    <select name="material_id" id="input-material" class="form-control">
                                                        <option value="">All Materials</option>
                                                        @foreach ($materials as $material)
                                                            <option value="{{ $material->id }}">{{ $material->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-2">
                                                <div>
                                                    <button type="submit" name="submit" class="btn btn-sm btn-primary">Show</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Project</th>
                                    <th>Material</th>
                                    <th class="text-right">Quantity</th>
                                    <th>Unit</th>
                                    <th>Last Updated</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($inventories as $inventory)
                                    <tr id="inventory-tr-{{$inventory->id}}">
                                        <td class="text-center">{{$loop->index + 1}}</td>
                                        <td>{{ $inventory->project->project_name }}</td>
                                        <td>{{ $inventory->material->name }}</td>
                                        <td class="text-right">{{ number_format($inventory->quantity, 2) }}</td>
                                        <td>{{ $inventory->material->unit }}</td>
                                        <td>{{ $inventory->last_updated }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                @can('Edit Inventory')
                                                    <button type="button"
                                                            onclick="loadFormModal('project_material_inventory_form', {className: 'ProjectMaterialInventory', id: {{$inventory->id}}}, 'Update Inventory', 'modal-md');"
                                                            class="btn btn-sm btn-primary">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan
                                                @can('Delete Inventory')
                                                    <button type="button"
                                                            onclick="deleteModelItem('ProjectMaterialInventory', {{$inventory->id}}, 'inventory-tr-{{$inventory->id}}');"
                                                            class="btn btn-sm btn-danger">
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
    </div>
@endsection

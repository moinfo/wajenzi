{{-- project_materials.blade.php --}}
@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Project Materials
                <div class="float-right">
                    @can('Create Material')
                        <button type="button" onclick="loadFormModal('project_material_form', {className: 'ProjectMaterial'}, 'Create New Material', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Material</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Materials</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form name="material_search" action="" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Name</span>
                                                    </div>
                                                    <input type="text" name="search" id="search" class="form-control" placeholder="Search material...">
                                                </div>
                                            </div>
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Unit</span>
                                                    </div>
                                                    <select name="unit" id="input-unit" class="form-control">
                                                        <option value="">All Units</option>
                                                        @foreach ($units as $unit)
                                                            <option value="{{ $unit }}">{{ $unit }}</option>
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
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Unit</th>
                                    <th class="text-right">Current Price</th>
                                    <th>Total Inventory</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($materials as $material)
                                    <tr id="material-tr-{{$material->id}}">
                                        <td class="text-center">{{$loop->index + 1}}</td>
                                        <td class="font-w600">{{ $material->name }}</td>
                                        <td>{{ $material->description }}</td>
                                        <td>{{ $material->unit }}</td>
                                        <td class="text-right">{{ number_format($material->current_price, 2) }}</td>
                                        <td class="text-right">{{ number_format($material->total_inventory, 2) }} {{ $material->unit }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
{{--                                                <a class="btn btn-sm btn-success" href="{{route('project_material',['id' => $material->id])}}">--}}
{{--                                                    <i class="fa fa-eye"></i>--}}
{{--                                                </a>--}}
                                                @can('Edit Material')
                                                    <button type="button"
                                                            onclick="loadFormModal('project_material_form', {className: 'ProjectMaterial', id: {{$material->id}}}, 'Edit Material', 'modal-md');"
                                                            class="btn btn-sm btn-primary">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan
                                                @can('Delete Material')
                                                    <button type="button"
                                                            onclick="deleteModelItem('ProjectMaterial', {{$material->id}}, 'material-tr-{{$material->id}}');"
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

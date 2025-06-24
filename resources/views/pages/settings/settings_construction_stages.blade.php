@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Construction Stages
                <div class="float-right">
                    @can('Add Construction Stage')
                        <button type="button" onclick="loadFormModal('settings_construction_stage_form', {className: 'ConstructionStage'}, 'Create New Stage', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Stage</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Construction Stages</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th class="text-center">Sort Order</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($construction_stages as $stage)
                                <tr id="stage-tr-{{$stage->id}}">
                                    <td class="text-center">{{$loop->index + 1}}</td>
                                    <td class="font-w600">{{ $stage->name }}</td>
                                    <td>{{ $stage->description ?? '-' }}</td>
                                    <td class="text-center">{{ $stage->sort_order }}</td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            @can('Edit Construction Stage')
                                                <button type="button" onclick="loadFormModal('settings_construction_stage_form', {className: 'ConstructionStage', id: {{$stage->id}}}, 'Edit {{$stage->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan
                                            @can('Delete Construction Stage')
                                                <button type="button" onclick="deleteModelItem('ConstructionStage', {{$stage->id}}, 'stage-tr-{{$stage->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete">
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
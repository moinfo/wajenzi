{{-- project_types.blade.php --}}
@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Project Types
                <div class="float-right">
                    @can('Create Project Type')
                        <button type="button" onclick="loadFormModal('project_type_form', {className: 'ProjectType'}, 'Create New Type', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Type</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Project Types</h3>
                    </div>
                    <div class="block-content">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Total Projects</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($projectTypes as $type)
                                    <tr id="type-tr-{{$type->id}}">
                                        <td class="text-center">{{$loop->index + 1}}</td>
                                        <td class="font-w600">{{ $type->name }}</td>
                                        <td>{{ $type->description }}</td>
                                        <td class="text-center">{{ $type->projects_count }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                @can('Edit Project Type')
                                                    <button type="button"
                                                            onclick="loadFormModal('project_type_form', {className: 'ProjectType', id: {{$type->id}}}, 'Edit Type', 'modal-md');"
                                                            class="btn btn-sm btn-primary">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan
                                                @can('Delete Project Type')
                                                    <button type="button"
                                                            onclick="deleteModelItem('ProjectType', {{$type->id}}, 'type-tr-{{$type->id}}');"
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

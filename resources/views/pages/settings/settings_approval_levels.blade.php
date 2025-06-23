@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    @can('Add Approval Level')
                        <button type="button" onclick="loadFormModal('settings_approval_level_form', {className: 'ApprovalLevel'}, 'Create New Approval Level', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10">
                            <i class="si si-plus">&nbsp;</i>New Approval Level</button> @endcan

                </div>
            </div>
            <div>

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Approval Levels</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Date</th>
                                <th>Approval Document Type</th>
                                <th>Order</th>
                                {{--                                <th>User Group</th>--}}
                                {{--                                <th>User Group Keyword</th>--}}
                                <th>Description</th>
                                <th>Status</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($approval_levels as $key => $value)
                                <tr>
                                    <th scope="row">
                                        <a href="#">{{$loop->iteration}}</a>
                                    </th>
                                    <td>{{ $value->updated_at }}</td>
                                    <td>{{ $value->approvalDocumentType->name ?? null}}</td>
                                    <td>{{ $value->order }}</td>
                                    {{--                                    <td>{{ $value->userGroup->name}}</td>--}}
                                    {{--                                    <td>{{ $value->userGroup->keyword}}</td>--}}
                                    <td>{{ \Str::limit($value->description, 100) }}</td>
                                    <td>{{ $value->action }}</td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit Approval Level')
                                                <button type="button" onclick="loadFormModal('settings_approval_level_form', {className: 'ApprovalLevel', id: {{$value->id}}}, 'Edit {{$value->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                            @can('Delete Approval Level')
                                                <button type="button" onclick="deleteModelItem('ApprovalLevel', {{$value->id}}, 'approval_level-tr-{{$value->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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

@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Wakala
                <div class="float-right">
                    @can('Add Wakala')
                        <button type="button"
                                onclick="loadFormModal('wakala_form', {className: 'Wakala'}, 'Create New Wakala', 'modal-md');"
                                class="btn btn-rounded btn-outline-primary min-width-125 mb-10">
                            <i class="si si-plus">&nbsp;</i>New Wakala
                        </button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Mawakala</h3>
                    </div>
                    <div class="block-content">
                        <div class="table-responsive">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="table-responsive">
                                        <table
                                            class="table table-bordered table-striped table-vcenter js-dataTable-full"
                                            data-ordering="false">
                                            <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Phone Number</th>
                                                <th>Location</th>
                                                <th>Agent ID</th>
                                                <th>Action</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($wakalas as $wakala)
                                                <tr>
                                                    <td>{{$loop->iteration}}</td>
                                                    <td>{{$wakala->name}}</td>
                                                    <td>{{$wakala->phone_number}}</td>
                                                    <td>{{$wakala->location}}</td>
                                                    <td>{{$wakala->agent_id}}</td>
                                                    <td class="text-center" >
                                                        <div class="btn-group">
                                                            @can('Edit Wakala')
                                                                <button type="button" onclick="loadFormModal('wakala_form', {className: 'Wakala', id: {{$wakala->id}}}, 'Edit {{$wakala->account_name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                                    <i class="fa fa-pencil"></i>
                                                                </button>
                                                            @endcan
                                                            @can('Delete Wakala')
                                                                <button type="button" onclick="deleteModelItem('Wakala', {{$wakala->id}}, 'wakala-tr-{{$wakala->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
                                                                    <i class="fa fa-times"></i>
                                                                </button>
                                                            @endcan
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection



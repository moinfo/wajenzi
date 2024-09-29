@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Beneficiaries
                <div class="float-right">
                    @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Add Beneficiary"))
                        <button type="button"
                                onclick="loadFormModal('beneficiary_form', {className: 'Beneficiary'}, 'Create New Beneficiary', 'modal-md');"
                                class="btn btn-rounded btn-outline-primary min-width-125 mb-10">
                            <i class="si si-plus">&nbsp;</i>New Beneficiary
                        </button>
                    @endif
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Beneficiaries</h3>
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
                                                <th>Account Name</th>
                                                <th>Account Number</th>
                                                <th>Bank Name</th>
                                                <th>Action</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($beneficiaries as $beneficiary)
                                                <tr>
                                                    <td>{{$loop->iteration}}</td>
                                                    <td>{{$beneficiary->account_name}}</td>
                                                    <td>{{$beneficiary->account_number}}</td>
                                                    <td>{{$beneficiary->bank->name}}</td>
                                                    <td class="text-center" >
                                                        <div class="btn-group">
                                                            @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Edit Beneficiary"))
                                                                <button type="button" onclick="loadFormModal('beneficiary_form', {className: 'Beneficiary', id: {{$beneficiary->id}}}, 'Edit {{$beneficiary->account_name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                                    <i class="fa fa-pencil"></i>
                                                                </button>
                                                            @endif
                                                            @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Delete Beneficiary"))
                                                                <button type="button" onclick="deleteModelItem('Beneficiary', {{$beneficiary->id}}, 'beneficiary-tr-{{$beneficiary->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
                                                                    <i class="fa fa-times"></i>
                                                                </button>
                                                            @endif

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



@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    @can('Add Account Type')
                        <button type="button" onclick="loadFormModal('settings_account_type_form', {className: 'AccountType'}, 'Create New Account Type', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10">
                            <i class="si si-plus">&nbsp;</i>New Account Type</button>@endcan

                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Account Types</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Type</th>
                                <th>Code</th>
                                <th>Normal Balance</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($account_types as $account_type)
                                <tr id="account_type-tr-{{$account_type->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $account_type->type }}</td>
                                    <td class="font-w600">{{ $account_type->code }}</td>
                                    <td class="font-w600">{{ $account_type->normal_balance }}</td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit Account Type')
                                                <button type="button" onclick="loadFormModal('settings_account_type_form', {className: 'AccountType', id: {{$account_type->id}}}, 'Edit {{$account_type->type}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                                @can('Delete Account Type')
                                                    <button type="button" onclick="deleteModelItem('AccountType', {{$account_type->id}}, 'account_type-tr-{{$account_type->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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

@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Financial Charges
                <div class="float-right">
                    @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Add Financial Charge"))
                        <button type="button" onclick="loadFormModal('financial_charge_form', {className: 'FinancialCharge'}, 'Create New Financial Charges', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Financial Charges</button>
                    @endif
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Financial Charges</h3>
                    </div>
                    <div class="block-content">
                        <p>This is a list containing all financial_charges</p>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Date</th>
                                    <th>Supplier</th>
                                    <th>Charge Name</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($financial_charges as $financial_charge)
                                    <tr id="financial_charge-tr-{{$financial_charge->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td class="font-w600">{{ $financial_charge->date }}</td>
                                        <td class="font-w600">{{ $financial_charge->supplier->name ?? null }}</td>
                                        <td class="font-w600">{{ $financial_charge->financialChargeCategory->name ?? null }}</td>
                                        <td class="d-none d-sm-table-cell">{{ $financial_charge->description }}
                                        <td class="font-w600">{{ number_format($financial_charge->amount, 2) }}</td>

                                        <td class="text-center">
                                            <div class="btn-group">
                                                @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Edit Financial Charge"))
                                                    <button type="button"
                                                            onclick="loadFormModal('financial_charge_form', {className: 'FinancialCharge', id: {{$financial_charge->id}}}, 'Edit {{$financial_charge->name}}', 'modal-md');"
                                                            class="btn btn-sm btn-primary js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endif


                                                    @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Delete Financial Charge"))
                                                        <button type="button"
                                                                onclick="deleteModelItem('FinancialCharge', {{$financial_charge->id}}, 'financial_charge-tr-{{$financial_charge->id}}');"
                                                                class="btn btn-sm btn-danger js-tooltip-enabled"
                                                                data-toggle="tooltip" title="Delete"
                                                                data-original-title="Delete">
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    @endif


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



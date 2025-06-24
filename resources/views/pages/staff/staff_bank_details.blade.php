@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Employee Management

                <div class="float-right">
                    @can('Add Staff Bank Detail')
                        <button type="button" onclick="loadFormModal('settings_staff_bank_detail_form', {className: 'StaffBankDetail'}, 'Create New Staff Bank Detail', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Staff Bank Detail</button>
                    @endcan

                </div>
            </div>
            <div>

                <div class="block">
                    <div class="block-header  block-header-default">
                        <h3 class="block-title">Staff Bank Details</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                        </div>
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Staff</th>
                                <th>Bank</th>
                                <th>Account Number</th>
                                <th>Branch</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach($staff_bank_details as $staff_bank_detail)
                                <tr id="staff_bank_detail-tr-{{$staff_bank_detail->id}}">
                                    <td class="text-center">
                                        {{$loop->iteration}}
                                    </td>
                                    <td class="font-w600">{{ $staff_bank_detail->staff->name ?? null }}</td>
                                    <td class="font-w600">{{ $staff_bank_detail->bank->name  ?? null}}</td>
                                    <td class="font-w600">{{ $staff_bank_detail->account_number}}</td>
                                    <td class="font-w600">{{ $staff_bank_detail->branch}}</td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                        @can('Edit Staff Bank Detail')
                                                <button type="button" onclick="loadFormModal('settings_staff_bank_detail_form', {className: 'StaffBankDetail', id: {{$staff_bank_detail->id}}}, 'Edit {{$staff_bank_detail->staff->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                                @can('Delete Staff Bank Detail')
                                                    <button type="button" onclick="deleteModelItem('StaffBankDetail', {{$staff_bank_detail->id}}, 'staff_bank_detail-tr-{{$staff_bank_detail->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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

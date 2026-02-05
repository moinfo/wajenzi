@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Settings
                @include('components.headed_paper_settings')
                <br/>
                <div class="block-header text-center">
                    <h3 class="block-title">Payroll Types</h3>
                </div>
                <div class="float-right">
                                        @can('Add Payroll Type')
                    <button type="button" onclick="loadFormModal('payroll_type_form', {className: 'PayrollType'}, 'Create New PayrollType', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                        <i class="si si-plus">&nbsp;</i>New PayrollType</button>
                                        @endcan

                </div>
            </div>
            <div>
                <div class="block">

                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>Name</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($payroll_types as $payroll_type)
                                <tr id="payroll_type-tr-{{$payroll_type->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $payroll_type->name }}</td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                                                                        @can('Edit Payroll Type')
                                            <button type="button" onclick="loadFormModal('payroll_type_form', {className: 'PayrollType', id: {{$payroll_type->id}}}, 'Edit {{$payroll_type->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                                                                        @endcan

                                                                                            @can('Delete Payroll Type')
                                            <button type="button" onclick="deleteModelItem('PayrollType', {{$payroll_type->id}}, 'payroll_type-tr-{{$payroll_type->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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

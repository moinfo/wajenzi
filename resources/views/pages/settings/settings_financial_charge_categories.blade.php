@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Financial Charge
                <div class="float-right">
                    @can('Add Financial Charge Category')
                        <button type="button" onclick="loadFormModal('settings_financial_charge_category_form', {className: 'FinancialChargeCategory'}, 'Create New Financial Charge Category', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Charge</button>
                    @endcan

                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Financial Charge</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Name</th>
                                <th>Charge</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($financial_charge_categories as $financial_charge_category)
                                <tr id="financial_charge_category-tr-{{$financial_charge_category->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $financial_charge_category->name }}</td>
                                    <td class="text-right">{{ number_format($financial_charge_category->charge) }}</td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit Financial Charge Category')
                                                <button type="button" onclick="loadFormModal('settings_financial_charge_category_form', {className: 'FinancialChargeCategory', id: {{$financial_charge_category->id}}}, 'Edit {{$financial_charge_category->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                                @can('Delete Financial Charge Category')
                                                    <button type="button" onclick="deleteModelItem('FinancialChargeCategory', {{$financial_charge_category->id}}, 'financial_charge_category-tr-{{$financial_charge_category->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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

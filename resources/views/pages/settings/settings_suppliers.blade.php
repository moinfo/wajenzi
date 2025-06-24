@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">SUPPLIER
                <div class="float-right">
                    @can('Add Supplier')
                        <button type="button" onclick="loadFormModal('settings_supplier_form', {className: 'Supplier'}, 'Create New Supplier', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Supplier</button>
                    @endcan
                    @can('Add Supplier')
                        <button type="button" onclick="loadFormModal('settings_supplier_contact_form', {className: 'SupplierContact'}, 'Add Supplier Contact', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>Add Supplier Contact</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">SUPPLIER</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full" data-ordering="false">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Address</th>
                                    <th>VRN</th>
                                    <th>System</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>

                                @foreach($suppliers as $supplier)
                                    @php

                                    @endphp
                                    <tr id="supplier-tr-{{$supplier->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td class="font-w600">{{ $supplier->name }}</td>
                                        <td class="font-w400">{{ $supplier->phone }}</td>
                                        <td class="font-w400">{{ $supplier->address }}</td>
                                        <td class="font-w400">{{ $supplier->vrn }}</td>
                                        <td class="font-w400">{{ $supplier->system->name ?? null }}</td>

                                        <td class="text-center" >
                                            <div class="btn-group">
                                                @can('Edit Supplier')
                                                    <button type="button" onclick="loadFormModal('settings_supplier_form', {className: 'Supplier', id: {{$supplier->id}}}, 'Edit {{$supplier->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan

                                                @can('Delete Supplier')
                                                    <button type="button" onclick="deleteModelItem('Supplier', {{$supplier->id}}, 'supplier-tr-{{$supplier->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                @endcan

                                            </div>
                                        </td>
                                    </tr>
                                    @php
                                    $supplier_contacts = \App\Models\SupplierContact::where('supplier_id',$supplier->id)->get();
                                    @endphp
                                    @if(count($supplier_contacts))
                                    <tr>
                                        <th class="text-right">#</th>
                                        <th colspan="2">Account Name</th>
                                        <th style="display: none;"></th>
                                        <th colspan="3">Account Number</th>
                                        <th style="display: none;"></th>
                                        <th style="display: none;"></th>
                                        <th colspan="4">Bank</th>
                                        <th style="display: none;"></th>
                                        <th style="display: none;"></th>
                                        <th style="display: none;"></th>
                                        <th></th>
                                        <th class="text-center" style="width: 100px;">Actions</th>
                                    </tr>
                                    @foreach($supplier_contacts as $supplier_contact)
                                        <tr id="supplier-contact-tr-{{$supplier_contact->id}}">
                                            <td class="text-right"> {{$loop->iteration}}</td>
                                            <td colspan="2" class="font-w600">{{ $supplier_contact->account_name }}</td>
                                            <th style="display: none;"></th>
                                            <td colspan="3" class="font-w400">{{ $supplier_contact->account_number }}</td>
                                            <th style="display: none;"></th>
                                            <th style="display: none;"></th>
                                            <td colspan="4" class="font-w400">{{ $supplier_contact->bank->name ?? null }}</td>
                                            <th style="display: none;"></th>
                                            <th style="display: none;"></th>
                                            <th style="display: none;"></th>
                                            <th></th>
                                            <td class="text-center" >
                                                <div class="btn-group">
                                                    @can('Edit Supplier')
                                                        <button type="button" onclick="loadFormModal('settings_supplier_contact_form', {className: 'SupplierContact', id: {{$supplier_contact->id}}}, 'Edit {{$supplier_contact->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                            <i class="fa fa-pencil"></i>
                                                        </button>
                                                    @endcan
                                                    @can('Delete Supplier')
                                                        <button type="button" onclick="deleteModelItem('SupplierContact', {{$supplier_contact->id}}, 'supplier-contact-tr-{{$supplier_contact->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @endif
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

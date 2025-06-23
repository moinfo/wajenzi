@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Settings
                <br/>
                <div class="block-header text-center">
                    <h3 class="block-title">Adjustments</h3>
                </div>
                <div class="float-right">
                    @can('Add Adjustment')
                        <button type="button" onclick="loadFormModal('settings_adjustment_form', {className: 'Adjustment'}, 'Create New Adjustment', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10">
                            <i class="si si-plus">&nbsp;</i>New Adjustment</button>
                    @endcan

                </div>
            </div>
            <div>

                <div class="block">

                    <div class="block-content">
                        <div class="row no-print m-t-10">
                        </div>
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Date</th>
                                <th>Staff</th>
                                <th>Amount</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach($adjustments as $adjustment)
                                <tr id="adjustment-tr-{{$adjustment->id}}">
                                    <td class="text-center">
                                        {{$loop->iteration}}
                                    </td>
                                    <td class="font-w600">{{ $adjustment->date  ?? null}}</td>
                                    <td class="font-w600">{{ $adjustment->staff->name ?? null }}</td>
                                    <td class="font-w600">{{ number_format($adjustment->amount)}}</td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                        @can('Edit Adjustment')
                                                <button type="button" onclick="loadFormModal('settings_adjustment_form', {className: 'Adjustment', id: {{$adjustment->id}}}, 'Edit {{$adjustment->staff->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                                @can('Delete Adjustment')
                                                    <button type="button" onclick="deleteModelItem('Adjustment', {{$adjustment->id}}, 'adjustment-tr-{{$adjustment->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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

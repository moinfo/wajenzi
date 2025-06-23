@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    @can('Add Deduction Setting')
                        <button type="button" onclick="loadFormModal('settings_deduction_settings_form', {className: 'DeductionSetting'}, 'Create New DeductionSetting', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10">
                            <i class="si si-plus">&nbsp;</i>New DeductionSetting</button>
                    @endcan

                </div>
            </div>
            <div>

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Deduction Setting</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Deduction</th>
                                <th>Minimum Amount</th>
                                <th>Maximum Amount</th>
                                <th>Employee Percentage %</th>
                                <th>Employer Percentage %</th>
                                <th>Additional Amount</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($deduction_settings as $deduction_setting)
                                <tr id="deduction_setting-tr-{{$deduction_setting->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $deduction_setting->deduction->name ?? null}}</td>
                                    <td class="text-right">{{ number_format($deduction_setting->minimum_amount)}}</td>
                                    <td class="text-right">{{ number_format($deduction_setting->maximum_amount)}}</td>
                                    <td class="text-right">{{ number_format($deduction_setting->employee_percentage,2)}}</td>
                                    <td class="text-right">{{ number_format($deduction_setting->employer_percentage,2)}}</td>
                                    <td class="text-right">{{ number_format($deduction_setting->additional_amount)}}</td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit Deduction Setting')
                                                <button type="button" onclick="loadFormModal('settings_deduction_settings_form', {className: 'DeductionSetting', id: {{$deduction_setting->id}}}, 'Edit {{$deduction_setting->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan
                                                @can('Delete Deduction Setting')
                                                    <button type="button" onclick="deleteModelItem('DeductionSetting', {{$deduction_setting->id}}, 'deduction_setting-tr-{{$deduction_setting->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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

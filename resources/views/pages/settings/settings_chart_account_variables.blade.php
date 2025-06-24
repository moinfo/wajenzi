@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    @can('Add Chart Account Variable')
                        <button type="button" onclick="loadFormModal('settings_chart_account_variable_form', {className: 'ChartAccountVariable'}, 'Create New Chart Account Variable', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Chart Account Variable</button>@endcan

                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Chart Account Variables</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Variable</th>
                                <th>Value</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($chart_account_variables as $chart_account_variable)
                                <tr id="chart_account_variable-tr-{{$chart_account_variable->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $chart_account_variable->variable }}</td>
                                    <td class="font-w600">{{ $chart_account_variable->value }}</td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit Chart Account Variable')
                                                <button type="button" onclick="loadFormModal('settings_chart_account_variable_form', {className: 'ChartAccountVariable', id: {{$chart_account_variable->id}}}, 'Edit {{$chart_account_variable->type}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                                @can('Delete Chart Account Variable')
                                                    <button type="button" onclick="deleteModelItem('ChartAccountVariable', {{$chart_account_variable->id}}, 'chart_account_variable-tr-{{$chart_account_variable->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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

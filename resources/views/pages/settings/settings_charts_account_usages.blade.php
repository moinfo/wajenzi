@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    @can('Add Charts Account Usage')
                        <button type="button" onclick="loadFormModal('settings_charts_account_usage_form', {className: 'ChartAccountUsage'}, 'Create New Charts Account Usage', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Charts Account Usage</button>@endcan

                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Charts Account Usages</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Name</th>
                                <th>Charts Account</th>
                                <th>Description</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($charts_account_usages as $charts_account_usage)
                                <tr id="charts_account_usage-tr-{{$charts_account_usage->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $charts_account_usage->name }}</td>
                                    <td class="font-w600">{{ $charts_account_usage->chartAccount->account_name ?? '' }}</td>
                                    <td class="font-w600">{{ $charts_account_usage->description }}</td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit Charts Account Usage')
                                                <button type="button" onclick="loadFormModal('settings_charts_account_usage_form', {className: 'ChartsAccountUsage', id: {{$charts_account_usage->id}}}, 'Edit {{$charts_account_usage->type}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                                @can('Delete Charts Account Usage')
                                                    <button type="button" onclick="deleteModelItem('ChartAccountUsage', {{$charts_account_usage->id}}, 'charts_account_usage-tr-{{$charts_account_usage->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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

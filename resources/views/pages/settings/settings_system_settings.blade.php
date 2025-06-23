@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    @can('Add System Setting')
                        <button type="button" onclick="loadFormModal('system_setting_form', {className: 'SystemSetting'}, 'Create New SystemSetting', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10">
                            <i class="si si-plus">&nbsp;</i>New System Setting</button>
                    @endcan

                </div>
            </div>
            <div>

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">System Settings</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Key</th>
                                <th>Value</th>
                                <th>Previous Value</th>
                                <th>Description</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($system_settings as $system_setting)
                                <tr id="system_setting-tr-{{$system_setting->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $system_setting->key }}</td>
                                    <td class="font-w600">{{ $system_setting->value }}</td>
                                    <td class="font-w600">{{ $system_setting->previous_value }}</td>
                                    <td class="font-w600">{{ $system_setting->description}}</td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                        @can('Edit System Setting')
                                                <button type="button" onclick="loadFormModal('system_setting_form', {className: 'SystemSetting', id: {{$system_setting->id}}}, 'Edit {{$system_setting->key}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                                @can('Delete System Setting')
                                                    <button type="button" onclick="deleteModelItem('SystemSetting', {{$system_setting->id}}, 'system_setting-tr-{{$system_setting->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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

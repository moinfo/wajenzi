@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Add Chart Account"))
                        <button type="button" onclick="loadFormModal('settings_chart_of_account_form', {className: 'ChartAccount'}, 'Create New Chart Account', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10">
                            <i class="si si-plus">&nbsp;</i>New Chart Account</button>@endif

                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Chart Accounts</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Code</th>
                                <th>Account Name</th>
                                <th>Currency</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                            $no_alpha = 'A';
                            @endphp
                            @foreach($account_types as $account_type)
                                <tr>
                                    <td>{{$no_alpha}}</td>
                                    <td>{{$account_type->code}}</td>
                                    <td>{{$account_type->type}}</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                @php
                                    $no_alpha ++;
                                @endphp
                                @endforeach
                            @foreach($chart_of_accounts as $chart_of_account)
                                <tr id="chart_of_account-tr-{{$chart_of_account->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $chart_of_account->name }}</td>
                                    <td class="d-none d-sm-table-cell">{{ $chart_of_account->description }}
                                    </td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Edit Chart Account"))
                                                <button type="button" onclick="loadFormModal('settings_chart_of_account_form', {className: 'ChartAccount', id: {{$chart_of_account->id}}}, 'Edit {{$chart_of_account->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endif

                                                @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Delete Chart Account"))
                                                    <button type="button" onclick="deleteModelItem('ChartAccount', {{$chart_of_account->id}}, 'chart_of_account-tr-{{$chart_of_account->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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
@endsection

@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    @can('Add Chart Account')
                        <button type="button" onclick="loadFormModal('settings_chart_of_account_form', {className: 'ChartAccount'}, 'Create New Chart Account', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10">
                            <i class="si si-plus">&nbsp;</i>New Chart Account</button>
                    @endcan
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
                        <table class="table table-bordered table-striped table-vcenter">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th colspan="4">Code</th>
                                <th>Account Name</th>
                                <th>Currency</th>
                                <th class="text-center" style="width: 100px;">Option</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                // Get top level accounts (parents)
                                $topAccounts = $account_types;
                                $counter = 1;
                                $counter_letter = 'A';
                            @endphp

                            @foreach($topAccounts as $accountType)
                                <tr class="bg-success text-white">
                                    <td class="text-center">{{ $counter_letter }}</td>
                                    <td colspan="3">{{ $accountType->code }}</td>
                                    <td></td>
                                    <td>{{ strtoupper($accountType->type) }}</td>
                                    <td></td>
                                    <td class="text-center"></td>
                                </tr>

                                @php
                                    $counter_letter++;
                                        // Get first level children for this account type
                                        $firstLevelAccounts = $chart_of_accounts->where('account_type', $accountType->id)->whereNull('parent');
                                @endphp

                                @foreach($firstLevelAccounts as $firstLevelAccount)
                                    <tr class="bg-warning text-white">
                                        <td class="text-center">{{ $counter++ }}</td>
                                        <td colspan="3">{{ $firstLevelAccount->code }}</td>
                                        <td></td>
                                        <td>{{ strtoupper($firstLevelAccount->account_name) }}</td>
                                        <td>{{ \App\Models\Currency::find($firstLevelAccount->currency)->symbol }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                @can('Edit Chart Account')
                                                    <button type="button" onclick="loadFormModal('settings_chart_of_account_form', {className: 'ChartAccount', id: {{ $firstLevelAccount->id }}}, 'Edit {{ $firstLevelAccount->account_name }}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan
                                                @can('Delete Chart Account')
                                                    <button type="button" onclick="deleteModelItem('ChartAccount', {{ $firstLevelAccount->id }}, 'chart_of_account-tr-{{ $firstLevelAccount->id }}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>

                                    @php
                                        // Get second level children (specific account categories)
                                        $secondLevelAccounts = $chart_of_accounts->where('parent', $firstLevelAccount->id);
                                    @endphp

                                    @foreach($secondLevelAccounts as $secondLevelAccount)
                                        <tr class="bg-primary text-white">
                                            <td class="text-center">{{ $counter++ }}</td>
                                            <td></td>
                                            <td colspan="2">{{ $secondLevelAccount->code }}</td>
                                            <td></td>
                                            <td>{{ $secondLevelAccount->account_name }}</td>
                                            <td>{{ \App\Models\Currency::find($secondLevelAccount->currency)->symbol }}</td>

                                            <td class="text-center">
                                                <div class="btn-group">
                                                    @can('Edit Chart Account')
                                                        <button type="button" onclick="loadFormModal('settings_chart_of_account_form', {className: 'ChartAccount', id: {{ $secondLevelAccount->id }}}, 'Edit {{ $secondLevelAccount->account_name }}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                            <i class="fa fa-pencil"></i>
                                                        </button>
                                                    @endcan
                                                    @can('Delete Chart Account')
                                                        <button type="button" onclick="deleteModelItem('ChartAccount', {{ $secondLevelAccount->id }}, 'chart_of_account-tr-{{ $secondLevelAccount->id }}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>

                                        @php
                                            // Get third level children (specific accounts)
                                            $thirdLevelAccounts = $chart_of_accounts->where('parent', $secondLevelAccount->id);
                                        @endphp

                                        @foreach($thirdLevelAccounts as $thirdLevelAccount)
                                            <tr class="bg-secondary text-white">
                                                <td class="text-center">{{ $counter++ }}</td>
                                                <td></td>
                                                <td></td>
                                                <td style="padding-left: 30px;">{{ $thirdLevelAccount->code }}</td>
                                                <td></td>
                                                <td>{{ $thirdLevelAccount->account_name }}</td>
                                                <td>{{ \App\Models\Currency::find($thirdLevelAccount->currency)->symbol }}</td>

                                                <td class="text-center">
                                                    <div class="btn-group">
                                                        @can('Edit Chart Account')
                                                            <button type="button" onclick="loadFormModal('settings_chart_of_account_form', {className: 'ChartAccount', id: {{ $thirdLevelAccount->id }}}, 'Edit {{ $thirdLevelAccount->account_name }}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                                <i class="fa fa-pencil"></i>
                                                            </button>
                                                        @endcan
                                                        @can('Delete Chart Account')
                                                            <button type="button" onclick="deleteModelItem('ChartAccount', {{ $thirdLevelAccount->id }}, 'chart_of_account-tr-{{ $thirdLevelAccount->id }}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
                                                                <i class="fa fa-times"></i>
                                                            </button>
                                                        @endcan
                                                    </div>
                                                </td>
                                            </tr>

                                        @php
                                            // Get third level children (specific accounts)
                                            $fourthLevelAccounts = $chart_of_accounts->where('parent', $thirdLevelAccount->id);
                                        @endphp

                                        @foreach($fourthLevelAccounts as $fourthLevelAccount)
                                            <tr>
                                                <td class="text-center">{{ $counter++ }}</td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td style="padding-left: 30px;">{{ $fourthLevelAccount->code }}</td>
                                                <td>{{ $fourthLevelAccount->account_name }}</td>
                                                <td>{{ \App\Models\Currency::find($fourthLevelAccount->currency)->symbol }}</td>

                                                <td class="text-center">
                                                    <div class="btn-group">
                                                        @can('Edit Chart Account')
                                                            <button type="button" onclick="loadFormModal('settings_chart_of_account_form', {className: 'ChartAccount', id: {{ $fourthLevelAccount->id }}}, 'Edit {{ $fourthLevelAccount->account_name }}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                                <i class="fa fa-pencil"></i>
                                                            </button>
                                                        @endcan
                                                        @can('Delete Chart Account')
                                                            <button type="button" onclick="deleteModelItem('ChartAccount', {{ $fourthLevelAccount->id }}, 'chart_of_account-tr-{{ $fourthLevelAccount->id }}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
                                                                <i class="fa fa-times"></i>
                                                            </button>
                                                        @endcan
                                                    </div>
                                                </td>
                                            </tr>
                                    @endforeach
                                    @endforeach
                                    @endforeach
                                @endforeach
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

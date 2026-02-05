@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    @can('Add Exchange Rate')
                        <button type="button" onclick="loadFormModal('settings_exchange_rate_form', {className: 'ExchangeRate'}, 'Create New Exchange Rate', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Exchange Rate</button>@endcan

                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Exchange Rates</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Foreign Currency</th>
                                <th>Base Currency</th>
                                <th>Rate</th>
                                <th>Month</th>
                                <th>Year</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($exchange_rates as $exchange_rate)
                                <tr id="exchange_rate-tr-{{$exchange_rate->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $exchange_rate->foreignCurrency->name ?? null }} - {{ $exchange_rate->foreignCurrency->symbol ?? null }}</td>
                                    <td class="font-w600">{{ $exchange_rate->baseCurrency->name ?? null }} - {{ $exchange_rate->baseCurrency->symbol ?? null }}</td>
                                    <td class="font-w600">{{ $exchange_rate->rate }}</td>
                                    <td class="font-w600">{{ $exchange_rate->month }}</td>
                                    <td class="font-w600">{{ $exchange_rate->year }}</td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit Exchange Rate')
                                                <button type="button" onclick="loadFormModal('settings_exchange_rate_form', {className: 'ExchangeRate', id: {{$exchange_rate->id}}}, 'Edit {{$exchange_rate->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                                @can('Delete Exchange Rate')
                                                    <button type="button" onclick="deleteModelItem('ExchangeRate', {{$exchange_rate->id}}, 'exchange_rate-tr-{{$exchange_rate->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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

@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    @can('Add Allowance Subscription')
                        <button type="button" onclick="loadFormModal('settings_allowance_subscriptions_form', {className: 'AllowanceSubscription'}, 'Create New AllowanceSubscription', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New AllowanceSubscription</button>@endcan

                </div>
            </div>
            <div>

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Allowance Subscription</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Date</th>
                                <th>Name</th>
                                <th>Allowance</th>
                                <th>Amount</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $sum = 0;
                            ?>
                            @foreach($allowance_subscriptions as $allowance_subscription)
                                <?php
                                    $sum += $allowance_subscription->amount;
                                ?>
                                <tr id="allowance_subscription-tr-{{$allowance_subscription->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $allowance_subscription->date }}</td>
                                    <td class="font-w600">{{ $allowance_subscription->staff->name ?? null}}</td>
                                    <td class="font-w600">{{ $allowance_subscription->allowance->name ?? null}}</td>
                                    <td class="text-right">{{ number_format($allowance_subscription->amount) }}
                                    </td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit Allowance Subscription')
                                                <button type="button" onclick="loadFormModal('settings_allowance_subscriptions_form', {className: 'AllowanceSubscription', id: {{$allowance_subscription->id}}}, 'Edit {{$allowance_subscription->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan
                                                @can('Delete Allowance Subscription')
                                                    <button type="button" onclick="deleteModelItem('AllowanceSubscription', {{$allowance_subscription->id}}, 'allowance_subscription-tr-{{$allowance_subscription->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                @endcan

                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td class="text-right">{{number_format($sum)}}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

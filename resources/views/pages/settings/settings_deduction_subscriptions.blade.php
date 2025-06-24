@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    @can('Add Deduction Subscription')
                        <button type="button" onclick="loadFormModal('settings_deduction_subscriptions_form', {className: 'DeductionSubscription'}, 'Create New DeductionSubscription', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Deduction Subscription</button>
                    @endcan

                </div>
            </div>
            <div>

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Deduction Subscription</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Name</th>
                                <th>Deduction</th>
                                <th>Membership Number</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($deduction_subscriptions as $deduction_subscription)
                                <tr id="deduction_subscription-tr-{{$deduction_subscription->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $deduction_subscription->staff->name ?? null }}</td>
                                    <td class="font-w600">{{ $deduction_subscription->deduction->name ?? null}}</td>
                                    <td class="font-w600">{{ $deduction_subscription->membership_number}}</td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit Deduction Subscription')
                                                <button type="button" onclick="loadFormModal('settings_deduction_subscriptions_form', {className: 'DeductionSubscription', id: {{$deduction_subscription->id}}}, 'Edit {{$deduction_subscription->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan
                                                @can('Delete Deduction Subscription')
                                                    <button type="button" onclick="deleteModelItem('DeductionSubscription', {{$deduction_subscription->id}}, 'deduction_subscription-tr-{{$deduction_subscription->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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

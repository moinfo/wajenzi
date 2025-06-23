@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Petty Cash Refill Request
                <div class="float-right">
                    @can('Add Petty Cash Refill Request')
                        <button type="button" onclick="loadFormModal('petty_cash_refill_request_form', {className: 'PettyCashRefillRequest'}, 'Create New Petty Cash Refill Request', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Petty Cash Refill Request</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Petty Cash Refill Request</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form  name="petty_cash_refill_requests_search" action="{{route('petty_cash_refill_requests')}}" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Start</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon2">End</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-2">
                                                <div>
                                                    <button type="submit" name="submit"  class="btn btn-sm btn-primary">Show</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <br/>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Date</th>
                                    <th>Document Number</th>
                                    <th>Balance on Request</th>
                                    <th>Refill Amount</th>
                                    <th>Requested User</th>
                                    <th scope="col">Attachment</th>
                                    <th scope="col">Approvals</th>
                                    <th scope="col">Status</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $refill_amount = 0;
                                ?>
                                @foreach($petty_cash_refill_requests as $petty_cash_refill_request)
                                    <?php
                                    $refill_amount += $petty_cash_refill_request->refill_amount;
                                    ?>
                                    @php
                                        $approval_document_types_id = 12;
                                        $approvals = \App\Models\ApprovalLevel::getUsersApprovals($approval_document_types_id);
                                    @endphp


                                    <tr id="petty_cash_refill_request-tr-{{$petty_cash_refill_request->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td class="font-w600">{{ $petty_cash_refill_request->date }}</td>
                                        <td class="font-w600">{{ $petty_cash_refill_request->document_number }}</td>
                                        <td class="font-w600">{{ number_format($petty_cash_refill_request->balance, 2) }}</td>
                                        <td class="font-w600">{{ number_format($petty_cash_refill_request->refill_amount, 2) }}</td>
                                        <td class="font-w600">{{ $petty_cash_refill_request->user->name ?? ''}}</td>
                                        <td class="text-center">
                                            @if($petty_cash_refill_request->file != null)
                                                <a href="{{ url("$petty_cash_refill_request->file") }}">Attachment</a>
                                            @else
                                                No File
                                            @endif
                                        </td>
                                        <td class="approvals-cell">
                                            <div class="approval-badges">
                                                @foreach($approvals as $approval)
                                                    @php
                                                        $approval_level_id = $approval->id;
                                                        $document_id = $petty_cash_refill_request->id;
                                                        $group_name = \App\Models\ApprovalLevel::getUserGroupName($approval_level_id);
                                                        $approves = \App\Models\Approval::getApproved($approval_level_id,$document_id);
                                                    @endphp
                                                    @if(count($approves))
                                                        @foreach($approves as $approve)
                                                            @if($approve->user_group_id == $approval->user_group_id)
                                                                <span class="approval-badge approved">
                            <i class="fa fa-check"></i>{{$group_name ?? null}}
                        </span>
                                                            @else
                                                                <span class="approval-badge pending">
                            <i class="fa fa-clock-o"></i>{{$group_name ?? null}}
                        </span>
                                                            @endif
                                                        @endforeach
                                                    @else
                                                        <span class="approval-badge pending">
                    <i class="fa fa-clock-o"></i>{{$group_name ?? null}}
                </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </td>

                                        <td>
                                            @if($petty_cash_refill_request->status == 'PENDING')
                                                <div class="badge badge-warning">{{ $petty_cash_refill_request->status}}</div>
                                            @elseif($petty_cash_refill_request->status == 'APPROVED')
                                                <div class="badge badge-primary">{{ $petty_cash_refill_request->status}}</div>
                                            @elseif($petty_cash_refill_request->status == 'REJECTED')
                                                <div class="badge badge-danger">{{ $petty_cash_refill_request->status}}</div>
                                            @elseif($petty_cash_refill_request->status == 'PAID')
                                                <div class="badge badge-primary">{{ $petty_cash_refill_request->status}}</div>
                                            @elseif($petty_cash_refill_request->status == 'COMPLETED')
                                                <div class="badge badge-success">{{ $petty_cash_refill_request->status}}</div>
                                            @else
                                                <div class="badge badge-secondary">{{ $petty_cash_refill_request->status}}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success js-tooltip-enabled" href="{{route('petty_cash_refill_request',['id' => $petty_cash_refill_request->id,'document_type_id'=>12])}}"><i class="fa fa-eye"></i></a>
                                            @can('Edit Petty Cash Refill Request')
                                                    <button type="button"
                                                            onclick="loadFormModal('petty_cash_refill_request_form', {className: 'PettyCashRefillRequest', id: {{$petty_cash_refill_request->id}}}, 'Edit Petty Cash Refill Request', 'modal-md');"
                                                            class="btn btn-sm btn-primary js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan

                                                    @can('Delete Petty Cash Refill Request')
                                                        <button type="button"
                                                                onclick="deleteModelItem('PettyCashRefillRequest', {{$petty_cash_refill_request->id}}, 'petty_cash_refill_request-tr-{{$petty_cash_refill_request->id}}');"
                                                                class="btn btn-sm btn-danger js-tooltip-enabled"
                                                                data-toggle="tooltip" title="Delete"
                                                                data-original-title="Delete">
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
                                    <td class="text-right text-dark" colspan="5"><b>{{number_format($refill_amount,2)}}</b></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
{{--                                    <td></td>--}}
                                </tr>
                                </tfoot>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection



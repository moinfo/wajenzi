@extends('layouts.backend')

@section('content')

    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Imprest Request
                <div class="float-right">
                    @can('Add Imprest Request')
                        <button type="button" onclick="loadFormModal('imprest_request_form', {className: 'ImprestRequest'}, 'Create New Imprest Request', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Imprest Request</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Imprest Request</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form  name="imprest_requests_search" action="{{route('imprest_requests')}}" id="filter-form" method="post" autocomplete="off">
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
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Requested User</th>
                                    <th>Project</th>
                                    <th scope="col">Attachment</th>
                                    <th scope="col">Approvals</th>
                                    <th scope="col">Status</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $imprest_amount = 0;
                                ?>
                                @foreach($imprest_requests as $imprest_request)
                                    <?php
                                    $imprest_amount += $imprest_request->amount;
                                    ?>
                                    @php
                                        $approval_document_types_id = 13;
                                        $approvals = \App\Models\ApprovalLevel::getUsersApprovals($approval_document_types_id);
                                    @endphp


                                    <tr id="imprest_request-tr-{{$imprest_request->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td class="font-w600">{{ $imprest_request->date }}</td>
                                        <td class="font-w600">{{ $imprest_request->document_number }}</td>
                                        <td class="font-w600">{{ $imprest_request->description }}</td>
                                        <td class="font-w600">{{ number_format($imprest_request->amount, 2) }}</td>
                                        <td class="font-w600">{{ $imprest_request->user->name ?? ''}}</td>
                                        <td class="font-w600">{{ $imprest_request->project->project_name ?? ''}}</td>
                                        <td class="text-center">
                                            @if($imprest_request->file != null)
                                                <a href="{{ url("$imprest_request->file") }}">Attachment</a>
                                            @else
                                                No File
                                            @endif
                                        </td>
                                        <td class="approvals-cell">
                                            <div class="approval-badges">
                                                @foreach($approvals as $approval)
                                                    @php
                                                        $approval_level_id = $approval->id;
                                                        $document_id = $imprest_request->id;
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
                                            @if($imprest_request->status == 'PENDING')
                                                <div class="badge badge-warning">{{ $imprest_request->status}}</div>
                                            @elseif($imprest_request->status == 'APPROVED')
                                                <div class="badge badge-primary">{{ $imprest_request->status}}</div>
                                            @elseif($imprest_request->status == 'REJECTED')
                                                <div class="badge badge-danger">{{ $imprest_request->status}}</div>
                                            @elseif($imprest_request->status == 'PAID')
                                                <div class="badge badge-primary">{{ $imprest_request->status}}</div>
                                            @elseif($imprest_request->status == 'COMPLETED')
                                                <div class="badge badge-success">{{ $imprest_request->status}}</div>
                                            @else
                                                <div class="badge badge-secondary">{{ $imprest_request->status}}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success js-tooltip-enabled" href="{{route('imprest_request',['id' => $imprest_request->id,'document_type_id'=>13])}}"><i class="fa fa-eye"></i></a>
                                            @can('Edit Imprest Request')
                                                    <button type="button"
                                                            onclick="loadFormModal('imprest_request_form', {className: 'ImprestRequest', id: {{$imprest_request->id}}}, 'Edit Imprest Request', 'modal-md');"
                                                            class="btn btn-sm btn-primary js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan

                                                    @can('Delete Imprest Request')
                                                        <button type="button"
                                                                onclick="deleteModelItem('ImprestRequest', {{$imprest_request->id}}, 'imprest_request-tr-{{$imprest_request->id}}');"
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
                                    <td class="text-right text-dark" colspan="5"><b>{{number_format($imprest_amount,2)}}</b></td>
                                    <td></td>
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

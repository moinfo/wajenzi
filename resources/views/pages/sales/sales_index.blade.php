@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Sales
                <div class="float-right">
                    @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Add Sales"))
                        <button type="button" onclick="loadFormModal('sale_form', {className: 'Sale'}, 'Create New Sales', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Sales</button>
                    @endif
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Sales</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form  name="collection_search" action="" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Start Date</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon2">End Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon3">EFD</span>
                                                    </div>
                                                    <select name="efd_id" id="input-efd-id" class="form-control" aria-describedby="basic-addon3">
                                                        <option value="">All EFD</option>
                                                        @foreach ($efds as $efd)
                                                            <option value="{{ $efd->id }}"> {{ $efd->name }} </option>
                                                        @endforeach
                                                    </select>
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
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Date</th>
                                    <th>EFD Name</th>
                                    <th>Turnover</th>
                                    <th>NET (A+B+C)</th>
                                    <th>Tax</th>
                                    <th>Turnover (EX + SR)</th>
                                    <th>Attachment</th>
                                    <th scope="col">Approvals</th>
                                    <th scope="col">Status</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $total_amount = 0;
                                $total_net = 0;
                                $total_tax = 0;
                                $total_turn_over = 0;
                                ?>
                                @foreach($sales as $sale)
                                    <?php
                                    $amount = $sale->amount;
                                    $total_amount += $amount;
                                    $net = $sale->net;
                                    $total_net += $net;
                                    $tax = $sale->tax;
                                    $total_tax += $tax;
                                    $turn_over = $sale->turn_over;
                                    $total_turn_over += $turn_over;
                                    ?>
                                    @php
                                        $approval_document_types_id = 2;
                                        $approvals = \App\Models\ApprovalLevel::getUsersApprovals($approval_document_types_id);
                                    @endphp

                                    <tr id="sale-tr-{{$sale->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td class="font-w600">{{ $sale->date }}</td>
                                        <td class="font-w600">{{ $sale->efd }}</td>
                                        <td class="text-right">{{ number_format($sale->amount, 2) }}</td>
                                        <td class="text-right">{{ number_format($sale->net, 2) }}</td>
                                        <td class="text-right">{{ number_format($sale->tax, 2) }}</td>
                                        <td class="text-right">{{ number_format($sale->turn_over, 2) }}</td>
                                        <td class="text-center">
                                            @if($sale->file != null)
                                                <a href="{{ url("$sale->file") }}">Attachment</a>
                                            @else
                                                No File
                                            @endif
                                        </td>
                                        <td class="approvals-cell">
                                            <div class="approval-badges">
                                                @foreach($approvals as $approval)
                                                    @php
                                                        $approval_level_id = $approval->id;
                                                        $document_id = $sale->id;
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
                                            @if($sale->status == 'PENDING')
                                                <div class="badge badge-warning">{{ $sale->status}}</div>
                                            @elseif($sale->status == 'APPROVED')
                                                <div class="badge badge-primary">{{ $sale->status}}</div>
                                            @elseif($sale->status == 'REJECTED')
                                                <div class="badge badge-danger">{{ $sale->status}}</div>
                                            @elseif($sale->status == 'PAID')
                                                <div class="badge badge-primary">{{ $sale->status}}</div>
                                            @elseif($sale->status == 'COMPLETED')
                                                <div class="badge badge-success">{{ $sale->status}}</div>
                                            @else
                                                <div class="badge badge-secondary">{{ $sale->status}}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success js-tooltip-enabled" href="{{route('sale',['id' => $sale->id,'document_type_id'=>2])}}"><i class="fa fa-eye"></i></a>
                                            @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Edit Sales"))
                                                    <button type="button"
                                                            onclick="loadFormModal('sale_form', {className: 'Sale', id: {{$sale->id}}}, 'Edit {{$sale->efd}}', 'modal-md');"
                                                            class="btn btn-sm btn-primary js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endif

                                                    @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Delete Sales"))
                                                        <button type="button"
                                                                onclick="deleteModelItem('Sale', {{$sale->id}}, 'sale-tr-{{$sale->id}}');"
                                                                class="btn btn-sm btn-danger js-tooltip-enabled"
                                                                data-toggle="tooltip" title="Delete"
                                                                data-original-title="Delete">
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    @endif

                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td colspan="3"></td>
                                    <td class="text-right">{{ number_format($total_amount, 2) }}</td>
                                    <td class="text-right">{{ number_format($total_net, 2) }}</td>
                                    <td class="text-right">{{ number_format($total_tax, 2) }}</td>
                                    <td class="text-right">{{ number_format($total_turn_over, 2) }}</td>
                                    <td colspan="4"></td>
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



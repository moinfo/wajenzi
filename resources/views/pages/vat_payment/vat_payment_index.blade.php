@extends('layouts.backend')

@section('content')
    <style>
        .badge {
            display: inline-block;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 30px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Primary Badge - for Approved/Paid */
        .badge-primary {
            background-color: #4361ee !important;
            color: #ffffff !important;
        }

        /* Success Badge - for Completed */
        .badge-success {
            background-color: #2dce89 !important;
            color: #ffffff !important;
        }

        /* Warning Badge - for Pending */
        .badge-warning {
            background-color: #fb6340 !important;
            color: #ffffff !important;
        }

        /* Danger Badge - for Rejected */
        .badge-danger {
            background-color: #f5365c !important;
            color: #ffffff !important;
        }

        /* Secondary Badge - for Other statuses */
        .badge-secondary {
            background-color: #6c757d !important;
            color: #ffffff !important;
        }

        /* Approval Timeline Badges */
        .center-block.badge {
            display: inline-block;
            margin: 2px 0;
            min-width: 100px !important;
            padding: 6px 12px;
        }

        .center-block.badge .fa {
            margin-right: 4px;
        }

        /* Add hover effect */
        .badge:hover {
            opacity: 0.9;
        }

        /* Make sure icons inside badges are properly aligned */
        .badge i,
        .badge .fa {
            font-size: 10px;
            vertical-align: middle;
        }

        /* Status Column specific styling */
        td .badge {
            min-width: 85px;
            padding: 6px 12px;
        }
        /* Approval Badges Container */
        .approvals-cell {
            padding: 8px !important;
            white-space: nowrap;
            min-width: 200px;
        }

        .approval-badges {
            display: flex;
            gap: 4px;
            flex-wrap: nowrap;
            align-items: center;
        }

        /* Individual Badge Styling */
        .approval-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            line-height: 1;
            white-space: nowrap;
            margin: 0 2px;
        }

        /* Approved Badge Style */
        .approval-badge.approved {
            background-color: #4361ee;
            color: white;
        }

        /* Pending Badge Style */
        .approval-badge.pending {
            background-color: #f59e0b;
            color: white;
        }

        /* Icon Styling */
        .approval-badge i {
            margin-right: 4px;
            font-size: 10px;
        }

        /* Table Adjustments */
        .table td {
            vertical-align: middle !important;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .approval-badges {
                flex-wrap: wrap;
            }

            .approval-badge {
                margin-bottom: 4px;
            }
        }
    </style>
    <div class="main-container">
        <div class="content">
            <div class="content-heading">VAT Payment
                <div class="float-right">
                    <button type="button"
                            onclick="loadFormModal('vat_payment_form', {className: 'VatPayment'}, 'Create New VatPayment', 'modal-md');"
                            class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i
                            class="si si-plus">&nbsp;</i>New VatPayment
                    </button>
                </div>
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-gd-lake">
                        <h3 class="block-title">All Payments</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form name="vat_payment_search" action="" id="filter-form" method="post"
                                          autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"
                                                              id="basic-addon1">Start Date</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date"
                                                           class="form-control datepicker-index-form datepicker"
                                                           aria-describedby="basic-addon1" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon2">End Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date"
                                                           class="form-control datepicker-index-form datepicker"
                                                           aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-2">
                                                <div>
                                                    <button type="submit" name="submit" class="btn btn-sm btn-primary">
                                                        Show
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="js-dataTable-full"
                                   class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Date</th>
                                    <th>Bank Name</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Attachment</th>
                                    <th scope="col">Approvals</th>
                                    <th scope="col">Status</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php

                                use Illuminate\Support\Facades\DB;

                                $vat_payment = new \App\Models\VatPayment();
                                $start_date = $_POST['start_date'] ?? date('Y-m-01');
                                $end_date = $_POST['end_date'] ?? date('Y-m-t');

//                                $vat_payments = $vat_payment->getAll($start_date,$end_date);
                                $sum = 0;
                                ?>
                                @foreach($vat_payments as $vat_payment)
                                        <?php
                                        $sum += $vat_payment->amount;
                                        ?>
                                    @php
                                        $approval_document_types_id = 4;
                                        $approvals = \App\Models\ApprovalLevel::getUsersApprovals($approval_document_types_id);
                                    @endphp

                                    <tr id="vat_payment-tr-{{$vat_payment->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td>{{ $vat_payment->date }}</td>
                                        <td>{{ $vat_payment->bank->name }}</td>
                                        <td class="font-w600">{{ $vat_payment->description }}</td>
                                        <td class="text-right">{{ number_format($vat_payment->amount, 2) }}</td>
                                        <td class="text-center">
                                            @if($vat_payment->file != null)
                                                <a href="{{ url("$vat_payment->file") }}">Attachment</a>
                                            @else
                                                No File
                                            @endif
                                        </td>
                                        <td class="approvals-cell">
                                            <div class="approval-badges">
                                                @foreach($approvals as $approval)
                                                    @php
                                                        $approval_level_id = $approval->id;
                                                        $document_id = $vat_payment->id;
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
                                            @if($vat_payment->status == 'PENDING')
                                                <div class="badge badge-warning">{{ $vat_payment->status}}</div>
                                            @elseif($vat_payment->status == 'APPROVED')
                                                <div class="badge badge-primary">{{ $vat_payment->status}}</div>
                                            @elseif($vat_payment->status == 'REJECTED')
                                                <div class="badge badge-danger">{{ $vat_payment->status}}</div>
                                            @elseif($vat_payment->status == 'PAID')
                                                <div class="badge badge-primary">{{ $vat_payment->status}}</div>
                                            @elseif($vat_payment->status == 'COMPLETED')
                                                <div class="badge badge-success">{{ $vat_payment->status}}</div>
                                            @else
                                                <div class="badge badge-secondary">{{ $vat_payment->status}}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success js-tooltip-enabled"
                                                   href="{{route('individual_vat_payment',['id' => $vat_payment->id,'document_type_id'=>4])}}"><i
                                                        class="fa fa-eye"></i></a>
                                                <button type="button"
                                                        onclick="loadFormModal('vat_payment_form', {className: 'VatPayment', id: {{$vat_payment->id}}}, 'Edit VatPayment', 'modal-md');"
                                                        class="btn btn-sm btn-primary js-tooltip-enabled"
                                                        data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                                <button type="button"
                                                        onclick="deleteModelItem('VatPayment', {{$vat_payment->id}}, 'vat_payment-tr-{{$vat_payment->id}}');"
                                                        class="btn btn-sm btn-danger js-tooltip-enabled"
                                                        data-toggle="tooltip" title="Delete"
                                                        data-original-title="Delete">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td class="text-right text-dark" colspan="5"><b>{{number_format($sum,2)}}</b></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
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




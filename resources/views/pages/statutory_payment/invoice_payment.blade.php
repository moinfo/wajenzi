@extends('layouts.backend')

@section('content')
    <?php
    use App\Models\Approval;use Illuminate\Http\Request;
    $notifiable_id = Auth::user()->id;
    $route_id =request()->route('id');
    $invoice_id =request()->route('invoice_id');
    $route_document_type_id =request()->route('document_type_id');
    $base_route = 'invoice/'.$route_id.'/'.$route_document_type_id.'/'.$invoice_id;
    foreach( Auth::user()->unreadNotifications as $notification){
        if($notification->data['link'] == $base_route){
            $notification_id= \App\Models\Notification::Where('notifiable_id',$notifiable_id)->where('data->link', $base_route)->get()->first()->id;
            $notification = auth()->user()->notifications()->find($notification_id);
            if($notification) {
                $notification->markAsRead();
            }
        }
    }
    $invoice_payment_id = $invoice_payment->id;

    ?>
    @if($invoice_payment == null)
        @php
            header("Location: " . URL::to('/404'), true, 302);
            exit();
        @endphp
    @endif
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Invoice Payment
                <div class="float-right">
                </div>
            </div>
            <div class="block block-themed">
                <div class="block-content">
                    <div class="row no-print m-t-10">
                        <div class="class col-md-12">
                            <div class="class card-box">
                                <div class="row" style="border-bottom: 3px solid gray">
                                    <div class="col-md-3 text-right">
                                        <img class="" src="{{ asset('media/logo/wajenzilogo.png') }}" alt="" height="100">
                                    </div>
                                    <div class="col-md-6 text-center">
                                           <span class="text-center font-size-h3">{{settings('ORGANIZATION_NAME')}}</span><br/>
            <span class="text-center font-size-h5">{{settings('COMPANY_ADDRESS_LINE_1')}}</span><br/>
            <span class="text-center font-size-h5">{{settings('COMPANY_ADDRESS_LINE_2')}}</span><br/>
            <span class="text-center font-size-h5">{{settings('COMPANY_PHONE_NUMBER')}}</span><br/>
            <span class="text-center font-size-h5">{{settings('TAX_IDENTIFICATION_NUMBER')}}</span><br/>
                                    </div>
                                    <div class="col-md-3 text-right">
                                        <a href="{{url("invoice/$invoice_id")}}" type="button"
                                           class="btn btn-sm btn-danger"><i class="fa fa arrow-left"></i>Back</a>
                                    </div>
                                </div>
                            </div>
                            <br/>
                        </div>
                    </div>
                </div>
            </div>
            <div>
            <div>
                <div class="block">
                    <div class="block-header">
                        <h3 class="block-title">{{$invoice_payment->invoice->product->name ?? null}}</h3>
                    </div>
                    <div class="block-content">

                            <table class="table table-bordered table-striped table-vcenter">
                                <tbody>
                                <tr>
                                    <th width="30%">Category</th>
                                    <td>{{$invoice_payment->invoice->product->subCategory->category->name ?? null}}</td>
                                </tr>
                                <tr>
                                    <th width="30%">Sub Category</th>
                                    <td>{{$invoice_payment->invoice->product->subCategory->name ?? null}}</td>
                                </tr>
                                <tr>
                                    <th width="30%">Description</th>
                                    <td>{{$invoice_payment->description}}</td>
                                </tr>
                                <tr>
                                    <th width="30%">Amount</th>
                                    <td>{{number_format($invoice_payment->amount,2)}}</td>
                                </tr>
                                <tr>
                                    <th width="30%">Date</th>
                                    <td>{{$invoice_payment->date}}</td>
                                </tr>
                                <tr>
                                    <th>Due Date</th>
                                    <td>{{$invoice_payment->invoice->due_date}}</td>
                                </tr>
                                <tr>
                                    <th width="30%">Uploaded File</th>
                                    <td width="70%" class="bold">
                                        @if($invoice_payment->file != null)
                                        <a href="{{ url($invoice_payment->file) }}" target="_blank">View</a>
                                            @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th width="30%">status</th>
                                    @if($invoice_payment->status == 'PENDING')
                                        <td width="70%" class="bold text-capitalize"
                                            style="font-size: 16px!important">
                                            <div
                                                class="badge badge-warning badge-pill">{{ $invoice_payment->status}}</div>
                                        </td>
                                    @elseif($invoice_payment->status == 'APPROVED')
                                        <td width="70%" class="bold text-capitalize"
                                            style="font-size: 16px!important">
                                            <div
                                                class="badge badge-primary badge-pill">{{ $invoice_payment->status}}</div>
                                        </td>
                                    @elseif($invoice_payment->status == 'REJECTED')
                                        <td width="70%" class="bold text-capitalize"
                                            style="font-size: 16px!important">
                                            <div
                                                class="badge badge-danger badge-pill">{{ $invoice_payment->status}}</div>
                                        </td>
                                    @elseif($invoice_payment->status == 'PAID')
                                        <td width="70%" class="bold text-capitalize"
                                            style="font-size: 16px!important">
                                            <div
                                                class="badge badge-primary badge-pill">{{ $invoice_payment->status}}</div>
                                        </td>
                                    @elseif($invoice_payment->status == 'COMPLETED')
                                        <td width="70%" class="bold text-capitalize"
                                            style="font-size: 16px!important">
                                            <div
                                                class="badge badge-success badge-pill">{{ $invoice_payment->status}}</div>
                                        </td>
                                    @else
                                        <td width="70%" class="bold text-capitalize"
                                            style="font-size: 16px!important">
                                            <div
                                                class="badge badge-secondary badge-pill">{{ $invoice_payment->status}}</div>
                                        </td>
                                    @endif
                                </tr>
                                </tbody>
                            </table>


                    </div>
                </div>
            </div>
                <div>
                    <div class="block">
                        <div class="block-header">
                            <h3 class="block-title text-center">APPROVALS</h3>
                        </div>
                        <div class="block-content">
                            <table class="table table-bordered table-striped table-vcenter" id="payroll">
                                <thead>
                                @php

                                    $approval_document_types_id = 1;
                                    $approvals = \App\Models\ApprovalLevel::getUsersApprovals($approval_document_types_id);
                                @endphp
                                <tr>
                                    <td class="text-center">Prepared By</td>
                                    @foreach($approvals as $approval)
                                        @php
                                            $group_name = \App\Models\ApprovalLevel::getUserGroupName($approval->id);
                                        @endphp
                                        <td>{{$group_name ?? null}} Approval</td>
                                    @endforeach
                                </tr>
                                <tr>
                                    <td class="text-center">{{$invoice_payment->user->name ?? null}} <br/>
                                        <img src="{{ asset($invoice_payment->user->file ?? null) }}" alt="" width="120">
                                    </td>
                                    @foreach($approvals as $approval)
                                        @php
                                            $approved = \App\Models\Approval::getApprovedDocument($approval->id,$approval_document_types_id,$invoice_payment_id);
                                            $approver = \App\Models\User::getUserName($approved['user_id']);
                                            $signature = \App\Models\User::getUserSignature($approved['user_id']);
                                        @endphp
                                        <td class="text-center">{{$approver}} <br/>
                                            @if($signature)
                                                <img src="{{ asset($signature) }}" alt="" width="120">
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                                <tr>
                                    <td class="text-center">{{$invoice_payment->created_at}}</td>
                                    @foreach($approvals as $approval)
                                        @php
                                            $approved = \App\Models\Approval::getApprovedDocument($approval->id,$approval_document_types_id,$invoice_payment_id);
                                            $comment = $approved['comments'];
                                        @endphp
                                        <td class="text-center">
                                            @if($approved['created_at'])
                                                <span class='pull-right link no-print text-primary'
                                                      onclick='showComments("{{$comment}}")'><i
                                                        class='fa fa-comment'>&nbsp;</i> Comments </span>
                                                {{$approved['created_at'] ?? ''}}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>

                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
                <div>
                    <form method="post" action="{{route('hr_settings_approvals')}}" enctype="multipart/form-data">
                        @csrf
                        <div class="block">
                            <div class="block-content">
                                <div class="row">
                                    <?php
                                    $get_user_group_id = \App\Models\AssignUserGroup::getAssignUserGroup(Auth::user()->id);
                                    foreach ($get_user_group_id as $index => $item) {
                                        $arr[] = $item->user_group_id;
                                    }
                                    ?>
                                    @if($nextApproval)
                                        @if($rejected)
                                            <div class="col-md-9">
                                                <span class='pull-right'>This Payroll was rejected <i class='text-light'>Comment: {{$rejected->comments}}</i></span>
                                            </div>
                                            <div class="col-md-3">
                                            </div>
                                        @else
                                            @if(!in_array($nextApproval->user_group_id,$arr))
                                                <div class="col-md-12">
                                                <span class='pull-left'><i
                                                        class='fa fa-clock'>&nbsp;&nbsp;&nbsp;&nbsp;</i> Waiting {{$nextApproval->user_group_name}} for Approval</span>
                                                </div>
                                            @else
                                                <div class="col-md-12">
                                                    <input type="hidden" name="status" id="status" value="APPROVED">
                                                    <input type="hidden" name="approval_document_types_id" id="approval_document_types_id" value="{{$nextApproval->document_id}}">
                                                    <input type="hidden" name="link" id="link" value="invoice_approve/{{$document_id}}/1/{{$invoice_id}}">
                                                    <input type="hidden" name="user_id" id="user_id" value="{{Auth::user()->id }}">
                                                    <input type="hidden" name="approval_level_id" id="approval_level_id" value="{{$nextApproval->order_id ?? null}}">
                                                    <input type="hidden" name="user_group_id" id="user_group_id" value="{{$nextApproval->user_group_id ?? null}}">
                                                    <input type="hidden" name="document_id" id="document_id" value="{{$document_id}}">
                                                    <input type="hidden" name="document_type_id" id="document_type_id" value="1">
                                                    <input type="hidden" name="approval_date" id="approval_date" value="<?=date('Y-m-d H:i:s')?>">
                                                    <input type="hidden" name="route" id="route" value="invoice/{{$invoice_id}}">
                                                    <input type="hidden" name="due_date" value="{{$invoice_payment->invoice->due_date}}">
                                                    <input type="hidden" name="route" id="route" value="invoice/{{$invoice_id}}">
                                                    <input type="hidden" name="invoice_id" id="invoice_id" value="{{$invoice_id}}">
                                                    <input type="hidden" name="approval_date" id="approval_date" value="<?=date('Y-m-d H:i:s')?>">
                                                    <br/>
                                                    <div class="form-group row">
                                                        <label for="example-text-input" class="col-md-2 col-form-label">Comments</label>
                                                        <div class="col-md-10">
                                                        <textarea class="form-control" type="text" id="comments"
                                                                  name="comments" rows="3" required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="btn-group pull-right">
                                                            <button type="submit" class="btn btn-alt-primary btn-sm"
                                                                    name="approveItem" value="StatutoryInvoicePayment">Approve now
                                                            </button>
                                                            <button type="submit" class="btn btn-alt-danger btn-sm"
                                                                    name="rejectItem" value="StatutoryInvoicePayment">Reject
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                    @elseif($approvalCompleted)
                                        <div class="col-md-10">

                                        </div>
                                        <div class="col-md-2">
                                            <span class='text-primary'><i class='fa fa-check '>&nbsp;&nbsp;&nbsp;</i> Payrolls Approved</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
        </div>
    </div>

@endsection


<script>
    function showComments(comment) {
        swal.fire({
            title: "Comments",
            text: comment,
            // type: "input"
        });
    }
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>




@extends('layouts.backend')

@section('content')
    <?php
    use App\Models\Approval;use Illuminate\Http\Request;
    $notifiable_id = Auth::user()->id;
    $route_id =request()->route('id');
    $route_document_type_id =request()->route('document_type_id');
    $base_route = 'transaction_movement/'.$route_id.'/'.$route_document_type_id;
    foreach( Auth::user()->unreadNotifications as $notification){
        if($notification->data['link'] == $base_route){
            $notification_id= \App\Models\Notification::Where('notifiable_id',$notifiable_id)->where('data->link', $base_route)->get()->first()->id;
            $notification = auth()->user()->notifications()->find($notification_id);
            if($notification) {
                $notification->markAsRead();
            }
        }
    }
    ?>
    @if($transaction_movement == null)
        @php
            header("Location: " . URL::to('/404'), true, 302);
            exit();
        @endphp
    @endif
    <div class="main-container">
        <div class="content">
            <div class="content-heading">TransactionMovement
                <div class="float-right">
                </div>
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-gd-lake">
                        <h3 class="block-title">{{$transaction_movement->supplier->name ?? null }}</h3>
                    </div>
                    <div class="block-content">
                        <form method="post" action="{{route('hr_settings_approvals')}}" enctype="multipart/form-data">
                            @csrf
                            <table class="table table-bordered table-striped table-vcenter">
                                <tbody>
                                <tr>
                                    <th width="30%">Description</th>
                                    <td>{{$transaction_movement->description ?? null}}</td>
                                </tr>
                                <tr>
                                    <th>Total Amount</th>
                                    <td>{{ number_format($transaction_movement->amount, 2)}}</td>
                                </tr>
                                <tr>
                                    <th>Date</th>
                                    <td>{{$transaction_movement->date}}</td>
                                </tr>
                                <tr>
                                    <th width="30%">Uploaded File</th>
                                    <td width="70%" class="bold">
                                        @if($transaction_movement->file != null)
                                        <a href="{{ url($transaction_movement->file) }}" target="_blank">View</a>
                                            @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th width="30%">status</th>
                                    @if($transaction_movement->status == 'PENDING')
                                        <td width="70%" class="bold text-capitalize"
                                            style="font-size: 16px!important">
                                            <div
                                                class="badge badge-warning badge-pill">{{ $transaction_movement->status}}</div>
                                        </td>
                                    @elseif($transaction_movement->status == 'APPROVED')
                                        <td width="70%" class="bold text-capitalize"
                                            style="font-size: 16px!important">
                                            <div
                                                class="badge badge-primary badge-pill">{{ $transaction_movement->status}}</div>
                                        </td>
                                    @elseif($transaction_movement->status == 'REJECTED')
                                        <td width="70%" class="bold text-capitalize"
                                            style="font-size: 16px!important">
                                            <div
                                                class="badge badge-danger badge-pill">{{ $transaction_movement->status}}</div>
                                        </td>
                                    @elseif($transaction_movement->status == 'PAID')
                                        <td width="70%" class="bold text-capitalize"
                                            style="font-size: 16px!important">
                                            <div
                                                class="badge badge-primary badge-pill">{{ $transaction_movement->status}}</div>
                                        </td>
                                    @elseif($transaction_movement->status == 'COMPLETED')
                                        <td width="70%" class="bold text-capitalize"
                                            style="font-size: 16px!important">
                                            <div
                                                class="badge badge-success badge-pill">{{ $transaction_movement->status}}</div>
                                        </td>
                                    @else
                                        <td width="70%" class="bold text-capitalize"
                                            style="font-size: 16px!important">
                                            <div
                                                class="badge badge-secondary badge-pill">{{ $transaction_movement->status}}</div>
                                        </td>
                                    @endif
                                </tr>
                                </tbody>
                            </table>
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
                                            <span class='pull-right'>This TransactionMovements was rejected <i class='text-light'>Comment: {{$rejected->comments}}</i></span>
                                        </div>
                                        <div class="col-md-3">
                                        </div>
                                    @else
                                        @if(!in_array($nextApproval->user_group_id,$arr))
                                            <div class="col-md-12">
                                                <span class='pull-left'><i class='fa fa-clock'>&nbsp;&nbsp;&nbsp;&nbsp;</i> Waiting {{$nextApproval->user_group_name}} for Approval</span>
                                            </div>
                                        @else
                                            <div class="col-md-12">
                                                <input type="hidden" name="status" id="status" value="APPROVED">
                                                <input type="hidden" name="approval_document_types_id" id="approval_document_types_id" value="{{$nextApproval->document_id}}">
                                                <input type="hidden" name="link" id="link" value="transaction_movement/{{$document_id}}/9">
                                                <input type="hidden" name="user_id" id="user_id" value="{{Auth::user()->id }}">
                                                <input type="hidden" name="approval_level_id" id="approval_level_id" value="{{$nextApproval->order_id ?? null}}">
                                                <input type="hidden" name="user_group_id" id="user_group_id" value="{{$nextApproval->user_group_id ?? null}}">
                                                <input type="hidden" name="document_id" id="document_id" value="{{$document_id}}">
                                                <input type="hidden" name="document_type_id" id="document_type_id" value="9">
                                                <input type="hidden" name="approval_date" id="approval_date" value="<?=date('Y-m-d H:i:s')?>">
                                                <br/>
                                                <div class="form-group row">
                                                    <label for="example-text-input" class="col-md-2 col-form-label">Comments</label>
                                                    <div class="col-md-10">
                                                        <textarea class="form-control" type="text" id="comments" name="comments" rows="3" required></textarea>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="btn-group pull-right">
                                                        <button type="submit" class="btn btn-alt-primary btn-sm" name="approveItem" value="TransactionMovement">Approve now</button>
                                                        <button type="submit" class="btn btn-alt-danger btn-sm" name="rejectItem" value="TransactionMovement">Reject</button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                @elseif($approvalCompleted)
                                    <div class="col-md-9">
                                        <span class='text-primary'><i class='fa fa-check '>&nbsp;&nbsp;&nbsp;</i> TransactionMovements Approved</span>
                                    </div>
                                    <div class="col-md-3">

                                    </div>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection


<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>




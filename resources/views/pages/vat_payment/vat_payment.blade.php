@extends('layouts.backend')

@section('content')
    <?php
    use App\Models\Approval;use Illuminate\Http\Request;
    $notifiable_id = Auth::user()->id;
    $route_id =request()->route('id');
    $route_document_type_id =request()->route('document_type_id');
    $base_route = 'vat_payment/'.$route_id.'/'.$route_document_type_id;
    foreach( Auth::user()->unreadNotifications as $notification){
        if($notification->data['link'] == $base_route){
            $notification_id= \App\Models\Notification::Where('notifiable_id',$notifiable_id)->where('data->link', $base_route)->get()->first()->id;
            $notification = auth()->user()->notifications()->find($notification_id);
            if($notification) {
                $notification->markAsRead();
            }
        }
    }
    $vat_payment_id = $vat_payment->id;

    ?>
    @if($vat_payment == null)
        @php
            header("Location: " . URL::to('/404'), true, 302);
            exit();
        @endphp
    @endif
    <!-- Main Container -->
    <div class="main-container">
        <div class="content">
            <!-- Header Section -->
            <div class="page-header">
                <div class="header-content">
                    <h1 class="page-title">Individual VAT Payment</h1>
                    <div class="bank-name">{{$vat_payment->bank_name}}</div>
                </div>
            </div>

            <!-- Payment Details Card -->
            <div class="details-card">
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Description</label>
                            <div class="info-value">{{$vat_payment->description}}</div>
                        </div>
                        <div class="info-item">
                            <label>Total Amount</label>
                            <div class="info-value amount">TZS {{ number_format($vat_payment->amount, 2)}}</div>
                        </div>
                        <div class="info-item">
                            <label>Date</label>
                            <div class="info-value">{{$vat_payment->date}}</div>
                        </div>
                        <div class="info-item">
                            <label>Uploaded File</label>
                            <div class="info-value">
                                @if($vat_payment->file != null)
                                    <a href="{{ url($vat_payment->file) }}" target="_blank" class="file-link">
                                        <i class="fa fa-file-pdf"></i> View Document
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="info-item">
                            <label>Status</label>
                            <div class="info-value">
                                @if($vat_payment->status == 'PENDING')
                                    <span class="status-badge pending">{{ $vat_payment->status}}</span>
                                @elseif($vat_payment->status == 'APPROVED')
                                    <span class="status-badge approved">{{ $vat_payment->status}}</span>
                                @elseif($vat_payment->status == 'REJECTED')
                                    <span class="status-badge rejected">{{ $vat_payment->status}}</span>
                                @elseif($vat_payment->status == 'PAID')
                                    <span class="status-badge paid">{{ $vat_payment->status}}</span>
                                @elseif($vat_payment->status == 'COMPLETED')
                                    <span class="status-badge completed">{{ $vat_payment->status}}</span>
                                @else
                                    <span class="status-badge default">{{ $vat_payment->status}}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Approvals Section -->
            <div class="approvals-section">
                <h2 class="section-title">Approval Flow</h2>

                <div class="approval-timeline">
                    <!-- Prepared By -->
                    <div class="timeline-item active">
                        <div class="timeline-content">
                            <div class="approver-info">
                                <div class="approver-role">Prepared By</div>
                                <div class="approver-name">{{$vat_payment->user->name ?? null}}</div>
                                @if($vat_payment->user->file)
                                    <div class="signature">
                                        <img src="{{ asset($vat_payment->user->file) }}" alt="signature">
                                    </div>
                                @endif
                                <div class="approval-date">{{$vat_payment->created_at}}</div>
                            </div>
                        </div>
                    </div>

                    @php
                        $approval_document_types_id = 4;
                        $approvals = \App\Models\ApprovalLevel::getUsersApprovals($approval_document_types_id);
                    @endphp
                    <!-- Approval Steps -->
                    @foreach($approvals as $index => $approval)
                        @php
                            $group_name = \App\Models\ApprovalLevel::getUserGroupName($approval->id);
                            $approved = \App\Models\Approval::getApprovedDocument($approval->id,$approval_document_types_id,$vat_payment_id);
                            $approver = \App\Models\User::getUserName($approved['user_id']);
                            $signature = \App\Models\User::getUserSignature($approved['user_id']);
                        @endphp
                        <div class="timeline-item {{$approved['created_at'] ? 'active' : ''}}">
                            <div class="timeline-content">
                                <div class="approver-info">
                                    <div class="approver-role">{{$group_name ?? null}} Approval</div>
                                    <div class="approver-name">{{$approver}}</div>
                                    @if($signature)
                                        <div class="signature">
                                            <img src="{{ asset($signature) }}" alt="signature">
                                        </div>
                                    @endif
                                    @if($approved['created_at'])
                                        <div class="approval-date">
                                            {{$approved['created_at']}}
                                            @if($approved['comments'])
                                                <span class="comments-link" onclick='showComments("{{$approved['comments']}}")'>
                                                <i class="fa fa-comment"></i> View Comments
                                            </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <?php
                $get_user_group_id = \App\Models\AssignUserGroup::getAssignUserGroup(Auth::user()->id);
                foreach ($get_user_group_id as $index => $item) {
                    $arr[] = $item->user_group_id;
                }
                ?>
                <!-- Approval Actions -->
                @if($nextApproval && !$rejected && in_array($nextApproval->user_group_id,$arr))
                    <div class="approval-actions">
                        <form method="post" action="{{route('hr_settings_approvals')}}" class="approval-form">

                            @csrf
                            <input type="hidden" name="status" value="APPROVED">
                            <input type="hidden" name="approval_document_types_id" value="{{$nextApproval->document_id}}">
                            <input type="hidden" name="link" value="vat_payment/{{$document_id}}/4">
                            <input type="hidden" name="user_id" value="{{Auth::user()->id}}">
                            <input type="hidden" name="approval_level_id" value="{{$nextApproval->order_id ?? null}}">
                            <input type="hidden" name="user_group_id" value="{{$nextApproval->user_group_id ?? null}}">
                            <input type="hidden" name="document_id" value="{{$document_id}}">
                            <input type="hidden" name="document_type_id" value="20">
                            <input type="hidden" name="approval_date" value="<?=date('Y-m-d H:i:s')?>">
                            <input type="hidden" name="route" value="vat_payment">

                            <div class="comments-field">
                                <label>Comments</label>
                                <textarea name="comments" required placeholder="Enter your comments here..."></textarea>
                            </div>

                            <div class="action-buttons">
                                <button type="submit" name="approveItem" value="VatPayment" class="btn-approve">
                                    <i class="fa fa-check"></i> Approve
                                </button>
                                <button type="submit" name="rejectItem" value="VatPayment" class="btn-reject">
                                    <i class="fa fa-times"></i> Reject
                                </button>
                            </div>
                        </form>
                    </div>
                @elseif($rejected)
                    <div class="rejection-notice">
                        <i class="fa fa-exclamation-circle"></i>
                        <span>This payment was rejected</span>
                        <p class="rejection-comment">{{$rejected->comments}}</p>
                    </div>
                @elseif($approvalCompleted)
                    <div class="approval-complete">
                        <i class="fa fa-check-circle"></i>
                        <span>VAT Payment Approved</span>
                    </div>
                @endif
            </div>
        </div>
    </div>


@endsection




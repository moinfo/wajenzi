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
    $sale_id = $sales->id;
//dd($sales);
    ?>
    @if($sales == null)
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
                    <h1 class="page-title">Individual Sales</h1>
                    <div class="bank-name">{{$sales->efd->name}}</div>
                </div>
            </div>

            <!-- Payment Details Card -->
            <div class="details-card">
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Turnover</label>
                            <div class="info-value">{{number_format($sales->amount)}}</div>
                        </div>
                        <div class="info-item">
                            <label>NET (A+B+C)</label>
                            <div class="info-value amount"> {{number_format($sales->net)}}</div>
                        </div>
                        <div class="info-item">
                            <label>Tax</label>
                            <div class="info-value amount">{{number_format($sales->tax)}}</div>
                        </div>
                        <div class="info-item">
                            <label>Turnover (EX + SR)</label>
                            <div class="info-value amount">{{number_format($sales->turn_over)}}</div>
                        </div>
                        <div class="info-item">
                            <label>Date</label>
                            <div class="info-value">{{$sales->date}}</div>
                        </div>
                        <div class="info-item">
                            <label>Uploaded File</label>
                            <div class="info-value">
                                @if($sales->file != null)
                                    <a href="{{ url($sales->file) }}" target="_blank" class="file-link">
                                        <i class="fa fa-file-pdf"></i> View Document
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="info-item">
                            <label>Status</label>
                            <div class="info-value">
                                @if($sales->status == 'PENDING')
                                    <span class="status-badge pending">{{ $sales->status}}</span>
                                @elseif($sales->status == 'APPROVED')
                                    <span class="status-badge approved">{{ $sales->status}}</span>
                                @elseif($sales->status == 'REJECTED')
                                    <span class="status-badge rejected">{{ $sales->status}}</span>
                                @elseif($sales->status == 'PAID')
                                    <span class="status-badge paid">{{ $sales->status}}</span>
                                @elseif($sales->status == 'COMPLETED')
                                    <span class="status-badge completed">{{ $sales->status}}</span>
                                @else
                                    <span class="status-badge default">{{ $sales->status}}</span>
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
                                <div class="approver-name">{{$sales->user->name ?? null}}</div>
                                @if($sales->user->file)
                                    <div class="signature">
                                        <img src="{{ asset($sales->user->file) }}" alt="signature">
                                    </div>
                                @endif
                                <div class="approval-date">{{$sales->created_at}}</div>
                            </div>
                        </div>
                    </div>

                    @php
                        $approval_document_types_id = 2;
                        $approvals = \App\Models\ApprovalLevel::getUsersApprovals($approval_document_types_id);
                    @endphp
                        <!-- Approval Steps -->
                    @foreach($approvals as $index => $approval)
                        @php
                            $group_name = \App\Models\ApprovalLevel::getUserGroupName($approval->id);
                            $approved = \App\Models\Approval::getApprovedDocument($approval->id,$approval_document_types_id,$sale_id);
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
                            <input type="hidden" name="link" value="sales/{{$document_id}}/2">
                            <input type="hidden" name="user_id" value="{{Auth::user()->id}}">
                            <input type="hidden" name="approval_level_id" value="{{$nextApproval->order_id ?? null}}">
                            <input type="hidden" name="user_group_id" value="{{$nextApproval->user_group_id ?? null}}">
                            <input type="hidden" name="document_id" value="{{$document_id}}">
                            <input type="hidden" name="document_type_id" value="2">
                            <input type="hidden" name="approval_date" value="<?=date('Y-m-d H:i:s')?>">
                            <input type="hidden" name="route" value="sale">

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
                        <span>Sales Approved</span>
                    </div>
                @endif
            </div>
        </div>
    </div>


@endsection




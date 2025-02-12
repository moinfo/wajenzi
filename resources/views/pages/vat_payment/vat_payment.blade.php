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

    <style>
        /* Base Styles */
        .main-container {
            padding: 2rem;
            background: #f8fafc;
        }

        /* Header Styles */
        .page-header {
            margin-bottom: 2rem;
        }

        .header-content {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .page-title {
            font-size: 1.5rem;
            color: #1a202c;
            margin: 0;
            font-weight: 600;
        }

        .bank-name {
            color: #4a5568;
            font-size: 1.1rem;
            margin-top: 0.5rem;
        }

        /* Details Card */
        .details-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .info-item label {
            font-size: 0.875rem;
            color: #718096;
            font-weight: 500;
        }

        .info-value {
            font-size: 1rem;
            color: #2d3748;
        }

        .info-value.amount {
            font-weight: 600;
            color: #2563eb;
        }

        .file-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }

        .file-link:hover {
            text-decoration: underline;
        }

        /* Status Badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-badge.pending { background: #fef3c7; color: #d97706; }
        .status-badge.approved { background: #dcfce7; color: #15803d; }
        .status-badge.rejected { background: #fee2e2; color: #dc2626; }
        .status-badge.paid { background: #e0e7ff; color: #4f46e5; }
        .status-badge.completed { background: #dcfce7; color: #15803d; }
        .status-badge.default { background: #f3f4f6; color: #6b7280; }

        /* Approval Timeline */
        .approvals-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .section-title {
            font-size: 1.25rem;
            color: #1a202c;
            margin-bottom: 2rem;
            font-weight: 600;
        }

        .approval-timeline {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            position: relative;
            padding-left: 2rem;
        }

        .timeline-item {
            position: relative;
            padding-left: 2rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -2px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e5e7eb;
        }

        .timeline-item::after {
            content: '';
            position: absolute;
            left: -6px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #e5e7eb;
        }

        .timeline-item.active::after {
            background: #2563eb;
        }

        .approver-info {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .approver-role {
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }

        .approver-name {
            color: #4a5568;
            margin-bottom: 1rem;
        }

        .signature {
            margin: 1rem 0;
        }

        .signature img {
            max-width: 120px;
            height: auto;
        }

        .approval-date {
            font-size: 0.875rem;
            color: #718096;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .comments-link {
            color: #2563eb;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .comments-link:hover {
            text-decoration: underline;
        }

        /* Approval Actions */
        .approval-actions {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }

        .approval-form {
            max-width: 600px;
            margin: 0 auto;
        }

        .comments-field {
            margin-bottom: 1.5rem;
        }

        .comments-field label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #4a5568;
        }

        .comments-field textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            min-height: 100px;
            resize: vertical;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn-approve, .btn-reject {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-approve {
            background: #2563eb;
            color: white;
        }

        .btn-approve:hover {
            background: #1d4ed8;
        }

        .btn-reject {
            background: #dc2626;
            color: white;
        }

        .btn-reject:hover {
            background: #b91c1c;
        }

        /* Notices */
        .rejection-notice, .approval-complete {
            margin-top: 2rem;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }

        .rejection-notice {
            background: #fee2e2;
            color: #dc2626;
        }

        .approval-complete {
            background: #dcfce7;
            color: #15803d;
        }

        .rejection-notice i, .approval-complete i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .rejection-notice span, .approval-complete span {
            font-weight: 500;
            margin-left: 0.5rem;
        }

        .rejection-comment {
            margin-top: 0.5rem;
            font-style: italic;
            color: #b91c1c;
        }

        /* Waiting Notice */
        .waiting-notice {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            font-size: 0.875rem;
            padding: 1rem;
            background: #f3f4f6;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .waiting-notice i {
            color: #2563eb;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .header-content {
                padding: 1rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-approve, .btn-reject {
                width: 100%;
                justify-content: center;
            }

            .approval-timeline {
                padding-left: 1rem;
            }

            .timeline-item {
                padding-left: 1rem;
            }
        }

        /* Print Styles */
        @media print {
            .main-container {
                padding: 0;
            }

            .action-buttons,
            .comments-field,
            .file-link,
            .comments-link {
                display: none;
            }

            .approvals-section,
            .details-card {
                box-shadow: none;
                border: 1px solid #e5e7eb;
            }

            .approval-timeline::before {
                display: none;
            }
        }

        /* Sweet Alert Customization */
        .swal2-popup {
            border-radius: 12px;
        }

        .swal2-title {
            color: #1a202c !important;
            font-size: 1.25rem !important;
        }

        .swal2-content {
            color: #4a5568 !important;
        }

        /* Custom Scrollbar */
        .departments-list::-webkit-scrollbar {
            width: 6px;
        }

        .departments-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .departments-list::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .departments-list::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }

        /* Animation Effects */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .details-card,
        .approvals-section {
            animation: fadeIn 0.3s ease-out;
        }

        /* Hover Effects */
        .file-link:hover {
            color: #1d4ed8;
        }

        .comments-link:hover {
            color: #1d4ed8;
        }

        .timeline-item:hover .approver-info {
            border-color: #2563eb;
            transition: border-color 0.2s ease;
        }

        /* Focus States */
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn-approve:focus,
        .btn-reject:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
    </style>

    <!-- Additional JavaScript for Smooth Scrolling -->
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Enhance Comments Display
        function showComments(comment) {
            Swal.fire({
                title: 'Comments',
                text: comment,
                confirmButtonColor: '#2563eb',
                customClass: {
                    popup: 'swal-wide',
                    title: 'swal-title',
                    content: 'swal-content'
                }
            });
        }
    </script>

@endsection




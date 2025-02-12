@if($approvalService->userCanApprove($nextApproval) && !$rejected)
    <div class="approval-actions">
        <form method="post" action="{{ route('hr_settings_approvals') }}" class="approval-form">
            @csrf
            @php
                $formData = $approvalService->getApprovalFormData($nextApproval, $document_id, $approval_document_type_id, $route);
            @endphp
            @foreach($formData as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach

            <div class="comments-field">
                <label>Comments</label>
                <textarea name="comments" required placeholder="Enter your comments here..."></textarea>
            </div>

            <div class="action-buttons">
                <button type="submit" name="approveItem" value="{{$model}}" class="btn-approve">
                    <i class="fa fa-check"></i> Approve
                </button>
                <button type="submit" name="rejectItem" value="{{$model}}" class="btn-reject">
                    <i class="fa fa-times"></i> Reject
                </button>
            </div>
        </form>
    </div>
@elseif($rejected)
    <div class="rejection-notice">
        <i class="fa fa-exclamation-circle"></i>
        <span>This payment was rejected</span>
        <p class="rejection-comment">{{ $rejected->comments }}</p>
    </div>
@elseif($approvalCompleted)
    <div class="approval-complete">
        <i class="fa fa-check-circle"></i>
        <span>{{$page_name}} Approved</span>
    </div>
@endif

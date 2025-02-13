<div class="timeline-item active">
    <div class="timeline-content">
        <div class="approver-info">
            <div class="approver-role">Prepared By</div>
            <div class="approver-name">{{ $approval_data->user->name ?? null }}</div>
            @if($approval_data->user->file)
                <div class="signature">
                    <img src="{{ asset($approval_data->user->file) }}" alt="signature">
                </div>
            @endif
            <div class="approval-date">{{ $approval_data->created_at }}</div>
        </div>
    </div>
</div>

<div class="timeline-item {{ $item['approved_at'] ? 'active' : '' }}">
    <div class="timeline-content">
        <div class="approver-info">
            <div class="approver-role">{{ $item['group_name'] ?? null }} Approval</div>
            <div class="approver-name">{{ $item['approver'] }}</div>
            @if($item['signature'])
                <div class="signature">
                    <img src="{{ asset($item['signature']) }}" alt="signature">
                </div>
            @endif
            @if($item['approved_at'])
                <div class="approval-date">
                    {{ $item['approved_at'] }}
                    @if($item['comments'])
                        <span class="comments-link" onclick='showComments("{{ $item['comments'] }}")'>
                            <i class="fa fa-comment"></i> View Comments
                        </span>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

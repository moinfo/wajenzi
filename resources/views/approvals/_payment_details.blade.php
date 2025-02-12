<div class="details-card">
    <div class="card-body">
        <div class="info-grid">
            @foreach($details as $label => $value)
                <div class="info-item">
                    <label>{{ $label }}</label>
                    <div class="info-value">{{ $value }}</div>
                </div>
            @if($label == 'Uploaded File')
                <div class="info-item">
                    <label>Uploaded File</label>
                    <div class="info-value">
                        @if($value)
                            <a href="{{ url($value) }}" target="_blank" class="file-link">
                                <i class="fa fa-file-pdf"></i> View Document
                            </a>
                        @endif
                    </div>
                </div>
                @endif
            @endforeach
            <div class="info-item">
                <label>Status</label>
                <div class="info-value">
                    {!! $approvalService->getStatusBadge($approval_data->status) !!}
                </div>
            </div>
        </div>
    </div>
</div>

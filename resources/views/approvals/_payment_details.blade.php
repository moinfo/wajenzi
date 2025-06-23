<div class="details-card">
    <style>
        .details-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .card-body {
            padding: 25px;
        }

        .card-header {
            background-color: #f8f9fa;
            padding: 15px 25px;
            border-bottom: 1px solid #e9ecef;
        }

        .card-header h3 {
            margin: 0;
            color: #0066cc;
            font-weight: 600;
            font-size: 18px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .info-item {
            margin-bottom: 5px;
        }

        .info-item label {
            display: block;
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 6px;
            font-weight: 500;
        }

        .info-value {
            font-size: 16px;
            color: #212529;
            font-weight: 500;
            padding: 8px 12px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #0066cc;
        }

        .file-link {
            display: inline-flex;
            align-items: center;
            color: #0066cc;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .file-link:hover {
            color: #004c99;
            text-decoration: underline;
        }

        .file-link i {
            margin-right: 8px;
            font-size: 18px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-created {
            background-color: #e3f2fd;
            color: #0d6efd;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #ffc107;
        }

        .status-approved {
            background-color: #d1e7dd;
            color: #198754;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #dc3545;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .card-body {
                padding: 15px;
            }
        }
    </style>

    <div class="card-header">
        <h3><i class="fas fa-clipboard-list me-2"></i> Project Details</h3>
    </div>

    <div class="card-body">
        <div class="info-grid">
            @foreach($details as $label => $value)
                @if($label != 'Uploaded File')
                    <div class="info-item">
                        <label>{{ $label }}</label>
                        <div class="info-value">{{ $value }}</div>
                    </div>
                @endif
            @endforeach

            @if(isset($details['Uploaded File']) && $details['Uploaded File'])
                <div class="info-item">
                    <label>Uploaded File</label>
                    <div class="info-value">
                        <a href="{{ url($details['Uploaded File']) }}" target="_blank" class="file-link">
                            <i class="fa fa-file-pdf"></i> View Document
                        </a>
                    </div>
                </div>
            @endif

            <div class="info-item">
                <label>Status</label>
                <div class="info-value">
                    {!! $approvalService->getStatusBadge($approval_data->status) !!}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-header">
    <style>
        /* Custom styles for the header */
        .page-header {
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }
        .header-container {
            background-color: white;
            box-shadow: 0 6px 18px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        .header-top {
            padding: 12px 20px;
            background: linear-gradient(90deg, #f8f9fa, #ffffff);
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
        }
        .btn-action {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }
        .btn-print {
            background-color: #0066cc;
            color: white;
            border: none;
        }
        .btn-print:hover {
            background-color: #0052a3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .btn-back {
            background-color: white;
            color: #212529;
            border: 1px solid #dee2e6;
        }
        .btn-back:hover {
            background-color: #f1f3f5;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .header-main {
            padding: 25px 20px;
            position: relative;
        }
        .header-main::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 40%;
            background: linear-gradient(135deg, transparent, rgba(0, 102, 204, 0.03));
            z-index: 0;
            pointer-events: none;
        }
        .company-logo {
            max-height: 80px;
            padding: 4px;
            background: white;
            border-radius: 50%;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .company-info h2 {
            color: #0066cc;
            margin-bottom: 8px;
            font-weight: 700;
            font-size: 20px;
        }
        .company-address p {
            margin-bottom: 3px;
            color: #555;
            font-size: 14px;
        }
        .project-title {
            font-weight: 800;
            color: #212529;
            font-size: 28px;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }
        .project-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -6px;
            height: 3px;
            width: 40px;
            background-color: #ff9900;
            border-radius: 2px;
        }
        .document-number-container {
            border: 2px solid #0066cc;
            background-color: #f0f7ff;
            padding: 10px 15px;
            border-radius: 6px;
            display: inline-block;
            box-shadow: 0 3px 10px rgba(0, 102, 204, 0.1);
        }
        .document-number-container p {
            margin-bottom: 0;
            font-weight: 700;
            color: #0066cc;
        }
        .meta-info {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px dashed #dee2e6;
            font-size: 14px;
            color: #666;
        }
        .meta-info p {
            margin-bottom: 3px;
        }
        .meta-info i {
            margin-right: 5px;
            color: #0066cc;
        }
        @media (max-width: 768px) {
            .project-title {
                text-align: center;
                margin: 10px auto;
                display: block;
                font-size: 24px;
            }
            .project-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            .document-number-container {
                display: block;
                text-align: center;
                margin: 0 auto;
                max-width: 200px;
            }
        }
    </style>

    <div class="header-container">
        <!-- Action Buttons -->
        <div class="header-top">
            <button class="btn btn-action btn-print" onclick="window.print()">
                <i class="fas fa-print me-2"></i> Print
            </button>
            <button class="btn btn-action btn-back" onclick="history.back()">
                <i class="fas fa-arrow-left me-2"></i> Back
            </button>
        </div>

        <div class="header-content">
            <div class="header-main">
                <div class="row align-items-center">
                    <!-- Logo Section -->
                    <div class="col-md-1 text-center text-md-start mb-3 mb-md-0">
                        <img src="{{ asset('media/logo/wajenzilogo.png') }}" alt="Company Logo" class="company-logo">
                    </div>

                    <!-- Address Section -->
                    <div class="col-md-7 company-info mb-3 mb-md-0">
                        <h2>{{settings('ORGANIZATION_NAME')}}</h2>
                        <div class="company-address">
                            <p><i class="fas fa-building me-2"></i> {{settings('COMPANY_ADDRESS_LINE_1')}}</p>
                            <p><i class="fas fa-phone-alt me-2"></i> Tel: {{settings('COMPANY_PHONE_NUMBER')}}</p>
                            <p><i class="fas fa-envelope me-2"></i> P. O. Box {{settings('COMPANY_ADDRESS_LINE_2')}}</p>
                        </div>
                    </div>

                    <!-- Title Section -->
                    <div class="col-md-4 text-md-end">
                        <h2 class="project-title">{{$page_name}}</h2>
                        @if($approval_data->document_number)
                            <div class="document-number-container">
                                <p><i class="fas fa-file-contract me-2"></i> No. {{$approval_data->document_number}}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Request Information -->
                <div class="row meta-info">
                    <div class="col-md-6">
                        <p><i class="fas fa-user"></i> Requested by : {{$approval_data_name ?? 'System Admin'}}</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p><i class="fas fa-calendar-alt"></i> Created Time : {{$approval_data->created_at}}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

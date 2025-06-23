@extends('layouts.backend')

@section('content')
    @inject('approvalService', 'App\Services\ApprovalService')

    @if($approval_data == null)
        @php
            header("Location: " . URL::to('/404'), true, 302);
            exit();
        @endphp
    @endif

    <!-- Main Container -->
    <div class="main-container">
        <div class="content">
            <!-- Header Section -->
            @include('approvals._header', ['page_name'=> $page_name,'approval_data_name'=> $approval_data_name ])

            <!-- Payment Details Card -->
            @include('approvals._payment_details', ['approval_data' => $approval_data])

            <!-- Approvals Section -->
            <div class="approvals-section">
                <style>
                    .approvals-section {
                        background-color: #fff;
                        border-radius: 10px;
                        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
                        margin-bottom: 30px;
                        overflow: hidden;
                        border: 1px solid rgba(0, 0, 0, 0.05);
                    }

                    .section-header {
                        background-color: #f8f9fa;
                        padding: 15px 25px;
                        border-bottom: 1px solid #e9ecef;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    }

                    .section-title {
                        margin: 0;
                        color: #0066cc;
                        font-weight: 600;
                        font-size: 18px;
                        display: flex;
                        align-items: center;
                    }

                    .section-title i {
                        margin-right: 10px;
                        color: #0066cc;
                    }

                    .section-body {
                        padding: 25px;
                    }

                    .approval-steps {
                        position: relative;
                        margin-bottom: 20px;
                    }

                    .approval-timeline {
                        position: absolute;
                        top: 0;
                        bottom: 0;
                        left: 20px;
                        width: 2px;
                        background-color: #dee2e6;
                        z-index: 1;
                    }

                    .approval-submit-container {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        background-color: #f8f9fa;
                        padding: 15px 25px;
                        border-radius: 8px;
                        margin-top: 20px;
                    }

                    .submit-message {
                        color: #6c757d;
                        font-size: 15px;
                    }

                    .btn-submit {
                        background-color: #4CAF50;
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        border-radius: 6px;
                        font-weight: 600;
                        transition: all 0.3s ease;
                        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                    }

                    .btn-submit:hover {
                        background-color: #3e8e41;
                        transform: translateY(-2px);
                        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
                    }

                    /* Status indicators */
                    .status-indicator {
                        display: inline-block;
                        width: 12px;
                        height: 12px;
                        border-radius: 50%;
                        margin-right: 8px;
                    }

                    .status-pending {
                        background-color: #ffc107;
                    }

                    .status-approved {
                        background-color: #4CAF50;
                    }

                    .status-rejected {
                        background-color: #dc3545;
                    }

                    .status-waiting {
                        background-color: #6c757d;
                    }
                </style>

                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-tasks"></i> Approval Flow
                    </h2>
                    <div class="flow-status">
                        <span class="badge bg-info">In Progress</span>
                    </div>
                </div>

                <div class="section-body">
                    <!-- Approval Component -->
                    <x-ringlesoft-approval-actions :model="$approval_data" />
                </div>
            </div>
        </div>
    </div>
@endsection

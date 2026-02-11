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
    <div class="container-fluid">
        <div class="content">
            <!-- Header Section -->
            @include('approvals._header', ['page_name' => $page_name, 'approval_data_name' => $approval_data_name])

            <!-- Request Details Card -->
            @include('approvals._payment_details', ['approval_data' => $approval_data, 'details' => $details])

            <!-- BOQ Item Details (if linked) -->
            @if($request->boqItem)
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">BOQ Item Details</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">BOQ Quantity</h6>
                                    <h4>{{ number_format($request->boqItem->quantity, 2) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">Already Requested</h6>
                                    <h4>{{ number_format($request->boqItem->quantity_requested ?? 0, 2) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">Received</h6>
                                    <h4>{{ number_format($request->boqItem->quantity_received ?? 0, 2) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">Remaining</h6>
                                    <h4>{{ number_format($request->boqItem->quantity_remaining ?? 0, 2) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($request->purpose)
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Purpose / Justification</h3>
                </div>
                <div class="block-content">
                    <p class="p-3 bg-light rounded">{{ $request->purpose }}</p>
                </div>
            </div>
            @endif

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
                    <x-ringlesoft-approval-actions :model="$approval_data" />
                </div>
            </div>

            <!-- Actions for Approved Requests -->
            @if(strtoupper($request->status) === 'APPROVED')
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Next Steps</h3>
                </div>
                <div class="block-content">
                    <a href="{{ route('supplier_quotations.by_request', ['id' => $request->id]) }}" class="btn btn-primary">
                        <i class="fa fa-file-invoice-dollar"></i> Manage Quotations
                    </a>
                    @if($request->quotations && $request->quotations->count() >= 3)
                        <a href="{{ route('quotation_comparison.create', ['material_request_id' => $request->id]) }}" class="btn btn-success ml-2">
                            <i class="fa fa-balance-scale"></i> Create Comparison
                        </a>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection

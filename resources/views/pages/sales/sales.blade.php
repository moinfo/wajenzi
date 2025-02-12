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
            @include('approvals._payment_details', ['sales' => $approval_data])

            <!-- Approvals Section -->
            <div class="approvals-section">
                <h2 class="section-title">Approval Flow</h2>

                <div class="approval-timeline">
                    <!-- Prepared By -->
                    @include('approvals._prepared_by', ['sales' => $approval_data])

                    <!-- Approval Steps -->
                    @foreach($approvalService->getApprovalTimeline(2, $approval_data->id) as $timelineItem)
                        @include('approvals._timeline_item', ['item' => $timelineItem])
                    @endforeach
                </div>

                <!-- Approval Actions -->
                @include('approvals._approval_actions', [
                    'nextApproval' => $nextApproval,
                    'rejected' => $rejected,
                    'approvalCompleted' => $approvalCompleted,
                    'documentId' => $document_id,
                    'approvalService' => $approvalService
                ])
            </div>
        </div>
    </div>
@endsection

@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <i class="fa fa-clipboard-list"></i> {{ $request->request_number }}
            <div class="float-right">
                <a href="{{ route('labor.requests.index') }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
                @if($request->isDraft())
                    <a href="{{ route('labor.requests.edit', $request->id) }}" class="btn btn-rounded btn-warning min-width-100 mb-10">
                        <i class="fa fa-edit"></i> Edit
                    </a>
                    <form action="{{ route('labor.requests.submit', $request->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-rounded btn-success min-width-100 mb-10"
                            onclick="return confirm('Submit this request for approval?')">
                            <i class="fa fa-paper-plane"></i> Submit
                        </button>
                    </form>
                @elseif($request->canCreateContract())
                    <a href="{{ route('labor.contracts.create', $request->id) }}" class="btn btn-rounded btn-primary min-width-100 mb-10">
                        <i class="fa fa-file-contract"></i> Create Contract
                    </a>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Request Details</h3>
                        <div class="block-options">
                            <span class="badge badge-{{ $request->status_badge_class }} badge-lg">
                                {{ ucfirst($request->status) }}
                            </span>
                        </div>
                    </div>
                    <div class="block-content">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Project:</strong> {{ $request->project?->project_name }}</p>
                                <p><strong>Construction Phase:</strong> {{ $request->constructionPhase?->name ?? 'N/A' }}</p>
                                <p><strong>Work Location:</strong> {{ $request->work_location ?? 'N/A' }}</p>
                                <p><strong>Requested By:</strong> {{ $request->requester?->name }}</p>
                                <p><strong>Date Requested:</strong> {{ $request->created_at?->format('Y-m-d H:i') }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Duration:</strong> {{ $request->estimated_duration_days ?? 'N/A' }} days</p>
                                <p><strong>Start Date:</strong> {{ $request->start_date?->format('Y-m-d') ?? 'N/A' }}</p>
                                <p><strong>End Date:</strong> {{ $request->end_date?->format('Y-m-d') ?? 'N/A' }}</p>
                                <p><strong>Materials Included:</strong> {{ $request->materials_included ? 'Yes' : 'No' }}</p>
                            </div>
                        </div>

                        <hr>
                        <h5>Work Description</h5>
                        <p class="bg-light p-3 rounded">{{ $request->work_description }}</p>

                        @if($request->payment_terms)
                            <h5>Payment Terms</h5>
                            <p class="bg-light p-3 rounded">{{ $request->payment_terms }}</p>
                        @endif

                        @if($request->artisan_assessment)
                            <h5>Artisan Assessment</h5>
                            <p class="bg-light p-3 rounded">{{ $request->artisan_assessment }}</p>
                        @endif

                        @if($request->rejection_reason)
                            <div class="alert alert-danger">
                                <h5><i class="fa fa-times-circle"></i> Rejection Reason</h5>
                                <p class="mb-0">{{ $request->rejection_reason }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                @if($request->contract)
                    <div class="block">
                        <div class="block-header block-header-default bg-info">
                            <h3 class="block-title text-white">
                                <i class="fa fa-file-contract"></i> Associated Contract
                            </h3>
                        </div>
                        <div class="block-content">
                            <p><strong>Contract #:</strong>
                                <a href="{{ route('labor.contracts.show', $request->contract->id) }}">
                                    {{ $request->contract->contract_number }}
                                </a>
                            </p>
                            <p><strong>Status:</strong>
                                <span class="badge badge-{{ $request->contract->status_badge_class }}">
                                    {{ ucfirst($request->contract->status) }}
                                </span>
                            </p>
                            <p><strong>Amount Paid:</strong>
                                {{ number_format($request->contract->amount_paid, 0) }} /
                                {{ number_format($request->contract->total_amount, 0) }}
                                ({{ number_format($request->contract->payment_progress, 1) }}%)
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-md-4">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Artisan Information</h3>
                    </div>
                    <div class="block-content">
                        @if($request->artisan)
                            <p><strong>Name:</strong> {{ $request->artisan->name }}</p>
                            <p><strong>Trade:</strong> {{ $request->artisan->trade_skill ?? 'N/A' }}</p>
                            <p><strong>Phone:</strong> {{ $request->artisan->phone ?? 'N/A' }}</p>
                            <p><strong>ID Number:</strong> {{ $request->artisan->id_number ?? 'N/A' }}</p>
                            @if($request->artisan->rating)
                                <p><strong>Rating:</strong>
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fa fa-star {{ $i <= $request->artisan->rating ? 'text-warning' : 'text-muted' }}"></i>
                                    @endfor
                                </p>
                            @endif
                        @else
                            <p class="text-muted">No artisan assigned yet</p>
                        @endif
                    </div>
                </div>

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Financial Summary</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-borderless">
                            <tr>
                                <td>Proposed Amount:</td>
                                <td class="text-right">{{ number_format($request->proposed_amount, 0) }} {{ $request->currency }}</td>
                            </tr>
                            @if($request->negotiated_amount)
                                <tr>
                                    <td>Negotiated Amount:</td>
                                    <td class="text-right">{{ number_format($request->negotiated_amount, 0) }} {{ $request->currency }}</td>
                                </tr>
                            @endif
                            @if($request->approved_amount)
                                <tr class="bg-success text-white">
                                    <td><strong>Approved Amount:</strong></td>
                                    <td class="text-right"><strong>{{ number_format($request->approved_amount, 0) }} {{ $request->currency }}</strong></td>
                                </tr>
                            @endif
                            <tr class="border-top">
                                <td><strong>Final Amount:</strong></td>
                                <td class="text-right"><strong>{{ number_format($request->final_amount, 0) }} {{ $request->currency }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if($request->isApproved())
                    <div class="block">
                        <div class="block-header block-header-default bg-success">
                            <h3 class="block-title text-white">Approval Information</h3>
                        </div>
                        <div class="block-content">
                            <p><strong>Approved By:</strong> {{ $request->approver?->name ?? 'N/A' }}</p>
                            <p><strong>Approved At:</strong> {{ $request->approved_at?->format('Y-m-d H:i') ?? 'N/A' }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Approval Flow Section (visible once submitted) --}}
        @if(!$request->isDraft())
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
                    <span class="badge badge-{{ $request->status_badge_class }}">{{ ucfirst($request->status) }}</span>
                </div>
            </div>

            <div class="section-body">
                <x-ringlesoft-approval-actions :model="$request" />
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

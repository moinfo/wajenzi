@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">Sales Daily Report - {{ $report->report_date->format('M d, Y') }}</h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item">Projects</li>
                    <li class="breadcrumb-item"><a href="{{ route('sales_daily_reports') }}">Sales Daily Reports</a></li>
                    <li class="breadcrumb-item active" aria-current="page">View Report</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    <!-- Header Information -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Report Information</h3>
            <div class="block-options">
                @if($report->canEdit())
                    <a href="{{ route('sales_daily_report.edit', $report->id) }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-edit"></i> Edit
                    </a>
                @endif
                <a href="{{ route('sales_daily_report.export', $report->id) }}" class="btn btn-sm btn-success" target="_blank">
                    <i class="fa fa-file-pdf"></i> Export PDF
                </a>
            </div>
        </div>
        <div class="block-content">
            <div class="row">
                <div class="col-md-4">
                    <h6 class="text-muted">Report Date:</h6>
                    <p><strong>{{ $report->report_date->format('F d, Y') }}</strong></p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Prepared by:</h6>
                    <p><strong>{{ $report->preparedBy->name }}</strong></p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Department:</h6>
                    <p><strong>{{ $report->department()->first()?->name ?? '-' }}</strong></p>
                </div>
                <div class="col-12 mt-2">
                    <h6 class="text-muted">Status:</h6>
                    @if($report->status == 'DRAFT')
                        <span class="badge badge-secondary badge-lg">Draft</span>
                    @elseif($report->status == 'PENDING')
                        <span class="badge badge-warning badge-lg">Pending Approval</span>
                    @elseif($report->status == 'APPROVED')
                        <span class="badge badge-success badge-lg">Approved</span>
                    @else
                        <span class="badge badge-danger badge-lg">Rejected</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Summary -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">1. DAILY SUMMARY</h3>
        </div>
        <div class="block-content">
            <p>{{ $report->daily_summary }}</p>
        </div>
    </div>

    <!-- Lead Follow-ups & Interactions -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">2. LEAD FOLLOW-UPS & INTERACTIONS</h3>
        </div>
        <div class="block-content">
            <p class="text-muted mb-3">Summary of the Follow-ups and Customer Acquisition activities...</p>

            @if($report->leadFollowups->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 50px;">S/n</th>
                                <th>Lead</th>
                                <th>Interaction Type</th>
                                <th>Details/Discussion</th>
                                <th>Outcome</th>
                                <th>Next Step</th>
                                <th>Follow-Up Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report->leadFollowups as $index => $followup)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        @if($followup->lead)
                                            <strong>{{ $followup->lead->name }}</strong>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($followup->clientSource)
                                            <span class="badge badge-info">{{ $followup->clientSource->name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $followup->details_discussion ?: '-' }}</td>
                                    <td>{{ $followup->outcome ?: '-' }}</td>
                                    <td>{{ $followup->next_step ?: '-' }}</td>
                                    <td>
                                        @if($followup->followup_date)
                                            {{ $followup->followup_date->format('M d, Y') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-muted mt-2"><small>*Table extracted from client interaction database/excel</small></p>
            @else
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> No lead follow-ups recorded for this date.
                </div>
            @endif
        </div>
    </div>

    <!-- Sales Activity -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">3. SALES ACTIVITY</h3>
        </div>
        <div class="block-content">
            <h5>3.1 Summary of Daily Sales made, Invoice generated, payment made...etc.</h5>

            @if($report->salesActivities->count() > 0)
                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 50px;">S/n</th>
                                <th>Invoice No</th>
                                <th>Invoice sum/Price</th>
                                <th>Activity</th>
                                <th>Status/Payment</th>
                                <th>Payment Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report->salesActivities as $index => $activity)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $activity->invoice_no ?: '-' }}</td>
                                    <td><strong>{{ number_format($activity->invoice_sum, 2) }}</strong></td>
                                    <td>{{ $activity->activity }}</td>
                                    <td>
                                        @if($activity->status == 'paid')
                                            <span class="badge badge-success">Paid</span>
                                        @elseif($activity->status == 'partial')
                                            <span class="badge badge-warning">Partial</span>
                                        @else
                                            <span class="badge badge-danger">Not Paid</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($activity->payment_amount)
                                            <strong>{{ number_format($activity->payment_amount, 2) }}</strong>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-active">
                                <td colspan="2"><strong>Total Sales</strong></td>
                                <td><strong>{{ number_format($report->getTotalSalesAmount(), 2) }}</strong></td>
                                <td colspan="2">
                                    <small>
                                        Paid: {{ number_format($report->getPaidSalesAmount(), 2) }} |
                                        Unpaid: {{ number_format($report->getUnpaidSalesAmount(), 2) }}
                                    </small>
                                </td>
                                <td><strong>{{ number_format($report->salesActivities->sum('payment_amount'), 2) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Customer Acquisition Cost Report -->
                @if($report->customerAcquisitionCost)
                    <div class="mt-4">
                        <h5>3.2 Daily Customer Acquisition Cost (CAC) Report</h5>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Cost Breakdown</h6>
                                        <table class="table table-sm">
                                            <tr>
                                                <td>Marketing Cost:</td>
                                                <td><strong>{{ number_format($report->customerAcquisitionCost->marketing_cost, 2) }}</strong></td>
                                            </tr>
                                            <tr>
                                                <td>Sales Cost:</td>
                                                <td><strong>{{ number_format($report->customerAcquisitionCost->sales_cost, 2) }}</strong></td>
                                            </tr>
                                            <tr>
                                                <td>Other Cost:</td>
                                                <td><strong>{{ number_format($report->customerAcquisitionCost->other_cost, 2) }}</strong></td>
                                            </tr>
                                            <tr class="table-active">
                                                <td><strong>Total Cost:</strong></td>
                                                <td><strong>{{ number_format($report->customerAcquisitionCost->total_cost, 2) }}</strong></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">CAC Calculation</h6>
                                        <div class="text-center">
                                            <h3 class="text-primary">{{ number_format($report->customerAcquisitionCost->cac_value, 2) }}</h3>
                                            <p class="text-muted">Cost per Customer</p>
                                            <small>{{ $report->customerAcquisitionCost->new_customers }} new customers acquired</small>
                                        </div>
                                        @if($report->customerAcquisitionCost->notes)
                                            <hr>
                                            <h6>Notes:</h6>
                                            <p class="small">{{ $report->customerAcquisitionCost->notes }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> No sales activities recorded for this date.
                </div>
            @endif
        </div>
    </div>

    <!-- Issues or Client Concerns -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">4. ISSUES OR CLIENT CONCERNS</h3>
        </div>
        <div class="block-content">
            @if($report->clientConcerns->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 50px;">S/n</th>
                                <th>Client</th>
                                <th>Issue/Concern</th>
                                <th>Action Taken or Required</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report->clientConcerns as $index => $concern)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        @if($concern->client)
                                            <strong>{{ $concern->client->first_name }} {{ $concern->client->last_name }}</strong>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $concern->issue_concern }}</td>
                                    <td>{{ $concern->action_taken }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-success">
                    <i class="fa fa-check-circle"></i> No client concerns reported for this date.
                </div>
            @endif
        </div>
    </div>

    <!-- Notes & Recommendations -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">5. NOTES & RECOMMENDATIONS</h3>
        </div>
        <div class="block-content">
            @if($report->notes_recommendations)
                <div class="alert alert-light">
                    <p class="mb-0">{{ $report->notes_recommendations }}</p>
                </div>
            @else
                <p class="text-muted">Use this section for any important observations, client preferences, or suggestions for team coordination.</p>
            @endif
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="block block-rounded">
        <div class="block-content">
            <div class="row">
                <div class="col-12">
                    <!-- RingleSoft Approval Component -->
                    <x-ringlesoft-approval-actions :model="$report" />

                    <a href="{{ route('sales_daily_reports') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

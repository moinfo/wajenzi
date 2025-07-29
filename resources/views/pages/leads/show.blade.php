@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">Lead Details - {{ $lead->name }}</h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item">Projects</li>
                    <li class="breadcrumb-item"><a href="{{ route('leads.index') }}">Leads</a></li>
                    <li class="breadcrumb-item active" aria-current="page">View Lead</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    <!-- Lead Information -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Lead Information</h3>
            <div class="block-options">
                <a href="{{ route('leads.edit', $lead->id) }}" class="btn btn-sm btn-primary">
                    <i class="fa fa-edit"></i> Edit
                </a>
            </div>
        </div>
        <div class="block-content">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted">Name:</h6>
                    <p><strong>{{ $lead->name }}</strong></p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">Email:</h6>
                    <p><a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a></p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">Phone:</h6>
                    <p>
                        @if($lead->phone)
                            <a href="tel:{{ $lead->phone }}">{{ $lead->phone }}</a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">Source:</h6>
                    <p>
                        @if($lead->clientSource)
                            <span class="badge badge-info">{{ $lead->clientSource->name }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">Status:</h6>
                    <p>
                        @if($lead->status == 'active')
                            <span class="badge badge-success badge-lg">Active</span>
                        @elseif($lead->status == 'converted')
                            <span class="badge badge-primary badge-lg">Converted</span>
                        @else
                            <span class="badge badge-secondary badge-lg">Inactive</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">Created By:</h6>
                    <p><strong>{{ $lead->createdBy->name }}</strong></p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">Created At:</h6>
                    <p>{{ $lead->created_at->format('F d, Y \a\t H:i') }}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">Last Updated:</h6>
                    <p>{{ $lead->updated_at->format('F d, Y \a\t H:i') }}</p>
                </div>
                @if($lead->address)
                    <div class="col-12">
                        <h6 class="text-muted">Address:</h6>
                        <p>{{ $lead->address }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Lead Follow-ups History -->
    @if($lead->leadFollowups->count() > 0)
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Follow-up History</h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 50px;">S/n</th>
                                <th>Report Date</th>
                                <th>Interaction Type</th>
                                <th>Details/Discussion</th>
                                <th>Outcome</th>
                                <th>Next Step</th>
                                <th>Follow-Up Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lead->leadFollowups as $index => $followup)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $followup->salesDailyReport->report_date->format('M d, Y') }}</td>
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
            </div>
        </div>
    @endif

    <!-- Action Buttons -->
    <div class="block block-rounded">
        <div class="block-content">
            <div class="row">
                <div class="col-12">
                    <a href="{{ route('leads.edit', $lead->id) }}" class="btn btn-primary">
                        <i class="fa fa-edit"></i> Edit Lead
                    </a>
                    
                    <form method="POST" action="{{ route('leads.destroy', $lead->id) }}" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this lead?')">
                            <i class="fa fa-trash"></i> Delete Lead
                        </button>
                    </form>

                    <a href="{{ route('leads.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Leads
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
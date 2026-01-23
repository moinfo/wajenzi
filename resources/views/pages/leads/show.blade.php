@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">
                Lead Details - {{ $lead->lead_number ?? $lead->name }}
            </h1>
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
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <!-- Lead Status Summary -->
    <div class="row">
        <div class="col-md-3">
            <div class="block block-rounded text-center">
                <div class="block-content block-content-full">
                    <div class="py-3">
                        @if($lead->leadStatus)
                            @php
                                $statusClass = match(strtolower($lead->leadStatus->name)) {
                                    'won' => 'text-success',
                                    'lost' => 'text-danger',
                                    'proposal sent' => 'text-warning',
                                    'new' => 'text-primary',
                                    default => 'text-secondary'
                                };
                            @endphp
                            <p class="h1 {{ $statusClass }} mb-0">{{ $lead->leadStatus->name }}</p>
                        @else
                            <p class="h1 text-muted mb-0">-</p>
                        @endif
                        <p class="text-muted mb-0">Lead Status</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="block block-rounded text-center">
                <div class="block-content block-content-full">
                    <div class="py-3">
                        <p class="h1 text-primary mb-0">
                            {{ $lead->estimated_value ? 'TZS ' . number_format($lead->estimated_value) : '-' }}
                        </p>
                        <p class="text-muted mb-0">Estimated Value</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="block block-rounded text-center">
                <div class="block-content block-content-full">
                    <div class="py-3">
                        <p class="h3 mb-0">{{ $lead->salesperson->name ?? '-' }}</p>
                        <p class="text-muted mb-0">Salesperson</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="block block-rounded text-center">
                <div class="block-content block-content-full">
                    <div class="py-3">
                        <p class="h3 mb-0">
                            @if($lead->latestFollowup && $lead->latestFollowup->followup_date)
                                {{ $lead->latestFollowup->followup_date->format('d M Y') }}
                            @else
                                <span class="text-muted">Not Set</span>
                            @endif
                        </p>
                        <p class="text-muted mb-0">Next Follow-up</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lead Information -->
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Lead Information</h3>
            <div class="block-options">
                <a href="{{ route('leads.edit', $lead->id) }}" class="btn btn-sm btn-primary">
                    <i class="fa fa-edit"></i> Edit
                </a>
            </div>
        </div>
        <div class="block-content">
            <div class="row">
                <div class="col-md-4">
                    <h6 class="text-muted">Lead Number</h6>
                    <p><strong>{{ $lead->lead_number ?? '-' }}</strong></p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Lead Date</h6>
                    <p>{{ $lead->lead_date ? $lead->lead_date->format('d F Y') : '-' }}</p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Record Status</h6>
                    <p>
                        @if($lead->status == 'active')
                            <span class="badge badge-success">Active</span>
                        @elseif($lead->status == 'converted')
                            <span class="badge badge-primary">Converted</span>
                        @else
                            <span class="badge badge-secondary">Inactive</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Client Details -->
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Client Details</h3>
        </div>
        <div class="block-content">
            <div class="row">
                <div class="col-md-4">
                    <h6 class="text-muted">Client Name</h6>
                    <p><strong>{{ $lead->name }}</strong></p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Phone</h6>
                    <p>
                        @if($lead->phone)
                            <a href="tel:{{ $lead->phone }}"><i class="fa fa-phone mr-1"></i>{{ $lead->phone }}</a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Email</h6>
                    <p>
                        @if($lead->email)
                            <a href="mailto:{{ $lead->email }}"><i class="fa fa-envelope mr-1"></i>{{ $lead->email }}</a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Service & Location -->
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Service & Location</h3>
        </div>
        <div class="block-content">
            <div class="row">
                <div class="col-md-4">
                    <h6 class="text-muted">Lead Source</h6>
                    <p>
                        @if($lead->leadSource)
                            <span class="badge badge-info">{{ $lead->leadSource->name }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Service Interested</h6>
                    <p>
                        @if($lead->serviceInterested)
                            <span class="badge badge-secondary">{{ $lead->serviceInterested->name }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Estimated Value</h6>
                    <p><strong>{{ $lead->estimated_value ? 'TZS ' . number_format($lead->estimated_value, 2) : '-' }}</strong></p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <h6 class="text-muted">Site Location</h6>
                    <p>{{ $lead->site_location ?: '-' }}</p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">City</h6>
                    <p>{{ $lead->city ?: '-' }}</p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Full Address</h6>
                    <p>{{ $lead->address ?: '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Assignment & Notes -->
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Assignment & Notes</h3>
        </div>
        <div class="block-content">
            <div class="row">
                <div class="col-md-4">
                    <h6 class="text-muted">Salesperson</h6>
                    <p><strong>{{ $lead->salesperson->name ?? '-' }}</strong></p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Created By</h6>
                    <p>{{ $lead->createdBy->name ?? '-' }}</p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted">Created At</h6>
                    <p>{{ $lead->created_at->format('d F Y \a\t H:i') }}</p>
                </div>
            </div>
            @if($lead->notes)
            <div class="row">
                <div class="col-12">
                    <h6 class="text-muted">Notes</h6>
                    <div class="alert alert-light">
                        {!! nl2br(e($lead->notes)) !!}
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Add New Follow-up -->
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title"><i class="fa fa-plus-circle text-success mr-2"></i>Add New Follow-up</h3>
        </div>
        <div class="block-content">
            <form method="POST" action="{{ route('leads.followup.store', $lead->id) }}">
                @csrf
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="followup_date" class="required">Followup Date</label>
                            <input type="date" class="form-control @error('followup_date') is-invalid @enderror"
                                   id="followup_date" name="followup_date"
                                   value="{{ old('followup_date', now()->format('Y-m-d')) }}" required>
                            @error('followup_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="form-group">
                            <label for="details_discussion" class="required">Followup Remarks</label>
                            <input type="text" class="form-control @error('details_discussion') is-invalid @enderror"
                                   id="details_discussion" name="details_discussion"
                                   value="{{ old('details_discussion') }}"
                                   placeholder="What was discussed with the client?" required>
                            @error('details_discussion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="outcome">Followup Result</label>
                            <input type="text" class="form-control @error('outcome') is-invalid @enderror"
                                   id="outcome" name="outcome"
                                   value="{{ old('outcome') }}"
                                   placeholder="Result of the follow-up">
                            @error('outcome')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label for="next_step">Next Action</label>
                            <input type="text" class="form-control @error('next_step') is-invalid @enderror"
                                   id="next_step" name="next_step"
                                   value="{{ old('next_step') }}"
                                   placeholder="What should be done next?">
                            @error('next_step')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fa fa-save mr-1"></i> Save Follow-up
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lead Follow-ups History -->
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Follow-up History</h3>
            <div class="block-options">
                <span class="badge badge-primary">{{ $lead->leadFollowups->count() }} Follow-ups</span>
            </div>
        </div>
        <div class="block-content">
            @if($lead->leadFollowups->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 50px;">S/n</th>
                                <th>Followup Date</th>
                                <th>Followup Remarks</th>
                                <th>Followup Result</th>
                                <th>Next Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lead->leadFollowups->sortByDesc('followup_date')->sortByDesc('created_at') as $index => $followup)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        @if($followup->followup_date)
                                            {{ $followup->followup_date->format('d M Y') }}
                                        @else
                                            {{ $followup->created_at->format('d M Y') }}
                                        @endif
                                    </td>
                                    <td>{{ $followup->details_discussion ?: '-' }}</td>
                                    <td>{{ $followup->outcome ?: '-' }}</td>
                                    <td>{{ $followup->next_step ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info">
                    <i class="fa fa-info-circle mr-2"></i> No follow-ups recorded yet for this lead.
                </div>
            @endif
        </div>
    </div>

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
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this lead?')">
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

@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>Sent Emails</h1>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('billing.dashboard') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <div class="block block-themed">
            <div class="block-content">
                
                <!-- Filters -->
                <div class="row no-print m-t-10">
                    <div class="col-md-12">
                        <div class="card-box">
                            <form method="GET" action="{{ route('billing.emails.index') }}" class="row">
                                <div class="col-md-2">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Type</span>
                                        </div>
                                        <select name="document_type" class="form-control">
                                            <option value="">All Types</option>
                                            <option value="invoice" {{ request('document_type') == 'invoice' ? 'selected' : '' }}>Invoice</option>
                                            <option value="proforma" {{ request('document_type') == 'proforma' ? 'selected' : '' }}>Proforma</option>
                                            <option value="quote" {{ request('document_type') == 'quote' ? 'selected' : '' }}>Quote</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-2">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Status</span>
                                        </div>
                                        <select name="status" class="form-control">
                                            <option value="">All Status</option>
                                            <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Email</span>
                                        </div>
                                        <input type="text" name="recipient_email" class="form-control" value="{{ request('recipient_email') }}" placeholder="Search email...">
                                    </div>
                                </div>
                                
                                <div class="col-md-2">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">From</span>
                                        </div>
                                        <input type="text" name="from_date" class="form-control datepicker" value="{{ request('from_date') }}">
                                    </div>
                                </div>
                                
                                <div class="col-md-2">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">To</span>
                                        </div>
                                        <input type="text" name="to_date" class="form-control datepicker" value="{{ request('to_date') }}">
                                    </div>
                                </div>
                                
                                <div class="col-md-1">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="{{ route('billing.emails.index') }}" class="btn btn-secondary">Clear</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Emails Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Document</th>
                                <th>Client</th>
                                <th>Recipient</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Sent By</th>
                                <th>Sent At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($emails as $email)
                                <tr>
                                    <td>
                                        <strong>{{ ucfirst($email->document_type) }}</strong><br>
                                        <small class="text-muted">{{ $email->document->document_number ?? 'N/A' }}</small>
                                        @if($email->has_attachment)
                                            <br><small class="text-success"><i class="fa fa-paperclip"></i> PDF Attached</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($email->document && $email->document->client)
                                            {{ $email->document->client->company_name }}<br>
                                            <small class="text-muted">{{ $email->document->client->contact_person }}</small>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $email->recipient_email }}
                                        @if($email->cc_emails && $email->cc_emails != 'null')
                                            <br><small class="text-muted">CC: {{ $email->cc_emails }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span title="{{ $email->subject }}">
                                            {{ Str::limit($email->subject, 40) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $email->status_color }}">
                                            {{ ucfirst($email->status) }}
                                        </span>
                                        @if($email->status === 'failed' && $email->error_message)
                                            <br><small class="text-danger" title="{{ $email->error_message }}">
                                                {{ Str::limit($email->error_message, 30) }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>{{ $email->sender->name ?? 'System' }}</td>
                                    <td>{{ $email->sent_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('billing.emails.show', $email) }}" 
                                               class="btn btn-sm btn-info" title="View Details">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            
                                            <a href="{{ route('billing.emails.resend.form', $email) }}" 
                                               class="btn btn-sm btn-warning" title="Resend Email">
                                                <i class="fa fa-repeat"></i>
                                            </a>
                                            
                                            @if($email->document)
                                                @if($email->document_type === 'invoice')
                                                    <a href="{{ route('billing.invoices.show', $email->document) }}" 
                                                       class="btn btn-sm btn-secondary" title="View Document">
                                                        <i class="fa fa-file"></i>
                                                    </a>
                                                @elseif($email->document_type === 'proforma')
                                                    <a href="{{ route('billing.proformas.show', $email->document) }}" 
                                                       class="btn btn-sm btn-secondary" title="View Document">
                                                        <i class="fa fa-file"></i>
                                                    </a>
                                                @elseif($email->document_type === 'quote')
                                                    <a href="{{ route('billing.quotations.show', $email->document) }}" 
                                                       class="btn btn-sm btn-secondary" title="View Document">
                                                        <i class="fa fa-file"></i>
                                                    </a>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fa fa-inbox fa-3x mb-3"></i>
                                            <br>No emails found
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($emails->hasPages())
                    <div class="d-flex justify-content-center">
                        {{ $emails->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
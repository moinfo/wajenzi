@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>Resend Email</h1>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('billing.emails.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Emails
                    </a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Resend Email</h3>
                    </div>
                    <div class="block-content">
                        <form method="POST" action="{{ route('billing.emails.resend', $email) }}">
                            @csrf
                            
                            <div class="form-group">
                                <label>To Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $email->recipient_email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="form-group">
                                <label>CC (Optional)</label>
                                <input type="text" name="cc" class="form-control @error('cc') is-invalid @enderror"
                                       value="{{ old('cc', is_array($email->cc_emails) ? implode(', ', $email->cc_emails) : $email->cc_emails) }}"
                                       placeholder="Multiple emails separated by commas">
                                @error('cc')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="form-group">
                                <label>Subject <span class="text-danger">*</span></label>
                                <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror"
                                       value="{{ old('subject', $email->subject) }}" required>
                                @error('subject')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="form-group">
                                <label>Message <span class="text-danger">*</span></label>
                                <textarea name="message" class="form-control @error('message') is-invalid @enderror" 
                                          rows="8" required>{{ old('message', $email->message) }}</textarea>
                                @error('message')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="form-group">
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> 
                                    PDF attachment will be automatically included with this email.
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-paper-plane"></i> Resend Email
                                </button>
                                <a href="{{ route('billing.emails.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Original Email Details</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Document:</strong></td>
                                <td>
                                    {{ ucfirst($email->document_type) }}<br>
                                    <small class="text-muted">{{ $email->document->document_number ?? 'N/A' }}</small>
                                </td>
                            </tr>
                            @if($email->document && $email->document->client)
                            <tr>
                                <td><strong>Client:</strong></td>
                                <td>
                                    {{ $email->document->client->company_name }}<br>
                                    <small class="text-muted">{{ $email->document->client->contact_person }}</small>
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <td><strong>Original Recipient:</strong></td>
                                <td>{{ $email->recipient_email }}</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge badge-{{ $email->status_color }}">
                                        {{ ucfirst($email->status) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Sent By:</strong></td>
                                <td>{{ $email->sender->name ?? 'System' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Sent At:</strong></td>
                                <td>{{ $email->sent_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Attachment:</strong></td>
                                <td>
                                    @if($email->has_attachment)
                                        <span class="text-success"><i class="fa fa-check"></i> PDF Included</span>
                                    @else
                                        <span class="text-warning"><i class="fa fa-times"></i> No Attachment</span>
                                    @endif
                                </td>
                            </tr>
                            @if($email->status === 'failed' && $email->error_message)
                            <tr>
                                <td><strong>Error:</strong></td>
                                <td>
                                    <small class="text-danger">{{ $email->error_message }}</small>
                                </td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>
                
                @if($email->document)
                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Quick Actions</h3>
                    </div>
                    <div class="block-content">
                        @if($email->document_type === 'invoice')
                            <a href="{{ route('billing.invoices.show', $email->document) }}" class="btn btn-primary btn-block">
                                <i class="fa fa-file"></i> View Invoice
                            </a>
                            <a href="{{ route('billing.invoices.pdf', $email->document) }}" class="btn btn-secondary btn-block" target="_blank">
                                <i class="fa fa-download"></i> Download PDF
                            </a>
                        @elseif($email->document_type === 'proforma')
                            <a href="{{ route('billing.proformas.show', $email->document) }}" class="btn btn-primary btn-block">
                                <i class="fa fa-file"></i> View Proforma
                            </a>
                            <a href="{{ route('billing.proformas.pdf', $email->document) }}" class="btn btn-secondary btn-block" target="_blank">
                                <i class="fa fa-download"></i> Download PDF
                            </a>
                        @elseif($email->document_type === 'quote')
                            <a href="{{ route('billing.quotations.show', $email->document) }}" class="btn btn-primary btn-block">
                                <i class="fa fa-file"></i> View Quotation
                            </a>
                            <a href="{{ route('billing.quotations.pdf', $email->document) }}" class="btn btn-secondary btn-block" target="_blank">
                                <i class="fa fa-download"></i> Download PDF
                            </a>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
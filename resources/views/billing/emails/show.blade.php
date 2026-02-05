@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>
                        <i class="fa fa-envelope-open-text"></i>
                        Email Details
                    </h1>
                    <span class="badge badge-{{ $email->status === 'sent' ? 'success' : ($email->status === 'failed' ? 'danger' : 'warning') }} badge-lg">
                        {{ ucfirst($email->status) }}
                    </span>
                </div>
                <div class="col-md-6 text-right">
                    @if($email->status === 'failed')
                        <a href="{{ route('billing.emails.resend', $email) }}" class="btn btn-warning">
                            <i class="fa fa-paper-plane"></i> Resend
                        </a>
                    @endif
                    <a href="{{ route('billing.emails.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                @if($email->status === 'failed' && $email->error_message)
                    <div class="alert alert-danger">
                        <h4 class="alert-heading">
                            <i class="fa fa-exclamation-triangle"></i>
                            Email Failed
                        </h4>
                        <p class="mb-0"><strong>Error:</strong> {{ $email->error_message }}</p>
                    </div>
                @elseif($email->status === 'sent')
                    <div class="alert alert-success">
                        <h4 class="alert-heading">
                            <i class="fa fa-check-circle"></i>
                            Email Sent Successfully
                        </h4>
                        <p class="mb-0">This email was sent successfully on {{ $email->sent_at->format('d/m/Y H:i:s') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="block block-rounded">
                    <div class="block-header">
                        <h3 class="block-title">
                            <i class="fa fa-info-circle"></i>
                            Email Information
                        </h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-vcenter">
                            <tr>
                                <td width="35%"><strong>Document Type:</strong></td>
                                <td>
                                    <span class="badge badge-primary">
                                        {{ ucfirst(str_replace('_', ' ', $email->document_type)) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>To:</strong></td>
                                <td>{{ $email->recipient_email }}</td>
                            </tr>
                            <tr>
                                <td><strong>Subject:</strong></td>
                                <td>{{ $email->subject }}</td>
                            </tr>
                            <tr>
                                <td><strong>Attachment:</strong></td>
                                <td>
                                    @if($email->has_attachment)
                                        <span class="badge badge-success">
                                            <i class="fa fa-paperclip"></i>
                                            PDF Attached
                                        </span>
                                    @else
                                        <span class="badge badge-secondary">No Attachment</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Sent At:</strong></td>
                                <td>
                                    @if($email->sent_at)
                                        {{ $email->sent_at->format('d/m/Y H:i:s') }}
                                        <small class="text-muted">({{ $email->sent_at->diffForHumans() }})</small>
                                    @else
                                        <span class="text-muted">Not sent</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Sent By:</strong></td>
                                <td>{{ $email->sender->name ?? 'System' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="block block-rounded">
                    <div class="block-header">
                        <h3 class="block-title">
                            <i class="fa fa-file-invoice"></i>
                            Document Information
                        </h3>
                    </div>
                    <div class="block-content">
                            <tr>
                                <td width="35%"><strong>Document #:</strong></td>
                                <td>
                                    <a href="{{ route('billing.' . $email->document_type . 's.show', $email->document) }}">
                                        {{ $email->document->document_number }}
                                        <i class="fa fa-external-link-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Client:</strong></td>
                                <td>{{ $email->document->client->company_name }}</td>
                            </tr>
                            <tr>
                                <td><strong>Contact:</strong></td>
                                <td>{{ $email->document->client->contact_person ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Amount:</strong></td>
                                <td>
                                    <strong>{{ $email->document->currency_code }} {{ number_format($email->document->total_amount, 2) }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Issue Date:</strong></td>
                                <td>{{ $email->document->issue_date->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge badge-{{ $email->document->status === 'paid' ? 'success' : ($email->document->status === 'overdue' ? 'danger' : 'warning') }}">
                                        {{ ucfirst(str_replace('_', ' ', $email->document->status)) }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @if($email->message)
            <div class="row">
                <div class="col-12">
                    <div class="block block-rounded">
                        <div class="block-header">
                            <h3 class="block-title">
                                <i class="fa fa-comment-alt"></i>
                                Email Message
                            </h3>
                        </div>
                        <div class="block-content">
                            <div class="bg-light p-3 rounded">
                                {!! nl2br(e($email->message)) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="block block-rounded">
                    <div class="block-header">
                        <h3 class="block-title">
                            <i class="fa fa-cogs"></i>
                            Actions
                        </h3>
                    </div>
                    <div class="block-content">
                        @if($email->status === 'failed')
                            <a href="{{ route('billing.emails.resend', $email) }}" class="btn btn-warning">
                                <i class="fa fa-paper-plane"></i>
                                Resend Email
                            </a>
                        @endif
                        
                        <a href="{{ route('billing.' . $email->document_type . 's.show', $email->document) }}" 
                           class="btn btn-primary">
                            <i class="fa fa-eye"></i>
                            View Document
                        </a>
                        
                        <a href="{{ route('billing.' . $email->document_type . 's.pdf', $email->document) }}" 
                           class="btn btn-secondary" target="_blank">
                            <i class="fa fa-file-pdf"></i>
                            Download PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
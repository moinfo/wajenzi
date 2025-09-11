@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>Proforma Invoice {{ $proforma->document_number }}</h1>
                    <span class="badge badge-{{ $proforma->status_color ?? 'secondary' }} badge-lg">
                        {{ ucfirst(str_replace('_', ' ', $proforma->status)) }}
                    </span>
                </div>
                <div class="col-md-6 text-right">
                    @if($proforma->is_editable)
                        <a href="{{ route('billing.proformas.edit', $proforma) }}" class="btn btn-primary">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                    @endif

                    <a href="{{ route('billing.proformas.pdf', $proforma) }}" class="btn btn-secondary" target="_blank">
                        <i class="fa fa-download"></i> PDF
                    </a>

                    <button type="button" class="btn btn-info" onclick="sendEmailModal()">
                        <i class="fa fa-envelope"></i> Send Email
                    </button>

                    @if(in_array($proforma->status, ['sent', 'viewed', 'accepted']))
                        <form action="{{ route('billing.proformas.convert', $proforma) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Convert this proforma to invoice?')">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-file-invoice-dollar"></i> Convert to Invoice
                            </button>
                        </form>
                    @endif

                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown">
                            More Actions
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="{{ route('billing.proformas.duplicate', $proforma) }}">
                                <i class="fa fa-copy"></i> Duplicate
                            </a>
                            @if($proforma->is_editable)
                                <form action="{{ route('billing.proformas.destroy', $proforma) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Are you sure you want to cancel this proforma?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-warning">
                                        <i class="fa fa-times"></i> Cancel
                                    </button>
                                </form>
                            @endif
                            <a class="dropdown-item" href="{{ route('billing.proformas.index') }}">
                                <i class="fa fa-list"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Proforma Details -->
            <div class="col-md-8">
                <div class="block block-themed">
                    <div class="block-content">
                        @include('components.headed_paper')

                        <!-- Proforma Header -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h3>PROFORMA INVOICE</h3>
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td width="120"><strong>Proforma #:</strong></td>
                                        <td>{{ $proforma->document_number }}</td>
                                    </tr>
                                    @if($proforma->reference_number)
                                        <tr>
                                            <td><strong>Reference:</strong></td>
                                            <td>{{ $proforma->reference_number }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td><strong>Issue Date:</strong></td>
                                        <td>{{ $proforma->issue_date->format('d/m/Y') }}</td>
                                    </tr>
                                    @if($proforma->valid_until_date)
                                        <tr>
                                            <td><strong>Valid Until:</strong></td>
                                            <td>
                                                {{ $proforma->valid_until_date->format('d/m/Y') }}
                                                @if($proforma->valid_until_date->isPast() && $proforma->status != 'accepted')
                                                    <span class="text-warning">(Expired)</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                    @if($proforma->sales_person)
                                        <tr>
                                            <td><strong>Sales Person:</strong></td>
                                            <td>{{ $proforma->sales_person }}</td>
                                        </tr>
                                    @endif
                                </table>
                            </div>

                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Proforma For:</h5>
                                    </div>
                                    <div class="card-body">
                                        <strong>{{ $proforma->client->company_name }}</strong><br>
                                        @if($proforma->client->contact_person)
                                            {{ $proforma->client->contact_person }}<br>
                                        @endif
                                        {{ $proforma->client->full_billing_address }}<br>
                                        @if($proforma->client->phone)
                                            Phone: {{ $proforma->client->phone }}<br>
                                        @endif
                                        @if($proforma->client->email)
                                            Email: {{ $proforma->client->email }}<br>
                                        @endif
                                        @if($proforma->client->tax_identification_number)
                                            TIN: {{ $proforma->client->tax_identification_number }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Line Items -->
                        <div class="table-responsive mt-4">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Item/Description</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-center">Unit</th>
                                        <th class="text-right">Unit Price</th>
                                        <th class="text-right">Tax</th>
                                        <th class="text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($proforma->items as $item)
                                        <tr>
                                            <td>
                                                <strong>{{ $item->item_name }}</strong>
                                                @if($item->description)
                                                    <br><small class="text-muted">{{ $item->description }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                                            <td class="text-center">{{ $item->unit_of_measure ?? '-' }}</td>
                                            <td class="text-right">{{ $proforma->currency_code }} {{ number_format($item->unit_price, 2) }}</td>
                                            <td class="text-right">
                                                @if($item->tax_percentage > 0)
                                                    {{ $item->tax_percentage }}%<br>
                                                    <small>{{ $proforma->currency_code }} {{ number_format($item->tax_amount, 2) }}</small>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-right">{{ $proforma->currency_code }} {{ number_format($item->line_total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Totals -->
                        <div class="row">
                            <div class="col-md-6">
                                @if($proforma->notes)
                                    <div class="mb-3">
                                        <strong>Notes:</strong><br>
                                        {{ $proforma->notes }}
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Subtotal:</strong></td>
                                        <td class="text-right">{{ $proforma->currency_code }} {{ number_format($proforma->subtotal_amount, 2) }}</td>
                                    </tr>
                                    @if($proforma->discount_amount > 0)
                                        <tr>
                                            <td><strong>Discount:</strong></td>
                                            <td class="text-right text-success">-{{ $proforma->currency_code }} {{ number_format($proforma->discount_amount, 2) }}</td>
                                        </tr>
                                    @endif
                                    @if($proforma->tax_amount > 0)
                                        <tr>
                                            <td><strong>Tax:</strong></td>
                                            <td class="text-right">{{ $proforma->currency_code }} {{ number_format($proforma->tax_amount, 2) }}</td>
                                        </tr>
                                    @endif
                                    @if($proforma->shipping_amount > 0)
                                        <tr>
                                            <td><strong>Shipping:</strong></td>
                                            <td class="text-right">{{ $proforma->currency_code }} {{ number_format($proforma->shipping_amount, 2) }}</td>
                                        </tr>
                                    @endif
                                    <tr class="table-warning">
                                        <td><strong>Total:</strong></td>
                                        <td class="text-right"><strong>{{ $proforma->currency_code }} {{ number_format($proforma->total_amount, 2) }}</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Terms & Footer -->
                        @if($proforma->terms_conditions)
                            <div class="mt-4">
                                <strong>Terms & Conditions:</strong><br>
                                {{ $proforma->terms_conditions }}
                            </div>
                        @endif

                        @if($proforma->footer_text)
                            <div class="mt-3 text-center">
                                <em>{{ $proforma->footer_text }}</em>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Proforma Info -->
                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Proforma Information</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td>Created by:</td>
                                <td>{{ $proforma->creator->name ?? 'System' }}</td>
                            </tr>
                            <tr>
                                <td>Created:</td>
                                <td>{{ $proforma->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @if($proforma->sent_at)
                                <tr>
                                    <td>Sent:</td>
                                    <td>{{ $proforma->sent_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endif
                            @if($proforma->viewed_at)
                                <tr>
                                    <td>Viewed:</td>
                                    <td>{{ $proforma->viewed_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>

                <!-- Email History -->
                @if($proforma->emails->count() > 0)
                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Email History</h3>
                        <div class="block-options">
                            <a href="{{ route('billing.emails.index', ['document_type' => 'proforma', 'document_id' => $proforma->id]) }}" 
                               class="btn btn-sm btn-secondary">
                                <i class="fa fa-list"></i> View All
                            </a>
                        </div>
                    </div>
                    <div class="block-content">
                        @foreach($proforma->emails->take(3) as $email)
                            <div class="mb-3 pb-3 @if(!$loop->last) border-bottom @endif">
                                <div class="row">
                                    <div class="col-md-8">
                                        <strong>{{ $email->recipient_email }}</strong>
                                        <span class="badge badge-{{ $email->status_color }} ml-2">
                                            {{ ucfirst($email->status) }}
                                        </span>
                                        <br>
                                        <small class="text-muted">{{ Str::limit($email->subject, 50) }}</small>
                                        <br>
                                        <small class="text-muted">{{ $email->sent_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        @if($email->has_attachment)
                                            <small class="text-success"><i class="fa fa-paperclip"></i> PDF</small><br>
                                        @endif
                                        <a href="{{ route('billing.emails.resend.form', $email) }}" 
                                           class="btn btn-xs btn-warning">
                                            <i class="fa fa-repeat"></i> Resend
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        
                        @if($proforma->emails->count() > 3)
                            <div class="text-center">
                                <a href="{{ route('billing.emails.index', ['document_type' => 'proforma', 'document_id' => $proforma->id]) }}" 
                                   class="btn btn-sm btn-link">
                                    View all {{ $proforma->emails->count() }} emails
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Related Documents -->
                @if($proforma->parentDocument || $proforma->childDocuments->count() > 0)
                    <div class="block block-themed">
                        <div class="block-header">
                            <h3 class="block-title">Related Documents</h3>
                        </div>
                        <div class="block-content">
                            @if($proforma->parentDocument)
                                <div class="mb-2">
                                    <strong>Converted from:</strong><br>
                                    <a href="{{ route('billing.quotations.show', $proforma->parentDocument) }}">
                                        {{ ucfirst($proforma->parentDocument->document_type) }}
                                        {{ $proforma->parentDocument->document_number }}
                                    </a>
                                </div>
                            @endif

                            @foreach($proforma->childDocuments as $child)
                                <div class="mb-2">
                                    <strong>{{ ucfirst($child->document_type) }}:</strong><br>
                                    @if($child->document_type == 'invoice')
                                        <a href="{{ route('billing.invoices.show', $child) }}">
                                            {{ $child->document_number }}
                                        </a>
                                    @else
                                        <a href="{{ route('billing.proformas.show', $child) }}">
                                            {{ $child->document_number }}
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Conversion Actions -->
                @if($proforma->status == 'accepted' || $proforma->status == 'viewed')
                    <div class="block block-themed">
                        <div class="block-header">
                            <h3 class="block-title">Next Steps</h3>
                        </div>
                        <div class="block-content">
                            <form action="{{ route('billing.proformas.convert', $proforma) }}" method="POST"
                                  onsubmit="return confirm('Convert this proforma to invoice?')">
                                @csrf
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fa fa-exchange-alt"></i> Convert to Invoice
                                </button>
                            </form>
                            <small class="text-muted">Convert this accepted proforma to a billable invoice</small>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('billing.proformas.send-email', $proforma) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Send Proforma via Email</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>To Email</label>
                        <input type="email" name="email" class="form-control"
                               value="{{ $proforma->client->email }}" required>
                    </div>
                    <div class="form-group">
                        <label>CC (Optional)</label>
                        <input type="text" name="cc" class="form-control"
                               placeholder="Multiple emails separated by commas">
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" class="form-control"
                               value="Proforma Invoice {{ $proforma->document_number }}" required>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" class="form-control" rows="6" required>Dear {{ $proforma->client->contact_person ?? $proforma->client->company_name }},

Please find attached proforma invoice {{ $proforma->document_number }} for {{ $proforma->currency_code }} {{ number_format($proforma->total_amount, 2) }}.

@if($proforma->valid_until_date)
This proforma is valid until {{ $proforma->valid_until_date->format('d/m/Y') }}.
@endif

We look forward to working with you!

Best regards</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Email</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function sendEmailModal() {
    $('#emailModal').modal('show');
}
</script>

@endsection
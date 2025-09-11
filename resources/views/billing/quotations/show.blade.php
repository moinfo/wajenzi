@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>Quotation {{ $quotation->document_number }}</h1>
                    <span class="badge badge-{{ $quotation->status_color ?? 'secondary' }} badge-lg">
                        {{ ucfirst(str_replace('_', ' ', $quotation->status)) }}
                    </span>
                </div>
                <div class="col-md-6 text-right">
                    @if($quotation->is_editable)
                        <a href="{{ route('billing.quotations.edit', $quotation) }}" class="btn btn-primary">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                    @endif

                    <a href="{{ route('billing.quotations.pdf', $quotation) }}" class="btn btn-secondary" target="_blank">
                        <i class="fa fa-download"></i> PDF
                    </a>

                    <button type="button" class="btn btn-info" onclick="sendEmailModal()">
                        <i class="fa fa-envelope"></i> Send Email
                    </button>

                    @if(in_array($quotation->status, ['sent', 'viewed', 'accepted']))
                        <div class="btn-group">
                            <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown">
                                <i class="fa fa-exchange-alt"></i> Convert
                            </button>
                            <div class="dropdown-menu">
                                <form action="{{ route('billing.quotations.convert-to-proforma', $quotation) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item" onclick="return confirm('Convert this quotation to proforma?')">
                                        <i class="fa fa-file-invoice"></i> Convert to Proforma
                                    </button>
                                </form>
                                <form action="{{ route('billing.quotations.convert', $quotation) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item" onclick="return confirm('Convert this quotation to invoice?')">
                                        <i class="fa fa-file-invoice-dollar"></i> Convert to Invoice
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown">
                            More Actions
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="{{ route('billing.quotations.duplicate', $quotation) }}">
                                <i class="fa fa-copy"></i> Duplicate
                            </a>
                            @if($quotation->is_editable)
                                <form action="{{ route('billing.quotations.destroy', $quotation) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Are you sure you want to cancel this quotation?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-warning">
                                        <i class="fa fa-times"></i> Cancel
                                    </button>
                                </form>
                            @endif
                            <a class="dropdown-item" href="{{ route('billing.quotations.index') }}">
                                <i class="fa fa-list"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Quotation Details -->
            <div class="col-md-8">
                <div class="block block-themed">
                    <div class="block-content">
                        @include('components.headed_paper')

                        <!-- Quotation Header -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h3>QUOTATION</h3>
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td width="120"><strong>Quote #:</strong></td>
                                        <td>{{ $quotation->document_number }}</td>
                                    </tr>
                                    @if($quotation->reference_number)
                                        <tr>
                                            <td><strong>Reference:</strong></td>
                                            <td>{{ $quotation->reference_number }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td><strong>Issue Date:</strong></td>
                                        <td>{{ $quotation->issue_date->format('d/m/Y') }}</td>
                                    </tr>
                                    @if($quotation->valid_until_date)
                                        <tr>
                                            <td><strong>Valid Until:</strong></td>
                                            <td>
                                                {{ $quotation->valid_until_date->format('d/m/Y') }}
                                                @if($quotation->valid_until_date->isPast() && $quotation->status != 'accepted')
                                                    <span class="text-warning">(Expired)</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                    @if($quotation->po_number)
                                        <tr>
                                            <td><strong>PO Number:</strong></td>
                                            <td>{{ $quotation->po_number }}</td>
                                        </tr>
                                    @endif
                                </table>
                            </div>

                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Quote For:</h5>
                                    </div>
                                    <div class="card-body">
                                        <strong>{{ $quotation->client->first_name }} {{ $quotation->client->last_name }}</strong><br>
                                        {{ $quotation->client->address }}<br>
                                        @if($quotation->client->phone_number)
                                            Phone: {{ $quotation->client->phone_number }}<br>
                                        @endif
                                        @if($quotation->client->email)
                                            Email: {{ $quotation->client->email }}<br>
                                        @endif
                                        @if($quotation->client->identification_number)
                                            ID: {{ $quotation->client->identification_number }}
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
                                    @foreach($quotation->items as $item)
                                        <tr>
                                            <td>
                                                <strong>{{ $item->item_name }}</strong>
                                                @if($item->description)
                                                    <br><small class="text-muted">{{ $item->description }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                                            <td class="text-center">{{ $item->unit_of_measure ?? '-' }}</td>
                                            <td class="text-right">{{ $quotation->currency_code }} {{ number_format($item->unit_price, 2) }}</td>
                                            <td class="text-right">
                                                @if($item->tax_percentage > 0)
                                                    {{ $item->tax_percentage }}%<br>
                                                    <small>{{ $quotation->currency_code }} {{ number_format($item->tax_amount, 2) }}</small>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-right">{{ $quotation->currency_code }} {{ number_format($item->line_total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Totals -->
                        <div class="row">
                            <div class="col-md-6">
                                @if($quotation->notes)
                                    <div class="mb-3">
                                        <strong>Notes:</strong><br>
                                        {{ $quotation->notes }}
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Subtotal:</strong></td>
                                        <td class="text-right">{{ $quotation->currency_code }} {{ number_format($quotation->subtotal_amount, 2) }}</td>
                                    </tr>
                                    @if($quotation->discount_amount > 0)
                                        <tr>
                                            <td><strong>Discount:</strong></td>
                                            <td class="text-right text-success">-{{ $quotation->currency_code }} {{ number_format($quotation->discount_amount, 2) }}</td>
                                        </tr>
                                    @endif
                                    @if($quotation->tax_amount > 0)
                                        <tr>
                                            <td><strong>Tax:</strong></td>
                                            <td class="text-right">{{ $quotation->currency_code }} {{ number_format($quotation->tax_amount, 2) }}</td>
                                        </tr>
                                    @endif
                                    @if($quotation->shipping_amount > 0)
                                        <tr>
                                            <td><strong>Shipping:</strong></td>
                                            <td class="text-right">{{ $quotation->currency_code }} {{ number_format($quotation->shipping_amount, 2) }}</td>
                                        </tr>
                                    @endif
                                    <tr class="table-warning">
                                        <td><strong>Total:</strong></td>
                                        <td class="text-right"><strong>{{ $quotation->currency_code }} {{ number_format($quotation->total_amount, 2) }}</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Terms & Footer -->
                        @if($quotation->terms_conditions)
                            <div class="mt-4">
                                <strong>Terms & Conditions:</strong><br>
                                {{ $quotation->terms_conditions }}
                            </div>
                        @endif

                        @if($quotation->footer_text)
                            <div class="mt-3 text-center">
                                <em>{{ $quotation->footer_text }}</em>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Quotation Info -->
                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Quotation Information</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td>Created by:</td>
                                <td>{{ $quotation->creator->name ?? 'System' }}</td>
                            </tr>
                            <tr>
                                <td>Created:</td>
                                <td>{{ $quotation->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @if($quotation->sent_at)
                                <tr>
                                    <td>Sent:</td>
                                    <td>{{ $quotation->sent_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endif
                            @if($quotation->viewed_at)
                                <tr>
                                    <td>Viewed:</td>
                                    <td>{{ $quotation->viewed_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endif
                            @if($quotation->sales_person)
                                <tr>
                                    <td>Sales Person:</td>
                                    <td>{{ $quotation->sales_person }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td>Payment Terms:</td>
                                <td>{{ ucwords(str_replace('_', ' ', $quotation->payment_terms)) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Related Documents -->
                @if($quotation->parentDocument || $quotation->childDocuments->count() > 0)
                    <div class="block block-themed">
                        <div class="block-header">
                            <h3 class="block-title">Related Documents</h3>
                        </div>
                        <div class="block-content">
                            @if($quotation->parentDocument)
                                <div class="mb-2">
                                    <strong>Converted from:</strong><br>
                                    <a href="{{ route('billing.quotations.show', $quotation->parentDocument) }}">
                                        {{ ucfirst($quotation->parentDocument->document_type) }}
                                        {{ $quotation->parentDocument->document_number }}
                                    </a>
                                </div>
                            @endif

                            @foreach($quotation->childDocuments as $child)
                                <div class="mb-2">
                                    <strong>{{ ucfirst($child->document_type) }}:</strong><br>
                                    @if($child->document_type == 'invoice')
                                        <a href="{{ route('billing.invoices.show', $child) }}">
                                            {{ $child->document_number }}
                                        </a>
                                    @elseif($child->document_type == 'proforma')
                                        <a href="{{ route('billing.proformas.show', $child) }}">
                                            {{ $child->document_number }}
                                        </a>
                                    @else
                                        <a href="{{ route('billing.quotations.show', $child) }}">
                                            {{ $child->document_number }}
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Conversion Actions -->
                @if($quotation->status == 'accepted' || $quotation->status == 'viewed')
                    <div class="block block-themed">
                        <div class="block-header">
                            <h3 class="block-title">Next Steps</h3>
                        </div>
                        <div class="block-content">
                            <form action="{{ route('billing.quotations.convert', $quotation) }}" method="POST"
                                  onsubmit="return confirm('Convert this quotation to invoice?')">
                                @csrf
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fa fa-exchange-alt"></i> Convert to Invoice
                                </button>
                            </form>
                            <small class="text-muted">Convert this accepted quotation to a billable invoice</small>
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
            <form method="POST" action="{{ route('billing.quotations.send-email', $quotation) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Send Quotation via Email</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>To Email</label>
                        <input type="email" name="email" class="form-control"
                               value="{{ $quotation->client->email }}" required>
                    </div>
                    <div class="form-group">
                        <label>CC (Optional)</label>
                        <input type="text" name="cc" class="form-control"
                               placeholder="Multiple emails separated by commas">
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" class="form-control"
                               value="Quotation {{ $quotation->document_number }}" required>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" class="form-control" rows="6" required>Dear {{ $quotation->client->first_name }} {{ $quotation->client->last_name }},

Please find attached quotation {{ $quotation->document_number }} for {{ $quotation->currency_code }} {{ number_format($quotation->total_amount, 2) }}.

@if($quotation->valid_until_date)
This quote is valid until {{ $quotation->valid_until_date->format('d/m/Y') }}.
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

{{--@push('scripts')--}}
<script>
function sendEmailModal() {
    $('#emailModal').modal('show');
}
</script>
{{--@endpush--}}

@endsection

@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>Invoice {{ $invoice->document_number }}</h1>
                    <span class="badge badge-{{ $invoice->status_color }} badge-lg">
                        {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                    </span>
                </div>
                <div class="col-md-6 text-right">
                    @if($invoice->is_editable)
                        <a href="{{ route('billing.invoices.edit', $invoice) }}" class="btn btn-primary">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                    @endif

                    <a href="{{ route('billing.invoices.pdf', $invoice) }}" class="btn btn-secondary" target="_blank">
                        <i class="fa fa-download"></i> PDF
                    </a>

                    <button type="button" class="btn btn-info" onclick="sendEmailModal()">
                        <i class="fa fa-envelope"></i> Send Email
                    </button>

                    @if(!$invoice->is_paid && $invoice->balance_amount > 0)
                        <button type="button" class="btn btn-warning" onclick="sendReminderModal()">
                            <i class="fa fa-bell"></i> Send Reminder
                        </button>
                    @endif

                    @if(!$invoice->is_paid && $invoice->balance_amount > 0)
                        <button type="button" class="btn btn-success" onclick="recordPaymentModal()">
                            <i class="fa fa-money"></i> Record Payment
                        </button>
                    @endif

                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown">
                            More Actions
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="{{ route('billing.invoices.duplicate', $invoice) }}">
                                <i class="fa fa-copy"></i> Duplicate
                            </a>
                            @if(!$invoice->is_paid && $invoice->balance_amount > 0 && !$invoice->late_fee_applied_at)
                                <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="applyLateFeeModal()">
                                    <i class="fa fa-plus"></i> Apply Late Fee
                                </a>
                            @endif
                            @if($invoice->status !== 'void')
                                <a class="dropdown-item text-warning" href="{{ route('billing.invoices.void', $invoice) }}"
                                   onclick="return confirm('Are you sure you want to void this invoice?')">
                                    <i class="fa fa-ban"></i> Void
                                </a>
                            @endif
                            <a class="dropdown-item" href="{{ route('billing.invoices.index') }}">
                                <i class="fa fa-list"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Invoice Details -->
            <div class="col-md-8">
                <div class="block block-themed">
                    <div class="block-content">
                        @include('components.headed_paper')

                        <!-- Invoice Header -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h3>INVOICE</h3>
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td width="120"><strong>Invoice #:</strong></td>
                                        <td>{{ $invoice->document_number }}</td>
                                    </tr>
                                    @if($invoice->reference_number)
                                        <tr>
                                            <td><strong>Reference:</strong></td>
                                            <td>{{ $invoice->reference_number }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td><strong>Issue Date:</strong></td>
                                        <td>{{ $invoice->issue_date->format('d/m/Y') }}</td>
                                    </tr>
                                    @if($invoice->due_date)
                                        <tr>
                                            <td><strong>Due Date:</strong></td>
                                            <td>
                                                {{ $invoice->due_date->format('d/m/Y') }}
                                                @if($invoice->is_overdue)
                                                    <span class="text-danger">(Overdue)</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                    @if($invoice->po_number)
                                        <tr>
                                            <td><strong>PO Number:</strong></td>
                                            <td>{{ $invoice->po_number }}</td>
                                        </tr>
                                    @endif
                                </table>
                            </div>

                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Bill To:</h5>
                                    </div>
                                    <div class="card-body">
                                        <strong>{{ $invoice->client->first_name }} {{ $invoice->client->last_name }}</strong><br>
                                        {{ $invoice->client->address }}<br>
                                        @if($invoice->client->phone_number)
                                            Phone: {{ $invoice->client->phone_number }}<br>
                                        @endif
                                        @if($invoice->client->email)
                                            Email: {{ $invoice->client->email }}<br>
                                        @endif
                                        @if($invoice->client->identification_number)
                                            ID: {{ $invoice->client->identification_number }}
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
                                    @foreach($invoice->items as $item)
                                        <tr>
                                            <td>
                                                <strong>{{ $item->item_name }}</strong>
                                                @if($item->description)
                                                    <br><small class="text-muted">{{ $item->description }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                                            <td class="text-center">{{ $item->unit_of_measure ?? '-' }}</td>
                                            <td class="text-right">{{ $invoice->currency_code }} {{ number_format($item->unit_price, 2) }}</td>
                                            <td class="text-right">
                                                @if($item->tax_percentage > 0)
                                                    {{ $item->tax_percentage }}%<br>
                                                    <small>{{ $invoice->currency_code }} {{ number_format($item->tax_amount, 2) }}</small>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-right">{{ $invoice->currency_code }} {{ number_format($item->line_total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Totals -->
                        <div class="row">
                            <div class="col-md-6">
                                @if($invoice->notes)
                                    <div class="mb-3">
                                        <strong>Notes:</strong><br>
                                        {{ $invoice->notes }}
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Subtotal:</strong></td>
                                        <td class="text-right">{{ $invoice->currency_code }} {{ number_format($invoice->subtotal_amount, 2) }}</td>
                                    </tr>
                                    @if($invoice->discount_amount > 0)
                                        <tr>
                                            <td><strong>Discount:</strong></td>
                                            <td class="text-right text-success">-{{ $invoice->currency_code }} {{ number_format($invoice->discount_amount, 2) }}</td>
                                        </tr>
                                    @endif
                                    @if($invoice->tax_amount > 0)
                                        <tr>
                                            <td><strong>Tax:</strong></td>
                                            <td class="text-right">{{ $invoice->currency_code }} {{ number_format($invoice->tax_amount, 2) }}</td>
                                        </tr>
                                    @endif
                                    @if($invoice->shipping_amount > 0)
                                        <tr>
                                            <td><strong>Shipping:</strong></td>
                                            <td class="text-right">{{ $invoice->currency_code }} {{ number_format($invoice->shipping_amount, 2) }}</td>
                                        </tr>
                                    @endif
                                    <tr class="table-warning">
                                        <td><strong>Total:</strong></td>
                                        <td class="text-right"><strong>{{ $invoice->currency_code }} {{ number_format($invoice->total_amount, 2) }}</strong></td>
                                    </tr>
                                    @if($invoice->paid_amount > 0)
                                        <tr class="table-success">
                                            <td><strong>Paid:</strong></td>
                                            <td class="text-right"><strong>{{ $invoice->currency_code }} {{ number_format($invoice->paid_amount, 2) }}</strong></td>
                                        </tr>
                                        <tr class="table-info">
                                            <td><strong>Balance:</strong></td>
                                            <td class="text-right"><strong>{{ $invoice->currency_code }} {{ number_format($invoice->balance_amount, 2) }}</strong></td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                        </div>

                        <!-- Terms & Footer -->
                        @if($invoice->terms_conditions)
                            <div class="mt-4">
                                <strong>Terms & Conditions:</strong><br>
                                {{ $invoice->terms_conditions }}
                            </div>
                        @endif

                        @if($invoice->footer_text)
                            <div class="mt-3 text-center">
                                <em>{{ $invoice->footer_text }}</em>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Invoice Info -->
                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Invoice Information</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td>Created by:</td>
                                <td>{{ $invoice->creator->name ?? 'System' }}</td>
                            </tr>
                            <tr>
                                <td>Created:</td>
                                <td>{{ $invoice->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @if($invoice->sent_at)
                                <tr>
                                    <td>Sent:</td>
                                    <td>{{ $invoice->sent_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endif
                            @if($invoice->viewed_at)
                                <tr>
                                    <td>Viewed:</td>
                                    <td>{{ $invoice->viewed_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endif
                            @if($invoice->paid_at)
                                <tr>
                                    <td>Paid:</td>
                                    <td>{{ $invoice->paid_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>

                <!-- Payment History -->
                @if($invoice->payments->count() > 0)
                    <div class="block block-themed">
                        <div class="block-header">
                            <h3 class="block-title">Payment History</h3>
                        </div>
                        <div class="block-content">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($invoice->payments as $payment)
                                            <tr>
                                                <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                                <td>{{ $invoice->currency_code }} {{ number_format($payment->amount, 2) }}</td>
                                                <td>
                                                    {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                                                    @if($payment->reference_number)
                                                        <br><small class="text-muted">Ref: {{ $payment->reference_number }}</small>
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

                <!-- Related Documents -->
                @if($invoice->parentDocument || $invoice->childDocuments->count() > 0)
                    <div class="block block-themed">
                        <div class="block-header">
                            <h3 class="block-title">Related Documents</h3>
                        </div>
                        <div class="block-content">
                            @if($invoice->parentDocument)
                                <div class="mb-2">
                                    <strong>Converted from:</strong><br>
                                    <a href="{{ route('billing.invoices.show', $invoice->parentDocument) }}">
                                        {{ ucfirst($invoice->parentDocument->document_type) }}
                                        {{ $invoice->parentDocument->document_number }}
                                    </a>
                                </div>
                            @endif

                            @foreach($invoice->childDocuments as $child)
                                <div class="mb-2">
                                    <strong>{{ ucfirst($child->document_type) }}:</strong><br>
                                    <a href="{{ route('billing.invoices.show', $child) }}">
                                        {{ $child->document_number }}
                                    </a>
                                </div>
                            @endforeach
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
            <form method="POST" action="{{ route('billing.invoices.send-email', $invoice) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Send Invoice via Email</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>To Email</label>
                        <input type="email" name="email" class="form-control"
                               value="{{ $invoice->client->email }}" required>
                    </div>
                    <div class="form-group">
                        <label>CC (Optional)</label>
                        <input type="text" name="cc" class="form-control"
                               placeholder="Multiple emails separated by commas">
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" class="form-control"
                               value="Invoice {{ $invoice->document_number }}" required>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" class="form-control" rows="6" required>Dear {{ $invoice->client->first_name }} {{ $invoice->client->last_name }},

Please find attached invoice {{ $invoice->document_number }} for {{ $invoice->currency_code }} {{ number_format($invoice->total_amount, 2) }}.

@if($invoice->due_date)
Payment is due by {{ $invoice->due_date->format('d/m/Y') }}.
@endif

Thank you for your business!

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

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('billing.invoices.payment', $invoice) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Amount (Max: {{ $invoice->currency_code }} {{ number_format($invoice->balance_amount, 2) }})</label>
                        <input type="number" name="amount" class="form-control"
                               max="{{ $invoice->balance_amount }}" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Date</label>
                        <input type="text" name="payment_date" class="form-control datepicker"
                               value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method" class="form-control" required>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="online">Online Payment</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reference Number</label>
                        <input type="text" name="reference_number" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reminder Modal -->
<div class="modal fade" id="reminderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('billing.invoices.send-reminder', $invoice) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Send Payment Reminder</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>To Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="{{ $invoice->client->email }}" required>
                    </div>
                    <div class="form-group">
                        <label>CC Email (separate multiple emails with commas)</label>
                        <input type="text" name="cc" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Reminder Type</label>
                        <select name="reminder_type" class="form-control" id="reminderType" onchange="updateReminderSubject()" required>
                            <option value="manual">Manual Reminder</option>
                            <option value="before_due">Before Due Date</option>
                            <option value="overdue">Overdue Payment</option>
                            <option value="late_fee">Late Fee Applied</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" class="form-control" id="reminderSubject"
                               value="Payment Reminder - Invoice {{ $invoice->document_number }}" required>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" class="form-control" rows="6" id="reminderMessage" required>Dear {{ $invoice->client->first_name }} {{ $invoice->client->last_name }},

This is a friendly reminder regarding your outstanding invoice payment.

Invoice Number: {{ $invoice->document_number }}
Due Date: {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A' }}
Outstanding Amount: {{ $invoice->currency_code ?? 'TZS' }} {{ number_format($invoice->balance_amount, 2) }}

Please arrange payment at your earliest convenience. If you have already made this payment, please disregard this reminder.

Thank you for your business!

Best regards,
{{ config('app.name') }} Team</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fa fa-bell"></i> Send Reminder
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Late Fee Modal -->
<div class="modal fade" id="lateFeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('billing.invoices.apply-late-fee', $invoice) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Apply Late Fee</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        This will add a late fee to the invoice. This action cannot be undone.
                    </div>
                    <div class="form-group">
                        <label>Late Fee Percentage</label>
                        <div class="input-group">
                            <input type="number" name="late_fee_percentage" class="form-control" 
                                   value="10" min="0" max="100" step="0.01" id="lateFeePercentage" 
                                   onchange="calculateLateFee()" required>
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Late Fee Amount</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $invoice->currency_code ?? 'TZS' }}</span>
                            </div>
                            <input type="text" class="form-control" id="lateFeeAmount" readonly>
                        </div>
                        <small class="text-muted">
                            Calculated on original amount: {{ $invoice->currency_code ?? 'TZS' }} {{ number_format($invoice->total_amount - $invoice->late_fee_amount, 2) }}
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa fa-plus"></i> Apply Late Fee
                    </button>
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

function recordPaymentModal() {
    $('#paymentModal').modal('show');
}

function sendReminderModal() {
    $('#reminderModal').modal('show');
}

function applyLateFeeModal() {
    $('#lateFeeModal').modal('show');
    calculateLateFee();
}

function updateReminderSubject() {
    const reminderType = document.getElementById('reminderType').value;
    const subjectField = document.getElementById('reminderSubject');
    const messageField = document.getElementById('reminderMessage');
    const invoiceNumber = '{{ $invoice->document_number }}';
    
    switch(reminderType) {
        case 'before_due':
            subjectField.value = `Payment Reminder - Invoice ${invoiceNumber} (Due Soon)`;
            break;
        case 'overdue':
            subjectField.value = `Overdue Payment Notice - Invoice ${invoiceNumber}`;
            break;
        case 'late_fee':
            subjectField.value = `Late Fee Applied - Invoice ${invoiceNumber}`;
            break;
        default:
            subjectField.value = `Payment Reminder - Invoice ${invoiceNumber}`;
    }
}

function calculateLateFee() {
    const percentage = parseFloat(document.getElementById('lateFeePercentage').value) || 0;
    const originalAmount = {{ $invoice->total_amount - $invoice->late_fee_amount }};
    const lateFeeAmount = (originalAmount * percentage) / 100;
    
    document.getElementById('lateFeeAmount').value = lateFeeAmount.toFixed(2);
}
</script>
{{--@endpush--}}

@endsection

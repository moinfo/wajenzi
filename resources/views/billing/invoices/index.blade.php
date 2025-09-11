@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>Invoices</h1>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('billing.invoices.create') }}" class="btn btn-success">
                        <i class="fa fa-plus"></i> New Invoice
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
                            <form method="GET" action="{{ route('billing.invoices.index') }}" class="row">
                                <div class="col-md-3">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Status</span>
                                        </div>
                                        <select name="status" class="form-control">
                                            <option value="">All Statuses</option>
                                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                                            <option value="viewed" {{ request('status') == 'viewed' ? 'selected' : '' }}>Viewed</option>
                                            <option value="partial_paid" {{ request('status') == 'partial_paid' ? 'selected' : '' }}>Partial Paid</option>
                                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                            <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Client</span>
                                        </div>
                                        <select name="client_id" class="form-control">
                                            <option value="">All Clients</option>
                                            @foreach($clients as $client)
                                                <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                                    {{ $client->company_name }}
                                                </option>
                                            @endforeach
                                        </select>
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
                                
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="{{ route('billing.invoices.index') }}" class="btn btn-secondary">Clear</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Invoices Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Client</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)
                                <tr>
                                    <td>
                                        <strong>{{ $invoice->document_number }}</strong>
                                        @if($invoice->reference_number)
                                            <br><small class="text-muted">Ref: {{ $invoice->reference_number }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $invoice->client->company_name }}
                                        @if($invoice->client->contact_person)
                                            <br><small class="text-muted">{{ $invoice->client->contact_person }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $invoice->issue_date->format('d/m/Y') }}</td>
                                    <td>
                                        @if($invoice->due_date)
                                            {{ $invoice->due_date->format('d/m/Y') }}
                                            @if($invoice->is_overdue)
                                                <br><small class="text-danger">{{ $invoice->due_date->diffForHumans() }}</small>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $invoice->currency_code }} {{ number_format($invoice->total_amount, 2) }}</td>
                                    <td>{{ $invoice->currency_code }} {{ number_format($invoice->paid_amount, 2) }}</td>
                                    <td>{{ $invoice->currency_code }} {{ number_format($invoice->balance_amount, 2) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $invoice->status_color }}">
                                            {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('billing.invoices.show', $invoice) }}" 
                                               class="btn btn-sm btn-info" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            
                                            @if($invoice->is_editable)
                                                <a href="{{ route('billing.invoices.edit', $invoice) }}" 
                                                   class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                            @endif
                                            
                                            <a href="{{ route('billing.invoices.pdf', $invoice) }}" 
                                               class="btn btn-sm btn-secondary" title="Download PDF" target="_blank">
                                                <i class="fa fa-download"></i>
                                            </a>
                                            
                                            @if(!$invoice->is_paid && $invoice->balance_amount > 0)
                                                <button type="button" class="btn btn-sm btn-success" 
                                                        onclick="recordPayment({{ $invoice->id }})" title="Record Payment">
                                                    <i class="fa fa-money"></i>
                                                </button>
                                            @endif
                                            
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" 
                                                        data-toggle="dropdown">
                                                    <i class="fa fa-ellipsis-h"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="{{ route('billing.invoices.duplicate', $invoice) }}">
                                                        <i class="fa fa-copy"></i> Duplicate
                                                    </a>
                                                    @if($invoice->status !== 'void')
                                                        <a class="dropdown-item text-warning" 
                                                           href="{{ route('billing.invoices.void', $invoice) }}"
                                                           onclick="return confirm('Are you sure you want to void this invoice?')">
                                                            <i class="fa fa-ban"></i> Void
                                                        </a>
                                                    @endif
                                                    @if($invoice->is_editable)
                                                        <div class="dropdown-divider"></div>
                                                        <a class="dropdown-item text-danger" 
                                                           href="{{ route('billing.invoices.destroy', $invoice) }}"
                                                           onclick="return confirm('Are you sure you want to delete this invoice?')">
                                                            <i class="fa fa-trash"></i> Delete
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fa fa-inbox fa-3x mb-3"></i>
                                            <br>No invoices found
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($invoices->hasPages())
                    <div class="d-flex justify-content-center">
                        {{ $invoices->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="paymentForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Amount</label>
                        <input type="number" name="amount" class="form-control" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Date</label>
                        <input type="text" name="payment_date" class="form-control datepicker" value="{{ date('Y-m-d') }}" required>
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

@push('scripts')
<script>
function recordPayment(invoiceId) {
    $('#paymentForm').attr('action', `/billing/invoices/${invoiceId}/payment`);
    $('#paymentModal').modal('show');
}
</script>
@endpush

@endsection
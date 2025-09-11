@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>Quotations</h1>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('billing.quotations.create') }}" class="btn btn-success">
                        <i class="fa fa-plus"></i> New Quotation
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
                            <form method="GET" action="{{ route('billing.quotations.index') }}" class="row">
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
                                            <option value="accepted" {{ request('status') == 'accepted' ? 'selected' : '' }}>Accepted</option>
                                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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
                                                    {{ $client->first_name }} {{ $client->last_name }}
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
                                    <a href="{{ route('billing.quotations.index') }}" class="btn btn-secondary">Clear</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Quotations Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Quote #</th>
                                <th>Client</th>
                                <th>Issue Date</th>
                                <th>Valid Until</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                            <tbody>
                                @forelse($quotations as $quotation)
                                    <tr>
                                        <td>
                                            <a href="{{ route('billing.quotations.show', $quotation) }}" class="text-primary font-weight-bold">
                                                {{ $quotation->document_number }}
                                            </a>
                                        </td>
                                        <td>
                                            <strong>{{ $quotation->client->first_name }} {{ $quotation->client->last_name }}</strong>
                                            @if($quotation->client->contact_person)
                                                <br><small class="text-muted">{{ $quotation->client->contact_person }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $quotation->issue_date->format('d/m/Y') }}</td>
                                        <td>
                                            @if($quotation->valid_until_date)
                                                {{ $quotation->valid_until_date->format('d/m/Y') }}
                                                @if($quotation->valid_until_date->isPast() && !in_array($quotation->status, ['accepted', 'cancelled']))
                                                    <br><small class="text-warning">Expired</small>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <strong>{{ $quotation->currency_code }} {{ number_format($quotation->total_amount, 2) }}</strong>
                                        </td>
                                        <td>
                                            @php
                                                $statusClass = match($quotation->status) {
                                                    'draft' => 'badge-secondary',
                                                    'pending' => 'badge-info', 
                                                    'sent' => 'badge-primary',
                                                    'viewed' => 'badge-warning text-dark',
                                                    'accepted' => 'badge-success',
                                                    'rejected' => 'badge-danger',
                                                    'cancelled' => 'badge-dark',
                                                    default => 'badge-secondary'
                                                };
                                            @endphp
                                            <span class="badge {{ $statusClass }}">
                                                {{ ucfirst(str_replace('_', ' ', $quotation->status)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('billing.quotations.show', $quotation) }}" 
                                                   class="btn btn-sm btn-outline-primary" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                
                                                @if($quotation->is_editable)
                                                    <a href="{{ route('billing.quotations.edit', $quotation) }}" 
                                                       class="btn btn-sm btn-outline-secondary" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                @endif
                                                
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split" 
                                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <span class="sr-only">Toggle Dropdown</span>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        <a class="dropdown-item" href="{{ route('billing.quotations.pdf', $quotation) }}" target="_blank">
                                                            <i class="fa fa-file-pdf"></i> PDF
                                                        </a>
                                                        <a class="dropdown-item" href="{{ route('billing.quotations.duplicate', $quotation) }}">
                                                            <i class="fa fa-copy"></i> Duplicate
                                                        </a>
                                                        
                                                        @if($quotation->status == 'accepted')
                                                            <div class="dropdown-divider"></div>
                                                            <form action="{{ route('billing.quotations.convert', $quotation) }}" method="POST" class="d-inline"
                                                                  onsubmit="return confirm('Convert this quotation to invoice?')">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item text-success">
                                                                    <i class="fa fa-exchange-alt"></i> Convert to Invoice
                                                                </button>
                                                            </form>
                                                        @endif
                                                        
                                                        @if($quotation->is_editable)
                                                            <div class="dropdown-divider"></div>
                                                            <form action="{{ route('billing.quotations.destroy', $quotation) }}" 
                                                                  method="POST" class="d-inline"
                                                                  onsubmit="return confirm('Are you sure you want to cancel this quotation?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i class="fa fa-ban"></i> Cancel
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fa fa-file-text-o fa-3x mb-3"></i>
                                                <h5>No quotations found</h5>
                                                <p>Create your first quotation to get started</p>
                                                <a href="{{ route('billing.quotations.create') }}" class="btn btn-success">
                                                    <i class="fa fa-plus"></i> New Quotation
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($quotations->hasPages())
                        <div class="text-center">
                            {{ $quotations->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
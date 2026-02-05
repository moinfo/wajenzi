@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>{{ $statusTitle }}</h1>
                    <span class="badge badge-{{ $currentStatus === 'paid' ? 'success' : ($currentStatus === 'overdue' ? 'danger' : ($currentStatus === 'draft' ? 'secondary' : ($currentStatus === 'cancelled' ? 'warning' : 'info'))) }} badge-lg">
                        {{ $invoices->total() }} {{ $invoices->total() === 1 ? 'Invoice' : 'Invoices' }}
                    </span>
                </div>
                <div class="col-md-6 text-right">
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-filter"></i> Filter by Status
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item {{ request()->routeIs('billing.invoices.index') ? 'active' : '' }}" 
                               href="{{ route('billing.invoices.index') }}">
                                <i class="fa fa-list"></i> All Invoices
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item {{ request()->routeIs('billing.invoices.paid') ? 'active' : '' }}" 
                               href="{{ route('billing.invoices.paid') }}">
                                <i class="fa fa-check-circle text-success"></i> Paid
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('billing.invoices.unpaid') ? 'active' : '' }}" 
                               href="{{ route('billing.invoices.unpaid') }}">
                                <i class="fa fa-clock text-info"></i> Unpaid
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('billing.invoices.overdue') ? 'active' : '' }}" 
                               href="{{ route('billing.invoices.overdue') }}">
                                <i class="fa fa-exclamation-triangle text-danger"></i> Overdue
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('billing.invoices.draft') ? 'active' : '' }}" 
                               href="{{ route('billing.invoices.draft') }}">
                                <i class="fa fa-edit text-secondary"></i> Draft
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('billing.invoices.cancelled') ? 'active' : '' }}" 
                               href="{{ route('billing.invoices.cancelled') }}">
                                <i class="fa fa-times text-warning"></i> Cancelled
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('billing.invoices.refunded') ? 'active' : '' }}" 
                               href="{{ route('billing.invoices.refunded') }}">
                                <i class="fa fa-undo text-primary"></i> Refunded
                            </a>
                        </div>
                    </div>
                    @if($currentStatus !== 'cancelled' && $currentStatus !== 'refunded')
                        <a href="{{ route('billing.invoices.create') }}" class="btn btn-success">
                            <i class="fa fa-plus"></i> New Invoice
                        </a>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="block block-themed">
            <div class="block-content">
                
                <!-- Filters -->
                <div class="row no-print m-t-10">
                    <div class="col-md-12">
                        <div class="card-box">
                            <form method="GET" action="{{ request()->url() }}" class="row">
                                <div class="col-md-4">
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
                                <div class="col-md-3">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">From</span>
                                        </div>
                                        <input type="text" name="from_date" class="form-control datepicker" 
                                               placeholder="From Date" value="{{ request('from_date') }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">To</span>
                                        </div>
                                        <input type="text" name="to_date" class="form-control datepicker" 
                                               placeholder="To Date" value="{{ request('to_date') }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fa fa-search"></i> Filter
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Invoices Table -->
                <div class="table-responsive">
                    <table class="table table-hover table-vcenter">
                        <thead>
                            <tr>
                                <th width="12%">Invoice #</th>
                                <th width="15%">Client</th>
                                <th width="10%">Issue Date</th>
                                @if($currentStatus !== 'draft')
                                    <th width="10%">Due Date</th>
                                @endif
                                <th width="12%" class="text-right">Amount</th>
                                @if($currentStatus === 'paid')
                                    <th width="12%" class="text-right">Paid Amount</th>
                                @elseif($currentStatus === 'unpaid')
                                    <th width="12%" class="text-right">Balance</th>
                                @elseif($currentStatus === 'overdue')
                                    <th width="8%">Days Overdue</th>
                                    <th width="12%" class="text-right">Balance</th>
                                @endif
                                <th width="10%">Status</th>
                                <th width="15%" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)
                                <tr>
                                    <td>
                                        <a href="{{ route('billing.invoices.show', $invoice) }}" class="font-weight-bold text-primary">
                                            {{ $invoice->document_number }}
                                        </a>
                                        @if($invoice->reference_number)
                                            <br><small class="text-muted">Ref: {{ $invoice->reference_number }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $invoice->client->company_name }}</strong>
                                        @if($invoice->client->contact_person)
                                            <br><small class="text-muted">{{ $invoice->client->contact_person }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $invoice->issue_date->format('d/m/Y') }}</td>
                                    @if($currentStatus !== 'draft')
                                        <td>
                                            {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A' }}
                                            @if($invoice->due_date && $invoice->due_date->isPast() && $currentStatus !== 'paid')
                                                <br><small class="text-danger">{{ $invoice->due_date->diffForHumans() }}</small>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="text-right">
                                        <strong>{{ $invoice->currency_code }} {{ number_format($invoice->total_amount, 2) }}</strong>
                                    </td>
                                    @if($currentStatus === 'paid')
                                        <td class="text-right">
                                            <span class="text-success font-weight-bold">
                                                {{ $invoice->currency_code }} {{ number_format($invoice->paid_amount, 2) }}
                                            </span>
                                        </td>
                                    @elseif($currentStatus === 'unpaid')
                                        <td class="text-right">
                                            <span class="text-warning font-weight-bold">
                                                {{ $invoice->currency_code }} {{ number_format($invoice->balance_amount, 2) }}
                                            </span>
                                        </td>
                                    @elseif($currentStatus === 'overdue')
                                        <td class="text-center">
                                            @if($invoice->due_date)
                                                <span class="badge badge-danger">
                                                    {{ now()->diffInDays($invoice->due_date) }} days
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <span class="text-danger font-weight-bold">
                                                {{ $invoice->currency_code }} {{ number_format($invoice->balance_amount, 2) }}
                                            </span>
                                        </td>
                                    @endif
                                    <td>
                                        <span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'overdue' ? 'danger' : ($invoice->status === 'draft' ? 'secondary' : ($invoice->status === 'cancelled' || $invoice->status === 'void' ? 'warning' : 'info'))) }}">
                                            {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('billing.invoices.show', $invoice) }}" class="btn btn-primary" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            @if($invoice->is_editable)
                                                <a href="{{ route('billing.invoices.edit', $invoice) }}" class="btn btn-secondary" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                            @endif
                                            <a href="{{ route('billing.invoices.pdf', $invoice) }}" class="btn btn-info" title="PDF" target="_blank">
                                                <i class="fa fa-file-pdf"></i>
                                            </a>
                                            @if($currentStatus !== 'cancelled' && $currentStatus !== 'refunded')
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown">
                                                        <i class="fa fa-cog"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        <a class="dropdown-item" href="{{ route('billing.invoices.duplicate', $invoice) }}">
                                                            <i class="fa fa-copy"></i> Duplicate
                                                        </a>
                                                        @if($invoice->balance_amount > 0)
                                                            <button type="button" class="dropdown-item" onclick="recordPaymentModal({{ $invoice->id }}, '{{ $invoice->document_number }}', {{ $invoice->balance_amount }})">
                                                                <i class="fa fa-credit-card"></i> Record Payment
                                                            </button>
                                                        @endif
                                                        @if($invoice->status !== 'paid')
                                                            <div class="dropdown-divider"></div>
                                                            <form action="{{ route('billing.invoices.void', $invoice) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item text-warning" onclick="return confirm('Are you sure you want to void this invoice?')">
                                                                    <i class="fa fa-ban"></i> Void
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $currentStatus === 'overdue' ? '8' : '7' }}" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fa fa-inbox fa-3x mb-3"></i>
                                            <h4>No {{ strtolower($statusTitle) }} found</h4>
                                            <p>There are no invoices matching your criteria.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($invoices->hasPages())
                    <div class="row">
                        <div class="col-md-12">
                            {{ $invoices->appends(request()->query())->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
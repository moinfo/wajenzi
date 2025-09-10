@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>Clients</h1>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ url('/project_clients') }}" class="btn btn-info">
                        <i class="fa fa-users"></i> Project Clients
                    </a>
                    <a href="{{ route('billing.clients.create') }}" class="btn btn-success">
                        <i class="fa fa-plus"></i> New Client
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
                            <form method="GET" action="{{ route('billing.clients.index') }}" class="row">
                                <div class="col-md-3">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Search</span>
                                        </div>
                                        <input type="text" name="search" class="form-control" 
                                               value="{{ request('search') }}" placeholder="Company, contact, email...">
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Type</span>
                                        </div>
                                        <select name="client_type" class="form-control">
                                            <option value="">All Types</option>
                                            <option value="customer" {{ request('client_type') == 'customer' ? 'selected' : '' }}>Customer</option>
                                            <option value="vendor" {{ request('client_type') == 'vendor' ? 'selected' : '' }}>Vendor</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Status</span>
                                        </div>
                                        <select name="status" class="form-control">
                                            <option value="">All Statuses</option>
                                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="{{ route('billing.clients.index') }}" class="btn btn-secondary">Clear</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Clients Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Contact Person</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Balance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clients as $client)
                                <tr>
                                    <td>
                                        <a href="{{ route('billing.clients.show', $client) }}" class="text-primary font-weight-bold">
                                            {{ $client->company_name }}
                                        </a>
                                        @if($client->tax_identification_number)
                                            <br><small class="text-muted">TIN: {{ $client->tax_identification_number }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $client->contact_person ?? '-' }}</td>
                                    <td>{{ $client->email ?? '-' }}</td>
                                    <td>{{ $client->phone ?? '-' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $client->client_type == 'customer' ? 'primary' : 'info' }}">
                                            {{ ucfirst($client->client_type) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $client->is_active ? 'success' : 'secondary' }}">
                                            {{ $client->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        @if($client->balance_amount != 0)
                                            <span class="text-{{ $client->balance_amount > 0 ? 'danger' : 'success' }}">
                                                TZS {{ number_format(abs($client->balance_amount), 2) }}
                                                {{ $client->balance_amount > 0 ? '(Owed)' : '(Credit)' }}
                                            </span>
                                        @else
                                            <span class="text-muted">TZS 0.00</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('billing.clients.show', $client) }}" 
                                               class="btn btn-sm btn-outline-primary" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            
                                            <a href="{{ route('billing.clients.edit', $client) }}" 
                                               class="btn btn-sm btn-outline-secondary" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split" 
                                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <span class="sr-only">Toggle Dropdown</span>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <a class="dropdown-item" href="{{ route('billing.clients.statement', $client) }}" target="_blank">
                                                        <i class="fa fa-file-text"></i> Statement
                                                    </a>
                                                    
                                                    @if($client->client_type == 'customer')
                                                        <div class="dropdown-divider"></div>
                                                        <a class="dropdown-item" href="{{ route('billing.invoices.create', ['client_id' => $client->id]) }}">
                                                            <i class="fa fa-file-invoice"></i> New Invoice
                                                        </a>
                                                        <a class="dropdown-item" href="{{ route('billing.quotations.create', ['client_id' => $client->id]) }}">
                                                            <i class="fa fa-file-text"></i> New Quotation
                                                        </a>
                                                        <a class="dropdown-item" href="{{ route('billing.proformas.create', ['client_id' => $client->id]) }}">
                                                            <i class="fa fa-file"></i> New Proforma
                                                        </a>
                                                    @endif
                                                    
                                                    <div class="dropdown-divider"></div>
                                                    <form action="{{ route('billing.clients.destroy', $client) }}" 
                                                          method="POST" class="d-inline"
                                                          onsubmit="return confirm('Are you sure you want to delete this client?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="fa fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fa fa-users fa-3x mb-3"></i>
                                            <h5>No clients found</h5>
                                            <p>Add your first client to get started with billing</p>
                                            <a href="{{ route('billing.clients.create') }}" class="btn btn-success">
                                                <i class="fa fa-plus"></i> New Client
                                            </a>
                                            <a href="{{ url('/project_clients') }}" class="btn btn-info ml-2">
                                                <i class="fa fa-users"></i> View Project Clients
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($clients->hasPages())
                    <div class="text-center">
                        {{ $clients->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
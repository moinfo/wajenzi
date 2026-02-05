@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>Add New Client</h1>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ url('/project_clients') }}" class="btn btn-info">
                        <i class="fa fa-users"></i> View Project Clients
                    </a>
                    <a href="{{ route('billing.clients.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Clients
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Client Information</h3>
                    </div>
                    <div class="block-content">
                        <form method="POST" action="{{ route('billing.clients.store') }}">
                            @csrf

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="client_type">Client Type <span class="text-danger">*</span></label>
                                        <select name="client_type" id="client_type" class="form-control @error('client_type') is-invalid @enderror" required>
                                            <option value="">Select client type...</option>
                                            <option value="customer" {{ old('client_type') == 'customer' ? 'selected' : '' }}>Customer</option>
                                            <option value="vendor" {{ old('client_type') == 'vendor' ? 'selected' : '' }}>Vendor</option>
                                        </select>
                                        @error('client_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="company_name">Company Name <span class="text-danger">*</span></label>
                                        <input type="text" name="company_name" id="company_name" 
                                               class="form-control @error('company_name') is-invalid @enderror" 
                                               value="{{ old('company_name') }}" required>
                                        @error('company_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="contact_person">Contact Person</label>
                                        <input type="text" name="contact_person" id="contact_person" 
                                               class="form-control @error('contact_person') is-invalid @enderror" 
                                               value="{{ old('contact_person') }}">
                                        @error('contact_person')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" name="email" id="email" 
                                               class="form-control @error('email') is-invalid @enderror" 
                                               value="{{ old('email') }}">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Phone</label>
                                        <input type="text" name="phone" id="phone" 
                                               class="form-control @error('phone') is-invalid @enderror" 
                                               value="{{ old('phone') }}">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tax_identification_number">TIN Number</label>
                                        <input type="text" name="tax_identification_number" id="tax_identification_number" 
                                               class="form-control @error('tax_identification_number') is-invalid @enderror" 
                                               value="{{ old('tax_identification_number') }}">
                                        @error('tax_identification_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <h5 class="mt-4">Billing Address</h5>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="billing_address_line1">Address Line 1</label>
                                        <input type="text" name="billing_address_line1" id="billing_address_line1" 
                                               class="form-control @error('billing_address_line1') is-invalid @enderror" 
                                               value="{{ old('billing_address_line1') }}">
                                        @error('billing_address_line1')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="billing_address_line2">Address Line 2</label>
                                        <input type="text" name="billing_address_line2" id="billing_address_line2" 
                                               class="form-control @error('billing_address_line2') is-invalid @enderror" 
                                               value="{{ old('billing_address_line2') }}">
                                        @error('billing_address_line2')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="billing_city">City</label>
                                        <input type="text" name="billing_city" id="billing_city" 
                                               class="form-control @error('billing_city') is-invalid @enderror" 
                                               value="{{ old('billing_city') }}">
                                        @error('billing_city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="billing_postal_code">Postal Code</label>
                                        <input type="text" name="billing_postal_code" id="billing_postal_code" 
                                               class="form-control @error('billing_postal_code') is-invalid @enderror" 
                                               value="{{ old('billing_postal_code') }}">
                                        @error('billing_postal_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="billing_country">Country</label>
                                        <input type="text" name="billing_country" id="billing_country" 
                                               class="form-control @error('billing_country') is-invalid @enderror" 
                                               value="{{ old('billing_country', 'Tanzania') }}">
                                        @error('billing_country')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <h5 class="mt-4">Business Settings</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_terms">Payment Terms</label>
                                        <select name="payment_terms" id="payment_terms" class="form-control @error('payment_terms') is-invalid @enderror">
                                            <option value="net_30" {{ old('payment_terms', 'net_30') == 'net_30' ? 'selected' : '' }}>Net 30</option>
                                            <option value="net_15" {{ old('payment_terms') == 'net_15' ? 'selected' : '' }}>Net 15</option>
                                            <option value="net_7" {{ old('payment_terms') == 'net_7' ? 'selected' : '' }}>Net 7</option>
                                            <option value="due_on_receipt" {{ old('payment_terms') == 'due_on_receipt' ? 'selected' : '' }}>Due on Receipt</option>
                                            <option value="custom" {{ old('payment_terms') == 'custom' ? 'selected' : '' }}>Custom</option>
                                        </select>
                                        @error('payment_terms')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="credit_limit">Credit Limit</label>
                                        <input type="number" name="credit_limit" id="credit_limit" step="0.01" min="0"
                                               class="form-control @error('credit_limit') is-invalid @enderror" 
                                               value="{{ old('credit_limit', 0) }}">
                                        @error('credit_limit')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="notes">Notes</label>
                                        <textarea name="notes" id="notes" rows="3" 
                                                  class="form-control @error('notes') is-invalid @enderror" 
                                                  placeholder="Additional notes about this client...">{{ old('notes') }}</textarea>
                                        @error('notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-success">
                                    <i class="fa fa-check"></i> Create Client
                                </button>
                                <a href="{{ route('billing.clients.index') }}" class="btn btn-secondary">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Quick Actions</h3>
                    </div>
                    <div class="block-content">
                        <a href="{{ url('/project_clients') }}" class="btn btn-info btn-block">
                            <i class="fa fa-users"></i> View Project Clients
                        </a>
                        <small class="text-muted">View and manage clients from your project management system</small>
                    </div>
                </div>

                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Client Types</h3>
                    </div>
                    <div class="block-content">
                        <ul class="list-unstyled">
                            <li><strong>Customer:</strong> Clients who purchase from you</li>
                            <li><strong>Vendor:</strong> Suppliers or service providers</li>
                        </ul>
                    </div>
                </div>

                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Payment Terms</h3>
                    </div>
                    <div class="block-content">
                        <ul class="list-unstyled">
                            <li><strong>Net 30:</strong> Payment due in 30 days</li>
                            <li><strong>Net 15:</strong> Payment due in 15 days</li>
                            <li><strong>Net 7:</strong> Payment due in 7 days</li>
                            <li><strong>Due on Receipt:</strong> Payment due immediately</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
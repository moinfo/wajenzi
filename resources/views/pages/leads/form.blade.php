@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">
                {{ isset($object->id) ? 'Edit' : 'Create' }} Lead
            </h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item">Projects</li>
                    <li class="breadcrumb-item"><a href="{{ route('leads.index') }}">Leads</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ isset($object->id) ? 'Edit' : 'Create' }}</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ isset($object->id) ? route('leads.update', $object->id) : route('leads.store') }}">
        @csrf
        @if(isset($object->id))
            @method('PUT')
        @endif

        <!-- Basic Information -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Basic Information</h3>
            </div>
            <div class="block-content">
                <div class="row">
                    @if(isset($object->id) && $object->lead_number)
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Lead Number</label>
                            <input type="text" class="form-control" value="{{ $object->lead_number }}" readonly disabled>
                        </div>
                    </div>
                    @endif
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="lead_date">Lead Date</label>
                            <input type="date" class="form-control @error('lead_date') is-invalid @enderror"
                                   id="lead_date" name="lead_date"
                                   value="{{ old('lead_date', $object->lead_date ? $object->lead_date->format('Y-m-d') : now()->format('Y-m-d')) }}">
                            @error('lead_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-{{ isset($object->id) && $object->lead_number ? '4' : '5' }}">
                        <div class="form-group">
                            <label for="client_id">Select Existing Client <small class="text-muted">(optional)</small></label>
                            <select class="form-control @error('client_id') is-invalid @enderror"
                                    id="client_id" name="client_id">
                                <option value="">-- New Client / Manual Entry --</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}"
                                            data-name="{{ $client->first_name }} {{ $client->last_name }}"
                                            data-phone="{{ $client->phone_number }}"
                                            data-email="{{ $client->email }}"
                                            data-address="{{ $client->address }}"
                                            {{ old('client_id', $object->client_id) == $client->id ? 'selected' : '' }}>
                                        {{ $client->first_name }} {{ $client->last_name }} - {{ $client->phone_number }}
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-{{ isset($object->id) && $object->lead_number ? '4' : '5' }}">
                        <div class="form-group">
                            <label for="name" class="required">Client Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name"
                                   value="{{ old('name', $object->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="phone" class="required">Phone</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                   id="phone" name="phone"
                                   value="{{ old('phone', $object->phone) }}" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email"
                                   value="{{ old('email', $object->email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="lead_source_id" class="required">Lead Source</label>
                            <select class="form-control @error('lead_source_id') is-invalid @enderror"
                                    id="lead_source_id" name="lead_source_id" required>
                                <option value="">Select Source</option>
                                @foreach($leadSources as $source)
                                    <option value="{{ $source->id }}"
                                            {{ old('lead_source_id', $object->lead_source_id) == $source->id ? 'selected' : '' }}>
                                        {{ $source->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('lead_source_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service & Location -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Service & Location</h3>
            </div>
            <div class="block-content">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="service_interested_id" class="required">Service Interested</label>
                            <select class="form-control @error('service_interested_id') is-invalid @enderror"
                                    id="service_interested_id" name="service_interested_id" required>
                                <option value="">Select Service</option>
                                @foreach($serviceInteresteds as $service)
                                    <option value="{{ $service->id }}"
                                            {{ old('service_interested_id', $object->service_interested_id) == $service->id ? 'selected' : '' }}>
                                        {{ $service->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('service_interested_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="site_location">Site Location</label>
                            <input type="text" class="form-control @error('site_location') is-invalid @enderror"
                                   id="site_location" name="site_location"
                                   value="{{ old('site_location', $object->site_location) }}"
                                   placeholder="e.g. Kigamboni">
                            @error('site_location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" class="form-control @error('city') is-invalid @enderror"
                                   id="city" name="city"
                                   value="{{ old('city', $object->city) }}"
                                   placeholder="e.g. Dar es Salaam">
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="address">Full Address</label>
                            <textarea class="form-control @error('address') is-invalid @enderror"
                                      id="address" name="address" rows="2"
                                      placeholder="Full address (optional)">{{ old('address', $object->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lead Details & Assignment -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Lead Details & Assignment</h3>
            </div>
            <div class="block-content">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="estimated_value">Estimated Value (TZS)</label>
                            <input type="number" step="0.01" min="0"
                                   class="form-control @error('estimated_value') is-invalid @enderror"
                                   id="estimated_value" name="estimated_value"
                                   value="{{ old('estimated_value', $object->estimated_value) }}"
                                   placeholder="0.00">
                            @error('estimated_value')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="lead_status_id" class="required">Lead Status</label>
                            <select class="form-control @error('lead_status_id') is-invalid @enderror"
                                    id="lead_status_id" name="lead_status_id" required>
                                <option value="">Select Status</option>
                                @foreach($leadStatuses as $status)
                                    <option value="{{ $status->id }}"
                                            {{ old('lead_status_id', $object->lead_status_id) == $status->id ? 'selected' : '' }}>
                                        {{ $status->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('lead_status_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="salesperson_id" class="required">Salesperson</label>
                            <select class="form-control @error('salesperson_id') is-invalid @enderror"
                                    id="salesperson_id" name="salesperson_id" required>
                                <option value="">Select Salesperson</option>
                                @foreach($salespeople as $person)
                                    <option value="{{ $person->id }}"
                                            {{ old('salesperson_id', $object->salesperson_id) == $person->id ? 'selected' : '' }}>
                                        {{ $person->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('salesperson_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status">Record Status</label>
                            <select class="form-control @error('status') is-invalid @enderror"
                                    id="status" name="status">
                                <option value="active" {{ old('status', $object->status ?? 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="converted" {{ old('status', $object->status) == 'converted' ? 'selected' : '' }}>Converted</option>
                                <option value="inactive" {{ old('status', $object->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror"
                                      id="notes" name="notes" rows="3"
                                      placeholder="Additional notes about this lead...">{{ old('notes', $object->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="block block-rounded">
            <div class="block-content">
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> {{ isset($object->id) ? 'Update' : 'Create' }} Lead
                        </button>
                        <a href="{{ route('leads.index') }}" class="btn btn-secondary">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@endsection

@section('js_after')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const clientSelect = document.getElementById('client_id');
        const nameInput = document.getElementById('name');
        const phoneInput = document.getElementById('phone');
        const emailInput = document.getElementById('email');
        const addressInput = document.getElementById('address');

        clientSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];

            if (this.value) {
                // Fill from selected client
                nameInput.value = selectedOption.dataset.name || '';
                phoneInput.value = selectedOption.dataset.phone || '';
                emailInput.value = selectedOption.dataset.email || '';
                addressInput.value = selectedOption.dataset.address || '';
            } else {
                // Clear fields for manual entry
                nameInput.value = '';
                phoneInput.value = '';
                emailInput.value = '';
                addressInput.value = '';
            }
        });
    });
</script>
@endsection

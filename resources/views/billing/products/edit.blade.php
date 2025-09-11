@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading">
            <h1>Edit {{ ucfirst($product->type) }}: {{ $product->name }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('billing.dashboard') }}">Billing</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('billing.products.index') }}">Products & Services</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('billing.products.show', $product) }}">{{ $product->name }}</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="block">
                    <div class="block-header">
                        <h3 class="block-title">Product/Service Information</h3>
                    </div>
                    <div class="block-content">
                        <form action="{{ route('billing.products.update', $product) }}" method="POST">
                            @csrf
                            @method('PUT')
                            
                            <!-- Type Selection -->
                            <div class="form-group">
                                <label>Type <span class="text-danger">*</span></label>
                                <div class="form-check-inline">
                                    <label class="form-check-label">
                                        <input type="radio" name="type" value="product" class="form-check-input" 
                                               {{ old('type', $product->type) == 'product' ? 'checked' : '' }}
                                               onchange="toggleInventoryFields()"> Product
                                    </label>
                                </div>
                                <div class="form-check-inline ml-3">
                                    <label class="form-check-label">
                                        <input type="radio" name="type" value="service" class="form-check-input" 
                                               {{ old('type', $product->type) == 'service' ? 'checked' : '' }}
                                               onchange="toggleInventoryFields()"> Service
                                    </label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" 
                                               value="{{ old('name', $product->name) }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Code</label>
                                        <input type="text" name="code" class="form-control" 
                                               value="{{ old('code', $product->code) }}">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="3">{{ old('description', $product->description) }}</textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Category</label>
                                        <input type="text" name="category" class="form-control" 
                                               value="{{ old('category', $product->category) }}" list="categories">
                                        <datalist id="categories">
                                            @foreach($categories as $category)
                                                <option value="{{ $category }}">
                                            @endforeach
                                        </datalist>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Unit of Measure</label>
                                        <input type="text" name="unit_of_measure" class="form-control" 
                                               value="{{ old('unit_of_measure', $product->unit_of_measure) }}" 
                                               placeholder="e.g., pcs, kg, hrs, m2">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Unit Price <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">TZS</span>
                                            </div>
                                            <input type="number" name="unit_price" class="form-control" 
                                                   value="{{ old('unit_price', $product->unit_price) }}" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Purchase Price</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">TZS</span>
                                            </div>
                                            <input type="number" name="purchase_price" class="form-control" 
                                                   value="{{ old('purchase_price', $product->purchase_price) }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Tax Rate</label>
                                <select name="tax_rate_id" class="form-control">
                                    <option value="">No Tax</option>
                                    @foreach($taxRates as $taxRate)
                                        <option value="{{ $taxRate->id }}" 
                                                {{ old('tax_rate_id', $product->tax_rate_id) == $taxRate->id ? 'selected' : '' }}>
                                            {{ $taxRate->name }} ({{ $taxRate->rate }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Product-specific fields -->
                            <div id="productFields" style="{{ old('type', $product->type) == 'service' ? 'display:none' : '' }}">
                                <hr>
                                <h5>Product Details</h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>SKU</label>
                                            <input type="text" name="sku" class="form-control" 
                                                   value="{{ old('sku', $product->sku) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Barcode</label>
                                            <input type="text" name="barcode" class="form-control" 
                                                   value="{{ old('barcode', $product->barcode) }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="hidden" name="track_inventory" value="0">
                                        <input type="checkbox" name="track_inventory" value="1" 
                                               class="form-check-input" id="trackInventory"
                                               {{ old('track_inventory', $product->track_inventory) ? 'checked' : '' }}
                                               onchange="toggleStockFields()">
                                        <label class="form-check-label" for="trackInventory">
                                            Track Inventory
                                        </label>
                                    </div>
                                </div>

                                <div id="stockFields" style="{{ !old('track_inventory', $product->track_inventory) ? 'display:none' : '' }}">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Current Stock</label>
                                                <input type="number" name="current_stock" class="form-control" 
                                                       value="{{ old('current_stock', $product->current_stock) }}" step="0.01" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Minimum Stock</label>
                                                <input type="number" name="minimum_stock" class="form-control" 
                                                       value="{{ old('minimum_stock', $product->minimum_stock) }}" step="0.01" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Reorder Level</label>
                                                <input type="number" name="reorder_level" class="form-control" 
                                                       value="{{ old('reorder_level', $product->reorder_level) }}" step="0.01" min="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" 
                                           class="form-check-input" id="isActive"
                                           {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isActive">
                                        Active
                                    </label>
                                </div>
                            </div>

                            <div class="form-group text-right">
                                <a href="{{ route('billing.products.show', $product) }}" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> Update
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Usage Warning -->
                @if($product->documentItems()->count() > 0)
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> This {{ $product->type }} has been used in {{ $product->documentItems()->count() }} document(s). 
                        Changes to price may affect historical data.
                    </div>
                @endif

                <div class="block">
                    <div class="block-header">
                        <h3 class="block-title">Tips</h3>
                    </div>
                    <div class="block-content">
                        <ul class="list-unstyled">
                            <li><i class="fa fa-check text-success"></i> <strong>Code</strong> must be unique</li>
                            <li><i class="fa fa-check text-success"></i> Price changes affect new documents only</li>
                            <li><i class="fa fa-check text-success"></i> Inactive items won't appear in new documents</li>
                            <li><i class="fa fa-check text-success"></i> Stock tracking can't be disabled if items exist</li>
                        </ul>
                    </div>
                </div>

                <!-- Current Stock Info -->
                @if($product->track_inventory)
                    <div class="block">
                        <div class="block-header">
                            <h3 class="block-title">Current Stock</h3>
                        </div>
                        <div class="block-content text-center">
                            @if($product->current_stock !== null)
                                <h3 class="mb-0">
                                    <span class="badge badge-{{ $product->current_stock <= ($product->minimum_stock ?? 0) ? 'danger' : 'info' }} badge-lg">
                                        {{ number_format($product->current_stock, 2) }}
                                    </span>
                                </h3>
                                @if($product->current_stock <= ($product->minimum_stock ?? 0))
                                    <small class="text-danger">Below minimum level!</small>
                                @endif
                            @else
                                <h3 class="mb-0 text-muted">N/A</h3>
                                <small class="text-muted">Not tracked</small>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function toggleInventoryFields() {
    const productFields = document.getElementById('productFields');
    const productType = document.querySelector('input[name="type"]:checked').value;
    
    if (productType === 'service') {
        productFields.style.display = 'none';
    } else {
        productFields.style.display = 'block';
    }
}

function toggleStockFields() {
    const stockFields = document.getElementById('stockFields');
    const trackInventory = document.getElementById('trackInventory').checked;
    
    stockFields.style.display = trackInventory ? 'block' : 'none';
}
</script>
@endsection
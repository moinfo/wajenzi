@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading">
            <h1>Add New Product/Service</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('billing.dashboard') }}">Billing</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('billing.products.index') }}">Products & Services</a></li>
                    <li class="breadcrumb-item active">Create</li>
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
                        <form action="{{ route('billing.products.store') }}" method="POST">
                            @csrf
                            
                            <!-- Type Selection -->
                            <div class="form-group">
                                <label>Type <span class="text-danger">*</span></label>
                                <div class="form-check-inline">
                                    <label class="form-check-label">
                                        <input type="radio" name="type" value="product" class="form-check-input" 
                                               {{ old('type', 'product') == 'product' ? 'checked' : '' }}
                                               onchange="toggleInventoryFields()"> Product
                                    </label>
                                </div>
                                <div class="form-check-inline ml-3">
                                    <label class="form-check-label">
                                        <input type="radio" name="type" value="service" class="form-check-input" 
                                               {{ old('type') == 'service' ? 'checked' : '' }}
                                               onchange="toggleInventoryFields()"> Service
                                    </label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" 
                                               value="{{ old('name') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Code</label>
                                        <input type="text" name="code" class="form-control" 
                                               value="{{ old('code') }}" 
                                               placeholder="Auto-generated if empty">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Category</label>
                                        <input type="text" name="category" class="form-control" 
                                               value="{{ old('category') }}" list="categories">
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
                                               value="{{ old('unit_of_measure') }}" 
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
                                                   value="{{ old('unit_price') }}" step="0.01" min="0" required>
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
                                                   value="{{ old('purchase_price') }}" step="0.01" min="0">
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
                                                {{ old('tax_rate_id') == $taxRate->id ? 'selected' : '' }}>
                                            {{ $taxRate->name }} ({{ $taxRate->rate }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Product-specific fields -->
                            <div id="productFields" style="{{ old('type', 'product') == 'service' ? 'display:none' : '' }}">
                                <hr>
                                <h5>Product Details</h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>SKU</label>
                                            <input type="text" name="sku" class="form-control" 
                                                   value="{{ old('sku') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Barcode</label>
                                            <input type="text" name="barcode" class="form-control" 
                                                   value="{{ old('barcode') }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="hidden" name="track_inventory" value="0">
                                        <input type="checkbox" name="track_inventory" value="1" 
                                               class="form-check-input" id="trackInventory"
                                               {{ old('track_inventory', 1) ? 'checked' : '' }}
                                               onchange="toggleStockFields()">
                                        <label class="form-check-label" for="trackInventory">
                                            Track Inventory
                                        </label>
                                    </div>
                                </div>

                                <div id="stockFields" style="{{ !old('track_inventory', 1) ? 'display:none' : '' }}">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Current Stock</label>
                                                <input type="number" name="current_stock" class="form-control" 
                                                       value="{{ old('current_stock', 0) }}" step="0.01" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Minimum Stock</label>
                                                <input type="number" name="minimum_stock" class="form-control" 
                                                       value="{{ old('minimum_stock', 0) }}" step="0.01" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Reorder Level</label>
                                                <input type="number" name="reorder_level" class="form-control" 
                                                       value="{{ old('reorder_level', 0) }}" step="0.01" min="0">
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
                                           {{ old('is_active', 1) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isActive">
                                        Active
                                    </label>
                                </div>
                            </div>

                            <div class="form-group text-right">
                                <a href="{{ route('billing.products.index') }}" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="block">
                    <div class="block-header">
                        <h3 class="block-title">Tips</h3>
                    </div>
                    <div class="block-content">
                        <ul class="list-unstyled">
                            <li><i class="fa fa-check text-success"></i> <strong>Products</strong> are physical items you sell</li>
                            <li><i class="fa fa-check text-success"></i> <strong>Services</strong> are work you perform</li>
                            <li><i class="fa fa-check text-success"></i> Use <strong>categories</strong> to organize items</li>
                            <li><i class="fa fa-check text-success"></i> <strong>SKU</strong> helps with inventory tracking</li>
                            <li><i class="fa fa-check text-success"></i> Set <strong>reorder levels</strong> for automatic alerts</li>
                        </ul>
                    </div>
                </div>
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
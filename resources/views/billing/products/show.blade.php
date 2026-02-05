@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>{{ $product->name }}</h1>
                    <span class="badge badge-{{ $product->is_active ? 'success' : 'secondary' }} badge-lg">
                        {{ $product->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('billing.products.edit', $product) }}" class="btn btn-primary">
                        <i class="fa fa-edit"></i> Edit
                    </a>
                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown">
                            More Actions
                        </button>
                        <div class="dropdown-menu">
                            @if($product->is_active)
                                <a class="dropdown-item text-warning" href="{{ route('billing.products.deactivate', $product) }}"
                                   onclick="return confirm('Deactivate this {{ $product->type }}?')">
                                    <i class="fa fa-pause"></i> Deactivate
                                </a>
                            @else
                                <a class="dropdown-item text-success" href="{{ route('billing.products.activate', $product) }}">
                                    <i class="fa fa-play"></i> Activate
                                </a>
                            @endif
                            
                            @if($product->track_inventory)
                                <a class="dropdown-item" href="javascript:void(0)" onclick="adjustStockModal()">
                                    <i class="fa fa-boxes"></i> Adjust Stock
                                </a>
                            @endif
                            
                            @if($product->documentItems()->count() == 0)
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" href="{{ route('billing.products.destroy', $product) }}"
                                   onclick="return confirm('Delete this {{ $product->type }}? This cannot be undone.')">
                                    <i class="fa fa-trash"></i> Delete
                                </a>
                            @endif
                            
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route('billing.products.index') }}">
                                <i class="fa fa-list"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Product Details -->
            <div class="col-md-8">
                <div class="block">
                    <div class="block-header">
                        <h3 class="block-title">{{ ucfirst($product->type) }} Information</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-borderless">
                            <tr>
                                <td width="200"><strong>Type:</strong></td>
                                <td>
                                    <span class="badge badge-{{ $product->type == 'product' ? 'info' : 'success' }}">
                                        {{ ucfirst($product->type) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Code:</strong></td>
                                <td><span class="badge badge-secondary">{{ $product->code }}</span></td>
                            </tr>
                            @if($product->description)
                                <tr>
                                    <td><strong>Description:</strong></td>
                                    <td>{{ $product->description }}</td>
                                </tr>
                            @endif
                            @if($product->category)
                                <tr>
                                    <td><strong>Category:</strong></td>
                                    <td>{{ $product->category }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td><strong>Unit Price:</strong></td>
                                <td>
                                    <strong>TZS {{ number_format($product->unit_price, 2) }}</strong>
                                    @if($product->unit_of_measure)
                                        per {{ $product->unit_of_measure }}
                                    @endif
                                </td>
                            </tr>
                            @if($product->purchase_price)
                                <tr>
                                    <td><strong>Purchase Price:</strong></td>
                                    <td>TZS {{ number_format($product->purchase_price, 2) }}</td>
                                </tr>
                            @endif
                            @if($product->taxRate)
                                <tr>
                                    <td><strong>Tax Rate:</strong></td>
                                    <td>{{ $product->taxRate->name }} ({{ $product->taxRate->rate }}%)</td>
                                </tr>
                            @endif
                            @if($product->sku)
                                <tr>
                                    <td><strong>SKU:</strong></td>
                                    <td>{{ $product->sku }}</td>
                                </tr>
                            @endif
                            @if($product->barcode)
                                <tr>
                                    <td><strong>Barcode:</strong></td>
                                    <td>{{ $product->barcode }}</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>

                <!-- Usage History -->
                @if($recentItems->count() > 0)
                    <div class="block">
                        <div class="block-header">
                            <h3 class="block-title">Recent Usage</h3>
                        </div>
                        <div class="block-content">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Document</th>
                                            <th>Client</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentItems as $item)
                                            <tr>
                                                <td>{{ $item->created_at->format('d/m/Y') }}</td>
                                                <td>
                                                    <a href="{{ route('billing.invoices.show', $item->document) }}">
                                                        {{ $item->document->document_number }}
                                                    </a>
                                                </td>
                                                <td>{{ $item->document->client->company_name }}</td>
                                                <td>{{ number_format($item->quantity, 2) }}</td>
                                                <td>TZS {{ number_format($item->unit_price, 2) }}</td>
                                                <td>TZS {{ number_format($item->line_total, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Statistics -->
                <div class="block">
                    <div class="block-header">
                        <h3 class="block-title">Statistics</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td>Total Sold:</td>
                                <td><strong>{{ number_format($stats['total_sold'] ?? 0, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td>Total Revenue:</td>
                                <td><strong>TZS {{ number_format($stats['total_revenue'] ?? 0, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td>Average Price:</td>
                                <td><strong>TZS {{ number_format($stats['average_price'] ?? 0, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td>Times Used:</td>
                                <td><strong>{{ $stats['times_used'] }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Inventory Information -->
                @if($product->track_inventory)
                    <div class="block">
                        <div class="block-header">
                            <h3 class="block-title">Inventory</h3>
                        </div>
                        <div class="block-content">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td>Current Stock:</td>
                                    <td>
                                        @if($product->current_stock !== null)
                                            <span class="badge badge-{{ $product->current_stock <= ($product->minimum_stock ?? 0) ? 'danger' : 'info' }}">
                                                {{ number_format($product->current_stock, 2) }}
                                            </span>
                                            @if($product->current_stock <= ($product->minimum_stock ?? 0))
                                                <br><small class="text-danger">Low Stock!</small>
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Minimum Stock:</td>
                                    <td><strong>{{ $product->minimum_stock !== null ? number_format($product->minimum_stock, 2) : 'N/A' }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Reorder Level:</td>
                                    <td><strong>{{ $product->reorder_level !== null ? number_format($product->reorder_level, 2) : 'N/A' }}</strong></td>
                                </tr>
                            </table>
                            
                            <button type="button" class="btn btn-sm btn-primary btn-block" onclick="adjustStockModal()">
                                <i class="fa fa-boxes"></i> Adjust Stock
                            </button>
                        </div>
                    </div>
                @endif

                <!-- Product Info -->
                <div class="block">
                    <div class="block-header">
                        <h3 class="block-title">Product Information</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td>Created:</td>
                                <td>{{ $product->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            <tr>
                                <td>Updated:</td>
                                <td>{{ $product->updated_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($product->track_inventory)
<!-- Stock Adjustment Modal -->
<div class="modal fade" id="stockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('billing.products.adjust-stock', $product) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Adjust Stock</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Current Stock</label>
                        <input type="text" class="form-control" value="{{ $product->current_stock !== null ? number_format($product->current_stock, 2) : 'N/A' }}" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Adjustment Type</label>
                        <select name="adjustment_type" class="form-control" id="adjustmentType" onchange="updateStockPreview()" required>
                            <option value="">Select Type</option>
                            <option value="increase">Increase Stock</option>
                            <option value="decrease">Decrease Stock</option>
                            <option value="set">Set Stock Level</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity" class="form-control" step="0.01" min="0" 
                               id="adjustmentQuantity" onchange="updateStockPreview()" required>
                    </div>
                    
                    <div class="form-group">
                        <label>New Stock Level</label>
                        <input type="text" class="form-control" id="newStockLevel" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Reason</label>
                        <input type="text" name="reason" class="form-control" 
                               placeholder="e.g., Stock count, Damaged goods, etc." required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Adjust Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
@if($product->track_inventory)
function adjustStockModal() {
    $('#stockModal').modal('show');
}

function updateStockPreview() {
    const currentStock = {{ $product->current_stock ?? 0 }};
    const adjustmentType = document.getElementById('adjustmentType').value;
    const quantity = parseFloat(document.getElementById('adjustmentQuantity').value) || 0;
    let newStock = 0;
    
    switch(adjustmentType) {
        case 'increase':
            newStock = currentStock + quantity;
            break;
        case 'decrease':
            newStock = Math.max(0, currentStock - quantity);
            break;
        case 'set':
            newStock = quantity;
            break;
    }
    
    document.getElementById('newStockLevel').value = newStock.toFixed(2);
}
@endif
</script>
@endsection
@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>Products & Services</h1>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('billing.products.create') }}" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Add Product/Service
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="block">
            <div class="block-content">
                <form method="GET" class="form-inline">
                    <div class="form-group mr-3">
                        <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                    </div>
                    <div class="form-group mr-3">
                        <select name="type" class="form-control">
                            <option value="">All Types</option>
                            <option value="product" {{ request('type') == 'product' ? 'selected' : '' }}>Products</option>
                            <option value="service" {{ request('type') == 'service' ? 'selected' : '' }}>Services</option>
                        </select>
                    </div>
                    <div class="form-group mr-3">
                        <select name="category" class="form-control">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
                                    {{ $category }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mr-3">
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="low_stock" {{ request('status') == 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary">Filter</button>
                    <a href="{{ route('billing.products.index') }}" class="btn btn-light ml-2">Clear</a>
                </form>
            </div>
        </div>

        <!-- Products Table -->
        <div class="block">
            <div class="block-content">
                @if($products->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Category</th>
                                    <th>Unit Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $product)
                                    <tr>
                                        <td>
                                            <span class="badge badge-secondary">{{ $product->code }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ $product->name }}</strong>
                                            @if($product->description)
                                                <br><small class="text-muted">{{ Str::limit($product->description, 50) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $product->type == 'product' ? 'info' : 'success' }}">
                                                {{ ucfirst($product->type) }}
                                            </span>
                                        </td>
                                        <td>{{ $product->category ?: '-' }}</td>
                                        <td>
                                            <strong>TZS {{ number_format($product->unit_price, 2) }}</strong>
                                            @if($product->unit_of_measure)
                                                <br><small class="text-muted">per {{ $product->unit_of_measure }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($product->track_inventory && $product->current_stock !== null)
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
                                        <td>
                                            <span class="badge badge-{{ $product->is_active ? 'success' : 'secondary' }}">
                                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('billing.products.show', $product) }}" class="btn btn-sm btn-info">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="{{ route('billing.products.edit', $product) }}" class="btn btn-sm btn-primary">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                @if($product->documentItems()->count() == 0)
                                                    <form action="{{ route('billing.products.destroy', $product) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" 
                                                                onclick="return confirm('Are you sure?')">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $products->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fa fa-box fa-3x text-muted"></i>
                        <h4 class="mt-3">No products or services found</h4>
                        <p class="text-muted">Create your first product or service to get started.</p>
                        <a href="{{ route('billing.products.create') }}" class="btn btn-primary">
                            <i class="fa fa-plus"></i> Add Product/Service
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
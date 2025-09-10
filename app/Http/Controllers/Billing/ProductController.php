<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\BillingProduct;
use App\Models\BillingTaxRate;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = BillingProduct::query()
            ->when($request->search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                           ->orWhere('code', 'like', "%{$search}%")
                           ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($request->type, function ($query, $type) {
                return $query->where('type', $type);
            })
            ->when($request->category, function ($query, $category) {
                return $query->where('category', $category);
            })
            ->when($request->status, function ($query, $status) {
                if ($status === 'active') {
                    return $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    return $query->where('is_active', false);
                } elseif ($status === 'low_stock') {
                    return $query->lowStock();
                }
            })
            ->orderBy('name')
            ->paginate(20);

        $categories = BillingProduct::distinct()->pluck('category')->filter();
        
        return view('billing.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $taxRates = BillingTaxRate::where('is_active', true)->get();
        $categories = BillingProduct::distinct()->pluck('category')->filter();
        
        return view('billing.products.create', compact('taxRates', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:product,service',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:100|unique:billing_products_services,code',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'unit_of_measure' => 'nullable|string|max:50',
            'unit_price' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'tax_rate_id' => 'nullable|exists:billing_tax_rates,id',
            'sku' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'track_inventory' => 'boolean',
            'current_stock' => 'nullable|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
        ]);

        if ($request->type === 'service') {
            $request->merge([
                'track_inventory' => false,
                'current_stock' => null,
                'minimum_stock' => null,
                'reorder_level' => null
            ]);
        }

        $product = BillingProduct::create($request->all());

        return redirect()
            ->route('billing.products.show', $product)
            ->with('success', ucfirst($request->type) . ' created successfully.');
    }

    public function show(BillingProduct $product)
    {
        $product->load('taxRate');
        
        $recentItems = $product->documentItems()
            ->with(['document.client'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        $stats = [
            'total_sold' => $product->documentItems()->sum('quantity'),
            'total_revenue' => $product->documentItems()->sum('line_total'),
            'average_price' => $product->documentItems()->avg('unit_price'),
            'times_used' => $product->documentItems()->count(),
        ];

        return view('billing.products.show', compact('product', 'recentItems', 'stats'));
    }

    public function edit(BillingProduct $product)
    {
        $taxRates = BillingTaxRate::where('is_active', true)->get();
        $categories = BillingProduct::distinct()->pluck('category')->filter();
        
        return view('billing.products.edit', compact('product', 'taxRates', 'categories'));
    }

    public function update(Request $request, BillingProduct $product)
    {
        $request->validate([
            'type' => 'required|in:product,service',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:100|unique:billing_products_services,code,' . $product->id,
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'unit_of_measure' => 'nullable|string|max:50',
            'unit_price' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'tax_rate_id' => 'nullable|exists:billing_tax_rates,id',
            'sku' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'track_inventory' => 'boolean',
            'current_stock' => 'nullable|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
        ]);

        if ($request->type === 'service') {
            $request->merge([
                'track_inventory' => false,
                'current_stock' => null,
                'minimum_stock' => null,
                'reorder_level' => null
            ]);
        }

        $product->update($request->all());

        return redirect()
            ->route('billing.products.show', $product)
            ->with('success', ucfirst($request->type) . ' updated successfully.');
    }

    public function destroy(BillingProduct $product)
    {
        if ($product->documentItems()->count() > 0) {
            return back()->with('error', 'Cannot delete product/service with existing document items. Deactivate instead.');
        }

        $product->delete();

        return redirect()
            ->route('billing.products.index')
            ->with('success', ucfirst($product->type) . ' deleted successfully.');
    }

    public function activate(BillingProduct $product)
    {
        $product->update(['is_active' => true]);

        return back()->with('success', ucfirst($product->type) . ' activated successfully.');
    }

    public function deactivate(BillingProduct $product)
    {
        $product->update(['is_active' => false]);

        return back()->with('success', ucfirst($product->type) . ' deactivated successfully.');
    }

    public function lowStock()
    {
        $products = BillingProduct::lowStock()
            ->orderBy('current_stock', 'asc')
            ->get();

        return view('billing.products.low-stock', compact('products'));
    }

    public function adjustStock(Request $request, BillingProduct $product)
    {
        if (!$product->track_inventory) {
            return back()->with('error', 'This product does not track inventory.');
        }

        $request->validate([
            'adjustment_type' => 'required|in:increase,decrease,set',
            'quantity' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
        ]);

        $oldStock = $product->current_stock;

        switch ($request->adjustment_type) {
            case 'increase':
                $newStock = $oldStock + $request->quantity;
                break;
            case 'decrease':
                $newStock = max(0, $oldStock - $request->quantity);
                break;
            case 'set':
                $newStock = $request->quantity;
                break;
        }

        $product->update(['current_stock' => $newStock]);

        return back()->with('success', "Stock adjusted from {$oldStock} to {$newStock}. Reason: {$request->reason}");
    }

    public function search(Request $request)
    {
        $query = $request->get('q');
        
        $products = BillingProduct::where('is_active', true)
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get(['id', 'code', 'name', 'unit_price', 'tax_rate_id', 'unit_of_measure']);

        return response()->json($products);
    }
}
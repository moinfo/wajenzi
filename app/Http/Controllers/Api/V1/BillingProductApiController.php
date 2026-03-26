<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BillingProduct;
use App\Models\BillingTaxRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingProductApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = BillingProduct::with('taxRate')
            ->when($request->search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->type, fn ($query, $type) => $query->where('type', $type))
            ->when($request->category, fn ($query, $category) => $query->where('category', $category))
            ->when($request->status, function ($query, $status) {
                if ($status === 'active') {
                    return $query->where('is_active', true);
                }
                if ($status === 'inactive') {
                    return $query->where('is_active', false);
                }
                if ($status === 'low_stock') {
                    return $query->lowStock();
                }
                return $query;
            })
            ->orderBy('name')
            ->paginate($request->per_page ?? 100);

        return response()->json([
            'success' => true,
            'data' => $products->getCollection()->map(fn (BillingProduct $product) => $this->serializeProduct($product)),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function referenceData(): JsonResponse
    {
        $taxRates = BillingTaxRate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($taxRate) => [
                'id' => $taxRate->id,
                'name' => $taxRate->name,
                'rate' => $taxRate->rate,
            ]);

        $categories = BillingProduct::distinct()
            ->pluck('category')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'tax_rates' => $taxRates,
                'categories' => $categories,
                'types' => [
                    ['id' => 'product', 'name' => 'Product'],
                    ['id' => 'service', 'name' => 'Service'],
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $product = BillingProduct::create($this->normalizePayload($validated));
        $product->load('taxRate');

        return response()->json([
            'success' => true,
            'message' => ucfirst($product->type) . ' created successfully.',
            'data' => $this->serializeProduct($product),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $product = BillingProduct::with('taxRate')->findOrFail($id);
        $stats = [
            'total_sold' => $product->documentItems()->sum('quantity'),
            'total_revenue' => $product->documentItems()->sum('line_total'),
            'average_price' => $product->documentItems()->avg('unit_price'),
            'times_used' => $product->documentItems()->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => array_merge($this->serializeProduct($product), [
                'stats' => $stats,
            ]),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = BillingProduct::findOrFail($id);
        $validated = $this->validatePayload($request, $product->id);
        $product->update($this->normalizePayload($validated));
        $product->load('taxRate');

        return response()->json([
            'success' => true,
            'message' => ucfirst($product->type) . ' updated successfully.',
            'data' => $this->serializeProduct($product),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = BillingProduct::findOrFail($id);
        if ($product->documentItems()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete product/service with existing document items. Deactivate instead.',
            ], 422);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => ucfirst($product->type) . ' deleted successfully.',
        ]);
    }

    private function validatePayload(Request $request, ?int $productId = null): array
    {
        return $request->validate([
            'type' => 'required|in:product,service',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:100|unique:billing_products_services,code' . ($productId ? ',' . $productId : ''),
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'unit_of_measure' => 'nullable|string|max:50',
            'unit_price' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'tax_rate_id' => 'nullable|exists:billing_tax_rates,id',
            'sku' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'track_inventory' => 'nullable|boolean',
            'current_stock' => 'nullable|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);
    }

    private function normalizePayload(array $validated): array
    {
        $validated['track_inventory'] = (bool) ($validated['track_inventory'] ?? false);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        if (($validated['type'] ?? null) === 'service') {
            $validated['track_inventory'] = false;
            $validated['current_stock'] = null;
            $validated['minimum_stock'] = null;
            $validated['reorder_level'] = null;
        }

        return $validated;
    }

    private function serializeProduct(BillingProduct $product): array
    {
        return [
            'id' => $product->id,
            'type' => $product->type,
            'code' => $product->code,
            'name' => $product->name,
            'description' => $product->description,
            'category' => $product->category,
            'unit_of_measure' => $product->unit_of_measure,
            'unit_price' => $product->unit_price,
            'purchase_price' => $product->purchase_price,
            'tax_rate_id' => $product->tax_rate_id,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'track_inventory' => (bool) $product->track_inventory,
            'current_stock' => $product->current_stock,
            'minimum_stock' => $product->minimum_stock,
            'reorder_level' => $product->reorder_level,
            'is_active' => (bool) $product->is_active,
            'tax_rate' => $product->taxRate ? [
                'id' => $product->taxRate->id,
                'name' => $product->taxRate->name,
                'rate' => $product->taxRate->rate,
            ] : null,
            'created_at' => $product->created_at?->toISOString(),
            'updated_at' => $product->updated_at?->toISOString(),
        ];
    }
}

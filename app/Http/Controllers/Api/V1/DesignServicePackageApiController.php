<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DesignServicePackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DesignServicePackageApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $q = DesignServicePackage::query();
            if ($request->boolean('active_only')) {
                $q->where('is_active', true);
            }
            if ($rise = $request->string('rise_type')->toString()) {
                if (in_array($rise, ['low', 'high'])) {
                    $q->where('rise_type', $rise);
                }
            }
            $items = $q->orderBy('rise_type')->orderBy('sort_order')->orderBy('id')->get();
            return response()->json([
                'success' => true,
                'data'    => $items->map(fn ($i) => $this->formatItem($i))->values(),
                'meta'    => ['total' => $items->count()],
            ]);
        } catch (\Throwable $e) {
            Log::error('DesignServicePackage index error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch packages'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $this->validatePayload($request);
            $item = DesignServicePackage::create($validated);
            return response()->json([
                'success' => true,
                'data'    => $this->formatItem($item),
                'message' => 'Design package created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('DesignServicePackage store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create package: ' . $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->formatItem(DesignServicePackage::findOrFail($id))]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Package not found'], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = DesignServicePackage::findOrFail($id);
            $validated = $this->validatePayload($request);
            $item->update($validated);
            return response()->json([
                'success' => true,
                'data'    => $this->formatItem($item->fresh()),
                'message' => 'Design package updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('DesignServicePackage update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update package: ' . $e->getMessage()], 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            DesignServicePackage::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Design package deleted successfully']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete package'], 500);
        }
    }

    private function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:100',
            'rise_type'         => 'required|string|in:low,high',
            'price_usd'         => 'required|numeric|min:0',
            'included_services' => 'nullable|array',
            'included_services.*' => 'string|max:255',
            'sort_order'        => 'sometimes|integer|min:0',
            'is_active'         => 'sometimes|boolean',
        ]);
        // Default included_services to empty list if missing
        if (! array_key_exists('included_services', $validated)) {
            $validated['included_services'] = [];
        }
        return $validated;
    }

    private function formatItem(DesignServicePackage $p): array
    {
        return [
            'id'                => $p->id,
            'name'              => $p->name,
            'rise_type'         => $p->rise_type,
            'price_usd'         => (float) $p->price_usd,
            'included_services' => $p->included_services ?? [],
            'sort_order'        => (int) $p->sort_order,
            'is_active'         => (bool) $p->is_active,
            'created_at'        => optional($p->created_at)->format('Y-m-d H:i:s'),
            'updated_at'        => optional($p->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}

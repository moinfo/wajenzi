<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DesignServiceAddon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DesignServiceAddonApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $q = DesignServiceAddon::query();
            if ($request->boolean('active_only')) {
                $q->where('is_active', true);
            }
            $items = $q->orderBy('sort_order')->orderBy('id')->get();
            return response()->json([
                'success' => true,
                'data'    => $items->map(fn ($i) => $this->formatItem($i))->values(),
                'meta'    => ['total' => $items->count()],
            ]);
        } catch (\Throwable $e) {
            Log::error('DesignServiceAddon index error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch add-ons'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $item = DesignServiceAddon::create($this->validatePayload($request));
            return response()->json([
                'success' => true,
                'data'    => $this->formatItem($item),
                'message' => 'Design add-on created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('DesignServiceAddon store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create add-on: ' . $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->formatItem(DesignServiceAddon::findOrFail($id))]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Add-on not found'], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = DesignServiceAddon::findOrFail($id);
            $item->update($this->validatePayload($request));
            return response()->json([
                'success' => true,
                'data'    => $this->formatItem($item->fresh()),
                'message' => 'Design add-on updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('DesignServiceAddon update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update add-on: ' . $e->getMessage()], 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            DesignServiceAddon::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Design add-on deleted successfully']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete add-on'], 500);
        }
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'name'           => 'required|string|max:150',
            'price_low_usd'  => 'required|numeric|min:0',
            'price_high_usd' => 'required|numeric|min:0',
            'sort_order'     => 'sometimes|integer|min:0',
            'is_active'      => 'sometimes|boolean',
        ]);
    }

    private function formatItem(DesignServiceAddon $a): array
    {
        return [
            'id'             => $a->id,
            'name'           => $a->name,
            'price_low_usd'  => (float) $a->price_low_usd,
            'price_high_usd' => (float) $a->price_high_usd,
            'sort_order'     => (int) $a->sort_order,
            'is_active'      => (bool) $a->is_active,
            'created_at'     => optional($a->created_at)->format('Y-m-d H:i:s'),
            'updated_at'     => optional($a->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}

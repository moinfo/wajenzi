<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ItemSettingsApiController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $items = Item::orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (Item $item) => $this->formatItem($item))->values(),
                'meta' => [
                    'total' => $items->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Item settings index error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch items',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $item = Item::create($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
                'message' => 'Item created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Item settings store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create item: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = Item::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('Item settings show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Item not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = Item::findOrFail($id);
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);
            $item->update($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->fresh()),
                'message' => 'Item updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('Item settings update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update item: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $item = Item::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Item deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('Item settings destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete item',
            ], 500);
        }
    }

    private function formatItem(Item $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($item->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}

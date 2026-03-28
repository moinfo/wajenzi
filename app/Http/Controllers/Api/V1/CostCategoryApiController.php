<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CostCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CostCategoryApiController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $items = CostCategory::orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (CostCategory $item) => $this->formatItem($item))->values(),
                'meta' => [
                    'total' => $items->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('CostCategory index error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch cost categories',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $item = CostCategory::create($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
                'message' => 'Cost category created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('CostCategory store error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create cost category: '.$e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = CostCategory::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('CostCategory show error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Cost category not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = CostCategory::findOrFail($id);
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);
            $item->update($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->fresh()),
                'message' => 'Cost category updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('CostCategory update error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update cost category: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $item = CostCategory::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cost category deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('CostCategory destroy error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete cost category',
            ], 500);
        }
    }

    private function formatItem(CostCategory $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($item->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}

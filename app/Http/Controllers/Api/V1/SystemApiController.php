<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\System as SystemModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SystemApiController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $items = SystemModel::orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (SystemModel $item) => $this->formatItem($item))->values(),
                'meta' => [
                    'total' => $items->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('System index error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch systems',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            $item = SystemModel::create($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
                'message' => 'System created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('System store error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create system: '.$e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = SystemModel::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('System show error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'System not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = SystemModel::findOrFail($id);
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);
            $item->update($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->fresh()),
                'message' => 'System updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('System update error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update system: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $item = SystemModel::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'System deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('System destroy error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete system',
            ], 500);
        }
    }

    private function formatItem(SystemModel $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($item->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}

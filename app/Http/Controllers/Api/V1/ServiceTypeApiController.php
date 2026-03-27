<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ServiceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServiceTypeApiController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $items = ServiceType::orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (ServiceType $item) => $this->formatItem($item))->values(),
                'meta' => [
                    'total' => $items->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ServiceType index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch service types',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $item = ServiceType::create($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
                'message' => 'Service type created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('ServiceType store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create service type: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = ServiceType::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('ServiceType show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Service type not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = ServiceType::findOrFail($id);
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);
            $item->update($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->fresh()),
                'message' => 'Service type updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ServiceType update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service type: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $item = ServiceType::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Service type deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ServiceType destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete service type',
            ], 500);
        }
    }

    private function formatItem(ServiceType $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($item->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}

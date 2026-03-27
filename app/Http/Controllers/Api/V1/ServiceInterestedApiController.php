<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ServiceInterested;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServiceInterestedApiController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $items = ServiceInterested::orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (ServiceInterested $item) => $this->formatItem($item))->values(),
                'meta' => [
                    'total' => $items->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ServiceInterested index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch service interesteds',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $item = ServiceInterested::create($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
                'message' => 'Service interested created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('ServiceInterested store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create service interested: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = ServiceInterested::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('ServiceInterested show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Service interested not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = ServiceInterested::findOrFail($id);
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);
            $item->update($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->fresh()),
                'message' => 'Service interested updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ServiceInterested update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service interested: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $item = ServiceInterested::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Service interested deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ServiceInterested destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete service interested',
            ], 500);
        }
    }

    private function formatItem(ServiceInterested $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($item->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}

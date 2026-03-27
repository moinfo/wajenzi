<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LeadSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LeadSourceApiController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $items = LeadSource::orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (LeadSource $item) => $this->formatItem($item))->values(),
                'meta' => [
                    'total' => $items->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('LeadSource index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch lead sources',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $item = LeadSource::create($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
                'message' => 'Lead source created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('LeadSource store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create lead source: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = LeadSource::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('LeadSource show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lead source not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = LeadSource::findOrFail($id);
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);
            $item->update($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->fresh()),
                'message' => 'Lead source updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('LeadSource update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update lead source: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $item = LeadSource::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Lead source deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('LeadSource destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete lead source',
            ], 500);
        }
    }

    private function formatItem(LeadSource $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($item->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}

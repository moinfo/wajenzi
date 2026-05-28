<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SiteVisitLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SiteVisitLocationApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $q = SiteVisitLocation::query();
            if ($request->boolean('active_only')) {
                $q->where('is_active', true);
            }
            $items = $q->orderBy('sort_order')->orderBy('name')->get();
            return response()->json([
                'success' => true,
                'data'    => $items->map(fn ($i) => $this->formatItem($i))->values(),
                'meta'    => ['total' => $items->count()],
            ]);
        } catch (\Throwable $e) {
            Log::error('SiteVisitLocation index error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch locations'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $item = SiteVisitLocation::create($this->validatePayload($request));
            return response()->json([
                'success' => true,
                'data'    => $this->formatItem($item),
                'message' => 'Site visit location created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('SiteVisitLocation store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create location: ' . $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->formatItem(SiteVisitLocation::findOrFail($id))]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Location not found'], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = SiteVisitLocation::findOrFail($id);
            $item->update($this->validatePayload($request));
            return response()->json([
                'success' => true,
                'data'    => $this->formatItem($item->fresh()),
                'message' => 'Site visit location updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('SiteVisitLocation update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update location: ' . $e->getMessage()], 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            SiteVisitLocation::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Site visit location deleted successfully']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete location'], 500);
        }
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'name'                     => 'required|string|max:150',
            'base_cost_tzs'            => 'required|numeric|min:0',
            'preset_travel_tzs'        => 'sometimes|nullable|numeric|min:0',
            'preset_local_tzs'         => 'sometimes|nullable|numeric|min:0',
            'preset_allowance_tzs'     => 'sometimes|nullable|numeric|min:0',
            'preset_food_tzs'          => 'sometimes|nullable|numeric|min:0',
            'preset_accommodation_tzs' => 'sometimes|nullable|numeric|min:0',
            'sort_order'               => 'sometimes|integer|min:0',
            'is_active'                => 'sometimes|boolean',
        ]);
    }

    private function formatItem(SiteVisitLocation $l): array
    {
        return [
            'id'                       => $l->id,
            'name'                     => $l->name,
            'base_cost_tzs'            => (float) $l->base_cost_tzs,
            'preset_travel_tzs'        => (float) $l->preset_travel_tzs,
            'preset_local_tzs'         => (float) $l->preset_local_tzs,
            'preset_allowance_tzs'     => (float) $l->preset_allowance_tzs,
            'preset_food_tzs'          => (float) $l->preset_food_tzs,
            'preset_accommodation_tzs' => (float) $l->preset_accommodation_tzs,
            'sort_order'               => (int) $l->sort_order,
            'is_active'                => (bool) $l->is_active,
            'created_at'               => optional($l->created_at)->format('Y-m-d H:i:s'),
            'updated_at'               => optional($l->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DesignSpecialStructure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DesignSpecialStructureApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $q = DesignSpecialStructure::query();
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
            Log::error('DesignSpecialStructure index error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch special structures'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $item = DesignSpecialStructure::create($this->validatePayload($request));
            return response()->json([
                'success' => true,
                'data'    => $this->formatItem($item),
                'message' => 'Special structure created successfully',
            ], 201);
        } catch (\Throwable $e) {
            if ($e instanceof \Illuminate\Validation\ValidationException) throw $e;
            Log::error('DesignSpecialStructure store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create special structure.'], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->formatItem(DesignSpecialStructure::findOrFail($id))]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Special structure not found'], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = DesignSpecialStructure::findOrFail($id);
            $item->update($this->validatePayload($request));
            return response()->json([
                'success' => true,
                'data'    => $this->formatItem($item->fresh()),
                'message' => 'Special structure updated successfully',
            ]);
        } catch (\Throwable $e) {
            if ($e instanceof \Illuminate\Validation\ValidationException) throw $e;
            Log::error('DesignSpecialStructure update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update special structure.'], 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            DesignSpecialStructure::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Special structure deleted successfully']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete special structure'], 500);
        }
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'name'             => 'required|string|max:150',
            'rate_tzs_per_sqm' => 'required|numeric|min:0',
            'sort_order'       => 'sometimes|integer|min:0',
            'is_active'        => 'sometimes|boolean',
        ]);
    }

    private function formatItem(DesignSpecialStructure $s): array
    {
        return [
            'id'               => $s->id,
            'name'             => $s->name,
            'rate_tzs_per_sqm' => (float) $s->rate_tzs_per_sqm,
            'sort_order'       => (int) $s->sort_order,
            'is_active'        => (bool) $s->is_active,
            'created_at'       => optional($s->created_at)->format('Y-m-d H:i:s'),
            'updated_at'       => optional($s->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}

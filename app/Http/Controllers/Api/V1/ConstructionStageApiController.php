<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ConstructionStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConstructionStageApiController extends Controller
{
    public function index(): JsonResponse
    {
        $stages = ConstructionStage::with(['parent:id,name', 'children:id,parent_id'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stages->map(fn (ConstructionStage $stage) => $this->transformStage($stage))->values(),
            'meta' => [
                'total' => $stages->count(),
            ],
        ]);
    }

    public function referenceData(): JsonResponse
    {
        $parents = ConstructionStage::whereNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => [
                'parent_construction_stages' => $parents->map(fn (ConstructionStage $stage) => [
                    'id' => $stage->id,
                    'name' => $stage->name,
                ])->values(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $stage = ConstructionStage::with(['parent:id,name', 'children:id,parent_id'])->find($id);

        if (!$stage) {
            return response()->json([
                'success' => false,
                'message' => 'Construction stage not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformStage($stage),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:construction_stages,name',
            'parent_id' => 'nullable|exists:construction_stages,id',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $stage = ConstructionStage::create([
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);
        $stage->load(['parent:id,name', 'children:id,parent_id']);

        return response()->json([
            'success' => true,
            'message' => 'Construction stage created successfully',
            'data' => $this->transformStage($stage),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $stage = ConstructionStage::find($id);

        if (!$stage) {
            return response()->json([
                'success' => false,
                'message' => 'Construction stage not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:construction_stages,name,' . $id,
            'parent_id' => 'nullable|exists:construction_stages,id',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $parentId = array_key_exists('parent_id', $validated) ? $validated['parent_id'] : $stage->parent_id;
        if ($parentId && (int) $parentId === $stage->id) {
            return response()->json([
                'success' => false,
                'message' => 'A construction stage cannot be its own parent',
            ], 422);
        }

        $stage->update([
            'name' => $validated['name'] ?? $stage->name,
            'parent_id' => $parentId,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $stage->description,
            'sort_order' => $validated['sort_order'] ?? $stage->sort_order,
        ]);
        $stage->load(['parent:id,name', 'children:id,parent_id']);

        return response()->json([
            'success' => true,
            'message' => 'Construction stage updated successfully',
            'data' => $this->transformStage($stage),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $stage = ConstructionStage::withCount('children')->find($id);

        if (!$stage) {
            return response()->json([
                'success' => false,
                'message' => 'Construction stage not found',
            ], 404);
        }

        if (($stage->children_count ?? 0) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a construction stage that has child stages',
            ], 422);
        }

        $stage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Construction stage deleted successfully',
        ]);
    }

    private function transformStage(ConstructionStage $stage): array
    {
        return [
            'id' => $stage->id,
            'name' => $stage->name,
            'parent_id' => $stage->parent_id,
            'parent_name' => $stage->parent?->name,
            'description' => $stage->description,
            'sort_order' => $stage->sort_order ?? 0,
            'children_count' => $stage->relationLoaded('children') ? $stage->children->count() : 0,
            'created_at' => $stage->created_at?->toIso8601String(),
            'updated_at' => $stage->updated_at?->toIso8601String(),
        ];
    }
}

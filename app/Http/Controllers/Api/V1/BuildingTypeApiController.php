<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BuildingType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuildingTypeApiController extends Controller
{
    public function index(): JsonResponse
    {
        $types = BuildingType::with(['parent:id,name', 'children:id,parent_id'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $types->map(fn (BuildingType $type) => $this->transformType($type))->values(),
            'meta' => [
                'total' => $types->count(),
            ],
        ]);
    }

    public function referenceData(): JsonResponse
    {
        $parents = BuildingType::whereNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => [
                'parent_building_types' => $parents->map(fn (BuildingType $type) => [
                    'id' => $type->id,
                    'name' => $type->name,
                ])->values(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $type = BuildingType::with(['parent:id,name', 'children:id,parent_id'])->find($id);

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Building type not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformType($type),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:building_types,name',
            'parent_id' => 'nullable|exists:building_types,id',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'required|boolean',
        ]);

        $type = BuildingType::create([
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'],
        ]);
        $type->load(['parent:id,name', 'children:id,parent_id']);

        return response()->json([
            'success' => true,
            'message' => 'Building type created successfully',
            'data' => $this->transformType($type),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $type = BuildingType::find($id);

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Building type not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:building_types,name,' . $id,
            'parent_id' => 'nullable|exists:building_types,id',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'required|boolean',
        ]);

        $parentId = array_key_exists('parent_id', $validated) ? $validated['parent_id'] : $type->parent_id;
        if ($parentId && (int) $parentId === $type->id) {
          return response()->json([
              'success' => false,
              'message' => 'A building type cannot be its own parent',
          ], 422);
        }

        $type->update([
            'name' => $validated['name'] ?? $type->name,
            'parent_id' => $parentId,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $type->description,
            'sort_order' => $validated['sort_order'] ?? $type->sort_order,
            'is_active' => $validated['is_active'],
        ]);
        $type->load(['parent:id,name', 'children:id,parent_id']);

        return response()->json([
            'success' => true,
            'message' => 'Building type updated successfully',
            'data' => $this->transformType($type),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $type = BuildingType::withCount('children')->find($id);

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Building type not found',
            ], 404);
        }

        if (($type->children_count ?? 0) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a building type that has child building types',
            ], 422);
        }

        $type->delete();

        return response()->json([
            'success' => true,
            'message' => 'Building type deleted successfully',
        ]);
    }

    private function transformType(BuildingType $type): array
    {
        return [
            'id' => $type->id,
            'name' => $type->name,
            'parent_id' => $type->parent_id,
            'parent_name' => $type->parent?->name,
            'description' => $type->description,
            'sort_order' => $type->sort_order ?? 0,
            'is_active' => (bool) $type->is_active,
            'children_count' => $type->relationLoaded('children') ? $type->children->count() : 0,
            'created_at' => $type->created_at?->toIso8601String(),
            'updated_at' => $type->updated_at?->toIso8601String(),
        ];
    }
}

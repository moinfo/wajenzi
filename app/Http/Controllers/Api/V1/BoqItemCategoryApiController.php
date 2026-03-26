<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BoqItemCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoqItemCategoryApiController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = BoqItemCategory::with(['parent:id,name', 'children:id,parent_id'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories->map(fn (BoqItemCategory $category) => $this->transformCategory($category))->values(),
            'meta' => [
                'total' => $categories->count(),
            ],
        ]);
    }

    public function referenceData(): JsonResponse
    {
        $parents = BoqItemCategory::whereNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => [
                'parent_boq_item_categories' => $parents->map(fn (BoqItemCategory $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                ])->values(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $category = BoqItemCategory::with(['parent:id,name', 'children:id,parent_id'])->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'BOQ item category not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformCategory($category),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:boq_item_categories,name',
            'parent_id' => 'nullable|exists:boq_item_categories,id',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'required|boolean',
        ]);

        $category = BoqItemCategory::create([
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'],
        ]);
        $category->load(['parent:id,name', 'children:id,parent_id']);

        return response()->json([
            'success' => true,
            'message' => 'BOQ item category created successfully',
            'data' => $this->transformCategory($category),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = BoqItemCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'BOQ item category not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:boq_item_categories,name,' . $id,
            'parent_id' => 'nullable|exists:boq_item_categories,id',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'required|boolean',
        ]);

        $parentId = array_key_exists('parent_id', $validated) ? $validated['parent_id'] : $category->parent_id;
        if ($parentId && (int) $parentId === $category->id) {
            return response()->json([
                'success' => false,
                'message' => 'A BOQ item category cannot be its own parent',
            ], 422);
        }

        $category->update([
            'name' => $validated['name'] ?? $category->name,
            'parent_id' => $parentId,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $category->description,
            'sort_order' => $validated['sort_order'] ?? $category->sort_order,
            'is_active' => $validated['is_active'],
        ]);
        $category->load(['parent:id,name', 'children:id,parent_id']);

        return response()->json([
            'success' => true,
            'message' => 'BOQ item category updated successfully',
            'data' => $this->transformCategory($category),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $category = BoqItemCategory::withCount('children')->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'BOQ item category not found',
            ], 404);
        }

        if (($category->children_count ?? 0) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a BOQ item category that has child categories',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'BOQ item category deleted successfully',
        ]);
    }

    private function transformCategory(BoqItemCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'parent_id' => $category->parent_id,
            'parent_name' => $category->parent?->name,
            'description' => $category->description,
            'sort_order' => $category->sort_order ?? 0,
            'is_active' => (bool) $category->is_active,
            'children_count' => $category->relationLoaded('children') ? $category->children->count() : 0,
            'created_at' => $category->created_at?->toIso8601String(),
            'updated_at' => $category->updated_at?->toIso8601String(),
        ];
    }
}

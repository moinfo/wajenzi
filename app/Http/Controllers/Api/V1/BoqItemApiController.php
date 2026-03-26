<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BoqItemCategory;
use App\Models\BoqTemplateItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoqItemApiController extends Controller
{
    public function index(): JsonResponse
    {
        $items = BoqTemplateItem::with(['category:id,name,parent_id'])
            ->withCount('subActivityMaterials')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items->map(fn (BoqTemplateItem $item) => $this->transformItem($item))->values(),
            'meta' => [
                'total' => $items->count(),
            ],
        ]);
    }

    public function referenceData(): JsonResponse
    {
        $categories = BoqItemCategory::with('parent:id,name')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories->map(fn (BoqItemCategory $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'parent_name' => $category->parent?->name,
                ])->values(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $item = BoqTemplateItem::with(['category:id,name,parent_id'])
            ->withCount('subActivityMaterials')
            ->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'BOQ item not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformItem($item),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:boq_template_items,name',
            'category_id' => 'nullable|exists:boq_item_categories,id',
            'unit' => 'nullable|string|max:100',
            'base_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $item = BoqTemplateItem::create([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'] ?? null,
            'unit' => $validated['unit'] ?? null,
            'base_price' => $validated['base_price'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        $item->load('category:id,name,parent_id')->loadCount('subActivityMaterials');

        return response()->json([
            'success' => true,
            'message' => 'BOQ item created successfully',
            'data' => $this->transformItem($item),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = BoqTemplateItem::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'BOQ item not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:boq_template_items,name,' . $id,
            'category_id' => 'nullable|exists:boq_item_categories,id',
            'unit' => 'nullable|string|max:100',
            'base_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $item->update([
            'name' => $validated['name'] ?? $item->name,
            'category_id' => array_key_exists('category_id', $validated) ? $validated['category_id'] : $item->category_id,
            'unit' => array_key_exists('unit', $validated) ? $validated['unit'] : $item->unit,
            'base_price' => array_key_exists('base_price', $validated) ? $validated['base_price'] : $item->base_price,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $item->description,
        ]);

        $item->load('category:id,name,parent_id')->loadCount('subActivityMaterials');

        return response()->json([
            'success' => true,
            'message' => 'BOQ item updated successfully',
            'data' => $this->transformItem($item),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = BoqTemplateItem::withCount('subActivityMaterials')->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'BOQ item not found',
            ], 404);
        }

        if (($item->sub_activity_materials_count ?? 0) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete BOQ item that is already assigned to sub activities',
            ], 422);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'BOQ item deleted successfully',
        ]);
    }

    private function transformItem(BoqTemplateItem $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'category_id' => $item->category_id,
            'category_name' => $item->category?->name,
            'category_parent_name' => $item->category?->parent?->name,
            'unit' => $item->unit,
            'base_price' => $item->base_price !== null ? (float) $item->base_price : null,
            'description' => $item->description,
            'sub_activity_materials_count' => (int) ($item->sub_activity_materials_count ?? 0),
            'created_at' => $item->created_at?->toIso8601String(),
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BoqTemplate;
use App\Models\BuildingType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoqTemplateApiController extends Controller
{
    public function index(): JsonResponse
    {
        $templates = BoqTemplate::with([
            'buildingType.parent:id,name',
            'creator:id,name',
            'templateStages.templateActivities.templateSubActivities',
        ])->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $templates->map(fn (BoqTemplate $template) => $this->transformTemplate($template))->values(),
            'meta' => [
                'total' => $templates->count(),
            ],
        ]);
    }

    public function referenceData(): JsonResponse
    {
        $buildingTypes = BuildingType::with('parent:id,name')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        return response()->json([
            'success' => true,
            'data' => [
                'building_types' => $buildingTypes->map(fn (BuildingType $type) => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'parent_id' => $type->parent_id,
                    'parent_name' => $type->parent?->name,
                ])->values(),
                'roof_types' => [
                    ['name' => 'pitched_roof'],
                    ['name' => 'hidden_roof'],
                    ['name' => 'concrete_roof'],
                ],
                'room_options' => [
                    ['name' => '1'],
                    ['name' => '2'],
                    ['name' => '3'],
                    ['name' => '4'],
                    ['name' => '5+'],
                ],
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $template = BoqTemplate::with([
            'buildingType.parent:id,name',
            'creator:id,name',
            'templateStages.templateActivities.templateSubActivities',
        ])->find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'BOQ template not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformTemplate($template),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:boq_templates,name',
            'description' => 'nullable|string',
            'building_type_id' => 'nullable|exists:building_types,id',
            'roof_type' => 'nullable|in:pitched_roof,hidden_roof,concrete_roof',
            'no_of_rooms' => 'nullable|in:1,2,3,4,5+',
            'square_metre' => 'nullable|numeric|min:0',
            'run_metre' => 'nullable|numeric|min:0',
            'is_active' => 'required|boolean',
        ]);

        $template = BoqTemplate::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'building_type_id' => $validated['building_type_id'] ?? null,
            'roof_type' => $validated['roof_type'] ?? null,
            'no_of_rooms' => $validated['no_of_rooms'] ?? null,
            'square_metre' => $validated['square_metre'] ?? null,
            'run_metre' => $validated['run_metre'] ?? null,
            'is_active' => $validated['is_active'],
            'created_by' => $request->user()?->id,
        ]);

        $template->load([
            'buildingType.parent:id,name',
            'creator:id,name',
            'templateStages.templateActivities.templateSubActivities',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'BOQ template created successfully',
            'data' => $this->transformTemplate($template),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $template = BoqTemplate::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'BOQ template not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:boq_templates,name,' . $id,
            'description' => 'nullable|string',
            'building_type_id' => 'nullable|exists:building_types,id',
            'roof_type' => 'nullable|in:pitched_roof,hidden_roof,concrete_roof',
            'no_of_rooms' => 'nullable|in:1,2,3,4,5+',
            'square_metre' => 'nullable|numeric|min:0',
            'run_metre' => 'nullable|numeric|min:0',
            'is_active' => 'required|boolean',
        ]);

        $template->update([
            'name' => $validated['name'] ?? $template->name,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $template->description,
            'building_type_id' => array_key_exists('building_type_id', $validated) ? $validated['building_type_id'] : $template->building_type_id,
            'roof_type' => array_key_exists('roof_type', $validated) ? $validated['roof_type'] : $template->roof_type,
            'no_of_rooms' => array_key_exists('no_of_rooms', $validated) ? $validated['no_of_rooms'] : $template->no_of_rooms,
            'square_metre' => array_key_exists('square_metre', $validated) ? $validated['square_metre'] : $template->square_metre,
            'run_metre' => array_key_exists('run_metre', $validated) ? $validated['run_metre'] : $template->run_metre,
            'is_active' => $validated['is_active'],
        ]);

        $template->load([
            'buildingType.parent:id,name',
            'creator:id,name',
            'templateStages.templateActivities.templateSubActivities',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'BOQ template updated successfully',
            'data' => $this->transformTemplate($template),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $template = BoqTemplate::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'BOQ template not found',
            ], 404);
        }

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'BOQ template deleted successfully',
        ]);
    }

    private function transformTemplate(BoqTemplate $template): array
    {
        $stages = $template->templateStages ?? collect();
        $activityCount = $stages->sum(fn ($stage) => $stage->templateActivities->count());
        $subActivityCount = $stages->sum(
            fn ($stage) => $stage->templateActivities->sum(
                fn ($activity) => $activity->templateSubActivities->count()
            )
        );

        return [
            'id' => $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'building_type_id' => $template->building_type_id,
            'building_type_name' => $template->buildingType?->name,
            'building_type_parent_name' => $template->buildingType?->parent?->name,
            'roof_type' => $template->roof_type,
            'no_of_rooms' => $template->no_of_rooms,
            'square_metre' => $template->square_metre !== null ? (float) $template->square_metre : null,
            'run_metre' => $template->run_metre !== null ? (float) $template->run_metre : null,
            'is_active' => (bool) $template->is_active,
            'created_by' => $template->created_by,
            'creator_name' => $template->creator?->name,
            'stages_count' => $stages->count(),
            'activities_count' => $activityCount,
            'sub_activities_count' => $subActivityCount,
            'created_at' => $template->created_at?->toIso8601String(),
            'updated_at' => $template->updated_at?->toIso8601String(),
        ];
    }
}

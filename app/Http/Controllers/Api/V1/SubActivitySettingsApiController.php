<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\SubActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubActivitySettingsApiController extends Controller
{
    public function index(): JsonResponse
    {
        $subActivities = SubActivity::with([
            'activity:id,name,construction_stage_id',
            'activity.constructionStage:id,name',
        ])->withCount([
            'materials',
            'boqItems',
            'templateSubActivities',
        ])->orderBy('sort_order')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $subActivities->map(fn (SubActivity $subActivity) => $this->transformSubActivity($subActivity))->values(),
            'meta' => [
                'total' => $subActivities->count(),
            ],
        ]);
    }

    public function referenceData(): JsonResponse
    {
        $activities = Activity::with('constructionStage:id,name')
            ->orderBy('name')
            ->get(['id', 'construction_stage_id', 'name']);

        return response()->json([
            'success' => true,
            'data' => [
                'activities' => $activities->map(fn (Activity $activity) => [
                    'id' => $activity->id,
                    'name' => $activity->name,
                    'construction_stage_name' => $activity->constructionStage?->name,
                ])->values(),
                'duration_units' => [
                    ['name' => 'hours'],
                    ['name' => 'days'],
                    ['name' => 'weeks'],
                ],
                'skill_levels' => [
                    ['name' => 'unskilled'],
                    ['name' => 'semi_skilled'],
                    ['name' => 'skilled'],
                    ['name' => 'specialist'],
                ],
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $subActivity = SubActivity::with([
            'activity:id,name,construction_stage_id',
            'activity.constructionStage:id,name',
        ])->withCount([
            'materials',
            'boqItems',
            'templateSubActivities',
        ])->find($id);

        if (!$subActivity) {
            return response()->json([
                'success' => false,
                'message' => 'Sub activity not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformSubActivity($subActivity),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'activity_id' => 'required|exists:activities,id',
            'name' => 'required|string|max:255|unique:sub_activities,name',
            'description' => 'nullable|string',
            'estimated_duration_hours' => 'required|numeric|min:0',
            'duration_unit' => 'nullable|in:hours,days,weeks',
            'labor_requirement' => 'nullable|integer|min:0',
            'skill_level' => 'nullable|in:unskilled,semi_skilled,skilled,specialist',
            'can_run_parallel' => 'nullable|boolean',
            'weather_dependent' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $subActivity = SubActivity::create([
            'activity_id' => $validated['activity_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'estimated_duration_hours' => $validated['estimated_duration_hours'],
            'duration_unit' => $validated['duration_unit'] ?? 'days',
            'labor_requirement' => $validated['labor_requirement'] ?? null,
            'skill_level' => $validated['skill_level'] ?? 'semi_skilled',
            'can_run_parallel' => (bool) ($validated['can_run_parallel'] ?? false),
            'weather_dependent' => (bool) ($validated['weather_dependent'] ?? false),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);
        $subActivity->load(['activity:id,name,construction_stage_id', 'activity.constructionStage:id,name'])
            ->loadCount(['materials', 'boqItems', 'templateSubActivities']);

        return response()->json([
            'success' => true,
            'message' => 'Sub activity created successfully',
            'data' => $this->transformSubActivity($subActivity),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $subActivity = SubActivity::find($id);

        if (!$subActivity) {
            return response()->json([
                'success' => false,
                'message' => 'Sub activity not found',
            ], 404);
        }

        $validated = $request->validate([
            'activity_id' => 'sometimes|required|exists:activities,id',
            'name' => 'sometimes|required|string|max:255|unique:sub_activities,name,' . $id,
            'description' => 'nullable|string',
            'estimated_duration_hours' => 'sometimes|required|numeric|min:0',
            'duration_unit' => 'nullable|in:hours,days,weeks',
            'labor_requirement' => 'nullable|integer|min:0',
            'skill_level' => 'nullable|in:unskilled,semi_skilled,skilled,specialist',
            'can_run_parallel' => 'nullable|boolean',
            'weather_dependent' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $subActivity->update([
            'activity_id' => $validated['activity_id'] ?? $subActivity->activity_id,
            'name' => $validated['name'] ?? $subActivity->name,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $subActivity->description,
            'estimated_duration_hours' => $validated['estimated_duration_hours'] ?? $subActivity->estimated_duration_hours,
            'duration_unit' => $validated['duration_unit'] ?? $subActivity->duration_unit,
            'labor_requirement' => array_key_exists('labor_requirement', $validated) ? $validated['labor_requirement'] : $subActivity->labor_requirement,
            'skill_level' => $validated['skill_level'] ?? $subActivity->skill_level,
            'can_run_parallel' => array_key_exists('can_run_parallel', $validated) ? (bool) $validated['can_run_parallel'] : $subActivity->can_run_parallel,
            'weather_dependent' => array_key_exists('weather_dependent', $validated) ? (bool) $validated['weather_dependent'] : $subActivity->weather_dependent,
            'sort_order' => $validated['sort_order'] ?? $subActivity->sort_order,
        ]);
        $subActivity->load(['activity:id,name,construction_stage_id', 'activity.constructionStage:id,name'])
            ->loadCount(['materials', 'boqItems', 'templateSubActivities']);

        return response()->json([
            'success' => true,
            'message' => 'Sub activity updated successfully',
            'data' => $this->transformSubActivity($subActivity),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $subActivity = SubActivity::withCount(['materials', 'boqItems', 'templateSubActivities'])->find($id);

        if (!$subActivity) {
            return response()->json([
                'success' => false,
                'message' => 'Sub activity not found',
            ], 404);
        }

        $usageCount = (int) ($subActivity->materials_count ?? 0)
            + (int) ($subActivity->boq_items_count ?? 0)
            + (int) ($subActivity->template_sub_activities_count ?? 0);

        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete sub activity that is already in use',
            ], 422);
        }

        $subActivity->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sub activity deleted successfully',
        ]);
    }

    private function transformSubActivity(SubActivity $subActivity): array
    {
        return [
            'id' => $subActivity->id,
            'activity_id' => $subActivity->activity_id,
            'activity_name' => $subActivity->activity?->name,
            'construction_stage_name' => $subActivity->activity?->constructionStage?->name,
            'name' => $subActivity->name,
            'description' => $subActivity->description,
            'estimated_duration_hours' => $subActivity->estimated_duration_hours,
            'duration_unit' => $subActivity->duration_unit,
            'labor_requirement' => $subActivity->labor_requirement,
            'skill_level' => $subActivity->skill_level,
            'can_run_parallel' => (bool) $subActivity->can_run_parallel,
            'weather_dependent' => (bool) $subActivity->weather_dependent,
            'sort_order' => $subActivity->sort_order ?? 0,
            'materials_count' => (int) ($subActivity->materials_count ?? 0),
            'boq_items_count' => (int) ($subActivity->boq_items_count ?? 0),
            'template_sub_activities_count' => (int) ($subActivity->template_sub_activities_count ?? 0),
            'created_at' => $subActivity->created_at?->toIso8601String(),
            'updated_at' => $subActivity->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ConstructionStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivitySettingsApiController extends Controller
{
    public function index(): JsonResponse
    {
        $activities = Activity::with(['constructionStage:id,name', 'subActivities:id,activity_id'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $activities->map(fn (Activity $activity) => $this->transformActivity($activity))->values(),
            'meta' => [
                'total' => $activities->count(),
            ],
        ]);
    }

    public function referenceData(): JsonResponse
    {
        $stages = ConstructionStage::orderBy('sort_order')->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => [
                'construction_stages' => $stages->map(fn (ConstructionStage $stage) => [
                    'id' => $stage->id,
                    'name' => $stage->name,
                ])->values(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $activity = Activity::with(['constructionStage:id,name', 'subActivities:id,activity_id'])->find($id);

        if (!$activity) {
            return response()->json([
                'success' => false,
                'message' => 'Activity not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformActivity($activity),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'construction_stage_id' => 'required|exists:construction_stages,id',
            'name' => 'required|string|max:255|unique:activities,name',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $activity = Activity::create([
            'construction_stage_id' => $validated['construction_stage_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);
        $activity->load(['constructionStage:id,name', 'subActivities:id,activity_id']);

        return response()->json([
            'success' => true,
            'message' => 'Activity created successfully',
            'data' => $this->transformActivity($activity),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $activity = Activity::find($id);

        if (!$activity) {
            return response()->json([
                'success' => false,
                'message' => 'Activity not found',
            ], 404);
        }

        $validated = $request->validate([
            'construction_stage_id' => 'sometimes|required|exists:construction_stages,id',
            'name' => 'sometimes|required|string|max:255|unique:activities,name,' . $id,
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $activity->update([
            'construction_stage_id' => $validated['construction_stage_id'] ?? $activity->construction_stage_id,
            'name' => $validated['name'] ?? $activity->name,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $activity->description,
            'sort_order' => $validated['sort_order'] ?? $activity->sort_order,
        ]);
        $activity->load(['constructionStage:id,name', 'subActivities:id,activity_id']);

        return response()->json([
            'success' => true,
            'message' => 'Activity updated successfully',
            'data' => $this->transformActivity($activity),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $activity = Activity::withCount('subActivities')->find($id);

        if (!$activity) {
            return response()->json([
                'success' => false,
                'message' => 'Activity not found',
            ], 404);
        }

        if (($activity->sub_activities_count ?? 0) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete an activity that has sub activities',
            ], 422);
        }

        $activity->delete();

        return response()->json([
            'success' => true,
            'message' => 'Activity deleted successfully',
        ]);
    }

    private function transformActivity(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'construction_stage_id' => $activity->construction_stage_id,
            'construction_stage_name' => $activity->constructionStage?->name,
            'name' => $activity->name,
            'description' => $activity->description,
            'sort_order' => $activity->sort_order ?? 0,
            'sub_activities_count' => $activity->relationLoaded('subActivities') ? $activity->subActivities->count() : 0,
            'created_at' => $activity->created_at?->toIso8601String(),
            'updated_at' => $activity->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProjectType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectTypeApiController extends Controller
{
    public function index(): JsonResponse
    {
        $types = ProjectType::withCount('projects')
            ->orderBy('name')
            ->get();

        $data = $types->map(function ($type) {
            return [
                'id' => $type->id,
                'name' => $type->name,
                'description' => $type->description,
                'projects_count' => $type->projects_count,
                'created_at' => $type->created_at?->toIso8601String(),
                'updated_at' => $type->updated_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'total' => $data->count(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $type = ProjectType::withCount('projects')->find($id);

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Project type not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $type->id,
                'name' => $type->name,
                'description' => $type->description,
                'projects_count' => $type->projects_count,
                'created_at' => $type->created_at?->toIso8601String(),
                'updated_at' => $type->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $type = ProjectType::create($validated);
        $type->loadCount('projects');

        return response()->json([
            'success' => true,
            'message' => 'Project type created successfully',
            'data' => [
                'id' => $type->id,
                'name' => $type->name,
                'description' => $type->description,
                'projects_count' => $type->projects_count,
                'created_at' => $type->created_at?->toIso8601String(),
                'updated_at' => $type->updated_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $type = ProjectType::find($id);

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Project type not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $type->update($validated);
        $type->loadCount('projects');

        return response()->json([
            'success' => true,
            'message' => 'Project type updated successfully',
            'data' => [
                'id' => $type->id,
                'name' => $type->name,
                'description' => $type->description,
                'projects_count' => $type->projects_count,
                'created_at' => $type->created_at?->toIso8601String(),
                'updated_at' => $type->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $type = ProjectType::withCount('projects')->find($id);

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Project type not found',
            ], 404);
        }

        if ($type->projects_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete project type that has projects assigned',
            ], 422);
        }

        $type->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project type deleted successfully',
        ]);
    }
}

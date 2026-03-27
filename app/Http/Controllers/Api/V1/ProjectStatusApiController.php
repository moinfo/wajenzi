<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProjectStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProjectStatusApiController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $items = ProjectStatus::orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (ProjectStatus $item) => $this->formatItem($item))->values(),
                'meta' => [
                    'total' => $items->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectStatus index error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch project statuses',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $item = ProjectStatus::create($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
                'message' => 'Project status created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('ProjectStatus store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create project status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = ProjectStatus::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectStatus show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Project status not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = ProjectStatus::findOrFail($id);
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);
            $item->update($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->fresh()),
                'message' => 'Project status updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectStatus update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update project status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $item = ProjectStatus::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Project status deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectStatus destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete project status',
            ], 500);
        }
    }

    private function formatItem(ProjectStatus $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($item->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}

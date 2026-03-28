<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RoleApiController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $items = Role::orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (Role $item) => $this->formatItem($item))->values(),
                'meta' => [
                    'total' => $items->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Role index error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch roles',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $item = Role::create($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
                'message' => 'Role created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Role store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create role: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = Role::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('Role show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = Role::findOrFail($id);
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);
            $item->update($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->fresh()),
                'message' => 'Role updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('Role update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update role: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $item = Role::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('Role destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role',
            ], 500);
        }
    }

    private function formatItem(Role $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($item->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}

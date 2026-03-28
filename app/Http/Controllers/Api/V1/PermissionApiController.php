<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PermissionApiController extends Controller
{
    private const TYPES = ['MENU', 'SETTING', 'REPORT', 'CRUD'];

    public function index(): JsonResponse
    {
        try {
            $items = Permission::orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (Permission $item) => $this->formatItem($item))->values(),
                'meta' => [
                    'total' => $items->count(),
                    'types' => self::TYPES,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Permission index error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch permissions',
            ], 500);
        }
    }

    public function referenceData(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'types' => self::TYPES,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $this->validatePayload($request);
            $item = Permission::create($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
                'message' => 'Permission created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Permission store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create permission: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = Permission::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('Permission show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Permission not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = Permission::findOrFail($id);
            $validated = $this->validatePayload($request);
            $item->update($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->fresh()),
                'message' => 'Permission updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('Permission update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update permission: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $item = Permission::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Permission deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('Permission destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete permission',
            ], 500);
        }
    }

    private function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'permission_type' => 'required|in:' . implode(',', self::TYPES),
            'description' => 'nullable|string|max:255',
            'guard_name' => 'nullable|string|max:255',
            'module' => 'nullable|string|max:255',
        ]);

        $validated['description'] = $validated['description'] ?? $validated['name'];
        $validated['guard_name'] = $validated['guard_name'] ?? 'web';
        $validated['module'] = $validated['module'] ?? 'system';

        return $validated;
    }

    private function formatItem(Permission $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'guard_name' => $item->guard_name,
            'permission_type' => $item->permission_type,
            'module' => $item->module,
            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($item->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}

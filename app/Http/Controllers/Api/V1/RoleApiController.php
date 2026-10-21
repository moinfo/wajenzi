<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Traits\ClearsPermissionCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RoleApiController extends Controller
{
    use ClearsPermissionCache;

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

    /**
     * Permissions of a role, mirroring web `showRolePermissionsPage`.
     * Gated by the same permission the web uses to show the button.
     */
    public function permissions(Request $request, int $id): JsonResponse
    {
        if (!$request->user()?->can('Assign Role To Permissions')) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        try {
            $role = Role::findOrFail($id);

            $menuTree = Menu::with('children')
                ->whereNull('parent_id')
                ->where('status', 'ACTIVE')
                ->orderBy('list_order')
                ->get()
                ->map(fn ($m) => [
                    'name' => $m->name,
                    'children' => $m->children->pluck('name')->values()->all(),
                ])
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'role' => ['id' => $role->id, 'name' => $role->name],
                    'permissions' => Permission::orderBy('name')->get()
                        ->map(fn (Permission $p) => [
                            'id' => $p->id,
                            'name' => $p->name,
                            'permission_type' => $p->permission_type,
                        ])->values(),
                    'assigned_ids' => DB::table('role_has_permissions')
                        ->where('role_id', $id)
                        ->pluck('permission_id')
                        ->values(),
                    'menu_tree' => $menuTree,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Role permissions show error: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Role not found'], 404);
        }
    }

    /** Sync a role's permissions, mirroring web `updateRolePermissions`. */
    public function syncPermissions(Request $request, int $id): JsonResponse
    {
        if (!$request->user()?->can('Assign Role To Permissions')) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        try {
            $role = Role::findOrFail($id);
            $validated = $request->validate([
                'permission_ids' => 'required|array|min:1',
                'permission_ids.*' => 'exists:permissions,id',
            ]);

            $role->permissions()->sync($validated['permission_ids']);
            $this->clearPermissionCache();

            return response()->json([
                'success' => true,
                'message' => "Permissions for role '{$role->name}' updated successfully! Updated "
                    . count($validated['permission_ids']) . ' permissions.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No permissions selected. Please select at least one permission.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Role permissions sync error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating permissions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /** Users and whether they hold the role, mirroring the web role-users form. */
    public function users(Request $request, int $id): JsonResponse
    {
        if (!$request->user()?->can('Assign User To Roles')) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        try {
            $role = Role::findOrFail($id);
            $assigned = DB::table('model_has_roles')
                ->where('role_id', $id)
                ->where('model_type', User::class)
                ->pluck('model_id')
                ->all();

            return response()->json([
                'success' => true,
                'data' => [
                    'role' => ['id' => $role->id, 'name' => $role->name],
                    'users' => DB::table('users')->orderBy('name')->get(['id', 'name'])
                        ->map(fn ($u) => [
                            'id' => $u->id,
                            'name' => $u->name,
                            'assigned' => in_array($u->id, $assigned),
                        ])->values(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Role users show error: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Role not found'], 404);
        }
    }

    /** Replace the role's members, mirroring web `assignUsersToRole` (atomically). */
    public function syncUsers(Request $request, int $id): JsonResponse
    {
        if (!$request->user()?->can('Assign User To Roles')) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        try {
            $role = Role::findOrFail($id);
            $validated = $request->validate([
                'user_ids' => 'present|array',
                'user_ids.*' => 'exists:users,id',
            ]);

            DB::transaction(function () use ($id, $validated) {
                DB::table('model_has_roles')
                    ->where('role_id', $id)
                    ->where('model_type', User::class)
                    ->delete();

                $rows = collect($validated['user_ids'])->unique()->map(fn ($userId) => [
                    'role_id' => $id,
                    'model_id' => $userId,
                    'model_type' => User::class,
                ])->all();

                if ($rows) {
                    DB::table('model_has_roles')->insert($rows);
                }
            });
            $this->clearPermissionCache();

            return response()->json([
                'success' => true,
                'message' => "Users for role '{$role->name}' updated successfully!",
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid user selection',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Role users sync error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating users for role: ' . $e->getMessage(),
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

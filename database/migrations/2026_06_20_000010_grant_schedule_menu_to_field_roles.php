<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Field roles that can be assigned to schedule activities must be able to reach
 * the Project Schedules page from the menu. The sidebar gates each item with
 * @can($menu['name']), needing BOTH the parent "Projects" and child
 * "Project Schedules" permissions. Grant the missing ones:
 *   - Project Schedules → Site Supervisor, Service Engineer
 *   - Projects (parent)  → Site Supervisor, Service Engineer, Project Manager
 *
 * These roles have an empty guard_name while the permissions are guard 'web',
 * so Spatie's guard-aware grant rejects the pair — we write the
 * role_has_permissions pivot directly.
 */
return new class extends Migration
{
    private array $grants = [
        'Project Schedules' => ['Site Supervisor', 'Service Engineer'],
        'Projects'          => ['Site Supervisor', 'Service Engineer', 'Project Manager'],
    ];

    public function up(): void
    {
        foreach ($this->grants as $permissionName => $roleNames) {
            $permissionId = DB::table('permissions')->where('name', $permissionName)->where('guard_name', 'web')->value('id');
            if (!$permissionId) {
                continue;
            }

            $roleIds = DB::table('roles')->whereIn('name', $roleNames)->pluck('id');
            foreach ($roleIds as $roleId) {
                $exists = DB::table('role_has_permissions')
                    ->where('permission_id', $permissionId)->where('role_id', $roleId)->exists();
                if (!$exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permissionId,
                        'role_id'       => $roleId,
                    ]);
                }
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach ($this->grants as $permissionName => $roleNames) {
            $permissionId = DB::table('permissions')->where('name', $permissionName)->where('guard_name', 'web')->value('id');
            if (!$permissionId) {
                continue;
            }
            $roleIds = DB::table('roles')->whereIn('name', $roleNames)->pluck('id');
            DB::table('role_has_permissions')
                ->where('permission_id', $permissionId)
                ->whereIn('role_id', $roleIds)
                ->delete();
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};

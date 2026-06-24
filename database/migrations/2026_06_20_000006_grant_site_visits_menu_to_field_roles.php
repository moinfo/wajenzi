<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Make the Site Visits page visible to the field roles that get assigned to a
 * visit (Site Engineer → Civil Engineer, Site Supervisor). Architect already
 * holds the "Site Visits" menu permission. Without it the sidebar hides the page
 * (gated by @can($menu['name'])), so assignees couldn't reach their visits to
 * confirm readiness / upload the report.
 *
 * NOTE: these roles have an empty guard_name while the permission's guard is
 * 'web', so Spatie's guard-aware givePermissionTo() rejects the pairing. We write
 * the role_has_permissions pivot rows directly (the same way Architect has it).
 */
return new class extends Migration
{
    public function up(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'Site Visits')->where('guard_name', 'web')->value('id');
        if (!$permissionId) {
            return;
        }

        $roleIds = DB::table('roles')->whereIn('name', ['Civil Engineer', 'Site Supervisor'])->pluck('id');

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

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'Site Visits')->where('guard_name', 'web')->value('id');
        if (!$permissionId) {
            return;
        }

        $roleIds = DB::table('roles')->whereIn('name', ['Civil Engineer', 'Site Supervisor'])->pluck('id');

        DB::table('role_has_permissions')
            ->where('permission_id', $permissionId)
            ->whereIn('role_id', $roleIds)
            ->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};

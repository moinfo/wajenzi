<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The performance list already has a delete button + destroy() guarded by the
 * "Delete Performance Reviews" permission, but that permission never existed —
 * so the button never showed. Create it and grant it to the admin roles.
 */
return new class extends Migration
{
    private array $adminRoles = ['System Administrator', 'Managing Director'];

    public function up(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'Delete Performance Reviews')->where('guard_name', 'web')->value('id');

        if (!$permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name'            => 'Delete Performance Reviews',
                'guard_name'      => 'web',
                'permission_type' => 'CRUD',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Grant to admin roles (roles have an empty guard_name, so write the pivot directly).
        $roleIds = DB::table('roles')->whereIn('name', $this->adminRoles)->pluck('id');
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
        $permissionId = DB::table('permissions')->where('name', 'Delete Performance Reviews')->where('guard_name', 'web')->value('id');
        if ($permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};

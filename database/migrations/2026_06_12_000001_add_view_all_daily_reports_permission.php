<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('permissions')->where('name', 'View All Daily Reports')->exists()) {
            DB::table('permissions')->insert([
                'name'            => 'View All Daily Reports',
                'guard_name'      => 'web',
                'permission_type' => 'CRUD',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        $perm = DB::table('permissions')->where('name', 'View All Daily Reports')->first();
        $role = DB::table('roles')->where('name', 'Sales Manager')->first();

        if ($perm && $role) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'role_id'       => $role->id,
                'permission_id' => $perm->id,
            ]);
        }

        // Direct DB writes bypass Spatie's cache; flush so the change takes effect.
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $perm = DB::table('permissions')->where('name', 'View All Daily Reports')->first();
        $role = DB::table('roles')->where('name', 'Sales Manager')->first();

        if ($perm && $role) {
            DB::table('role_has_permissions')
                ->where('permission_id', $perm->id)
                ->where('role_id', $role->id)
                ->delete();
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

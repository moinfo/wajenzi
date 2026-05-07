<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('permissions')->where('name', 'Login As User')->exists()) {
            DB::table('permissions')->insert([
                'name'            => 'Login As User',
                'guard_name'      => 'web',
                'permission_type' => 'CRUD',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        $adminRoleId = DB::table('roles')->where('name', 'System Administrator')->value('id');
        if ($adminRoleId) {
            $permId = DB::table('permissions')->where('name', 'Login As User')->value('id');
            if ($permId && !DB::table('role_has_permissions')->where('role_id', $adminRoleId)->where('permission_id', $permId)->exists()) {
                DB::table('role_has_permissions')->insert(['permission_id' => $permId, 'role_id' => $adminRoleId]);
            }
        }
    }

    public function down(): void
    {
        $permId = DB::table('permissions')->where('name', 'Login As User')->value('id');
        if ($permId) {
            DB::table('role_has_permissions')->where('permission_id', $permId)->delete();
            DB::table('permissions')->where('id', $permId)->delete();
        }
    }
};
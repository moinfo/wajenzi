<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('permissions')->where('name', 'View Field Marketing Stats')->exists();
        if (!$exists) {
            DB::table('permissions')->insert([
                'name'            => 'View Field Marketing Stats',
                'guard_name'      => 'web',
                'permission_type' => 'REPORT',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Assign to System Administrator
        $adminRoleId = DB::table('roles')->where('name', 'System Administrator')->value('id');
        $permId = DB::table('permissions')->where('name', 'View Field Marketing Stats')->value('id');

        if ($adminRoleId && $permId) {
            $exists = DB::table('role_has_permissions')
                ->where('role_id', $adminRoleId)
                ->where('permission_id', $permId)
                ->exists();

            if (!$exists) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permId,
                    'role_id'       => $adminRoleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permId = DB::table('permissions')->where('name', 'View Field Marketing Stats')->value('id');
        if ($permId) {
            DB::table('role_has_permissions')->where('permission_id', $permId)->delete();
            DB::table('permissions')->where('id', $permId)->delete();
        }
    }
};

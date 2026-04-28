<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $adminRoleId = DB::table('roles')->where('name', 'System Administrator')->value('id');
        $permId      = DB::table('permissions')->where('name', 'View WhatsApp Reports')->value('id');

        if ($adminRoleId && $permId) {
            if (!DB::table('role_has_permissions')->where(['role_id' => $adminRoleId, 'permission_id' => $permId])->exists()) {
                DB::table('role_has_permissions')->insert(['role_id' => $adminRoleId, 'permission_id' => $permId]);
            }
        }
    }

    public function down(): void
    {
        $adminRoleId = DB::table('roles')->where('name', 'System Administrator')->value('id');
        $permId      = DB::table('permissions')->where('name', 'View WhatsApp Reports')->value('id');

        if ($adminRoleId && $permId) {
            DB::table('role_has_permissions')->where(['role_id' => $adminRoleId, 'permission_id' => $permId])->delete();
        }
    }
};

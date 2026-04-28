<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('permissions')->where('name', 'View WhatsApp Reports')->exists()) {
            DB::table('permissions')->insert([
                'name'            => 'View WhatsApp Reports',
                'guard_name'      => 'web',
                'permission_type' => 'CRUD',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Grant to all roles that have Manage WhatsApp Campaigns
        $managePerm  = DB::table('permissions')->where('name', 'Manage WhatsApp Campaigns')->first();
        $reportsPerm = DB::table('permissions')->where('name', 'View WhatsApp Reports')->first();

        if ($managePerm && $reportsPerm) {
            $roleIds = DB::table('role_has_permissions')
                ->where('permission_id', $managePerm->id)
                ->pluck('role_id');

            foreach ($roleIds as $roleId) {
                if (!DB::table('role_has_permissions')->where(['role_id' => $roleId, 'permission_id' => $reportsPerm->id])->exists()) {
                    DB::table('role_has_permissions')->insert(['role_id' => $roleId, 'permission_id' => $reportsPerm->id]);
                }
            }
        }
    }

    public function down(): void
    {
        $perm = DB::table('permissions')->where('name', 'View WhatsApp Reports')->first();
        if ($perm) {
            DB::table('role_has_permissions')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }
    }
};

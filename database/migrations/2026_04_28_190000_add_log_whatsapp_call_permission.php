<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('permissions')->where('name', 'Log WhatsApp Call')->exists()) {
            DB::table('permissions')->insert([
                'name'            => 'Log WhatsApp Call',
                'guard_name'      => 'web',
                'permission_type' => 'CRUD',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Grant to all roles that already have Edit WhatsApp Contact
        $editPerm = DB::table('permissions')->where('name', 'Edit WhatsApp Contact')->first();
        $callPerm = DB::table('permissions')->where('name', 'Log WhatsApp Call')->first();

        if ($editPerm && $callPerm) {
            $roleIds = DB::table('role_has_permissions')
                ->where('permission_id', $editPerm->id)
                ->pluck('role_id');

            foreach ($roleIds as $roleId) {
                if (!DB::table('role_has_permissions')->where(['role_id' => $roleId, 'permission_id' => $callPerm->id])->exists()) {
                    DB::table('role_has_permissions')->insert(['role_id' => $roleId, 'permission_id' => $callPerm->id]);
                }
            }
        }
    }

    public function down(): void
    {
        $perm = DB::table('permissions')->where('name', 'Log WhatsApp Call')->first();
        if ($perm) {
            DB::table('role_has_permissions')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }
    }
};

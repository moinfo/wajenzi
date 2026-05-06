<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('permissions')->where('name', 'Delete Project Schedule')->exists()) {
            DB::table('permissions')->insert([
                'name'            => 'Delete Project Schedule',
                'guard_name'      => 'web',
                'permission_type' => 'CRUD',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Grant to every role that already has Edit Project Schedule
        $editPerm   = DB::table('permissions')->where('name', 'Edit Project Schedule')->first();
        $deletePerm = DB::table('permissions')->where('name', 'Delete Project Schedule')->first();

        if ($editPerm && $deletePerm) {
            $roleIds = DB::table('role_has_permissions')
                ->where('permission_id', $editPerm->id)
                ->pluck('role_id');

            foreach ($roleIds as $roleId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'role_id'       => $roleId,
                    'permission_id' => $deletePerm->id,
                ]);
            }
        }
    }

    public function down(): void
    {
        $perm = DB::table('permissions')->where('name', 'Delete Project Schedule')->first();
        if ($perm) {
            DB::table('role_has_permissions')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }
    }
};

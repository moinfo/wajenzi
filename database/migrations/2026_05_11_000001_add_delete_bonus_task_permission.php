<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('permissions')->where('name', 'Delete Bonus Task')->exists()) {
            return;
        }

        $permId = DB::table('permissions')->insertGetId([
            'name'            => 'Delete Bonus Task',
            'guard_name'      => 'web',
            'permission_type' => 'CRUD',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $adminRoles = DB::table('roles')
            ->whereIn('name', ['System Administrator', 'Managing Director'])
            ->pluck('id');

        foreach ($adminRoles as $roleId) {
            DB::table('role_has_permissions')->insert([
                'role_id'       => $roleId,
                'permission_id' => $permId,
            ]);
        }
    }

    public function down(): void
    {
        $perm = DB::table('permissions')->where('name', 'Delete Bonus Task')->first();
        if ($perm) {
            DB::table('role_has_permissions')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }
    }
};
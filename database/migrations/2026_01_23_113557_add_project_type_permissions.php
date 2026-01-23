<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create permissions for Project Type Settings CRUD
        $permissions = [
            ['name' => 'Project Types Settings', 'guard_name' => 'web', 'permission_type' => 'SETTING', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Add Project Type Setting', 'guard_name' => 'web', 'permission_type' => 'CRUD', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Edit Project Type Setting', 'guard_name' => 'web', 'permission_type' => 'CRUD', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Delete Project Type Setting', 'guard_name' => 'web', 'permission_type' => 'CRUD', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('permissions')->insert($permissions);

        // Assign permissions to System Administrator role (role_id = 1)
        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['Project Types Settings', 'Add Project Type Setting', 'Edit Project Type Setting', 'Delete Project Type Setting'])
            ->pluck('id');

        $rolePermissions = $permissionIds->map(fn($id) => ['permission_id' => $id, 'role_id' => 1])->toArray();
        DB::table('role_has_permissions')->insert($rolePermissions);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove role permissions
        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['Project Types Settings', 'Add Project Type Setting', 'Edit Project Type Setting', 'Delete Project Type Setting'])
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();

        // Remove permissions
        DB::table('permissions')->whereIn('name', [
            'Project Types Settings', 'Add Project Type Setting', 'Edit Project Type Setting', 'Delete Project Type Setting',
        ])->delete();
    }
};

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
        Schema::create('project_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Create permissions for Project Status CRUD
        $permissions = [
            ['name' => 'Project Statuses', 'guard_name' => 'web', 'permission_type' => 'SETTING', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Add Project Status', 'guard_name' => 'web', 'permission_type' => 'CRUD', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Edit Project Status', 'guard_name' => 'web', 'permission_type' => 'CRUD', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Delete Project Status', 'guard_name' => 'web', 'permission_type' => 'CRUD', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('permissions')->insert($permissions);

        // Assign permissions to System Administrator role (role_id = 1)
        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['Project Statuses', 'Add Project Status', 'Edit Project Status', 'Delete Project Status'])
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
            ->whereIn('name', ['Project Statuses', 'Add Project Status', 'Edit Project Status', 'Delete Project Status'])
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();

        // Remove permissions
        DB::table('permissions')->whereIn('name', [
            'Project Statuses', 'Add Project Status', 'Edit Project Status', 'Delete Project Status',
        ])->delete();

        Schema::dropIfExists('project_statuses');
    }
};

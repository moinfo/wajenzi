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
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Create permissions for Lead Source CRUD
        $permissions = [
            ['name' => 'Lead Sources', 'guard_name' => 'web', 'permission_type' => 'SETTING', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Add Lead Source', 'guard_name' => 'web', 'permission_type' => 'CRUD', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Edit Lead Source', 'guard_name' => 'web', 'permission_type' => 'CRUD', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Delete Lead Source', 'guard_name' => 'web', 'permission_type' => 'CRUD', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('permissions')->insert($permissions);

        // Assign permissions to System Administrator role (role_id = 1)
        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['Lead Sources', 'Add Lead Source', 'Edit Lead Source', 'Delete Lead Source'])
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
            ->whereIn('name', ['Lead Sources', 'Add Lead Source', 'Edit Lead Source', 'Delete Lead Source'])
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();

        // Remove permissions
        DB::table('permissions')->whereIn('name', [
            'Lead Sources', 'Add Lead Source', 'Edit Lead Source', 'Delete Lead Source',
        ])->delete();

        Schema::dropIfExists('lead_sources');
    }
};

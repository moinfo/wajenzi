<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ScheduleActivityVisibilitySeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(
            ['name' => 'View All Schedule Activities', 'guard_name' => 'web'],
            ['permission_type' => 'CRUD']
        );

        $adminRoles = ['System Administrator', 'Managing Director', 'Admin'];

        foreach ($adminRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if (!$role) continue;

            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permission->id,
                'role_id'       => $role->id,
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Permission "View All Schedule Activities" created and assigned to admin roles.');
    }
}
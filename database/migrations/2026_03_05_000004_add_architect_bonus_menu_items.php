<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Create parent menu "Architect Bonus"
        $parentId = DB::table('menus')->insertGetId([
            'name' => 'Architect Bonus',
            'route' => 'architect-bonus.index',
            'icon' => 'fa fa-trophy',
            'parent_id' => null,
            'list_order' => 11,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Child menu items
        $children = [
            ['name' => 'Bonus Tasks', 'route' => 'architect-bonus.index', 'icon' => 'fa fa-tasks', 'list_order' => 1],
            ['name' => 'Bonus Report', 'route' => 'architect-bonus.report', 'icon' => 'fa fa-chart-bar', 'list_order' => 2],
            ['name' => 'Bonus Settings', 'route' => 'architect-bonus.weights', 'icon' => 'fa fa-cog', 'list_order' => 3],
        ];

        foreach ($children as $child) {
            DB::table('menus')->insert([
                'name' => $child['name'],
                'route' => $child['route'],
                'icon' => $child['icon'],
                'parent_id' => $parentId,
                'list_order' => $child['list_order'],
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add permissions for relevant roles
        $permissionNames = ['Architect Bonus', 'Bonus Tasks', 'Bonus Report', 'Bonus Settings'];

        foreach ($permissionNames as $permName) {
            $permId = DB::table('permissions')->insertGetId([
                'name' => $permName,
                'guard_name' => 'web',
                'permission_type' => 'MENU',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Assign to System Administrator (role_id 1) and Managing Director
            $adminRoles = DB::table('roles')->whereIn('name', ['System Administrator', 'Managing Director'])->pluck('id');
            foreach ($adminRoles as $roleId) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permId,
                ]);
            }

            // Architect Bonus and Bonus Tasks permissions also go to Architect role
            if (in_array($permName, ['Architect Bonus', 'Bonus Tasks'])) {
                $architectRole = DB::table('roles')->where('name', 'Architect')->first();
                if ($architectRole) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $architectRole->id,
                        'permission_id' => $permId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $parent = DB::table('menus')->where('name', 'Architect Bonus')->whereNull('parent_id')->first();
        if ($parent) {
            DB::table('menus')->where('parent_id', $parent->id)->delete();
            DB::table('menus')->where('id', $parent->id)->delete();
        }

        $permNames = ['Architect Bonus', 'Bonus Tasks', 'Bonus Report', 'Bonus Settings'];
        $permIds = DB::table('permissions')->whereIn('name', $permNames)->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->whereIn('id', $permIds)->delete();
    }
};

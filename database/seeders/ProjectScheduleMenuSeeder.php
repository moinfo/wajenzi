<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class ProjectScheduleMenuSeeder extends Seeder
{
    public function run()
    {
        // Create permission (match existing guard_name pattern)
        $permission = Permission::firstOrCreate(
            ['name' => 'Project Schedules', 'guard_name' => 'web']
        );

        // Assign to key roles via DB to avoid guard mismatch
        $roles = ['System Administrator', 'Managing Director', 'Architect', 'Project Manager'];
        foreach ($roles as $roleName) {
            $role = DB::table('roles')->where('name', $roleName)->first();
            if ($role) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permission->id,
                    'role_id' => $role->id,
                ]);
            }
        }

        // Find the Projects parent menu
        $projectsMenu = DB::table('menus')->where('name', 'Projects')->whereNull('parent_id')->first();

        if (!$projectsMenu) {
            $this->command->error('Projects menu not found.');
            return;
        }

        // Check if menu already exists
        $existing = DB::table('menus')
            ->where('name', 'Project Schedules')
            ->where('parent_id', $projectsMenu->id)
            ->first();

        if (!$existing) {
            // Insert after Lead Management (order 13), before Sites (order 14)
            // Shift Sites and items after it
            DB::table('menus')
                ->where('parent_id', $projectsMenu->id)
                ->where('list_order', '>=', 14)
                ->increment('list_order');

            DB::table('menus')->insert([
                'name' => 'Project Schedules',
                'route' => 'project-schedules.index',
                'icon' => 'fa fa-calendar-alt',
                'parent_id' => $projectsMenu->id,
                'list_order' => 14,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('Project Schedules menu item created under Projects.');
        } else {
            $this->command->info('Project Schedules menu item already exists.');
        }
    }
}

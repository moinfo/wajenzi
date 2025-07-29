<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class LeadManagementMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create permission first
        Permission::firstOrCreate(['name' => 'Lead Management']);

        // Find the Projects parent menu
        $projectsMenu = DB::table('menus')->where('name', 'Projects')->whereNull('parent_id')->first();
        
        if (!$projectsMenu) {
            // If Projects menu doesn't exist, create it
            $projectsMenuId = DB::table('menus')->insertGetId([
                'name' => 'Projects',
                'route' => 'projects',
                'icon' => 'fa fa-project-diagram',
                'parent_id' => null,
                'list_order' => 50,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            $projectsMenuId = $projectsMenu->id;
        }

        // Check if Lead Management menu already exists
        $existingMenu = DB::table('menus')
            ->where('name', 'Lead Management')
            ->where('parent_id', $projectsMenuId)
            ->first();

        if (!$existingMenu) {
            // Get the highest order for project submenu items
            $maxOrder = DB::table('menus')
                ->where('parent_id', $projectsMenuId)
                ->max('list_order') ?? 0;

            // Insert Lead Management menu item
            DB::table('menus')->insert([
                'name' => 'Lead Management',
                'route' => 'leads.index',
                'icon' => 'fa fa-users',
                'parent_id' => $projectsMenuId,
                'list_order' => $maxOrder + 1,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $this->command->info('Lead Management menu item created successfully under Projects menu.');
        } else {
            $this->command->info('Lead Management menu item already exists.');
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class SalesDailyReportMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create permission first
        Permission::firstOrCreate(['name' => 'Sales Daily Report']);

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

        // Check if Sales Daily Report menu already exists
        $existingMenu = DB::table('menus')
            ->where('name', 'Sales Daily Report')
            ->where('parent_id', $projectsMenuId)
            ->first();

        if (!$existingMenu) {
            // Get the highest order for project submenu items
            $maxOrder = DB::table('menus')
                ->where('parent_id', $projectsMenuId)
                ->max('list_order') ?? 0;

            // Insert Sales Daily Report menu item
            DB::table('menus')->insert([
                'name' => 'Sales Daily Report',
                'route' => 'sales_daily_reports',
                'icon' => 'fa fa-chart-line',
                'parent_id' => $projectsMenuId,
                'list_order' => $maxOrder + 1,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $this->command->info('Sales Daily Report menu item created successfully under Projects menu.');
        } else {
            $this->command->info('Sales Daily Report menu item already exists.');
        }
    }
}
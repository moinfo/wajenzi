<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class SiteManagementMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find the Projects parent menu
        $projectsMenu = Menu::where('name', 'Projects')->first();
        
        if (!$projectsMenu) {
            $this->command->error('Projects menu not found. Please ensure the Projects menu exists.');
            return;
        }

        // Site Management Menu (Admin only)
        $siteManagementMenu = Menu::firstOrCreate(
            ['name' => 'Site Management', 'parent_id' => $projectsMenu->id],
            [
                'name' => 'Site Management',
                'parent_id' => $projectsMenu->id,
                'icon' => 'fa fa-building',
                'route' => '#',
                'list_order' => 10,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // Site Management Sub-menus
        $siteManagementSubMenus = [
            [
                'name' => 'Sites',
                'route' => 'sites.index',
                'icon' => 'fa fa-list',
                'list_order' => 1
            ],
            [
                'name' => 'Add Site',
                'route' => 'sites.create',
                'icon' => 'fa fa-plus',
                'list_order' => 2
            ],
            [
                'name' => 'Supervisor Assignments',
                'route' => 'site-supervisor-assignments.index',
                'icon' => 'fa fa-user-tie',
                'list_order' => 3
            ]
        ];

        foreach ($siteManagementSubMenus as $submenu) {
            Menu::firstOrCreate(
                ['name' => $submenu['name'], 'parent_id' => $siteManagementMenu->id],
                [
                    'name' => $submenu['name'],
                    'parent_id' => $siteManagementMenu->id,
                    'route' => $submenu['route'],
                    'icon' => $submenu['icon'],
                    'list_order' => $submenu['list_order'],
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        // Site Daily Reports Menu
        $siteDailyReportsMenu = Menu::firstOrCreate(
            ['name' => 'Site Daily Reports', 'parent_id' => $projectsMenu->id],
            [
                'name' => 'Site Daily Reports',
                'parent_id' => $projectsMenu->id,
                'icon' => 'fa fa-clipboard-check',
                'route' => '#',
                'list_order' => 11,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // Site Daily Reports Sub-menus
        $siteDailyReportsSubMenus = [
            [
                'name' => 'Reports List',
                'route' => 'site-daily-reports.index',
                'icon' => 'fa fa-list',
                'list_order' => 1
            ],
            [
                'name' => 'Create Report',
                'route' => 'site-daily-reports.create',
                'icon' => 'fa fa-plus',
                'list_order' => 2
            ],
            [
                'name' => 'My Reports',
                'route' => 'site-daily-reports.my-reports',
                'icon' => 'fa fa-user',
                'list_order' => 3
            ]
        ];

        foreach ($siteDailyReportsSubMenus as $submenu) {
            Menu::firstOrCreate(
                ['name' => $submenu['name'], 'parent_id' => $siteDailyReportsMenu->id],
                [
                    'name' => $submenu['name'],
                    'parent_id' => $siteDailyReportsMenu->id,
                    'route' => $submenu['route'],
                    'icon' => $submenu['icon'],
                    'list_order' => $submenu['list_order'],
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        $this->command->info('Site Management menus seeded successfully!');
    }
}
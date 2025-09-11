<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BillingMenuSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        // Insert main Billing menu
        DB::table('menus')->insert([
            'id' => 89,
            'name' => 'Billing',
            'route' => '',
            'icon' => 'fa fa-file-invoice',
            'parent_id' => null,
            'list_order' => 5,
            'status' => 'ACTIVE',
            'created_at' => $now,
            'updated_at' => $now
        ]);

        // Insert billing sub-menus
        $billingMenus = [
            [
                'id' => 90,
                'name' => 'Dashboard',
                'route' => 'billing.dashboard',
                'icon' => 'fa fa-tachometer-alt',
                'parent_id' => 89,
                'list_order' => 1
            ],
            [
                'id' => 91,
                'name' => 'Quotations',
                'route' => 'billing.quotations.index',
                'icon' => 'fa fa-quote-left',
                'parent_id' => 89,
                'list_order' => 2
            ],
            [
                'id' => 92,
                'name' => 'Proformas',
                'route' => 'billing.proformas.index',
                'icon' => 'fa fa-file-invoice',
                'parent_id' => 89,
                'list_order' => 3
            ],
            [
                'id' => 93,
                'name' => 'Invoices',
                'route' => 'billing.invoices.index',
                'icon' => 'fa fa-file-invoice-dollar',
                'parent_id' => 89,
                'list_order' => 4
            ],
            [
                'id' => 94,
                'name' => 'Payments',
                'route' => 'billing.payments.index',
                'icon' => 'fa fa-credit-card',
                'parent_id' => 89,
                'list_order' => 5
            ],
            [
                'id' => 95,
                'name' => 'Email Management',
                'route' => 'billing.emails.index',
                'icon' => 'fa fa-envelope',
                'parent_id' => 89,
                'list_order' => 6
            ]
        ];

        foreach ($billingMenus as $menu) {
            DB::table('menus')->insert([
                'id' => $menu['id'],
                'name' => $menu['name'],
                'route' => $menu['route'],
                'icon' => $menu['icon'],
                'parent_id' => $menu['parent_id'],
                'list_order' => $menu['list_order'],
                'status' => 'ACTIVE',
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }

        // Insert permissions
        $permissions = [
            ['id' => 583, 'name' => 'Billing'],
            ['id' => 584, 'name' => 'Dashboard'],
            ['id' => 585, 'name' => 'Quotations'],
            ['id' => 586, 'name' => 'Proformas'],
            ['id' => 587, 'name' => 'Invoices'],
            ['id' => 588, 'name' => 'Payments'],
            ['id' => 589, 'name' => 'Email Management']
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insert([
                'id' => $permission['id'],
                'name' => $permission['name'],
                'guard_name' => 'web',
                'permission_type' => 'MENU',
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }

        $this->command->info('Billing menus and permissions added successfully!');
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('menus')->insert( [
            [
            'name' => 'Home',
            'route' => 'home',
            'icon' => 'si si-home',
            ], [
            'name' => 'Collection',
            'route' => 'collection',
            'icon' => 'si si-home',
            ], [
            'name' => 'Transaction Movement',
            'route' => 'transaction_movement',
            'icon' => 'si si-home',
            ],[
            'name' => 'Supplier Receiving',
            'route' => 'supplier_receiving',
            'icon' => 'si si-home',
            ], [
            'name' => 'Gross Profit',
            'route' => 'gross',
            'icon' => 'si si-home',
            ], [
                'name' => 'Expenses',
                'route' => 'expenses',
                'icon' => 'si si-home',
            ],[
            'name' => 'Reports',
            'route' => 'reports',
            'icon' => 'si si-home',
            ],[
            'name' => 'Settings',
            'route' => 'hr_settings',
            'icon' => 'si si-cog',
            ]

        ]);
    }
}

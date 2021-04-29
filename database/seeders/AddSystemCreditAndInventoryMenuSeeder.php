<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddSystemCreditAndInventoryMenuSeeder extends Seeder
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
                'name' => 'System Credit',
                'route' => 'system_credit',
                'icon' => 'si si-cog',
            ],[
                'name' => 'System Inventory',
                'route' => 'system_inventory',
                'icon' => 'si si-cog',
            ]

        ]);
    }
}

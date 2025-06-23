<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddSystemCashAndCapitalMenuSeeder extends Seeder
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
                'name' => 'System Cash',
                'route' => 'system_cash',
                'icon' => 'si si-cog',
            ],[
                'name' => 'System Capital',
                'route' => 'system_capital',
                'icon' => 'si si-cog',
            ]

        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddReceivingMenuSeeder extends Seeder
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
                'name' => 'Receiving',
                'route' => 'receiving',
                'icon' => 'si si-cog',
                'list_order' => '9',
            ]

        ]);
    }
}

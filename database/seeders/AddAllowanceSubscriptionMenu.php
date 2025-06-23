<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddAllowanceSubscriptionMenu extends Seeder
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
                'name' => 'Allowance Subscriptions',
                'route' => 'allowance_subscriptions',
                'icon' => 'si si-cog',
            ]

        ]);
    }
}

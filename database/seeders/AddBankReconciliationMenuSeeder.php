<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddBankReconciliationMenuSeeder extends Seeder
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
                'name' => 'Bank Reconciliation',
                'route' => 'bank_reconciliation',
                'icon' => 'si si-cog',
                'list_order' => '8',
            ]

        ]);
    }
}

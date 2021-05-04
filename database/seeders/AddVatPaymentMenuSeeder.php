<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddVatPaymentMenuSeeder extends Seeder
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
                'name' => 'VAT Payments',
                'route' => 'vat_payment',
                'icon' => 'si si-cog',
                'list_order' => '4',
            ]

        ]);
    }
}

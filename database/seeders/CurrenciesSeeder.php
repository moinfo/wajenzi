<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrenciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('currencies')->insert( [
            [
             'name' => 'Tanzanian Shillings',
            'symbol' => 'TZS',
            ] ,
            [
                'name' => 'United States Dollar',
                'symbol' => 'USD',
            ]
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddPayrollMenuSeeder extends Seeder
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
                'name' => 'Payroll',
                'route' => 'payroll',
                'icon' => 'si si-cog',
            ]

        ]);
    }
}

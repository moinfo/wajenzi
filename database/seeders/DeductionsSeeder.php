<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeductionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('deductions')->insert( [
            [
                'name' => 'PAYE',
                'nature' => 'TAXABLE',
                'abbreviation' => 'PAYE',
                'description' => 'Pay As You Earn',
                'registration_number' => '',
            ],[
                'name' => 'NSSF',
                'nature' => 'GROSS',
                'abbreviation' => 'NSSF',
                'description' => 'National Social Security Fund',
                'registration_number' => '',
            ],[
                'name' => 'WFC',
                'nature' => 'GROSS',
                'abbreviation' => 'WCF',
                'description' => 'Workers Compensation',
                'registration_number' => '',
            ],[
                'name' => 'HESLB',
                'nature' => 'NET',
                'abbreviation' => 'HESLB',
                'description' => 'Higher Education Students Loans Board',
                'registration_number' => '',
            ],[
                'name' => 'SDL',
                'nature' => 'GROSS',
                'abbreviation' => 'SDL',
                'description' => 'Skills & Development Levy',
                'registration_number' => '',
            ],[
                'name' => 'NHIF',
                'nature' => 'NET',
                'abbreviation' => 'NHIF',
                'description' => 'National Health Insurance Fund',
                'registration_number' => '',
            ],

        ]);
    }
}

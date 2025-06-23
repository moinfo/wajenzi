<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeductionSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('deduction_settings')->insert( [
            [
                'deduction_id' => '1',
                'minimum_amount' => '0',
                'maximum_amount' => '270000',
                'employee_percentage' => '0',
                'employer_percentage' => '0',
                'additional_amount' => '0'
            ],[
                'deduction_id' => '1',
                'minimum_amount' => '270000',
                'maximum_amount' => '520000',
                'employee_percentage' => '9',
                'employer_percentage' => '0',
                'additional_amount' => '0'
            ],[
                'deduction_id' => '1',
                'minimum_amount' => '520000',
                'maximum_amount' => '760000',
                'employee_percentage' => '20',
                'employer_percentage' => '0',
                'additional_amount' => '25000'
            ],[
                'deduction_id' => '1',
                'minimum_amount' => '760000',
                'maximum_amount' => '1000000',
                'employee_percentage' => '25',
                'employer_percentage' => '0',
                'additional_amount' => '70500'
            ],[
                'deduction_id' => '1',
                'minimum_amount' => '760000',
                'maximum_amount' => '1000000',
                'employee_percentage' => '25',
                'employer_percentage' => '0',
                'additional_amount' => '70500'
            ],[
                'deduction_id' => '1',
                'minimum_amount' => '760000',
                'maximum_amount' => '1000000',
                'employee_percentage' => '25',
                'employer_percentage' => '0',
                'additional_amount' => '70500'
            ],[
                'deduction_id' => '1',
                'minimum_amount' => '1000000',
                'maximum_amount' => '0',
                'employee_percentage' => '30',
                'employer_percentage' => '0',
                'additional_amount' => '130500'
            ],[
                'deduction_id' => '2',
                'minimum_amount' => '0',
                'maximum_amount' => '0',
                'employee_percentage' => '10',
                'employer_percentage' => '10',
                'additional_amount' => '0'
            ],[
                'deduction_id' => '3',
                'minimum_amount' => '0',
                'maximum_amount' => '0',
                'employee_percentage' => '0',
                'employer_percentage' => '1',
                'additional_amount' => '0'
            ],[
                'deduction_id' => '4',
                'minimum_amount' => '0',
                'maximum_amount' => '0',
                'employee_percentage' => '15',
                'employer_percentage' => '0',
                'additional_amount' => '0'
            ],[
                'deduction_id' => '5',
                'minimum_amount' => '0',
                'maximum_amount' => '0',
                'employee_percentage' => '0',
                'employer_percentage' => '4',
                'additional_amount' => '0'
            ],[
                'deduction_id' => '6',
                'minimum_amount' => '0',
                'maximum_amount' => '0',
                'employee_percentage' => '3',
                'employer_percentage' => '3',
                'additional_amount' => '0'
            ],

        ]);
    }
}

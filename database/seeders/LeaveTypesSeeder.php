<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveTypesSeeder extends Seeder
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
                'name' => 'Annual Leave'
            ] ,
            [
                'name' => 'Casual Leave'
            ],
            [
                'name' => 'Sick Leave'
            ],
            [
                'name' => 'Maternity Leave'
            ],
            [
                'name' => 'Paternity Leave'
            ]
        ]);
    }
}

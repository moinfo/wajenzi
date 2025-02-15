<?php

namespace Database\Seeders;

use App\Models\LeaveType;
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
        $leaveTypes = [
            [
                'name' => 'Annual Leave',
                'days_allowed' => 14,
                'description' => 'Regular annual leave entitlement',
                'notice_days' => 7  // 1 week notice
            ],
            [
                'name' => 'Sick Leave',
                'days_allowed' => 10,
                'description' => 'Leave for medical reasons',
                'notice_days' => 0  // No advance notice needed
            ],
            [
                'name' => 'Personal Leave',
                'days_allowed' => 5,
                'description' => 'Leave for personal matters',
                'notice_days' => 3  // 3 days notice
            ],
            [
                'name' => 'Maternity Leave',
                'days_allowed' => 90,
                'description' => 'Leave for maternity purposes',
                'notice_days' => 30 // 1 month notice
            ]
        ];

        foreach ($leaveTypes as $type) {
            LeaveType::create($type);
        }
    }
}

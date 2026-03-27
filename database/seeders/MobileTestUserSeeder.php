<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MobileTestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create a known test user for mobile app testing
        User::updateOrCreate(
            ['email' => 'mobile@test.com'],
            [
                'name' => 'Mobile Test User',
                'email' => 'mobile@test.com',
                'gender' => 'MALE',
                'employee_number' => 'MOB001',
                'national_id' => '199001011234567890',
                'tin' => '123456789',
                'recruitment_date' => now(),
                'department_id' => 1,
                'supervisor_id' => 1,
                'avatar_id' => 1,
                'email_verified_at' => now(),
                'password' => Hash::make('password123'), // password: password123
                'remember_token' => null,
            ]
        );

        $this->command->info('Mobile test user created: mobile@test.com / password123');
    }
}

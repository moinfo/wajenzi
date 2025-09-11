<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BillingReminderSetting;

class BillingReminderSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BillingReminderSetting::create([
            'auto_reminders_enabled' => true,
            'reminder_intervals' => [28, 21, 14, 7, 3, 1],
            'late_fees_enabled' => true,
            'late_fee_percentage' => 10.00,
            'late_fee_reminders_enabled' => true,
            'late_fee_reminder_interval' => 7,
        ]);
    }
}

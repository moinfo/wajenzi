<?php

namespace App\Console\Commands;

use App\Classes\Utility;
use App\Models\Message;
use App\Models\User;
use Illuminate\Console\Command;

class SendBirthdaySms extends Command
{
    protected $signature = 'sms:send-birthdays';

    protected $description = 'Send birthday SMS to employees whose birthday is today';

    public function handle()
    {
        $balance = Utility::getSmsBalance();
        if ($balance === null || $balance <= 0) {
            $this->error('Insufficient SMS balance. Aborting birthday SMS.');
            return Command::FAILURE;
        }

        $today = now();

        $users = User::where('status', 'ACTIVE')
            ->whereNotNull('dob')
            ->whereNotNull('phone_number')
            ->whereMonth('dob', $today->month)
            ->whereDay('dob', $today->day)
            ->get();

        $sentCount = 0;

        foreach ($users as $user) {
            $phone = $user->phone_number;
            if ($phone[0] == '0') {
                $phone = '255' . substr($phone, 1);
            } elseif ($phone[0] != '2') {
                $phone = '255' . $phone;
            }

            $message = "Happy Birthday {$user->name}! Wishing you a wonderful day from the Wajenzi team.";

            try {
                Utility::sendSingleDestination($phone, $message);

                Message::insert([
                    'name' => $user->name,
                    'phone' => $user->phone_number,
                    'message' => $message,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $sentCount++;
                $this->info("Sent birthday SMS to {$user->name} ({$phone})");
            } catch (\Exception $e) {
                $this->error("Failed to send to {$user->name}: {$e->getMessage()}");
            }
        }

        $this->info("Total birthday SMS sent: {$sentCount}");

        return Command::SUCCESS;
    }
}

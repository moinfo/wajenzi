<?php

namespace App\Console\Commands;

use App\Mail\FollowupReminderMail;
use App\Models\SalesLeadFollowup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendFollowupReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'followup:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email reminders for upcoming follow-ups (1 day before and on the day)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        // Get follow-ups due today
        $todayFollowups = SalesLeadFollowup::with(['lead.salesperson'])
            ->whereDate('followup_date', $today)
            ->whereHas('lead.salesperson')
            ->get();

        // Get follow-ups due tomorrow (1 day reminder)
        $tomorrowFollowups = SalesLeadFollowup::with(['lead.salesperson'])
            ->whereDate('followup_date', $tomorrow)
            ->whereHas('lead.salesperson')
            ->get();

        $sentCount = 0;

        // Send reminders for today's follow-ups
        foreach ($todayFollowups as $followup) {
            $salesperson = $followup->lead->salesperson;
            if ($salesperson && $salesperson->email) {
                try {
                    Mail::to($salesperson->email)->send(new FollowupReminderMail($followup, 'today'));
                    $sentCount++;
                    $this->info("Sent TODAY reminder to {$salesperson->email} for lead: {$followup->lead->name}");
                } catch (\Exception $e) {
                    $this->error("Failed to send email to {$salesperson->email}: {$e->getMessage()}");
                }
            }
        }

        // Send reminders for tomorrow's follow-ups
        foreach ($tomorrowFollowups as $followup) {
            $salesperson = $followup->lead->salesperson;
            if ($salesperson && $salesperson->email) {
                try {
                    Mail::to($salesperson->email)->send(new FollowupReminderMail($followup, 'tomorrow'));
                    $sentCount++;
                    $this->info("Sent TOMORROW reminder to {$salesperson->email} for lead: {$followup->lead->name}");
                } catch (\Exception $e) {
                    $this->error("Failed to send email to {$salesperson->email}: {$e->getMessage()}");
                }
            }
        }

        $this->info("Total reminders sent: {$sentCount}");
        $this->info("Today's follow-ups: {$todayFollowups->count()}, Tomorrow's follow-ups: {$tomorrowFollowups->count()}");

        return Command::SUCCESS;
    }
}

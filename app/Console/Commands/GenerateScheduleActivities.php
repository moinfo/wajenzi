<?php

namespace App\Console\Commands;

use App\Models\ProjectSchedule;
use App\Models\ProjectScheduleActivity;
use App\Services\ProjectScheduleService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateScheduleActivities extends Command
{
    protected $signature = 'schedules:generate-activities {--schedule= : Specific schedule ID, or all if omitted}';
    protected $description = 'Generate activities from templates for schedules that have no activities';

    public function handle()
    {
        $scheduleId = $this->option('schedule');

        $query = ProjectSchedule::query();
        if ($scheduleId) {
            $query->where('id', $scheduleId);
        }

        $schedules = $query->get();

        if ($schedules->isEmpty()) {
            $this->info('No schedules found.');
            return 0;
        }

        $generated = 0;

        foreach ($schedules as $schedule) {
            $existingCount = $schedule->activities()->count();

            if ($existingCount > 0) {
                $this->line("Schedule #{$schedule->id} already has {$existingCount} activities, skipping.");
                continue;
            }

            $this->line("Generating activities for schedule #{$schedule->id} (start: {$schedule->start_date})...");

            $startDate = Carbon::parse($schedule->start_date);

            // Call the protected method via reflection
            $method = new \ReflectionMethod(ProjectScheduleService::class, 'generateActivitiesFromTemplate');
            $method->invoke(null, $schedule, $startDate);

            // Update end date
            $lastActivity = $schedule->activities()->orderBy('end_date', 'desc')->first();
            if ($lastActivity) {
                $schedule->end_date = $lastActivity->end_date;
                $schedule->save();
            }

            $count = $schedule->activities()->count();
            $this->info("  -> Created {$count} activities, end date: {$schedule->end_date}");
            $generated++;
        }

        $this->info("Done. Generated activities for {$generated} schedule(s).");
        return 0;
    }
}

<?php

namespace App\Services;

use App\Mail\ArchitectAssignmentMail;
use App\Models\Lead;
use App\Models\ProjectActivityTemplate;
use App\Models\ProjectAssignment;
use App\Models\ProjectHoliday;
use App\Models\ProjectSchedule;
use App\Models\ProjectScheduleActivity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class ProjectScheduleService
{
    /**
     * Create a project schedule from template
     */
    public static function createScheduleFromTemplate(
        int $leadId,
        Carbon $startDate,
        ?int $architectId = null,
        ?int $createdBy = null
    ): ?ProjectSchedule {
        try {
            DB::beginTransaction();

            $lead = Lead::with('client')->findOrFail($leadId);

            // Auto-assign architect if not provided
            if (!$architectId) {
                $architect = ProjectAssignment::findArchitectWithLeastWorkload();
                $architectId = $architect?->id;
            }

            // Create the schedule
            $schedule = ProjectSchedule::create([
                'lead_id' => $leadId,
                'client_id' => $lead->client_id,
                'start_date' => $startDate,
                'status' => 'draft',
                'assigned_architect_id' => $architectId,
                'created_by' => $createdBy ?? auth()->id(),
            ]);

            // Generate activities from template
            self::generateActivitiesFromTemplate($schedule, $startDate);

            // Calculate end date using direct query
            $lastActivity = ProjectScheduleActivity::where('project_schedule_id', $schedule->id)
                ->orderBy('end_date', 'desc')
                ->first();
            if ($lastActivity) {
                $schedule->end_date = $lastActivity->end_date;
                $schedule->save();
            }

            // Create assignment record
            if ($architectId) {
                $architectRole = Role::where('name', 'Architect')->first();
                if ($architectRole) {
                    ProjectAssignment::create([
                        'lead_id' => $leadId,
                        'project_schedule_id' => $schedule->id,
                        'user_id' => $architectId,
                        'role_id' => $architectRole->id,
                        'status' => 'active',
                        'assigned_by' => $createdBy ?? auth()->id(),
                        'assigned_at' => now(),
                    ]);
                }
            }

            DB::commit();

            Log::info("Project schedule created for lead #{$leadId} with architect #{$architectId}");

            // Send email notification to assigned architect
            if ($architectId) {
                self::notifyArchitect($schedule);
            }

            return $schedule;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create project schedule: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send email notification to assigned architect
     */
    public static function notifyArchitect(ProjectSchedule $schedule): void
    {
        try {
            // Load relationships for email
            $schedule->load(['lead', 'assignedArchitect', 'activities']);

            $architect = $schedule->assignedArchitect;
            if (!$architect || !$architect->email) {
                Log::warning("Cannot notify architect - no architect or email for schedule #{$schedule->id}");
                return;
            }

            Mail::to($architect->email)->send(new ArchitectAssignmentMail($schedule));

            Log::info("Architect assignment notification sent to {$architect->email} for schedule #{$schedule->id}");

        } catch (\Exception $e) {
            // Don't fail the whole process if email fails
            Log::error("Failed to send architect notification: " . $e->getMessage());
        }
    }

    /**
     * Generate activities from template
     */
    protected static function generateActivitiesFromTemplate(ProjectSchedule $schedule, Carbon $startDate): void
    {
        $templates = ProjectActivityTemplate::getOrderedTemplates();
        $activityDates = [];

        foreach ($templates as $template) {
            // Determine start date based on predecessor
            if ($template->predecessor_code && isset($activityDates[$template->predecessor_code])) {
                // Start after predecessor ends (next working day)
                $activityStartDate = self::getNextWorkingDay($activityDates[$template->predecessor_code]['end_date']->copy()->addDay());
            } else {
                // First activity starts on project start date
                $activityStartDate = self::getNextWorkingDay($startDate->copy());
            }

            // Calculate end date based on duration (working days)
            $activityEndDate = self::addWorkingDays($activityStartDate, $template->duration_days);

            // Create the activity
            ProjectScheduleActivity::create([
                'project_schedule_id' => $schedule->id,
                'activity_code' => $template->activity_code,
                'name' => $template->name,
                'phase' => $template->phase,
                'discipline' => $template->discipline,
                'start_date' => $activityStartDate,
                'duration_days' => $template->duration_days,
                'end_date' => $activityEndDate,
                'predecessor_code' => $template->predecessor_code,
                'status' => 'pending',
                'sort_order' => $template->sort_order,
            ]);

            // Store for predecessor reference
            $activityDates[$template->activity_code] = [
                'start_date' => $activityStartDate,
                'end_date' => $activityEndDate,
            ];
        }
    }

    /**
     * Add working days to a date (excluding weekends and holidays)
     */
    public static function addWorkingDays(Carbon $date, int $days): Carbon
    {
        $result = $date->copy();
        $addedDays = 0;

        // Duration of 1 means same day (if it's a working day)
        while ($addedDays < $days - 1) {
            $result->addDay();
            if (self::isWorkingDay($result)) {
                $addedDays++;
            }
        }

        return $result;
    }

    /**
     * Get next working day
     */
    public static function getNextWorkingDay(Carbon $date): Carbon
    {
        $result = $date->copy();
        while (!self::isWorkingDay($result)) {
            $result->addDay();
        }
        return $result;
    }

    /**
     * Check if a date is a working day
     */
    public static function isWorkingDay(Carbon $date): bool
    {
        // Check if weekend (Saturday = 6, Sunday = 0)
        if ($date->isWeekend()) {
            return false;
        }

        // Check if holiday
        if (ProjectHoliday::isHoliday($date->format('Y-m-d'))) {
            return false;
        }

        return true;
    }

    /**
     * Recalculate schedule dates from a new start date
     */
    public static function recalculateSchedule(ProjectSchedule $schedule, Carbon $newStartDate): bool
    {
        try {
            DB::beginTransaction();

            $activities = $schedule->activities()->orderBy('sort_order')->get();
            $activityDates = [];

            foreach ($activities as $activity) {
                // Determine start date based on predecessor
                if ($activity->predecessor_code && isset($activityDates[$activity->predecessor_code])) {
                    $activityStartDate = self::getNextWorkingDay($activityDates[$activity->predecessor_code]['end_date']->copy()->addDay());
                } else {
                    $activityStartDate = self::getNextWorkingDay($newStartDate->copy());
                }

                // Calculate end date
                $activityEndDate = self::addWorkingDays($activityStartDate, $activity->duration_days);

                // Update the activity
                $activity->update([
                    'start_date' => $activityStartDate,
                    'end_date' => $activityEndDate,
                ]);

                // Store for predecessor reference
                $activityDates[$activity->activity_code] = [
                    'start_date' => $activityStartDate,
                    'end_date' => $activityEndDate,
                ];
            }

            // Update schedule dates
            $lastActivity = $schedule->activities()->orderBy('end_date', 'desc')->first();
            $schedule->update([
                'start_date' => $newStartDate,
                'end_date' => $lastActivity?->end_date,
            ]);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to recalculate schedule: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Auto-assign architect on first payment
     */
    public static function assignArchitectOnFirstPayment(int $leadId): ?ProjectSchedule
    {
        // Check if schedule already exists
        $existingSchedule = ProjectSchedule::where('lead_id', $leadId)->first();
        if ($existingSchedule) {
            return $existingSchedule;
        }

        // Create schedule starting from tomorrow
        $startDate = Carbon::tomorrow();
        return self::createScheduleFromTemplate($leadId, $startDate);
    }
}

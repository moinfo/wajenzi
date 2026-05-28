<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Repairs project_schedules.end_date values corrupted by a bug in
 * ProjectScheduleService::recalculateSchedule(): it used
 * activities()->orderBy('end_date','desc')->first(), but the activities()
 * relationship already applies orderBy('sort_order'), so the chained order
 * only became a SECONDARY sort and first() returned the EARLIEST activity —
 * collapsing each schedule's end_date down to (roughly) its start date.
 *
 * The code is fixed to use max('end_date'); this migration recomputes the
 * stored end_date for every schedule from the true max of its activities.
 * end_date is a derived value, so this is safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        $maxes = DB::table('project_schedule_activities')
            ->select('project_schedule_id', DB::raw('MAX(end_date) as max_end'))
            ->whereNotNull('end_date')
            ->groupBy('project_schedule_id')
            ->get();

        foreach ($maxes as $row) {
            DB::table('project_schedules')
                ->where('id', $row->project_schedule_id)
                ->update(['end_date' => $row->max_end]);
        }
    }

    public function down(): void
    {
        // No-op: end_date is a derived value; there is no meaningful prior state
        // to restore (the previous values were the corrupted ones).
    }
};

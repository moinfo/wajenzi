<?php

use App\Models\ProjectSchedule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelProcessApproval\DataObjects\ApprovalStatusStepData;

return new class extends Migration
{
    public function up(): void
    {
        // Schedules submitted before the flow steps were configured have an empty
        // `steps` JSON in process_approval_statuses, which makes nextApprovalStep()
        // return null and hides the Ringlesoft approval actions from CEO/MD/GM.
        // Rebuild the snapshot from the now-configured flow steps.
        ProjectSchedule::with('approvalStatus')->get()->each(function (ProjectSchedule $schedule) {
            $status = $schedule->approvalStatus;
            if (!$status) {
                return;
            }

            $currentSteps = is_array($status->steps) ? $status->steps : [];
            if (!empty($currentSteps)) {
                return;
            }

            $rebuilt = $schedule->approvalFlowSteps()
                ->map(fn ($step) => ApprovalStatusStepData::fromApprovalFlowStep($step)->toArray())
                ->all();

            if (empty($rebuilt)) {
                return;
            }

            DB::table('process_approval_statuses')
                ->where('id', $status->id)
                ->update(['steps' => json_encode($rebuilt), 'updated_at' => now()]);
        });
    }

    public function down(): void
    {
        // Intentionally non-reversible — restoring empty steps would re-break the workflow.
    }
};

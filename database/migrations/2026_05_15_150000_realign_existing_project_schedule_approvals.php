<?php

use App\Models\ProjectSchedule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelProcessApproval\DataObjects\ApprovalStatusStepData;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalActionEnum;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;

return new class extends Migration
{
    public function up(): void
    {
        ProjectSchedule::with('approvalStatus')->get()->each(function (ProjectSchedule $schedule) {
            $status = $schedule->approvalStatus;
            if (!$status) {
                return;
            }

            $flowSteps = $schedule->approvalFlowSteps();
            if ($flowSteps->isEmpty()) {
                return;
            }

            $rebuilt = $flowSteps->map(fn ($step) => ApprovalStatusStepData::fromApprovalFlowStep($step)->toArray())->all();

            $lifecycle = $schedule->status;

            // Schedules already past the approval gate (legacy one-click confirmations
            // pre-date the CEO/MD/GM flow). Mark every step as approved in the snapshot
            // so the UI accurately reflects "approved end-to-end".
            $isPastApproval = in_array($lifecycle, ['confirmed', 'in_progress', 'completed'], true);

            $newStatusEnum = match ($lifecycle) {
                'draft' => ApprovalStatusEnum::CREATED->value,
                'pending_confirmation' => $status->status, // leave wherever Ringlesoft currently has it (Submitted/Pending/etc.)
                default => $isPastApproval ? ApprovalStatusEnum::APPROVED->value : $status->status,
            };

            if ($isPastApproval) {
                foreach ($rebuilt as &$step) {
                    $step['process_approval_action'] = ApprovalActionEnum::APPROVED->value;
                }
                unset($step);
            }

            DB::table('process_approval_statuses')
                ->where('id', $status->id)
                ->update([
                    'steps' => json_encode($rebuilt),
                    'status' => $newStatusEnum,
                    'creator_id' => $schedule->assigned_architect_id ?? $status->creator_id,
                    'updated_at' => now(),
                ]);
        });
    }

    public function down(): void
    {
        // Non-reversible data realignment.
    }
};

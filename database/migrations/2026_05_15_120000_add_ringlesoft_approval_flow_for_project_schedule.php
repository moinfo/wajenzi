<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\ProjectSchedule;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;

return new class extends Migration
{
    public function up(): void
    {
        $existing = DB::table('process_approval_flows')
            ->where('approvable_type', 'App\\Models\\ProjectSchedule')
            ->first();

        $flowId = $existing
            ? $existing->id
            : DB::table('process_approval_flows')->insertGetId([
                'approvable_type' => 'App\\Models\\ProjectSchedule',
                'name' => 'ProjectSchedule',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $hasStep = DB::table('process_approval_flow_steps')
            ->where('process_approval_flow_id', $flowId)
            ->exists();

        if (!$hasStep) {
            DB::table('process_approval_flow_steps')->insert([
                'process_approval_flow_id' => $flowId,
                'role_id' => 2, // Managing Director
                'order' => 1,
                'action' => 'APPROVE',
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Backfill approval statuses for existing schedules so they can transition through the flow.
        $schedules = ProjectSchedule::whereDoesntHave('approvalStatus')->get();
        foreach ($schedules as $schedule) {
            $steps = $schedule->approvalFlowSteps()->map(fn ($item) => $item->toApprovalStatusArray());
            $isConfirmed = $schedule->status === 'confirmed';

            $schedule->approvalStatus()->create([
                'steps' => $steps,
                'status' => $isConfirmed ? ApprovalStatusEnum::APPROVED->value : ApprovalStatusEnum::CREATED->value,
                'creator_id' => $schedule->assigned_architect_id ?? $schedule->created_by ?? 1,
            ]);
        }

        // Realign creator_id on existing approval statuses so the assigned architect can submit.
        DB::table('process_approval_statuses as pas')
            ->join('project_schedules as ps', function ($join) {
                $join->on('pas.approvable_id', '=', 'ps.id')
                     ->where('pas.approvable_type', '=', 'App\\Models\\ProjectSchedule');
            })
            ->whereNotNull('ps.assigned_architect_id')
            ->update(['pas.creator_id' => DB::raw('ps.assigned_architect_id')]);
    }

    public function down(): void
    {
        DB::table('process_approval_statuses')
            ->where('approvable_type', 'App\\Models\\ProjectSchedule')
            ->delete();

        $flow = DB::table('process_approval_flows')
            ->where('approvable_type', 'App\\Models\\ProjectSchedule')
            ->first();

        if ($flow) {
            DB::table('process_approval_flow_steps')
                ->where('process_approval_flow_id', $flow->id)
                ->delete();
            DB::table('process_approval_flows')->where('id', $flow->id)->delete();
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\ProjectBoq;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create the approval flow for ProjectBoq
        $flowId = DB::table('process_approval_flows')->insertGetId([
            'approvable_type' => 'App\\Models\\ProjectBoq',
            'name' => 'ProjectBoq',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Add approval step: Managing Director (role_id=2) APPROVE
        DB::table('process_approval_flow_steps')->insert([
            'process_approval_flow_id' => $flowId,
            'role_id' => 2, // Managing Director
            'order' => 1,
            'action' => 'APPROVE',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Create approval statuses for existing BOQ records
        $boqs = ProjectBoq::whereDoesntHave('approvalStatus')->get();

        foreach ($boqs as $boq) {
            $steps = $boq->approvalFlowSteps()->map(fn($item) => $item->toApprovalStatusArray());
            $isApproved = strtolower($boq->status) === 'approved';

            $boq->approvalStatus()->create([
                'steps' => $steps,
                'status' => $isApproved ? ApprovalStatusEnum::APPROVED->value : ApprovalStatusEnum::SUBMITTED->value,
                'creator_id' => $boq->created_by ?? 1,
            ]);
        }
    }

    public function down(): void
    {
        // Remove approval statuses for ProjectBoq
        DB::table('process_approval_statuses')
            ->where('approvable_type', 'App\\Models\\ProjectBoq')
            ->delete();

        // Remove flow steps and flow
        $flow = DB::table('process_approval_flows')
            ->where('approvable_type', 'App\\Models\\ProjectBoq')
            ->first();

        if ($flow) {
            DB::table('process_approval_flow_steps')
                ->where('process_approval_flow_id', $flow->id)
                ->delete();
            DB::table('process_approval_flows')->where('id', $flow->id)->delete();
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Approval flow for KpiReview:
 *   Step 1: Supervisor (resolved per-employee by KpiReview::getNextApprovers() override)
 *           Stored role_id is a placeholder — the override ignores it.
 *   Step 2: Managing Director  (role_id = 2)
 *   Step 3: CEO                (role_id = 16)
 */
return new class extends Migration {
    public function up(): void
    {
        $existing = DB::table('process_approval_flows')
            ->where('approvable_type', 'App\\Models\\KpiReview')
            ->first();

        $flowId = $existing
            ? $existing->id
            : DB::table('process_approval_flows')->insertGetId([
                'approvable_type' => 'App\\Models\\KpiReview',
                'name'            => 'KpiReview',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

        $hasStep = DB::table('process_approval_flow_steps')
            ->where('process_approval_flow_id', $flowId)
            ->exists();

        if ($hasStep) {
            return;
        }

        // Resolve role ids by name (portable across environments where ids differ)
        $supervisorPlaceholderRole = DB::table('roles')->where('name', 'Managing Director')->value('id') ?? 2;
        $mdRoleId                  = DB::table('roles')->where('name', 'Managing Director')->value('id') ?? 2;
        $ceoRoleId                 = DB::table('roles')->where('name', 'CEO')->value('id')
                                  ?? DB::table('roles')->where('name', 'Chief Executive Officer')->value('id')
                                  ?? 16;

        $now = now();
        DB::table('process_approval_flow_steps')->insert([
            [
                'process_approval_flow_id' => $flowId,
                'role_id'   => $supervisorPlaceholderRole,
                'order'     => 1,
                'action'    => 'APPROVE',
                'active'    => 1,
                'created_at'=> $now,
                'updated_at'=> $now,
            ],
            [
                'process_approval_flow_id' => $flowId,
                'role_id'   => $mdRoleId,
                'order'     => 2,
                'action'    => 'APPROVE',
                'active'    => 1,
                'created_at'=> $now,
                'updated_at'=> $now,
            ],
            [
                'process_approval_flow_id' => $flowId,
                'role_id'   => $ceoRoleId,
                'order'     => 3,
                'action'    => 'APPROVE',
                'active'    => 1,
                'created_at'=> $now,
                'updated_at'=> $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('process_approval_statuses')
            ->where('approvable_type', 'App\\Models\\KpiReview')
            ->delete();

        $flow = DB::table('process_approval_flows')
            ->where('approvable_type', 'App\\Models\\KpiReview')
            ->first();
        if ($flow) {
            DB::table('process_approval_flow_steps')->where('process_approval_flow_id', $flow->id)->delete();
            DB::table('process_approval_flows')->where('id', $flow->id)->delete();
        }
    }
};

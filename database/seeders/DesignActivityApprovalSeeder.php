<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DesignActivityApprovalSeeder extends Seeder
{
    /**
     * Marks the phase-final activities as requiring CEO/MD approval and
     * creates a single ProcessApprovalFlow for ProjectScheduleActivity.
     *
     * Approval-required activity codes (end of each design phase):
     *   A0  — Survey complete         → unlocks 2D design
     *   A7  — 2D Final Draft          → unlocks 3D design
     *   B7  — 3D Final Draft          → unlocks Structural & MEP
     *   C1  — Structural & MEP done   → unlocks BOQ
     *   C2  — BOQ prepared            → unlocks final submission
     *   C4  — Final Submission        → full design sign-off
     */
    public function run(): void
    {
        $approvalCodes = ['A0', 'A7', 'B7', 'C1', 'C2', 'C4'];

        DB::table('project_activity_templates')
            ->whereIn('activity_code', $approvalCodes)
            ->update(['requires_approval' => true]);

        $this->command->info('Marked ' . count($approvalCodes) . ' activity templates as requiring approval.');

        // Resolve the Managing Director role (CEO/MD approver)
        $mdRole = DB::table('roles')->where('name', 'Managing Director')->first();
        if (!$mdRole) {
            $this->command->error('Managing Director role not found. Skipping approval flow creation.');
            return;
        }

        $approvableType = 'App\\Models\\ProjectScheduleActivity';

        $existingFlow = DB::table('process_approval_flows')
            ->where('approvable_type', $approvableType)
            ->first();

        if ($existingFlow) {
            $this->command->info("Approval flow already exists for ProjectScheduleActivity (ID: {$existingFlow->id})");
            return;
        }

        $flowId = DB::table('process_approval_flows')->insertGetId([
            'name'            => 'Design Stage Approval',
            'approvable_type' => $approvableType,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        DB::table('process_approval_flow_steps')->insert([
            'process_approval_flow_id' => $flowId,
            'role_id'                  => $mdRole->id,
            'action'                   => 'APPROVE',
            'order'                    => 1,
            'active'                   => 1,
            'created_at'               => now(),
            'updated_at'               => now(),
        ]);

        $this->command->info("Created Design Stage Approval flow (ID: {$flowId}) with Managing Director as approver.");
    }
}

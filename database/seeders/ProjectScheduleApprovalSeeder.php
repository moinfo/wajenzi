<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectScheduleApprovalSeeder extends Seeder
{
    /**
     * Creates a ProcessApprovalFlow for ProjectSchedule so that
     * submitted schedules require Managing Director sign-off before
     * activities can begin.
     */
    public function run(): void
    {
        $mdRole = DB::table('roles')->where('name', 'Managing Director')->first();
        if (!$mdRole) {
            $this->command->error('Managing Director role not found. Skipping.');
            return;
        }

        $approvableType = 'App\\Models\\ProjectSchedule';

        $existingFlow = DB::table('process_approval_flows')
            ->where('approvable_type', $approvableType)
            ->first();

        if ($existingFlow) {
            $this->command->info("Approval flow already exists for ProjectSchedule (ID: {$existingFlow->id})");
            return;
        }

        $flowId = DB::table('process_approval_flows')->insertGetId([
            'name'            => 'Project Schedule Approval',
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

        $this->command->info("Created Project Schedule Approval flow (ID: {$flowId}) with Managing Director as approver.");
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BoqPlanApprovalSeeder extends Seeder
{
    public function run(): void
    {
        $mdRole = DB::table('roles')->where('name', 'Managing Director')->first();
        if (!$mdRole) {
            $this->command->error('Managing Director role not found. Skipping.');
            return;
        }

        $approvableType = 'App\\Models\\ProjectBoqPlan';

        $existing = DB::table('process_approval_flows')
            ->where('approvable_type', $approvableType)
            ->first();

        if ($existing) {
            $this->command->info("Approval flow already exists for ProjectBoqPlan (ID: {$existing->id})");
            return;
        }

        $flowId = DB::table('process_approval_flows')->insertGetId([
            'name'            => 'BOQ Preparation Plan Approval',
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

        $this->command->info("Created BOQ Plan Approval flow (ID: {$flowId}) — Managing Director approver.");
    }
}

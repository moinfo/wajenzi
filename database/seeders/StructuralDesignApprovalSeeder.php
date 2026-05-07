<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StructuralDesignApprovalSeeder extends Seeder
{
    public function run(): void
    {
        $mdRole = DB::table('roles')->where('name', 'Managing Director')->first();

        if (!$mdRole) {
            $this->command->error('Managing Director role not found.');
            return;
        }

        $approvableType = 'App\\Models\\ProjectStructuralDesign';

        $existingFlow = DB::table('process_approval_flows')
            ->where('approvable_type', $approvableType)
            ->first();

        if ($existingFlow) {
            $this->command->info("Approval flow already exists for ProjectStructuralDesign (ID: {$existingFlow->id})");
            return;
        }

        $flowId = DB::table('process_approval_flows')->insertGetId([
            'name'            => 'Structural Design Approval',
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

        $this->command->info("Created Structural Design Approval flow (ID: {$flowId}).");
    }
}

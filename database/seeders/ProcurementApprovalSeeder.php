<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProcurementApprovalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates approval workflows for procurement models:
     * - ProjectMaterialRequest
     * - QuotationComparison
     * - MaterialInspection
     */
    public function run(): void
    {
        // Get role IDs
        $mdRole = DB::table('roles')->where('name', 'Managing Director')->first();
        $adminRole = DB::table('roles')->where('name', 'System Administrator')->first();
        $supervisorRole = DB::table('roles')->where('name', 'Site Supervisor')->first();

        if (!$mdRole) {
            $this->command->error('Managing Director role not found. Please create roles first.');
            return;
        }

        // Define approval flows for procurement models
        $flows = [
            [
                'name' => 'ProjectMaterialRequest',
                'approvable_type' => 'App\\Models\\ProjectMaterialRequest',
                'steps' => [
                    ['role_id' => $mdRole->id, 'action' => 'APPROVE', 'order' => 1],
                ]
            ],
            [
                'name' => 'QuotationComparison',
                'approvable_type' => 'App\\Models\\QuotationComparison',
                'steps' => [
                    ['role_id' => $mdRole->id, 'action' => 'APPROVE', 'order' => 1],
                ]
            ],
            [
                'name' => 'MaterialInspection',
                'approvable_type' => 'App\\Models\\MaterialInspection',
                'steps' => [
                    ['role_id' => $supervisorRole?->id ?? $mdRole->id, 'action' => 'VERIFY', 'order' => 1],
                    ['role_id' => $mdRole->id, 'action' => 'APPROVE', 'order' => 2],
                ]
            ],
        ];

        foreach ($flows as $flowConfig) {
            $this->createApprovalFlow($flowConfig);
        }

        $this->command->info('Procurement approval workflows configured successfully!');
    }

    /**
     * Create approval flow and its steps
     */
    private function createApprovalFlow(array $config): void
    {
        // Create or get the flow
        $existingFlow = DB::table('process_approval_flows')
            ->where('approvable_type', $config['approvable_type'])
            ->first();

        if ($existingFlow) {
            $flowId = $existingFlow->id;
            $this->command->info("Flow already exists for: {$config['name']} (ID: {$flowId})");
        } else {
            $flowId = DB::table('process_approval_flows')->insertGetId([
                'name' => $config['name'],
                'approvable_type' => $config['approvable_type'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $this->command->info("Created flow for: {$config['name']} (ID: {$flowId})");
        }

        // Create steps for this flow
        foreach ($config['steps'] as $step) {
            DB::table('process_approval_flow_steps')->updateOrInsert(
                [
                    'process_approval_flow_id' => $flowId,
                    'role_id' => $step['role_id'],
                    'action' => $step['action']
                ],
                [
                    'process_approval_flow_id' => $flowId,
                    'role_id' => $step['role_id'],
                    'order' => $step['order'],
                    'action' => $step['action'],
                    'active' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        $this->command->info("  - Added " . count($config['steps']) . " approval step(s)");
    }
}

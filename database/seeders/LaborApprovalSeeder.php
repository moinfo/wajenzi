<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LaborApprovalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates approval workflows for labor procurement models:
     * - LaborRequest (MD approval)
     * - LaborInspection (SPV verify → MD approve)
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

        // Define approval flows for labor procurement models
        $flows = [
            [
                'name' => 'LaborRequest',
                'approvable_type' => 'App\\Models\\LaborRequest',
                'steps' => [
                    ['role_id' => $mdRole->id, 'action' => 'APPROVE', 'order' => 1],
                ]
            ],
            [
                'name' => 'LaborInspection',
                'approvable_type' => 'App\\Models\\LaborInspection',
                'steps' => [
                    ['role_id' => $supervisorRole?->id ?? $mdRole->id, 'action' => 'VERIFY', 'order' => 1],
                    ['role_id' => $mdRole->id, 'action' => 'APPROVE', 'order' => 2],
                ]
            ],
        ];

        foreach ($flows as $flowConfig) {
            $this->createApprovalFlow($flowConfig);
        }

        // Create document types for labor procurement
        $this->createDocumentTypes();

        $this->command->info('Labor procurement approval workflows configured successfully!');
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
            // Get next ID manually (some tables don't have AUTO_INCREMENT)
            $flowId = (DB::table('process_approval_flows')->max('id') ?? 0) + 1;
            DB::table('process_approval_flows')->insert([
                'id' => $flowId,
                'name' => $config['name'],
                'approvable_type' => $config['approvable_type'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $this->command->info("Created flow for: {$config['name']} (ID: {$flowId})");
        }

        // Create steps for this flow
        foreach ($config['steps'] as $step) {
            // Check if step already exists
            $existingStep = DB::table('process_approval_flow_steps')
                ->where('process_approval_flow_id', $flowId)
                ->where('role_id', $step['role_id'])
                ->where('action', $step['action'])
                ->first();

            if (!$existingStep) {
                // Get next ID manually (some tables don't have AUTO_INCREMENT)
                $stepId = (DB::table('process_approval_flow_steps')->max('id') ?? 0) + 1;
                DB::table('process_approval_flow_steps')->insert([
                    'id' => $stepId,
                    'process_approval_flow_id' => $flowId,
                    'role_id' => $step['role_id'],
                    'order' => $step['order'],
                    'action' => $step['action'],
                    'active' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        $this->command->info("  - Added " . count($config['steps']) . " approval step(s)");
    }

    /**
     * Create document types for labor procurement if they don't exist
     */
    private function createDocumentTypes(): void
    {
        // Check if document_types table exists
        if (!\Illuminate\Support\Facades\Schema::hasTable('document_types')) {
            $this->command->warn('document_types table does not exist. Skipping document type creation.');
            return;
        }

        $documentTypes = [
            ['name' => 'Labor Request', 'description' => 'Labor/Artisan engagement request'],
            ['name' => 'Labor Inspection', 'description' => 'Labor work inspection record'],
        ];

        foreach ($documentTypes as $docType) {
            $existing = DB::table('document_types')->where('name', $docType['name'])->first();
            if (!$existing) {
                // Get next ID manually (some tables don't have AUTO_INCREMENT)
                $docId = (DB::table('document_types')->max('id') ?? 0) + 1;
                DB::table('document_types')->insert([
                    'id' => $docId,
                    'name' => $docType['name'],
                    'description' => $docType['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->command->info("Created document type: {$docType['name']}");
            }
        }
    }
}

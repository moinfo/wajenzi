<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class LaborTestDataSeeder extends Seeder
{
    private $userId;
    private $today;

    /**
     * Create test data for Labor Charge Procurement System
     *
     * Run with: php artisan db:seed --class=LaborTestDataSeeder
     *
     * To reset and recreate: php artisan db:seed --class=LaborTestDataSeeder
     * (The seeder will clean existing test data first)
     */
    public function run(): void
    {
        $this->userId = DB::table('users')->first()?->id ?? 1;
        $this->today = Carbon::now();

        $this->command->info('╔═══════════════════════════════════════════════════════╗');
        $this->command->info('║   Labor Charge Procurement - Test Data Seeder         ║');
        $this->command->info('╚═══════════════════════════════════════════════════════╝');
        $this->command->newLine();

        // Step 0: Clean existing test data
        $this->cleanTestData();

        // Step 1: Create test artisans
        $artisans = $this->createTestArtisans();

        // Step 2: Get a project for testing
        $project = $this->getTestProject();
        if (!$project) {
            $this->command->error('No approved project found. Please create a project first.');
            return;
        }

        // Step 3: Create labor requests (various statuses)
        $requests = $this->createLaborRequests($project, $artisans);

        // Step 4: Create contracts from approved requests
        $contracts = $this->createContracts($requests, $project);

        // Step 5: Create payment phases for all contracts
        $this->createAllPaymentPhases($contracts);

        // Step 6: Create work logs (with dates in current filter range)
        $this->createWorkLogs($contracts);

        // Step 7: Create inspections (various statuses)
        $this->createInspections($contracts);

        // Step 8: Process some payments to show payment workflow
        $this->processTestPayments($contracts);

        $this->command->newLine();
        $this->printSummary();
    }

    /**
     * Clean existing test data
     */
    private function cleanTestData(): void
    {
        $this->command->info('Cleaning existing test data...');

        // Delete in correct order due to foreign keys
        DB::table('labor_inspections')->delete();
        DB::table('labor_work_logs')->delete();
        DB::table('labor_payment_phases')->delete();
        DB::table('labor_contracts')->delete();
        DB::table('labor_requests')->delete();

        // Clean test artisans (by phone pattern)
        DB::table('suppliers')
            ->where('phone', 'LIKE', '071234500%')
            ->delete();

        $this->command->line('  ✓ Cleaned existing labor test data');
    }

    /**
     * Create test artisan suppliers
     */
    private function createTestArtisans(): array
    {
        $this->command->info('Creating test artisans...');

        $artisansData = [
            [
                'name' => 'John Mfundi',
                'phone' => '0712345001',
                'email' => 'john.mfundi@test.com',
                'is_artisan' => true,
                'trade_skill' => 'Mason',
                'daily_rate' => 50000,
                'id_number' => '19850101-12345-00001-01',
                'nmb_account' => '12345678901',
                'account_name' => 'John Mfundi',
                'rating' => 4.5,
                'previous_work_history' => 'Completed 15+ residential projects. Specializes in block work and plastering.',
            ],
            [
                'name' => 'Peter Selemani',
                'phone' => '0712345002',
                'email' => 'peter.selemani@test.com',
                'is_artisan' => true,
                'trade_skill' => 'Electrician',
                'daily_rate' => 70000,
                'id_number' => '19900215-54321-00002-02',
                'crdb_account' => '98765432101',
                'account_name' => 'Peter Selemani',
                'rating' => 4.8,
                'previous_work_history' => 'Licensed electrician with 10 years experience.',
            ],
            [
                'name' => 'Hassan Juma',
                'phone' => '0712345003',
                'email' => 'hassan.juma@test.com',
                'is_artisan' => true,
                'trade_skill' => 'Plumber',
                'daily_rate' => 60000,
                'id_number' => '19880520-11111-00003-03',
                'nmb_account' => '55555666677',
                'account_name' => 'Hassan Juma',
                'rating' => 4.2,
                'previous_work_history' => 'Expert in water systems and drainage.',
            ],
            [
                'name' => 'Grace Mbeki',
                'phone' => '0712345004',
                'email' => 'grace.mbeki@test.com',
                'is_artisan' => true,
                'trade_skill' => 'Painter',
                'daily_rate' => 40000,
                'id_number' => '19920710-22222-00004-04',
                'nmb_account' => '77778889990',
                'account_name' => 'Grace Mbeki',
                'rating' => 4.6,
                'previous_work_history' => 'Interior and exterior painting specialist.',
            ],
            [
                'name' => 'Michael Kazimoto',
                'phone' => '0712345005',
                'email' => 'michael.kazimoto@test.com',
                'is_artisan' => true,
                'trade_skill' => 'Carpenter',
                'daily_rate' => 55000,
                'id_number' => '19870305-33333-00005-05',
                'crdb_account' => '11112223334',
                'account_name' => 'Michael Kazimoto',
                'rating' => 4.7,
                'previous_work_history' => 'Custom furniture and roofing. 12 years experience.',
            ],
            [
                'name' => 'Fatuma Ali',
                'phone' => '0712345006',
                'email' => 'fatuma.ali@test.com',
                'is_artisan' => true,
                'trade_skill' => 'Tiler',
                'daily_rate' => 45000,
                'id_number' => '19890815-44444-00006-06',
                'nmb_account' => '88889990001',
                'account_name' => 'Fatuma Ali',
                'rating' => 4.4,
                'previous_work_history' => 'Floor and wall tiling specialist.',
            ],
        ];

        $artisans = [];
        foreach ($artisansData as $data) {
            $id = (DB::table('suppliers')->max('id') ?? 0) + 1;
            $data['id'] = $id;
            $data['supplier_type'] = 'INDIRECT';
            $data['created_at'] = $this->today->copy()->subDays(30);
            $data['updated_at'] = $this->today;

            DB::table('suppliers')->insert($data);
            $artisans[] = (object) $data;
            $this->command->line("  ├─ {$data['name']} ({$data['trade_skill']})");
        }

        $this->command->line("  └─ Created " . count($artisans) . " artisans");
        return $artisans;
    }

    /**
     * Get an approved project for testing
     */
    private function getTestProject()
    {
        $project = DB::table('projects')
            ->where('status', 'APPROVED')
            ->first();

        if ($project) {
            $this->command->info("Using project: {$project->project_name}");
        }

        return $project;
    }

    /**
     * Create labor requests with various statuses
     */
    private function createLaborRequests($project, array $artisans): array
    {
        $this->command->info('Creating labor requests...');

        $requestsData = [
            // Approved requests (will become contracts)
            [
                'artisan_index' => 0,
                'work_description' => 'Block work and plastering for ground floor internal walls. Approximately 120 sqm.',
                'work_location' => 'Block A, Ground Floor',
                'estimated_duration_days' => 14,
                'proposed_amount' => 2500000,
                'negotiated_amount' => 2200000,
                'status' => 'approved',
                'payment_terms' => '20% mobilization, 30% at 50%, 30% at 90%, 20% final',
            ],
            [
                'artisan_index' => 1,
                'work_description' => 'First fix electrical installation. Conduit and wiring for all floors.',
                'work_location' => 'Block A, All Floors',
                'estimated_duration_days' => 10,
                'proposed_amount' => 3500000,
                'negotiated_amount' => 3200000,
                'status' => 'approved',
                'payment_terms' => '30% mobilization, 40% rough-in complete, 30% after testing',
            ],
            [
                'artisan_index' => 4,
                'work_description' => 'Roof truss fabrication and installation. Hardwood trusses.',
                'work_location' => 'Block A, Roof Level',
                'estimated_duration_days' => 12,
                'proposed_amount' => 4500000,
                'negotiated_amount' => 4200000,
                'status' => 'approved',
                'payment_terms' => '20% deposit, 30% materials, 30% complete, 20% installed',
            ],
            // Pending request (awaiting MD approval)
            [
                'artisan_index' => 2,
                'work_description' => 'Plumbing rough-in for 4 bathrooms and kitchen.',
                'work_location' => 'Block A, Wet Areas',
                'estimated_duration_days' => 7,
                'proposed_amount' => 1800000,
                'negotiated_amount' => 1650000,
                'status' => 'pending',
                'payment_terms' => '25% mobilization, 50% rough-in, 25% after test',
            ],
            // Draft requests (not yet submitted)
            [
                'artisan_index' => 3,
                'work_description' => 'Interior painting for completed rooms. 2 coats emulsion.',
                'work_location' => 'Block A, Completed Rooms',
                'estimated_duration_days' => 5,
                'proposed_amount' => 800000,
                'negotiated_amount' => null,
                'status' => 'draft',
                'payment_terms' => null,
            ],
            [
                'artisan_index' => 5,
                'work_description' => 'Floor tiling for living areas and bathrooms.',
                'work_location' => 'Block A, Ground Floor',
                'estimated_duration_days' => 8,
                'proposed_amount' => 1200000,
                'negotiated_amount' => null,
                'status' => 'draft',
                'payment_terms' => null,
            ],
            // Rejected request
            [
                'artisan_index' => 3,
                'work_description' => 'External painting - Quote too high, to be re-negotiated.',
                'work_location' => 'Block A, External',
                'estimated_duration_days' => 10,
                'proposed_amount' => 3000000,
                'negotiated_amount' => 2800000,
                'status' => 'rejected',
                'payment_terms' => '50% upfront, 50% completion',
                'rejection_reason' => 'Quote significantly above market rate. Please re-negotiate.',
            ],
        ];

        $requests = [];
        $requestNum = 1;

        foreach ($requestsData as $data) {
            $artisan = $artisans[$data['artisan_index']];
            $id = (DB::table('labor_requests')->max('id') ?? 0) + 1;
            $requestNumber = 'LR-' . date('Y') . '-' . str_pad($requestNum++, 4, '0', STR_PAD_LEFT);

            // Set dates within current month
            $createdAt = $this->today->copy()->subDays(rand(1, 4));
            $startDate = $this->today->copy()->addDays(rand(5, 15));

            $requestData = [
                'id' => $id,
                'request_number' => $requestNumber,
                'project_id' => $project->id,
                'artisan_id' => $artisan->id,
                'requested_by' => $this->userId,
                'work_description' => $data['work_description'],
                'work_location' => $data['work_location'],
                'estimated_duration_days' => $data['estimated_duration_days'],
                'start_date' => $startDate,
                'end_date' => $startDate->copy()->addDays($data['estimated_duration_days']),
                'proposed_amount' => $data['proposed_amount'],
                'negotiated_amount' => $data['negotiated_amount'],
                'approved_amount' => $data['status'] === 'approved' ? ($data['negotiated_amount'] ?? $data['proposed_amount']) : null,
                'currency' => 'TZS',
                'payment_terms' => $data['payment_terms'],
                'status' => $data['status'],
                'artisan_assessment' => $data['status'] !== 'draft' ? 'Site visit completed. Scope verified with artisan.' : null,
                'rejection_reason' => $data['rejection_reason'] ?? null,
                'approved_by' => $data['status'] === 'approved' ? $this->userId : null,
                'approved_at' => $data['status'] === 'approved' ? $createdAt->copy()->addHours(rand(2, 8)) : null,
                'created_at' => $createdAt,
                'updated_at' => $this->today,
            ];

            DB::table('labor_requests')->insert($requestData);
            $requests[] = (object) $requestData;

            $statusIcon = match($data['status']) {
                'approved' => '✓',
                'pending' => '⏳',
                'draft' => '📝',
                'rejected' => '✗',
                default => '•'
            };
            $this->command->line("  ├─ {$statusIcon} {$requestNumber} ({$data['status']})");
        }

        $this->command->line("  └─ Created " . count($requests) . " requests");
        return $requests;
    }

    /**
     * Create contracts from approved requests
     */
    private function createContracts(array $requests, $project): array
    {
        $this->command->info('Creating labor contracts...');

        $contracts = [];
        $contractNum = 1;

        foreach ($requests as $request) {
            if ($request->status !== 'approved') {
                continue;
            }

            $id = (DB::table('labor_contracts')->max('id') ?? 0) + 1;
            $contractNumber = 'LC-' . date('Y') . '-' . str_pad($contractNum++, 4, '0', STR_PAD_LEFT);

            $totalAmount = $request->approved_amount ?? $request->negotiated_amount ?? $request->proposed_amount;
            $createdAt = $this->today->copy()->subDays(rand(1, 3));

            $contractData = [
                'id' => $id,
                'contract_number' => $contractNumber,
                'labor_request_id' => $request->id,
                'project_id' => $project->id,
                'artisan_id' => $request->artisan_id,
                'supervisor_id' => $this->userId,
                'contract_date' => $createdAt,
                'start_date' => Carbon::parse($request->start_date),
                'end_date' => Carbon::parse($request->end_date),
                'scope_of_work' => $request->work_description . "\n\nLocation: " . $request->work_location,
                'terms_conditions' => "1. Work must meet quality standards.\n2. Daily work logs required.\n3. Safety equipment mandatory.\n4. Payment upon milestone completion.",
                'total_amount' => $totalAmount,
                'amount_paid' => 0,
                'balance_amount' => $totalAmount,
                'currency' => 'TZS',
                'status' => 'active',
                'created_at' => $createdAt,
                'updated_at' => $this->today,
            ];

            DB::table('labor_contracts')->insert($contractData);
            $contracts[] = (object) $contractData;
            $this->command->line("  ├─ {$contractNumber} - " . number_format($totalAmount) . " TZS");
        }

        $this->command->line("  └─ Created " . count($contracts) . " contracts");
        return $contracts;
    }

    /**
     * Create payment phases for all contracts
     */
    private function createAllPaymentPhases(array $contracts): void
    {
        $this->command->info('Creating payment phases...');

        $phasesTemplate = [
            ['phase_number' => 1, 'phase_name' => 'Mobilization', 'percentage' => 20, 'milestone' => 'Contract signed and work commenced'],
            ['phase_number' => 2, 'phase_name' => 'Progress', 'percentage' => 30, 'milestone' => '50% of work completed'],
            ['phase_number' => 3, 'phase_name' => 'Substantial', 'percentage' => 30, 'milestone' => '90% of work completed'],
            ['phase_number' => 4, 'phase_name' => 'Final', 'percentage' => 20, 'milestone' => 'Final inspection passed'],
        ];

        $totalPhases = 0;
        foreach ($contracts as $contractIndex => $contract) {
            foreach ($phasesTemplate as $index => $phase) {
                $id = (DB::table('labor_payment_phases')->max('id') ?? 0) + 1;
                $amount = ($contract->total_amount * $phase['percentage']) / 100;

                // First contract: mobilization paid, progress due
                // Second contract: mobilization due
                // Third contract: all pending
                $status = 'pending';
                if ($contractIndex === 0) {
                    if ($index === 0) $status = 'paid';
                    elseif ($index === 1) $status = 'due';
                } elseif ($contractIndex === 1) {
                    if ($index === 0) $status = 'due';
                } elseif ($contractIndex === 2) {
                    if ($index === 0) $status = 'due';
                }

                DB::table('labor_payment_phases')->insert([
                    'id' => $id,
                    'labor_contract_id' => $contract->id,
                    'phase_number' => $phase['phase_number'],
                    'phase_name' => $phase['phase_name'],
                    'percentage' => $phase['percentage'],
                    'amount' => $amount,
                    'milestone_description' => $phase['milestone'],
                    'status' => $status,
                    'paid_at' => $status === 'paid' ? $this->today->copy()->subDays(1) : null,
                    'paid_by' => $status === 'paid' ? $this->userId : null,
                    'payment_reference' => $status === 'paid' ? 'NMB-TRF-' . rand(100000, 999999) : null,
                    'created_at' => $this->today->copy()->subDays(2),
                    'updated_at' => $this->today,
                ]);
                $totalPhases++;
            }
        }

        // Update first contract amount_paid
        if (count($contracts) > 0) {
            $firstContract = $contracts[0];
            $paidAmount = ($firstContract->total_amount * 20) / 100;
            DB::table('labor_contracts')
                ->where('id', $firstContract->id)
                ->update([
                    'amount_paid' => $paidAmount,
                    'balance_amount' => $firstContract->total_amount - $paidAmount
                ]);
        }

        $this->command->line("  └─ Created {$totalPhases} payment phases");
    }

    /**
     * Create work logs for contracts (dates within filter range)
     */
    private function createWorkLogs(array $contracts): void
    {
        $this->command->info('Creating work logs...');

        $weatherOptions = ['sunny', 'cloudy', 'rainy', 'sunny', 'sunny'];
        $workDescriptions = [
            'Site preparation and material staging completed.',
            'Foundation work progressing well. No issues encountered.',
            'Continued work as per schedule. Good team coordination.',
            'Completed major section. Quality check passed.',
            'Minor adjustments made. Progress on track.',
            'Reached milestone ahead of schedule.',
            'Material delivery received. Work continuing.',
            'Final touches being applied to completed sections.',
        ];

        $totalLogs = 0;
        foreach ($contracts as $contractIndex => $contract) {
            $numLogs = 4 + $contractIndex; // 4, 5, 6 logs per contract
            $currentProgress = 0;

            for ($i = 0; $i < $numLogs; $i++) {
                // Dates within current month (going back from today)
                $logDate = $this->today->copy()->subDays($numLogs - $i - 1);

                $progressIncrement = rand(10, 20);
                $currentProgress = min(95, $currentProgress + $progressIncrement);

                $id = (DB::table('labor_work_logs')->max('id') ?? 0) + 1;

                DB::table('labor_work_logs')->insert([
                    'id' => $id,
                    'labor_contract_id' => $contract->id,
                    'log_date' => $logDate->format('Y-m-d'),
                    'logged_by' => $this->userId,
                    'work_done' => $workDescriptions[$i % count($workDescriptions)],
                    'workers_present' => rand(2, 6),
                    'hours_worked' => rand(6, 10),
                    'progress_percentage' => $currentProgress,
                    'challenges' => ($i === 2) ? 'Minor delay due to weather conditions.' : null,
                    'weather_conditions' => $weatherOptions[array_rand($weatherOptions)],
                    'notes' => ($i === $numLogs - 1) ? 'Good progress overall.' : null,
                    'created_at' => $logDate,
                    'updated_at' => $logDate,
                ]);
                $totalLogs++;
            }
            $this->command->line("  ├─ {$contract->contract_number}: {$numLogs} logs");
        }

        $this->command->line("  └─ Created {$totalLogs} work logs");
    }

    /**
     * Create inspections with various statuses
     */
    private function createInspections(array $contracts): void
    {
        $this->command->info('Creating inspections...');

        $inspectionNum = 1;
        $totalInspections = 0;

        foreach ($contracts as $contractIndex => $contract) {
            // Get payment phase 2 (Progress) for this contract
            $progressPhase = DB::table('labor_payment_phases')
                ->where('labor_contract_id', $contract->id)
                ->where('phase_number', 2)
                ->first();

            $id = (DB::table('labor_inspections')->max('id') ?? 0) + 1;
            $inspectionNumber = 'LI-' . date('Y') . '-' . str_pad($inspectionNum++, 4, '0', STR_PAD_LEFT);

            // Different statuses for different contracts
            $status = match($contractIndex) {
                0 => 'verified',  // First contract - inspection verified
                1 => 'pending',   // Second contract - awaiting verification
                default => 'pending'
            };

            $result = 'pass';
            $workQuality = ['excellent', 'good', 'good'][$contractIndex] ?? 'good';
            $completionPct = [52, 48, 45][$contractIndex] ?? 50;

            DB::table('labor_inspections')->insert([
                'id' => $id,
                'inspection_number' => $inspectionNumber,
                'labor_contract_id' => $contract->id,
                'payment_phase_id' => $progressPhase?->id,
                'inspection_date' => $this->today->copy()->subDays(1),
                'inspector_id' => $this->userId,
                'inspection_type' => 'progress',
                'work_quality' => $workQuality,
                'completion_percentage' => $completionPct,
                'scope_compliance' => true,
                'defects_found' => $contractIndex === 1 ? 'Minor surface finish issues - to be corrected.' : 'No significant defects found.',
                'rectification_required' => $contractIndex === 1,
                'rectification_notes' => $contractIndex === 1 ? 'Touch up required on wall sections B3-B5.' : null,
                'result' => $result,
                'notes' => 'Work progressing satisfactorily. Artisan maintaining standards.',
                'status' => $status,
                'verified_by' => $status === 'verified' ? $this->userId : null,
                'verified_at' => $status === 'verified' ? $this->today : null,
                'created_at' => $this->today->copy()->subDays(1),
                'updated_at' => $this->today,
            ]);

            $statusIcon = $status === 'verified' ? '✓' : '⏳';
            $this->command->line("  ├─ {$statusIcon} {$inspectionNumber} ({$status})");
            $totalInspections++;
        }

        $this->command->line("  └─ Created {$totalInspections} inspections");
    }

    /**
     * Process test payments to demonstrate workflow
     */
    private function processTestPayments(array $contracts): void
    {
        $this->command->info('Processing test payments...');

        // The first contract already has mobilization paid via createAllPaymentPhases
        // Just log it here
        if (count($contracts) > 0) {
            $this->command->line("  └─ First contract mobilization phase marked as paid");
        }
    }

    /**
     * Print summary of created data
     */
    private function printSummary(): void
    {
        $this->command->info('╔═══════════════════════════════════════════════════════╗');
        $this->command->info('║               TEST DATA SUMMARY                       ║');
        $this->command->info('╠═══════════════════════════════════════════════════════╣');

        $artisanCount = DB::table('suppliers')->where('is_artisan', true)->count();
        $requestCount = DB::table('labor_requests')->count();
        $contractCount = DB::table('labor_contracts')->count();
        $phaseCount = DB::table('labor_payment_phases')->count();
        $logCount = DB::table('labor_work_logs')->count();
        $inspectionCount = DB::table('labor_inspections')->count();

        $this->command->line("║  Artisans:         {$artisanCount}                                    ║");
        $this->command->line("║  Labor Requests:   {$requestCount}                                    ║");
        $this->command->line("║  Contracts:        {$contractCount}                                    ║");
        $this->command->line("║  Payment Phases:   {$phaseCount}                                   ║");
        $this->command->line("║  Work Logs:        {$logCount}                                   ║");
        $this->command->line("║  Inspections:      {$inspectionCount}                                    ║");
        $this->command->info('╠═══════════════════════════════════════════════════════╣');

        $this->command->info('║  REQUEST STATUS:                                      ║');
        $statuses = DB::table('labor_requests')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();
        foreach ($statuses as $status) {
            $this->command->line("║    - {$status->status}: {$status->count}                                       ║");
        }

        $this->command->info('╠═══════════════════════════════════════════════════════╣');
        $this->command->info('║  PAYMENT PHASE STATUS:                                ║');
        $phaseStatuses = DB::table('labor_payment_phases')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();
        foreach ($phaseStatuses as $status) {
            $this->command->line("║    - {$status->status}: {$status->count}                                       ║");
        }

        $this->command->info('╠═══════════════════════════════════════════════════════╣');
        $this->command->info('║  INSPECTION STATUS:                                   ║');
        $inspStatuses = DB::table('labor_inspections')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();
        foreach ($inspStatuses as $status) {
            $this->command->line("║    - {$status->status}: {$status->count}                                       ║");
        }

        $this->command->info('╠═══════════════════════════════════════════════════════╣');
        $this->command->info('║  ACCESS URLS:                                         ║');
        $this->command->line('║    /labor/dashboard     - Dashboard                   ║');
        $this->command->line('║    /labor/requests      - Labor Requests              ║');
        $this->command->line('║    /labor/contracts     - Contracts                   ║');
        $this->command->line('║    /labor/logs          - Work Logs                   ║');
        $this->command->line('║    /labor/inspections   - Inspections                 ║');
        $this->command->line('║    /labor/payments      - Payments                    ║');
        $this->command->info('╚═══════════════════════════════════════════════════════╝');
    }
}

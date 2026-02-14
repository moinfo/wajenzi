<?php

namespace Database\Seeders;

use App\Models\BillingDocument;
use App\Models\BillingDocumentItem;
use App\Models\BillingPayment;
use App\Models\Project;
use App\Models\ProjectBoq;
use App\Models\ProjectBoqItem;
use App\Models\ProjectBoqSection;
use App\Models\ProjectConstructionPhase;
use App\Models\ProjectDailyReport;
use App\Models\ProjectDesign;
use App\Models\ProjectProgressImage;
use App\Models\ProjectSchedule;
use App\Models\ProjectScheduleActivity;
use App\Models\ProjectSiteVisit;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ClientPortalDemoSeeder extends Seeder
{
    private int $projectId = 1;   // "General" project
    private int $clientId = 2;    // Joseph Msembe
    private int $userId = 1;      // Mohamed Amiry (admin/supervisor)

    public function run(): void
    {
        $this->command->info('Seeding client portal demo data for Project #1 (General)...');

        $this->updateProject();
        $phases = $this->seedConstructionPhases();
        $this->seedSchedule($phases);
        $this->seedDailyReports($phases);
        $this->seedSiteVisits();
        $this->seedDesigns();
        $this->seedProgressImages($phases);
        $this->seedBillingDocuments();

        $this->command->info('Client portal demo data seeded successfully!');
    }

    private function updateProject(): void
    {
        $project = Project::find($this->projectId);
        $project->update([
            'contract_value' => 150000000.00,
            'project_manager_id' => $this->userId,
            'expected_end_date' => '2026-09-20',
        ]);
        $this->command->info('  Updated project contract value & manager.');
    }

    private function seedConstructionPhases(): array
    {
        if (ProjectConstructionPhase::where('project_id', $this->projectId)->exists()) {
            $this->command->info('  Construction phases already exist, skipping.');
            return ProjectConstructionPhase::where('project_id', $this->projectId)
                ->orderBy('start_date')->get()->all();
        }

        $phasesData = [
            ['phase_name' => 'Foundation',              'start_date' => '2025-04-01', 'end_date' => '2025-06-15', 'status' => 'completed'],
            ['phase_name' => 'Structure & Framing',     'start_date' => '2025-06-20', 'end_date' => '2025-09-30', 'status' => 'completed'],
            ['phase_name' => 'Roofing',                 'start_date' => '2025-10-01', 'end_date' => '2026-01-15', 'status' => 'in_progress'],
            ['phase_name' => 'Interior Finishes',       'start_date' => '2026-01-20', 'end_date' => '2026-05-30', 'status' => 'pending'],
            ['phase_name' => 'Exterior & Landscaping',  'start_date' => '2026-06-01', 'end_date' => '2026-09-15', 'status' => 'pending'],
        ];

        $phases = [];
        foreach ($phasesData as $data) {
            $phases[] = ProjectConstructionPhase::create(array_merge($data, [
                'project_id' => $this->projectId,
            ]));
        }

        $this->command->info('  Created ' . count($phases) . ' construction phases.');
        return $phases;
    }

    private function seedSchedule(array $phases): void
    {
        if (ProjectSchedule::where('client_id', $this->clientId)->exists()) {
            $this->command->info('  Schedule already exists, skipping.');
            return;
        }

        $schedule = ProjectSchedule::create([
            'lead_id' => 37, // existing lead
            'client_id' => $this->clientId,
            'start_date' => '2025-04-01',
            'end_date' => '2026-09-20',
            'status' => 'in_progress',
            'notes' => 'General project construction schedule',
            'created_by' => $this->userId,
        ]);

        $activities = [
            // Foundation
            ['activity_code' => 'F-001', 'name' => 'Site clearing & excavation',    'phase' => 'Foundation',          'start_date' => '2025-04-01', 'duration_days' => 14, 'status' => 'completed', 'sort_order' => 1],
            ['activity_code' => 'F-002', 'name' => 'Foundation footing & casting',   'phase' => 'Foundation',          'start_date' => '2025-04-20', 'duration_days' => 21, 'status' => 'completed', 'sort_order' => 2, 'predecessor_code' => 'F-001'],
            ['activity_code' => 'F-003', 'name' => 'Ground floor slab',             'phase' => 'Foundation',          'start_date' => '2025-05-15', 'duration_days' => 14, 'status' => 'completed', 'sort_order' => 3, 'predecessor_code' => 'F-002'],
            // Structure
            ['activity_code' => 'S-001', 'name' => 'Column erection (ground)',       'phase' => 'Structure & Framing', 'start_date' => '2025-06-20', 'duration_days' => 21, 'status' => 'completed', 'sort_order' => 4, 'predecessor_code' => 'F-003'],
            ['activity_code' => 'S-002', 'name' => 'Beam & lintel casting',          'phase' => 'Structure & Framing', 'start_date' => '2025-07-15', 'duration_days' => 28, 'status' => 'completed', 'sort_order' => 5, 'predecessor_code' => 'S-001'],
            ['activity_code' => 'S-003', 'name' => 'Block work (walls)',             'phase' => 'Structure & Framing', 'start_date' => '2025-08-15', 'duration_days' => 35, 'status' => 'completed', 'sort_order' => 6, 'predecessor_code' => 'S-002'],
            // Roofing
            ['activity_code' => 'R-001', 'name' => 'Ring beam & roof preparation',   'phase' => 'Roofing',             'start_date' => '2025-10-01', 'duration_days' => 14, 'status' => 'completed', 'sort_order' => 7, 'predecessor_code' => 'S-003'],
            ['activity_code' => 'R-002', 'name' => 'Timber truss installation',      'phase' => 'Roofing',             'start_date' => '2025-10-20', 'duration_days' => 21, 'status' => 'in_progress', 'sort_order' => 8, 'predecessor_code' => 'R-001'],
            ['activity_code' => 'R-003', 'name' => 'Roofing sheets & fascia',        'phase' => 'Roofing',             'start_date' => '2025-11-15', 'duration_days' => 14, 'status' => 'pending', 'sort_order' => 9, 'predecessor_code' => 'R-002'],
            // Interior
            ['activity_code' => 'I-001', 'name' => 'Plastering & rendering',         'phase' => 'Interior Finishes',   'start_date' => '2026-01-20', 'duration_days' => 28, 'status' => 'pending', 'sort_order' => 10],
            ['activity_code' => 'I-002', 'name' => 'Electrical & plumbing rough-in', 'phase' => 'Interior Finishes',   'start_date' => '2026-02-20', 'duration_days' => 21, 'status' => 'pending', 'sort_order' => 11, 'predecessor_code' => 'I-001'],
            ['activity_code' => 'I-003', 'name' => 'Floor tiling & painting',        'phase' => 'Interior Finishes',   'start_date' => '2026-03-20', 'duration_days' => 35, 'status' => 'pending', 'sort_order' => 12, 'predecessor_code' => 'I-002'],
            // Exterior
            ['activity_code' => 'E-001', 'name' => 'External plastering & painting', 'phase' => 'Exterior & Landscaping', 'start_date' => '2026-06-01', 'duration_days' => 28, 'status' => 'pending', 'sort_order' => 13],
            ['activity_code' => 'E-002', 'name' => 'Driveway & landscaping',         'phase' => 'Exterior & Landscaping', 'start_date' => '2026-07-01', 'duration_days' => 30, 'status' => 'pending', 'sort_order' => 14, 'predecessor_code' => 'E-001'],
        ];

        foreach ($activities as $actData) {
            $startDate = Carbon::parse($actData['start_date']);
            $endDate = $startDate->copy()->addDays($actData['duration_days']);

            ProjectScheduleActivity::create(array_merge($actData, [
                'project_schedule_id' => $schedule->id,
                'end_date' => $endDate->toDateString(),
                'completed_at' => $actData['status'] === 'completed' ? $endDate : null,
                'started_at' => in_array($actData['status'], ['completed', 'in_progress']) ? $startDate : null,
            ]));
        }

        $this->command->info('  Created schedule with ' . count($activities) . ' activities.');
    }

    private function seedDailyReports(array $phases): void
    {
        if (ProjectDailyReport::where('project_id', $this->projectId)->exists()) {
            $this->command->info('  Daily reports already exist, skipping.');
            return;
        }

        $reports = [
            [
                'report_date' => '2025-10-15',
                'weather_conditions' => 'Sunny, 28°C — clear skies all day',
                'work_completed' => 'Completed ring beam formwork on the east wing. Reinforcement bars placed and tied. Ready for concrete pour tomorrow.',
                'materials_used' => '120 bags cement (Simba), 8m³ coarse aggregate, 4m³ fine sand, 1.2 tons Y16 rebar, binding wire 50kg',
                'labor_hours' => 72,
                'issues_faced' => null,
            ],
            [
                'report_date' => '2025-10-16',
                'weather_conditions' => 'Partly cloudy, 26°C — light drizzle in afternoon',
                'work_completed' => 'Poured concrete for ring beam (east wing). Vibrated and leveled. Started ring beam formwork on west wing.',
                'materials_used' => '200 bags cement, 12m³ coarse aggregate, 6m³ fine sand, ready-mix concrete 4m³',
                'labor_hours' => 88,
                'issues_faced' => 'Afternoon rain delayed west wing formwork by 2 hours. Covered fresh concrete with polythene sheets.',
            ],
            [
                'report_date' => '2025-11-02',
                'weather_conditions' => 'Overcast, 24°C — humid',
                'work_completed' => 'Timber truss fabrication in progress — 6 of 12 trusses assembled. Roof timber delivered and quality checked.',
                'materials_used' => '48 pieces cypress timber (4x2), 24 pieces (6x2), 15kg roofing nails, wood preservative 20L',
                'labor_hours' => 64,
                'issues_faced' => 'Two timber pieces rejected due to warping. Supplier will replace by Thursday.',
            ],
            [
                'report_date' => '2025-12-05',
                'weather_conditions' => 'Hot, 32°C — dry season',
                'work_completed' => 'Installed 8 timber trusses on the main building. Ridge board and purlins fixed on 4 trusses. Fascia board work started.',
                'materials_used' => '32 pieces purlins (3x2 cypress), 8 ridge boards, 200 coach screws, 10kg wire nails, 2 rolls DPC',
                'labor_hours' => 80,
                'issues_faced' => null,
            ],
            [
                'report_date' => '2026-01-10',
                'weather_conditions' => 'Sunny, 30°C — clear',
                'work_completed' => 'All 12 trusses installed. Purlins and battens completed. Commenced laying roofing sheets on north face.',
                'materials_used' => '40 roofing sheets (gauge 28, mabati), roofing nails 20kg, bitumen felt 4 rolls',
                'labor_hours' => 56,
                'issues_faced' => 'One worker suffered minor cut from sheet metal edge — first aid administered, safety briefing conducted.',
            ],
        ];

        foreach ($reports as $rData) {
            ProjectDailyReport::create(array_merge($rData, [
                'project_id' => $this->projectId,
                'supervisor_id' => $this->userId,
            ]));
        }

        $this->command->info('  Created ' . count($reports) . ' daily reports.');
    }

    private function seedSiteVisits(): void
    {
        if (ProjectSiteVisit::where('project_id', $this->projectId)->exists()) {
            $this->command->info('  Site visits already exist, skipping.');
            return;
        }

        $visits = [
            [
                'visit_date' => '2025-07-20',
                'status' => 'APPROVED',
                'document_number' => 'SV-2025-00001',
                'location' => 'Plot 45, Mbezi Beach, Dar es Salaam',
                'description' => 'Routine structural inspection during column erection phase.',
                'findings' => "1. Column reinforcement spacing is within acceptable limits (Y16 @ 200mm c/c).\n2. Concrete mix ratio verified at 1:2:4 as per specification.\n3. Column verticality checked with plumb bob — all within 5mm tolerance.\n4. Curing observed — columns being water-cured as required.\n5. Formwork removal timeline is appropriate (7 days minimum observed).",
                'recommendations' => "1. Continue monitoring concrete curing for minimum 14 days.\n2. Consider adding more bracing on the taller columns (>3m) for wind resistance during construction.\n3. Ensure lap length of reinforcement meets design requirement of 40 x bar diameter.\n4. Next inspection recommended after beam casting is complete.",
            ],
            [
                'visit_date' => '2025-10-25',
                'status' => 'APPROVED',
                'document_number' => 'SV-2025-00002',
                'location' => 'Plot 45, Mbezi Beach, Dar es Salaam',
                'description' => 'Inspection before roofing phase — verify structural readiness.',
                'findings' => "1. Ring beam fully cured and shows no visible cracks — satisfactory.\n2. Wall block work completed to ring beam level on all sides.\n3. Window and door openings match architectural drawings.\n4. Lintels properly cast with Y12 reinforcement.\n5. Building is plumb and level — ready for roof structure.",
                'recommendations' => "1. Approved to proceed with roofing timber installation.\n2. Ensure DPC membrane is laid on ring beam before timber placement.\n3. All timber should be treated with preservative before installation.\n4. Request roofing contractor's method statement before work begins.",
            ],
            [
                'visit_date' => '2026-01-12',
                'status' => 'COMPLETED',
                'document_number' => 'SV-2026-00001',
                'location' => 'Plot 45, Mbezi Beach, Dar es Salaam',
                'description' => 'Mid-roofing progress inspection — quality check on truss installation.',
                'findings' => "1. 12 of 12 timber trusses installed — spacing at 1.2m centers as designed.\n2. Ridge board alignment is straight with no visible deflection.\n3. Purlins securely fixed with coach screws at every truss connection.\n4. Roofing sheets on north face installed — overlap adequate (150mm).\n5. Fascia boards installed neatly with proper paint finish.",
                'recommendations' => "1. Complete south face roofing sheets within 2 weeks to protect interior.\n2. Install guttering before rainy season starts (March).\n3. Add ceiling battens before interior finishing phase begins.\n4. Overall quality: EXCELLENT — project is on track.",
            ],
        ];

        foreach ($visits as $vData) {
            ProjectSiteVisit::create(array_merge($vData, [
                'project_id' => $this->projectId,
                'inspector_id' => $this->userId,
                'create_by_id' => $this->userId,
            ]));
        }

        $this->command->info('  Created ' . count($visits) . ' site visits.');
    }

    private function seedDesigns(): void
    {
        if (ProjectDesign::where('project_id', $this->projectId)->exists()) {
            $this->command->info('  Designs already exist, skipping.');
            return;
        }

        $designs = [
            ['version' => 1, 'design_type' => 'Architectural', 'status' => 'approved', 'client_feedback' => 'Approved. Client happy with floor plan layout and room sizes.'],
            ['version' => 1, 'design_type' => 'Structural',    'status' => 'approved', 'client_feedback' => 'Structural design approved by engineer. Foundation and beam sizes confirmed.'],
            ['version' => 1, 'design_type' => 'MEP',           'status' => 'review',   'client_feedback' => 'Under review — client requested additional power outlets in kitchen.'],
            ['version' => 2, 'design_type' => 'Architectural', 'status' => 'draft',    'client_feedback' => null],
        ];

        foreach ($designs as $dData) {
            ProjectDesign::create(array_merge($dData, [
                'project_id' => $this->projectId,
                'designer_id' => $this->userId,
            ]));
        }

        $this->command->info('  Created ' . count($designs) . ' designs.');
    }

    private function seedProgressImages(array $phases): void
    {
        if (ProjectProgressImage::where('project_id', $this->projectId)->exists()) {
            $this->command->info('  Progress images already exist, skipping.');
            return;
        }

        // Map phase names to IDs
        $phaseMap = [];
        foreach ($phases as $phase) {
            $phaseMap[$phase->phase_name] = $phase->id;
        }

        $images = [
            [
                'title' => 'Site Clearing Complete',
                'description' => 'Vegetation cleared and site leveled. Foundation trench marking in progress.',
                'taken_at' => '2025-04-05',
                'construction_phase_id' => $phaseMap['Foundation'] ?? null,
                'file' => '/storage/uploads/progress/site-clearing.jpg',
                'file_name' => 'site-clearing.jpg',
            ],
            [
                'title' => 'Foundation Footing Casting',
                'description' => 'Concrete pouring for strip foundation. Reinforcement bars in place.',
                'taken_at' => '2025-04-28',
                'construction_phase_id' => $phaseMap['Foundation'] ?? null,
                'file' => '/storage/uploads/progress/foundation-footing.jpg',
                'file_name' => 'foundation-footing.jpg',
            ],
            [
                'title' => 'Ground Floor Slab',
                'description' => 'Ground floor slab poured and curing. BRC mesh reinforcement used.',
                'taken_at' => '2025-05-20',
                'construction_phase_id' => $phaseMap['Foundation'] ?? null,
                'file' => '/storage/uploads/progress/ground-slab.jpg',
                'file_name' => 'ground-slab.jpg',
            ],
            [
                'title' => 'Columns & Wall Block Work',
                'description' => 'Columns erected and block work up to window sill level.',
                'taken_at' => '2025-08-25',
                'construction_phase_id' => $phaseMap['Structure & Framing'] ?? null,
                'file' => '/storage/uploads/progress/columns-walls.jpg',
                'file_name' => 'columns-walls.jpg',
            ],
            [
                'title' => 'Ring Beam Ready for Roof',
                'description' => 'Ring beam cast and cured. Building ready for roof timber installation.',
                'taken_at' => '2025-10-10',
                'construction_phase_id' => $phaseMap['Structure & Framing'] ?? null,
                'file' => '/storage/uploads/progress/ring-beam.jpg',
                'file_name' => 'ring-beam.jpg',
            ],
            [
                'title' => 'Timber Truss Installation',
                'description' => 'Roof trusses being installed. 8 of 12 trusses in position.',
                'taken_at' => '2025-12-05',
                'construction_phase_id' => $phaseMap['Roofing'] ?? null,
                'file' => '/storage/uploads/progress/truss-installation.jpg',
                'file_name' => 'truss-installation.jpg',
            ],
            [
                'title' => 'Roofing Sheets Progress',
                'description' => 'North face roofing sheets installed. Fascia boards painted.',
                'taken_at' => '2026-01-10',
                'construction_phase_id' => $phaseMap['Roofing'] ?? null,
                'file' => '/storage/uploads/progress/roofing-sheets.jpg',
                'file_name' => 'roofing-sheets.jpg',
            ],
        ];

        foreach ($images as $imgData) {
            ProjectProgressImage::create(array_merge($imgData, [
                'project_id' => $this->projectId,
                'uploaded_by' => $this->userId,
            ]));
        }

        $this->command->info('  Created ' . count($images) . ' progress images.');
    }

    private function seedBillingDocuments(): void
    {
        // Only seed if no billing docs exist for this project
        if (BillingDocument::where('project_id', $this->projectId)->exists()) {
            $this->command->info('  Billing documents for project 1 already exist, skipping.');
            return;
        }

        // --- Quote (Accepted) ---
        $quote = BillingDocument::create([
            'document_type' => 'quote',
            'document_number' => 'QUO-2025-00001',
            'client_id' => $this->clientId,
            'project_id' => $this->projectId,
            'status' => 'accepted',
            'issue_date' => '2025-03-01',
            'valid_until_date' => '2025-04-01',
            'currency_code' => 'TZS',
            'subtotal_amount' => 148000000,
            'tax_amount' => 0,
            'total_amount' => 148000000,
            'balance_amount' => 148000000,
            'notes' => 'Initial quotation for General residential construction project.',
            'created_by' => $this->userId,
        ]);
        $this->addQuoteItems($quote);

        // --- Proforma (Sent) ---
        $proforma = BillingDocument::create([
            'document_type' => 'proforma',
            'document_number' => 'PRO-2025-00010',
            'client_id' => $this->clientId,
            'project_id' => $this->projectId,
            'parent_document_id' => $quote->id,
            'status' => 'sent',
            'issue_date' => '2025-03-15',
            'valid_until_date' => '2025-04-15',
            'currency_code' => 'TZS',
            'subtotal_amount' => 45000000,
            'tax_amount' => 0,
            'total_amount' => 45000000,
            'balance_amount' => 45000000,
            'notes' => 'Proforma for foundation phase — 30% advance payment.',
            'created_by' => $this->userId,
        ]);
        $this->addProformaItems($proforma);

        // --- Invoice 1 (Paid) ---
        $inv1 = BillingDocument::create([
            'document_type' => 'invoice',
            'document_number' => 'INV-2025-00010',
            'client_id' => $this->clientId,
            'project_id' => $this->projectId,
            'status' => 'paid',
            'issue_date' => '2025-06-20',
            'due_date' => '2025-07-20',
            'payment_terms' => 'net_30',
            'currency_code' => 'TZS',
            'subtotal_amount' => 45000000,
            'tax_amount' => 0,
            'total_amount' => 45000000,
            'paid_amount' => 45000000,
            'balance_amount' => 0,
            'paid_at' => '2025-07-05',
            'notes' => 'Foundation phase completed — payment certificate #1.',
            'created_by' => $this->userId,
        ]);
        $this->addInvoice1Items($inv1);
        $this->addPayment($inv1, 45000000, '2025-07-05', 'bank_transfer', 'NMB-TXN-2025-44821');

        // --- Invoice 2 (Partial Paid) ---
        $inv2 = BillingDocument::create([
            'document_type' => 'invoice',
            'document_number' => 'INV-2025-00011',
            'client_id' => $this->clientId,
            'project_id' => $this->projectId,
            'status' => 'partial_paid',
            'issue_date' => '2025-10-05',
            'due_date' => '2025-11-05',
            'payment_terms' => 'net_30',
            'currency_code' => 'TZS',
            'subtotal_amount' => 52000000,
            'tax_amount' => 0,
            'total_amount' => 52000000,
            'paid_amount' => 30000000,
            'balance_amount' => 22000000,
            'notes' => 'Structure & framing phase — payment certificate #2.',
            'created_by' => $this->userId,
        ]);
        $this->addInvoice2Items($inv2);
        $this->addPayment($inv2, 20000000, '2025-10-28', 'bank_transfer', 'NMB-TXN-2025-55930');
        $this->addPayment($inv2, 10000000, '2025-11-15', 'mobile_money', 'MPESA-REF-7782301');

        // --- Invoice 3 (Overdue) ---
        $inv3 = BillingDocument::create([
            'document_type' => 'invoice',
            'document_number' => 'INV-2026-00010',
            'client_id' => $this->clientId,
            'project_id' => $this->projectId,
            'status' => 'overdue',
            'issue_date' => '2026-01-10',
            'due_date' => '2026-02-10',
            'payment_terms' => 'net_30',
            'currency_code' => 'TZS',
            'subtotal_amount' => 35000000,
            'tax_amount' => 0,
            'total_amount' => 35000000,
            'paid_amount' => 0,
            'balance_amount' => 35000000,
            'notes' => 'Roofing phase (partial) — payment certificate #3.',
            'created_by' => $this->userId,
        ]);
        $this->addInvoice3Items($inv3);

        $this->command->info('  Created 5 billing documents with items and payments.');
    }

    private function addQuoteItems(BillingDocument $doc): void
    {
        $items = [
            ['item_name' => 'Foundation Works',              'description' => 'Excavation, footings, ground floor slab',             'quantity' => 1, 'unit_price' => 42000000, 'unit_of_measure' => 'lot'],
            ['item_name' => 'Structural Works',              'description' => 'Columns, beams, lintels, ring beam, block walls',     'quantity' => 1, 'unit_price' => 48000000, 'unit_of_measure' => 'lot'],
            ['item_name' => 'Roofing',                       'description' => 'Timber trusses, purlins, roofing sheets, fascia',     'quantity' => 1, 'unit_price' => 28000000, 'unit_of_measure' => 'lot'],
            ['item_name' => 'Interior Finishes',             'description' => 'Plastering, tiling, painting, electrical, plumbing',  'quantity' => 1, 'unit_price' => 22000000, 'unit_of_measure' => 'lot'],
            ['item_name' => 'External Works & Landscaping',  'description' => 'External plastering, painting, driveway, gardens',    'quantity' => 1, 'unit_price' => 8000000,  'unit_of_measure' => 'lot'],
        ];
        $this->createItems($doc, $items);
    }

    private function addProformaItems(BillingDocument $doc): void
    {
        $items = [
            ['item_name' => 'Foundation Phase Advance (30%)', 'description' => 'Advance payment for foundation works', 'quantity' => 1, 'unit_price' => 45000000, 'unit_of_measure' => 'lot'],
        ];
        $this->createItems($doc, $items);
    }

    private function addInvoice1Items(BillingDocument $doc): void
    {
        $items = [
            ['item_name' => 'Site Clearing & Excavation',   'description' => 'Topsoil removal, trench excavation for strip foundation', 'quantity' => 1, 'unit_price' => 8500000,  'unit_of_measure' => 'lot'],
            ['item_name' => 'Foundation Footing & Casting',  'description' => 'Reinforced concrete strip foundation as per structural drawings', 'quantity' => 1, 'unit_price' => 18500000, 'unit_of_measure' => 'lot'],
            ['item_name' => 'Ground Floor Slab (BRC Mesh)',  'description' => '150mm thick concrete slab with BRC mesh reinforcement', 'quantity' => 185, 'unit_price' => 65000, 'unit_of_measure' => 'sqm'],
            ['item_name' => 'DPC & Waterproofing',           'description' => 'Damp proof course membrane on foundation walls', 'quantity' => 1, 'unit_price' => 5975000,  'unit_of_measure' => 'lot'],
        ];
        $this->createItems($doc, $items);
    }

    private function addInvoice2Items(BillingDocument $doc): void
    {
        $items = [
            ['item_name' => 'Column Erection',        'description' => '200x200mm reinforced concrete columns (Y16 rebar)',     'quantity' => 24, 'unit_price' => 450000,  'unit_of_measure' => 'pcs'],
            ['item_name' => 'Beam & Lintel Casting',   'description' => 'RC beams and lintels above windows/doors',              'quantity' => 1,  'unit_price' => 15200000, 'unit_of_measure' => 'lot'],
            ['item_name' => 'Block Work (6" Blocks)',  'description' => 'Hollow concrete block walls to ring beam level',        'quantity' => 4800, 'unit_price' => 3500, 'unit_of_measure' => 'pcs'],
            ['item_name' => 'Ring Beam',               'description' => 'Continuous reinforced concrete ring beam at wall plate', 'quantity' => 1,  'unit_price' => 10000000, 'unit_of_measure' => 'lot'],
        ];
        $this->createItems($doc, $items);
    }

    private function addInvoice3Items(BillingDocument $doc): void
    {
        $items = [
            ['item_name' => 'Timber Trusses (Fabrication & Install)', 'description' => '12 No. king-post trusses at 1.2m centers, treated cypress', 'quantity' => 12, 'unit_price' => 1200000, 'unit_of_measure' => 'pcs'],
            ['item_name' => 'Purlins & Battens',                      'description' => '3x2 cypress purlins and roofing battens',                     'quantity' => 1,  'unit_price' => 6800000, 'unit_of_measure' => 'lot'],
            ['item_name' => 'Roofing Sheets (Gauge 28 Mabati)',       'description' => 'Galvanized corrugated roofing sheets with ridge caps',        'quantity' => 85, 'unit_price' => 45000,   'unit_of_measure' => 'sheets'],
            ['item_name' => 'Fascia Boards & Painting',               'description' => 'Timber fascia boards painted with weather-resistant paint',   'quantity' => 1,  'unit_price' => 9975000, 'unit_of_measure' => 'lot'],
        ];
        $this->createItems($doc, $items);
    }

    private function createItems(BillingDocument $doc, array $items): void
    {
        foreach ($items as $i => $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            BillingDocumentItem::create(array_merge($item, [
                'document_id' => $doc->id,
                'item_type' => 'service',
                'line_total' => $lineTotal,
                'sort_order' => $i + 1,
            ]));
        }
    }

    private function addPayment(BillingDocument $doc, float $amount, string $date, string $method, string $ref): void
    {
        $payNum = 'PAY-' . Carbon::parse($date)->format('Y') . '-' . str_pad(
            BillingPayment::count() + 1, 5, '0', STR_PAD_LEFT
        );

        BillingPayment::create([
            'payment_number' => $payNum,
            'document_id' => $doc->id,
            'client_id' => $this->clientId,
            'payment_date' => $date,
            'amount' => $amount,
            'payment_method' => $method,
            'reference_number' => $ref,
            'status' => 'completed',
            'received_by' => $this->userId,
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the 7 KPI templates plus the common Section A and per-role Section B
 * items. Idempotent — uses updateOrInsert on a deterministic code, so re-running
 * the seeder will refresh definitions without duplicating rows.
 *
 * Content sourced from /docs/KPI/*.docx (extracted text). Section A is shared
 * across all 7 templates; Section B is role-specific.
 */
class KpiTemplateSeeder extends Seeder
{
     public function run(): void
    {
        foreach ($this->templates() as $tpl) {
            $this->seedTemplate($tpl);
        }
    }

    /**
     * One row per role-specific KPI form. Section B items are role-specific.
     */
    private function templates(): array
    {
        return [
            [
                'code' => 'architect', 'name' => 'Architect Performance Review', 'role_name' => 'Architect',
                'section_b_title' => 'Departmental Objectives', 'section_b_weight' => 70,
                'section_b_items' => [
                    ['kpa' => 'Design Quality', 'measure' => 'Deliver creative, functional, sustainable designs that meet client needs', 'target' => '90% client satisfaction; ≤10% revisions; sustainability standards met', 'weight' => 10],
                    ['kpa' => 'Site Visits', 'measure' => 'Conduct site visits to ensure construction aligns with design specifications', 'target' => 'Site visits completed on time; data collected; records kept',                 'weight' => 10],
                    ['kpa' => 'Documentation', 'measure' => 'Prepare accurate architectural plans, specifications, and documentation', 'target' => '95% reduction in errors; software used as main tool',                          'weight' => 10],
                    ['kpa' => 'Continuous Learning', 'measure' => 'Stay updated with industry trends; continuous skill development', 'target' => '10 new skills learned; 5 new trends integrated into projects',                  'weight' => 10],
                    ['kpa' => 'Reporting and Communication', 'measure' => 'Provide weekly reports; maintain clear comms with clients & teams', 'target' => 'Timely & comprehensive weekly reports',                                   'weight' => 10],
                    ['kpa' => 'On-Time Delivery', 'measure' => 'Ensure designs are completed on time with milestones met', 'target' => '98% reduction of delays; submit designs 2 days before deadline',                        'weight' => 10],
                    ['kpa' => 'Other Duties', 'measure' => 'Any other architect-related tasks assigned by MD or Engineers', 'target' => 'Demonstrate flexibility and adaptability',                                            'weight' => 10],
                ],
            ],

            [
                'code' => 'accountant', 'name' => 'Accountant Performance Review', 'role_name' => 'Accountant',
                'section_b_title' => 'Departmental Objectives', 'section_b_weight' => 70,
                'section_b_items' => [
                    ['kpa' => 'Financial Reporting & Accuracy', 'measure' => 'Prepare and submit monthly/quarterly/annual financial reports on time',     'target' => '100% reports submitted on or before deadlines', 'weight' => 5],
                    ['kpa' => 'Financial Reporting & Accuracy', 'measure' => 'Ensure reports are accurate',                                                  'target' => '<2% error margin',                              'weight' => 3],
                    ['kpa' => 'Financial Reporting & Accuracy', 'measure' => 'Process supplier invoices and ensure timely payments',                       'target' => '95% of supplier invoices paid within agreed terms', 'weight' => 2],
                    ['kpa' => 'Accounts Payable & Receivable',  'measure' => 'Issue client invoices upon milestones and follow up on collections',          'target' => '90% of client payments collected within 30 days',  'weight' => 2],
                    ['kpa' => 'Accounts Payable & Receivable',  'measure' => 'Monitor project budgets versus actual costs (variance reports)',             'target' => 'Variance reports submitted monthly',               'weight' => 3],
                    ['kpa' => 'Cash Flow Forecasting',          'measure' => 'Prepare accurate cash flow forecasts for projects and operations',           'target' => '≥90% forecast accuracy',                            'weight' => 5],
                    ['kpa' => 'Cash Flow Forecasting',          'measure' => 'Monitor inflows/outflows to prevent operational delays',                     'target' => 'Zero project delays due to cash flow gaps',         'weight' => 5],
                    ['kpa' => 'Petty Cash Management',          'measure' => 'Reconcile petty cash weekly; all transactions supported by receipts',         'target' => '100% reconciliations completed weekly',             'weight' => 5],
                    ['kpa' => 'Petty Cash Management',          'measure' => 'Submit replenishment requests promptly with full documentation',              'target' => '100% replenishment requests submitted within 2 days', 'weight' => 2],
                    ['kpa' => 'Bank & Supplier Reconciliations','measure' => 'Reconcile all company bank accounts monthly',                                'target' => '100% reconciliations completed monthly',            'weight' => 5],
                    ['kpa' => 'Bank & Supplier Reconciliations','measure' => 'Investigate and resolve discrepancies between bank statements and ledger',  'target' => 'All discrepancies resolved within 7 business days', 'weight' => 5],
                    ['kpa' => 'Bank & Supplier Reconciliations','measure' => 'Reconcile supplier accounts monthly',                                        'target' => 'Zero unresolved items after 30 days',               'weight' => 5],
                    ['kpa' => 'Project Cost Tracking',          'measure' => 'Maintain weekly cost records for ongoing projects',                          'target' => '100% weekly reports submitted on time',             'weight' => 5],
                    ['kpa' => 'Project Cost Tracking',          'measure' => 'Collaborate with procurement and site teams for accurate expense allocation','target' => '≥95% accurate cost allocation',                     'weight' => 5],
                    ['kpa' => 'Budget Monitoring & Cost Control','measure' => 'Identify and report variances; work with PMs to control costs',             'target' => '≤5% variance from approved budgets',                'weight' => 5],
                    ['kpa' => 'Compliance & Audit Readiness',   'measure' => 'Adhere to accounting standards, company policies, and tax regulations',     'target' => 'Zero major audit findings',                         'weight' => 5],
                    ['kpa' => 'Compliance & Audit Readiness',   'measure' => 'Maintain documentation for internal and external audits',                    'target' => '100% documents submitted on time',                  'weight' => 3],
                ],
            ],

            [
                'code' => 'qs', 'name' => 'Quantity Surveyor Performance Review', 'role_name' => 'Quantity Surveyor (QS)',
                'section_b_title' => 'Departmental Objectives', 'section_b_weight' => 70,
                'section_b_items' => [
                    ['kpa' => 'Cost Estimation & BOQs',          'measure' => 'Prepare accurate BOQs and cost estimates',                              'target' => 'Variance ±5% of actual cost',                'weight' => 10],
                    ['kpa' => 'Feasibility & Cost Analysis',     'measure' => 'Conduct feasibility studies and cost analyses',                         'target' => 'Reports submitted on time with clear recommendations', 'weight' => 10],
                    ['kpa' => 'Contract Management',             'measure' => 'Handle claims, variations, and payment certifications',                 'target' => '100% compliance with contract terms',         'weight' => 10],
                    ['kpa' => 'Negotiation & Procurement',       'measure' => 'Negotiate with suppliers/subcontractors for best value',                'target' => 'Cost savings achieved vs market rates',       'weight' => 5],
                    ['kpa' => 'Budget Monitoring & Control',     'measure' => 'Monitor project budgets and control costs',                             'target' => 'No cost overruns without prior approval',     'weight' => 10],
                    ['kpa' => 'Payment Certification',           'measure' => 'Certify contractor payments accurately',                                'target' => 'Zero disputes/complaints on payments',        'weight' => 5],
                    ['kpa' => 'Progress Verification',           'measure' => 'Verify on-site progress against engineer reports',                      'target' => 'Reports verified against actual site work',   'weight' => 10],
                    ['kpa' => 'Compliance',                      'measure' => 'Ensure legal, contractual, and safety compliance',                      'target' => '100% compliance',                              'weight' => 5],
                    ['kpa' => 'Advisory Role',                   'measure' => 'Provide cost-saving and risk-management advice',                        'target' => 'Evidence of cost savings & risk control',     'weight' => 5],
                ],
            ],

            [
                'code' => 'procurement', 'name' => 'Procurement Performance Review', 'role_name' => 'Procurement Officer',
                'section_b_title' => 'Departmental Objectives', 'section_b_weight' => 70,
                'section_b_items' => [
                    ['kpa' => 'Supplier Identification and Selection',  'measure' => 'Identify quality suppliers; market price comparison; delivery aligned with policy',  'target' => '95% suppliers meet quality standards',     'weight' => 10],
                    ['kpa' => 'Negotiation and Cost Control',           'measure' => 'Achieve cost savings via negotiation; manage material flow',                          'target' => 'Demonstrable savings; no surplus materials','weight' => 10],
                    ['kpa' => 'Timeliness and Delivery of Materials',   'measure' => 'Materials delivered as specified',                                                    'target' => '95% deliveries match specs (timeframe/qty/quality)', 'weight' => 10],
                    ['kpa' => 'Procurement Budget & Cost Management',   'measure' => 'Stay within procurement budget; avoid overspend',                                     'target' => '95% within budget; reduced waste',          'weight' => 10],
                    ['kpa' => 'Procurement Reporting & Data Analysis',  'measure' => 'Daily/weekly procurement reports; cost-saving insights',                              'target' => 'Daily reports submitted on time',           'weight' => 10],
                    ['kpa' => 'Supplier Relationship & Communication',  'measure' => 'Maintain supplier relations; resolve conflicts',                                      'target' => '95% good supplier relations; 98% conflict avoidance', 'weight' => 10],
                    ['kpa' => 'Compliance and Documentation',           'measure' => 'Comply with procurement policies and law; accurate POs/invoices',                     'target' => '95% POs/invoices error-free',               'weight' => 10],
                ],
            ],

            [
                'code' => 'site_supervisor', 'name' => 'Site Supervisor Performance Review', 'role_name' => 'Site Supervisor',
                'section_b_title' => 'Departmental Objectives', 'section_b_weight' => 70,
                'section_b_items' => [
                    ['kpa' => 'Work Quality',               'measure' => 'Ensure work complies with design/workmanship standards',           'target' => '≥95% accuracy/compliance',           'weight' => 10],
                    ['kpa' => 'Project Timeliness',         'measure' => 'Complete activities within schedule',                              'target' => '≥90% on-time delivery',               'weight' => 10],
                    ['kpa' => 'Safety & Compliance',        'measure' => 'Enforce site safety; zero accidents/violations',                   'target' => '100% safety compliance',              'weight' => 10],
                    ['kpa' => 'Reporting & Documentation',  'measure' => 'Submit timely accurate site reports',                              'target' => '≥95% reports on time',                'weight' => 10],
                    ['kpa' => 'Resource Management',        'measure' => 'Efficient material and labour utilisation',                        'target' => '≤5% material wastage',                'weight' => 10],
                    ['kpa' => 'Communication & Coordination','measure' => 'Effective teamwork with crew, engineers, architects, clients',    'target' => 'Positive feedback from team & clients','weight' => 10],
                    ['kpa' => 'Client Satisfaction',        'measure' => 'Client feedback on supervision and on-site conduct',               'target' => '≥90% positive client feedback',       'weight' => 10],
                ],
            ],

            [
                'code' => 'content_creator', 'name' => 'Digital Marketing & Content Creator Review', 'role_name' => 'Digital Marketing and Content Creator',
                'section_b_title' => 'Departmental Objectives', 'section_b_weight' => 70,
                'section_b_items' => [
                    ['kpa' => 'Content Strategy & Business Objectives', 'measure' => 'Leads generated from content',                            'target' => 'Monthly increase 20%',          'method' => 'CRM/lead tracking',          'weight' => 5],
                    ['kpa' => 'Content Strategy & Business Objectives', 'measure' => 'Brand awareness growth (reach, impressions, followers)',  'target' => 'Monthly increase 20%',          'method' => 'Analytics dashboards',       'weight' => 5],
                    ['kpa' => 'Performance Tracking & Analytics',       'measure' => 'Conversion rate of content-driven leads',                  'target' => 'Monthly increase ≥10%',         'method' => 'CRM reports',                'weight' => 5],
                    ['kpa' => 'Content Quality & Development',          'measure' => 'Engagement rate per post (likes, shares, comments, saves)','target' => '≥10% monthly',                  'method' => '(L+C+S+Sa)/Impressions×100','weight' => 5],
                    ['kpa' => 'Content Quality & Development',          'measure' => 'Content Value Score (audience saves/bookmarks)',           'target' => 'Followers growth 10% quarterly','method' => 'Platform insights',          'weight' => 5],
                    ['kpa' => 'Content Quality & Development',          'measure' => 'Average watch time (videos/reels)',                        'target' => '≥50% of total video length',    'method' => 'Social media insights',      'weight' => 4],
                    ['kpa' => 'Brand Consistency',                      'measure' => 'Compliance with brand guidelines',                         'target' => '100%',                          'method' => 'Design/content checks',      'weight' => 5],
                    ['kpa' => 'Content Planning, Creation & Scheduling','measure' => '% of monthly content calendar executed on time',          'target' => '≥95%',                          'method' => 'Planned vs published',       'weight' => 4],
                    ['kpa' => 'Content Planning, Creation & Scheduling','measure' => 'Posts published across platforms per month',               'target' => '3 posts per day',               'method' => 'Social media dashboards',    'weight' => 4],
                    ['kpa' => 'Content Planning, Creation & Scheduling','measure' => 'Consistency rate of posting vs schedule',                  'target' => '≥90%',                          'method' => 'Weekly/monthly review',      'weight' => 4],
                    ['kpa' => 'Multimedia Production',                  'measure' => '% of projects covered with photo/video highlights',        'target' => '≥90%',                          'method' => 'Project completion reports', 'weight' => 4],
                    ['kpa' => 'Multimedia Production',                  'measure' => 'Number of high-quality photos/videos per month',           'target' => 'All projects captured and saved','method' => 'File library/project reports','weight' => 4],
                    ['kpa' => 'Performance Tracking & Analytics',       'measure' => 'Monthly performance reports delivered on time',            'target' => '100%',                          'method' => 'Submission logs/CRM',        'weight' => 4],
                    ['kpa' => 'Performance Tracking & Analytics',       'measure' => 'Website/social traffic growth from content',               'target' => '≥10% monthly',                  'method' => 'Google Analytics / social',  'weight' => 4],
                    ['kpa' => 'Skill Development',                      'measure' => 'Trainings/workshops/certifications completed',             'target' => '≥1 per month',                  'method' => 'Training logs',              'weight' => 4],
                    ['kpa' => 'Creative Innovation',                    'measure' => 'New content formats/styles tested',                        'target' => '≥1 per month',                  'method' => 'Content calendar',           'weight' => 2],
                    ['kpa' => 'Tool & Technology Adoption',             'measure' => 'Proficiency in content tools/software',                    'target' => '≥80% proficiency',              'method' => 'Skill assessment',           'weight' => 2],
                ],
            ],

            [
                'code' => 'sales', 'name' => 'Sales and Customer Performance Review', 'role_name' => 'Sales and Marketing',
                'section_b_title' => 'Departmental Objectives', 'section_b_weight' => 70,
                'section_b_items' => [
                    ['kpa' => 'Financial Perspective',  'measure' => 'Site visits per month',                                            'target' => '10 visits/month',                       'weight' => 6],
                    ['kpa' => 'Financial Perspective',  'measure' => 'Drawings (Architecture, Structure, BOQ) — revenue contribution',  'target' => 'TZS 11,250,000+ → 1.4% bonus',          'weight' => 6],
                    ['kpa' => 'Financial Perspective',  'measure' => 'Construction Activities — revenue contribution',                  'target' => 'TZS 300M+ → 0.45% bonus',               'weight' => 10],
                    ['kpa' => 'Financial Perspective',  'measure' => 'Return on Investment (ROI) and cost control',                     'target' => '≥100% ROI per client/project',          'weight' => 3],
                    ['kpa' => 'Financial Perspective',  'measure' => 'Project leads conversion rate',                                    'target' => '20–30% of qualified leads converted',   'weight' => 2],
                    ['kpa' => 'Customer Perspective',   'measure' => 'Customer response time',                                           'target' => '90% of inquiries responded within 24h', 'weight' => 6],
                    ['kpa' => 'Customer Perspective',   'measure' => 'Customer Satisfaction Score (CSAT)',                               'target' => '≥85% positive feedback',                'weight' => 7],
                    ['kpa' => 'Customer Perspective',   'measure' => 'Proposal follow-up completion rate',                               'target' => '100% of proposals followed up within 5 days', 'weight' => 8],
                    ['kpa' => 'Internal Processes',     'measure' => 'CRM data accuracy',                                                 'target' => '100% client/project data updated timely','weight' => 6],
                    ['kpa' => 'Internal Processes',     'measure' => 'Sales report submission',                                          'target' => 'Weekly report submission on time',      'weight' => 5],
                    ['kpa' => 'Internal Processes',     'measure' => 'Daily client follow-up activity (calls/meetings/emails)',         'target' => '100% response to calls and enquiries',  'weight' => 5],
                    ['kpa' => 'Learning and Growth',    'measure' => 'Monthly marketing insights provided',                              'target' => '≥2 actionable insights per month',      'weight' => 3],
                    ['kpa' => 'Learning and Growth',    'measure' => 'Knowledge of services/pricing/trends',                             'target' => '80%+ in monthly knowledge assessment',  'weight' => 3],
                ],
            ],
        ];
    }

    /**
     * Upsert one template plus its Section A and B with all items, idempotently.
     */
    private function seedTemplate(array $tpl): void
    {
        $roleId = DB::table('roles')->where('name', $tpl['role_name'])->value('id');

        DB::table('kpi_templates')->updateOrInsert(
            ['code' => $tpl['code']],
            [
                'name'        => $tpl['name'],
                'role_id'     => $roleId,
                'frequency'   => 'monthly',
                'description' => null,
                'is_active'   => 1,
                'updated_at'  => now(),
                'created_at'  => now(),
            ]
        );
        $templateId = DB::table('kpi_templates')->where('code', $tpl['code'])->value('id');

        // Section A — common 30%
        DB::table('kpi_template_sections')->updateOrInsert(
            ['kpi_template_id' => $templateId, 'code' => 'A'],
            [
                'title'        => 'General Performance',
                'weight_total' => 30,
                'sort_order'   => 1,
                'is_common'    => 1,
                'updated_at'   => now(),
                'created_at'   => now(),
            ]
        );
        $sectionAId = DB::table('kpi_template_sections')
            ->where('kpi_template_id', $templateId)->where('code', 'A')->value('id');

        $this->seedItems($templateId, $sectionAId, \App\Models\KpiTemplate::COMMON_SECTION_A_ITEMS);

        // Section B — role-specific
        DB::table('kpi_template_sections')->updateOrInsert(
            ['kpi_template_id' => $templateId, 'code' => 'B'],
            [
                'title'        => $tpl['section_b_title'],
                'weight_total' => $tpl['section_b_weight'],
                'sort_order'   => 2,
                'is_common'    => 0,
                'updated_at'   => now(),
                'created_at'   => now(),
            ]
        );
        $sectionBId = DB::table('kpi_template_sections')
            ->where('kpi_template_id', $templateId)->where('code', 'B')->value('id');

        $this->seedItems($templateId, $sectionBId, $tpl['section_b_items']);
    }

    private function seedItems(int $templateId, int $sectionId, array $items): void
    {
        // Wipe existing items for this section so re-seeding always reflects current definitions.
        DB::table('kpi_items')
            ->where('kpi_template_id', $templateId)
            ->where('kpi_template_section_id', $sectionId)
            ->delete();

        $order = 1;
        $rows = [];
        foreach ($items as $i) {
            $rows[] = [
                'kpi_template_id'         => $templateId,
                'kpi_template_section_id' => $sectionId,
                'kpa'                     => $i['kpa'],
                'responsibility'          => null,
                'measure'                 => $i['measure'],
                'target'                  => $i['target'] ?? null,
                'weight'                  => $i['weight'],
                'measurement_method'      => $i['method'] ?? null,
                'sort_order'              => $order++,
                'is_active'               => 1,
                'created_at'              => now(),
                'updated_at'              => now(),
            ];
        }
        if (!empty($rows)) {
            DB::table('kpi_items')->insert($rows);
        }
    }
}

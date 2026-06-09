<?php

namespace App\Listeners\Concerns;

use Illuminate\Support\Str;

trait BuildsApprovalLinks
{
    /**
     * Legacy approval_document_types.id used by some controllers as a route segment.
     * 0 means "new-system only" — the legacy id is not needed for the route.
     */
    protected array $documentTypeMap = [
        'StatutoryPayment'        => 1,
        'Sale'                    => 2,
        'Purchase'                => 3,
        'VatPayment'              => 4,
        'Payroll'                 => 5,
        'AdvanceSalary'           => 6,
        'Loan'                    => 7,
        'LeaveRequest'            => 8,
        'ProjectClient'           => 9,
        'Project'                 => 10,
        'ProjectSiteVisit'        => 11,
        'PettyCashRefillRequest'  => 12,
        'ImprestRequest'          => 13,
        'SalesDailyReport'        => 14,
    ];

    protected function documentTypeIdFor(string $documentType): int
    {
        return $this->documentTypeMap[$documentType] ?? 0;
    }

    protected function getLinkForDocumentType(string $documentType, $documentId, ?int $documentTypeId = null): string
    {
        $documentTypeId = $documentTypeId ?? $this->documentTypeIdFor($documentType);

        switch ($documentType) {
            // Finance / HR
            case 'Sale':                   return "sales/{$documentId}/{$documentTypeId}";
            case 'Loan':                   return "staff_loans/{$documentId}/{$documentTypeId}";
            case 'Payroll':                return "payroll/{$documentId}/{$documentTypeId}";
            case 'AdvanceSalary':          return "settings/advance_salaries/{$documentId}/{$documentTypeId}";
            case 'Expense':                return "expense/{$documentId}/{$documentTypeId}";
            case 'StatutoryPayment':       return "settings/statutory_payments/{$documentId}/{$documentTypeId}";
            case 'VatPayment':             return "vat_payment/{$documentId}/{$documentTypeId}";
            case 'LeaveRequest':           return "leaves/{$documentId}";

            // Petty cash / Imprest
            case 'PettyCashRefillRequest': return "finance/petty_cash_management/petty_cash_refill_requests/{$documentId}/{$documentTypeId}";
            case 'ImprestRequest':         return "finance/imprest_management/imprest_requests/{$documentId}/{$documentTypeId}";

            // Projects
            case 'Project':                return "projects/{$documentId}/{$documentTypeId}";
            case 'ProjectClient':          return "project_clients/{$documentId}/{$documentTypeId}";
            case 'ProjectSiteVisit':       return "project_site_visit/show/{$documentId}/{$documentTypeId}";
            case 'ProjectMaterialRequest': return "project_material_request/show/{$documentId}";
            case 'ProjectSchedule':        return "project-schedules/{$documentId}";
            case 'ProjectScheduleActivity':return "project-schedules/{$documentId}";
            case 'ProjectBoq':             return "project_boq/show/{$documentId}";
            case 'ProjectBoqPlan':         return "project-boq-plans/{$documentId}";
            case 'ProjectStructuralDesign':return "structural-design/{$documentId}";
            case 'ProjectServiceDesign':   return "service-design/{$documentId}";

            // Procurement / Supply
            case 'Purchase':               return "purchase/{$documentId}/{$documentTypeId}";
            case 'QuotationComparison':    return "quotation_comparison/{$documentId}/{$documentTypeId}";
            case 'MaterialInspection':     return "material_inspection/{$documentId}/{$documentTypeId}";
            case 'MaterialTransfer':       return "material_transfer/{$documentId}/{$documentTypeId}";
            case 'SitePaymentRequest':     return "site-paylog/requests/{$documentId}";

            // Labor
            case 'LaborRequest':           return "labor/requests/{$documentId}/{$documentTypeId}";
            case 'LaborInspection':        return "labor/inspections/{$documentId}/{$documentTypeId}";

            // Reports
            case 'SalesDailyReport':       return "sales_daily_report/{$documentId}/{$documentTypeId}";
            case 'SiteDailyReport':        return "site_daily_report/{$documentId}/{$documentTypeId}";

            // HR / Performance
            case 'KpiReview':              return "performance/{$documentId}";

            default:
                return Str::snake($documentType) . "/{$documentId}/{$documentTypeId}";
        }
    }
}

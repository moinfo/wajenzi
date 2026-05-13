<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\ApprovalNotification;
use RingleSoft\LaravelProcessApproval\Events\ApprovalNotificationEvent;
use RingleSoft\LaravelProcessApproval\Events\ProcessSubmittedEvent;
use RingleSoft\LaravelProcessApproval\Events\ProcessApprovedEvent;
use Illuminate\Support\Str;
use App\Models\User;

class ApprovalNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the notification event.
     */
    public function handle(ApprovalNotificationEvent $event): void
    {
        // Flash a message to the session
        session()->flash('success', $event->message);
    }

    /**
     * Handle when a document is submitted.
     */
    public function handleSubmitted(ProcessSubmittedEvent $event): void
    {
        // Get the approvable model
        $approvable = $event->approvable;

        // Get the next step's approvers safely
        if (method_exists($approvable, 'findNextApprovers')) {
            $nextApprovers = $approvable->findNextApprovers();
        } else {
            try {
                $nextApprovers = $approvable->getNextApprovers();
            } catch (\Error $e) {
                // If we can't use getNextApprovers, get users by role directly
                $nextStep = $approvable->nextApprovalStep();
                if (!$nextStep || !$nextStep->role_id) {
                    return;
                }

                $nextApprovers = User::whereHas('roles', function($query) use ($nextStep) {
                    $query->where('id', $nextStep->role_id);
                })->get();
            }
        }

        // Notify each approver
        foreach ($nextApprovers as $approver) {
            $this->sendNotification($approver, $approvable);
        }
    }

    /**
     * Handle when a document is approved.
     */
    public function handleApproved(ProcessApprovedEvent $event): void
    {
        // Get the approvable model
        $approvable = $event->approval->approvable;

        // Only send notifications if there are more approval steps
        if (!$approvable->isApprovalCompleted()) {
            // Get the next step's approvers safely
            if (method_exists($approvable, 'findNextApprovers')) {
                $nextApprovers = $approvable->findNextApprovers();
            } else {
                try {
                    $nextApprovers = $approvable->getNextApprovers();
                } catch (\Error $e) {
                    // If we can't use getNextApprovers, get users by role directly
                    $nextStep = $approvable->nextApprovalStep();
                    if (!$nextStep || !$nextStep->role_id) {
                        return;
                    }

                    $nextApprovers = User::whereHas('roles', function($query) use ($nextStep) {
                        $query->where('id', $nextStep->role_id);
                    })->get();
                }
            }

            // Notify each approver
            foreach ($nextApprovers as $approver) {
                $this->sendNotification($approver, $approvable);
            }
        }
    }

    /**
     * Send notification to the approver.
     */
    protected function sendNotification($approver, $approvable): void
    {
        // Get the document type name from the model class
        $documentType = class_basename($approvable);

        // Format the document ID
        $documentId = $approvable->id;

        // Map model class names → approval_document_types.id (0 = new-system only)
        $documentTypeMap = [
            'StatutoryPayment'       => 1,
            'Sale'                   => 2,
            'Purchase'               => 3,
            'VatPayment'             => 4,
            'Payroll'                => 5,
            'AdvanceSalary'          => 6,
            'Loan'                   => 7,
            'LeaveRequest'           => 8,
            'ProjectClient'          => 9,
            'Project'                => 10,
            'ProjectSiteVisit'       => 11,
            'PettyCashRefillRequest' => 12,
            'ImprestRequest'         => 13,
            'SalesDailyReport'       => 14,
            'Expense'                => 0,
            'SiteDailyReport'        => 0,
            'ProjectMaterialRequest' => 0,
            'QuotationComparison'    => 0,
            'MaterialInspection'     => 0,
            'LaborRequest'           => 0,
            'LaborInspection'        => 0,
            'ProjectBoq'             => 0,
            'MaterialTransfer'       => 0,
            'ProjectScheduleActivity'=> 0,
            'ProjectStructuralDesign'=> 0,
            'ProjectSchedule'        => 0,
            'ProjectBoqPlan'         => 0,
            'ProjectServiceDesign'   => 0,
        ];

        $documentTypeId = $documentTypeMap[$documentType] ?? 0;

        // Determine the appropriate link format
        $link = $this->getLinkForDocumentType($documentType, $documentId, $documentTypeId);

        // Create notification data
        $notificationData = [
            'staff_id' => $approver->id,
            'link' => $link,
            'title' => "{$documentType} Waiting for Approval",
            'body' => "A new {$documentType} has been created and submitted. You are required to review and approve the created {$documentType}",
            'document_id' => (string)$documentId,
            'document_type_id' => (string)$documentTypeId
        ];

        // Send the notification
        $approver->notify(new ApprovalNotification($notificationData));
    }

    /**
     * Get the appropriate link for the notification based on document type.
     */
    protected function getLinkForDocumentType($documentType, $documentId, $documentTypeId): string
    {
        switch ($documentType) {
            // ── Finance / HR ─────────────────────────────────────────────
            case 'Sale':
                return "sales/{$documentId}/{$documentTypeId}";
            case 'Loan':
                return "staff_loans/{$documentId}/{$documentTypeId}";
            case 'Payroll':
                return "payroll/{$documentId}/{$documentTypeId}";
            case 'AdvanceSalary':
                return "settings/advance_salaries/{$documentId}/{$documentTypeId}";
            case 'Expense':
                return "expense/{$documentId}/{$documentTypeId}";
            case 'StatutoryPayment':
                return "settings/statutory_payments/{$documentId}/{$documentTypeId}";
            case 'VatPayment':
                return "vat_payment/{$documentId}/{$documentTypeId}";
            case 'LeaveRequest':
                return "leaves/{$documentId}";

            // ── Petty cash / Imprest ─────────────────────────────────────
            case 'PettyCashRefillRequest':
                return "finance/petty_cash_management/petty_cash_refill_requests/{$documentId}/{$documentTypeId}";
            case 'ImprestRequest':
                return "finance/imprest_management/imprest_requests/{$documentId}/{$documentTypeId}";

            // ── Projects ─────────────────────────────────────────────────
            case 'Project':
                return "projects/{$documentId}/{$documentTypeId}";
            case 'ProjectClient':
                return "project_clients/{$documentId}/{$documentTypeId}";
            case 'ProjectSiteVisit':
                return "project_site_visit/show/{$documentId}/{$documentTypeId}";
            case 'ProjectMaterialRequest':
                return "project_material_request/show/{$documentId}";
            case 'ProjectSchedule':
                return "project-schedules/{$documentId}";
            case 'ProjectScheduleActivity':
                return "project-schedules/{$documentId}";
            case 'ProjectBoq':
                return "project_boq/show/{$documentId}";
            case 'ProjectBoqPlan':
                return "project-boq-plans/{$documentId}";
            case 'ProjectStructuralDesign':
                return "structural-design/{$documentId}";
            case 'ProjectServiceDesign':
                return "service-design/{$documentId}";

            // ── Procurement / Supply ─────────────────────────────────────
            case 'Purchase':
                return "purchase/{$documentId}/{$documentTypeId}";
            case 'QuotationComparison':
                return "quotation_comparison/{$documentId}/{$documentTypeId}";
            case 'MaterialInspection':
                return "material_inspection/{$documentId}/{$documentTypeId}";
            case 'MaterialTransfer':
                return "material_transfer/{$documentId}/{$documentTypeId}";

            // ── Labor ────────────────────────────────────────────────────
            case 'LaborRequest':
                return "labor/requests/{$documentId}/{$documentTypeId}";
            case 'LaborInspection':
                return "labor/inspections/{$documentId}/{$documentTypeId}";

            // ── Reports ──────────────────────────────────────────────────
            case 'SalesDailyReport':
                return "sales_daily_report/{$documentId}/{$documentTypeId}";
            case 'SiteDailyReport':
                return "site_daily_report/{$documentId}/{$documentTypeId}";

            default:
                $documentTypeSlug = Str::snake($documentType);
                return "{$documentTypeSlug}/{$documentId}/{$documentTypeId}";
        }
    }
}

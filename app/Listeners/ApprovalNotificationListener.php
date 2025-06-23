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

        // Map document types to their corresponding type IDs
        $documentTypeMap = [
            'Sale' => 2,
            'Loan' => 7,
            'Payroll' => 5,
            'AdvanceSalary' => 6,
            'PettyCashRefillRequest' => 12,
            'ImprestRequest' => 13,
            'Project' => 10,
            'ProjectClient' => 9,
            // Add other mappings as needed
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
        // Map document types to their respective URL formats
        switch ($documentType) {
            case 'Loan':
                return "sales/{$documentId}/{$documentTypeId}";
            case 'Payroll':
                return "payroll/{$documentId}/{$documentTypeId}";
            case 'AdvanceSalary':
                return "settings/advance_salaries/{$documentId}/{$documentTypeId}";
            case 'PettyCashRefillRequest':
                return "finance/petty_cash_management/petty_cash_refill_requests/{$documentId}/{$documentTypeId}";
            case 'ImprestRequest':
                return "finance/imprest_management/imprest_requests/{$documentId}/{$documentTypeId}";
            case 'Project':
                return "projects/{$documentId}/{$documentTypeId}";
            case 'ProjectClient':
                return "project_clients/{$documentId}/{$documentTypeId}";
            default:
                // Default format if no specific mapping is found
                $documentTypeSlug = Str::snake($documentType);
                return "{$documentTypeSlug}/{$documentId}/{$documentTypeId}";
        }
    }
}

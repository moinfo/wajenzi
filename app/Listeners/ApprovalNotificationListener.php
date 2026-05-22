<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Listeners\Concerns\BuildsApprovalLinks;
use App\Notifications\ApprovalNotification;
use RingleSoft\LaravelProcessApproval\Events\ApprovalNotificationEvent;
use RingleSoft\LaravelProcessApproval\Events\ProcessSubmittedEvent;
use RingleSoft\LaravelProcessApproval\Events\ProcessApprovedEvent;
use App\Models\User;

class ApprovalNotificationListener implements ShouldQueue
{
    use InteractsWithQueue, BuildsApprovalLinks;

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
        $approvable = $event->approvable;
        foreach ($this->resolveNextApprovers($approvable) as $approver) {
            $this->sendNotification($approver, $approvable);
        }
    }

    /**
     * Handle when a document is approved at an intermediate step.
     * (Final approval is handled by ApprovalOutcomeListener.)
     */
    public function handleApproved(ProcessApprovedEvent $event): void
    {
        $approvable = $event->approval->approvable;
        if ($approvable->isApprovalCompleted()) {
            return;
        }
        foreach ($this->resolveNextApprovers($approvable) as $approver) {
            $this->sendNotification($approver, $approvable);
        }
    }

    /**
     * Resolve the next-step approvers, tolerating models that don't implement the
     * full Approvable contract by falling back to a role-based User lookup.
     */
    protected function resolveNextApprovers($approvable): iterable
    {
        if (method_exists($approvable, 'findNextApprovers')) {
            return $approvable->findNextApprovers();
        }
        try {
            return $approvable->getNextApprovers();
        } catch (\Error $e) {
            $nextStep = $approvable->nextApprovalStep();
            if (!$nextStep || !$nextStep->role_id) {
                return [];
            }
            return User::whereHas('roles', function ($q) use ($nextStep) {
                $q->where('id', $nextStep->role_id);
            })->get();
        }
    }

    /**
     * Send "awaiting your approval" notification to an approver.
     */
    protected function sendNotification($approver, $approvable): void
    {
        $documentType   = class_basename($approvable);
        $documentId     = $approvable->id;
        $documentTypeId = $this->documentTypeIdFor($documentType);
        $link           = $this->getLinkForDocumentType($documentType, $documentId, $documentTypeId);

        $approver->notify(new ApprovalNotification([
            'staff_id'         => $approver->id,
            'link'             => $link,
            'title'            => "{$documentType} Waiting for Approval",
            'body'             => "A new {$documentType} has been created and submitted. You are required to review and approve the created {$documentType}",
            'document_id'      => (string) $documentId,
            'document_type_id' => (string) $documentTypeId,
        ]));
    }
}

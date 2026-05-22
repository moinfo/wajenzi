<?php

namespace App\Listeners;

use App\Listeners\Concerns\BuildsApprovalLinks;
use App\Models\User;
use App\Notifications\ApprovalNotification;
use App\Support\ResolvesApprovableCreator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use RingleSoft\LaravelProcessApproval\Events\ProcessApprovalCompletedEvent;
use RingleSoft\LaravelProcessApproval\Events\ProcessRejectedEvent;
use RingleSoft\LaravelProcessApproval\Events\ProcessReturnedEvent;

/**
 * Notifies the document creator/submitter when their submission reaches a
 * terminal or actionable outcome: fully approved, rejected, or returned for changes.
 *
 * The next-approver flow is handled separately by ApprovalNotificationListener;
 * this listener exists to close the feedback loop back to the submitter.
 */
class ApprovalOutcomeListener implements ShouldQueue
{
    use InteractsWithQueue, BuildsApprovalLinks, ResolvesApprovableCreator;

    public function handleCompleted(ProcessApprovalCompletedEvent $event): void
    {
        $this->notifyCreator($event->approvable, 'approved');
    }

    public function handleRejected(ProcessRejectedEvent $event): void
    {
        $approvable = $event->approval->approvable ?? null;
        if (!$approvable) {
            return;
        }
        $this->notifyCreator($approvable, 'rejected', $event->approval->comment ?? null);
    }

    public function handleReturned(ProcessReturnedEvent $event): void
    {
        $approvable = $event->approval->approvable ?? null;
        if (!$approvable) {
            return;
        }
        $this->notifyCreator($approvable, 'returned', $event->approval->comment ?? null);
    }

    protected function notifyCreator($approvable, string $outcome, ?string $comment = null): void
    {
        $creatorId = $this->resolveCreatorId($approvable);
        if (!$creatorId) {
            return;
        }

        $creator = User::find($creatorId);
        if (!$creator) {
            return;
        }

        $documentType   = class_basename($approvable);
        $documentId     = $approvable->id;
        $documentTypeId = $this->documentTypeIdFor($documentType);
        $link           = $this->getLinkForDocumentType($documentType, $documentId, $documentTypeId);

        [$title, $body] = $this->messageFor($documentType, $outcome, $comment);

        $creator->notify(new ApprovalNotification([
            'staff_id'         => $creator->id,
            'link'             => $link,
            'title'            => $title,
            'body'             => $body,
            'outcome'          => $outcome,
            'document_id'      => (string) $documentId,
            'document_type_id' => (string) $documentTypeId,
        ]));
    }

    /**
     * Build [title, body] for the creator notification based on the outcome.
     */
    protected function messageFor(string $documentType, string $outcome, ?string $comment): array
    {
        $type = trim(preg_replace('/(?<!^)([A-Z])/', ' $1', $documentType)); // "ProjectBoq" → "Project Boq"

        switch ($outcome) {
            case 'approved':
                return [
                    "{$type} Approved",
                    "Your {$type} submission has been fully approved.",
                ];
            case 'rejected':
                $body = "Your {$type} submission has been rejected.";
                if ($comment) {
                    $body .= " Reason: {$comment}";
                }
                return ["{$type} Rejected", $body];
            case 'returned':
                $body = "Your {$type} submission has been returned for changes.";
                if ($comment) {
                    $body .= " Notes: {$comment}";
                }
                return ["{$type} Returned for Changes", $body];
            default:
                return [
                    "{$type} Update",
                    "There is an update on your {$type} submission.",
                ];
        }
    }
}

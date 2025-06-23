<?php

namespace App\Listeners;

use RingleSoft\LaravelProcessApproval\Events\ProcessSubmittedEvent;
use App\Notifications\AwaitingApprovalNotification;

class ProcessSubmittedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    /**
     * Handle the event.
     */
    public function handle(ProcessSubmittedEvent $event): void
    {
        // Get the next approvers
        $nextApprovers = $event->approvable->getNextApprovers();

        // Notify each approver
        foreach ($nextApprovers as $nextApprover) {
            $nextApprover->notify(new AwaitingApprovalNotification($event->approvable));
        }
    }
}

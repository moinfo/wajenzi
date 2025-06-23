<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Listeners\ApprovalNotificationListener;
use RingleSoft\LaravelProcessApproval\Events\ApprovalNotificationEvent;
use RingleSoft\LaravelProcessApproval\Events\ProcessSubmittedEvent;
use RingleSoft\LaravelProcessApproval\Events\ProcessApprovedEvent;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Original event for approval notifications
        ApprovalNotificationEvent::class => [
            ApprovalNotificationListener::class,
        ],

        // Events for notifying the next approvers
        ProcessSubmittedEvent::class => [
            ApprovalNotificationListener::class . '@handleSubmitted',
        ],
        ProcessApprovedEvent::class => [
            ApprovalNotificationListener::class . '@handleApproved',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

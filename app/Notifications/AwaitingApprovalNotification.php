<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AwaitingApprovalNotification extends Notification
{
    use Queueable;

    protected $approvable;

    /**
     * Create a new notification instance.
     */
    public function __construct($approvable)
    {
        $this->approvable = $approvable;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database']; // Add your preferred channels
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Request Awaiting Your Approval')
            ->line('A new request is awaiting your approval.')
            ->action('View Request', url('/requests/' . $this->approvable->id))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'approvable_id' => $this->approvable->id,
            'approvable_type' => get_class($this->approvable),
            'message' => 'A new request is awaiting your approval.'
        ];
    }
}

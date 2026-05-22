<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The notification data.
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new notification instance.
     *
     * @param  array  $data
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // Always deliver the in-app bell notification.
        // Only attempt email if the user has an address AND SMTP is actually configured —
        // otherwise we'd crash the submission with "530 5.7.0 Authentication required"
        // when the inline queue (sync) executes the mail channel.
        $channels = ['database'];
        if (!empty($notifiable->email) && self::isMailConfigured()) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    /**
     * Heuristic: is the configured mailer actually usable, or is it the default
     * placeholder that will fail with SMTP 530?
     */
    protected static function isMailConfigured(): bool
    {
        $mailer = config('mail.default');
        if (in_array($mailer, ['log', 'array', null], true)) {
            // 'log' is fine — emails are written to laravel.log — but treat as "skip"
            // here only if explicitly disabled by an empty mailer. We let 'log' through.
            return $mailer === 'log';
        }
        if ($mailer === 'smtp') {
            $host = config('mail.mailers.smtp.host');
            $user = config('mail.mailers.smtp.username');
            // Require both host and a non-empty username for SMTP.
            return !empty($host) && !empty($user);
        }
        // ses / mailgun / postmark / etc. — assume configured if they're picked explicitly.
        return true;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $link = url($this->data['link']);

        return (new MailMessage)
            ->subject($this->data['title'])
            ->line($this->data['body'])
            ->action('Review Document', $link)
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return $this->data;
    }
}

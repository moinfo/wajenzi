<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemActionNotification extends Notification
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected string $link;
    protected ?string $actionBy;
    protected ?int $documentId;
    protected array $channels = ['mail', 'database'];

    public function __construct(string $title, string $body, string $link, ?string $actionBy = null, ?int $documentId = null)
    {
        $this->title = $title;
        $this->body = $body;
        $this->link = $link;
        $this->actionBy = $actionBy;
        $this->documentId = $documentId;
    }

    public function onlyDatabase(): self
    {
        $this->channels = ['database'];
        return $this;
    }

    public function onlyMail(): self
    {
        $this->channels = ['mail'];
        return $this;
    }

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting("Hello {$notifiable->name},")
            ->line($this->body)
            ->action('View Details', url($this->link))
            ->line('Thank you for using Wajenzi Professional.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'link' => $this->link,
            'document_id' => $this->documentId,
            'action_by' => $this->actionBy,
        ];
    }
}

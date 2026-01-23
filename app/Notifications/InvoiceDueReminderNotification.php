<?php

namespace App\Notifications;

use App\Models\BillingDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceDueReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $invoice;

    /**
     * Create a new notification instance.
     */
    public function __construct(BillingDocument $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $isOverdue = $this->invoice->due_date->isPast() && !$this->invoice->due_date->isToday();
        $subject = $isOverdue
            ? 'OVERDUE: Invoice ' . $this->invoice->document_number . ' Payment Reminder'
            : 'Invoice ' . $this->invoice->document_number . ' Due Today';

        $greeting = $isOverdue ? 'Urgent Payment Reminder!' : 'Payment Reminder';

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($isOverdue
                ? 'The following invoice is overdue and requires immediate attention:'
                : 'The following invoice is due today:')
            ->line('**Invoice Number:** ' . $this->invoice->document_number)
            ->line('**Client:** ' . ($this->invoice->client->name ?? 'N/A'))
            ->line('**Total Amount:** TZS ' . number_format($this->invoice->total_amount, 2))
            ->line('**Balance Due:** TZS ' . number_format($this->invoice->balance_amount, 2))
            ->line('**Due Date:** ' . $this->invoice->due_date->format('d M Y'));

        if ($isOverdue) {
            $daysOverdue = $this->invoice->due_date->diffInDays(now());
            $message->line('**Days Overdue:** ' . $daysOverdue . ' days');
        }

        if ($this->invoice->project) {
            $message->line('**Project:** ' . $this->invoice->project->name);
        }

        $message->action('View Invoice', url('/billing/invoices/' . $this->invoice->id))
            ->line('Please follow up with the client to ensure timely payment.')
            ->salutation('Wajenzi Professional System');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $isOverdue = $this->invoice->due_date->isPast() && !$this->invoice->due_date->isToday();

        return [
            'type' => 'invoice_due_reminder',
            'invoice_id' => $this->invoice->id,
            'document_number' => $this->invoice->document_number,
            'client_name' => $this->invoice->client->name ?? 'N/A',
            'total_amount' => $this->invoice->total_amount,
            'balance_amount' => $this->invoice->balance_amount,
            'due_date' => $this->invoice->due_date->format('Y-m-d'),
            'is_overdue' => $isOverdue,
            'message' => $isOverdue
                ? 'Invoice ' . $this->invoice->document_number . ' is overdue!'
                : 'Invoice ' . $this->invoice->document_number . ' is due today.',
        ];
    }
}

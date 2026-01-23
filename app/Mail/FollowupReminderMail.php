<?php

namespace App\Mail;

use App\Models\SalesLeadFollowup;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FollowupReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public SalesLeadFollowup $followup;
    public string $reminderType;

    /**
     * Create a new message instance.
     */
    public function __construct(SalesLeadFollowup $followup, string $reminderType = 'today')
    {
        $this->followup = $followup;
        $this->reminderType = $reminderType;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->reminderType === 'today'
            ? "Follow-up Reminder: {$this->followup->lead->name} - Due Today!"
            : "Follow-up Reminder: {$this->followup->lead->name} - Due Tomorrow";

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.followup-reminder',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

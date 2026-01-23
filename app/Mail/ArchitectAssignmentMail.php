<?php

namespace App\Mail;

use App\Models\ProjectSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArchitectAssignmentMail extends Mailable
{
    use Queueable, SerializesModels;

    public ProjectSchedule $schedule;

    /**
     * Create a new message instance.
     */
    public function __construct(ProjectSchedule $schedule)
    {
        $this->schedule = $schedule;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $leadNumber = $this->schedule->lead->lead_number ?? 'New Project';
        $clientName = $this->schedule->lead->name ?? 'Client';

        return new Envelope(
            subject: "New Project Assignment: {$leadNumber} - {$clientName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.architect-assignment',
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

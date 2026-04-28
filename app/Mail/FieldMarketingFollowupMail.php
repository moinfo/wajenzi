<?php

namespace App\Mail;

use App\Models\FieldMarketingVisit;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FieldMarketingFollowupMail extends Mailable
{
    use Queueable, SerializesModels;

    public FieldMarketingVisit $visit;
    public string $reminderType;

    public function __construct(FieldMarketingVisit $visit, string $reminderType = 'today')
    {
        $this->visit = $visit;
        $this->reminderType = $reminderType;
    }

    public function envelope(): Envelope
    {
        $when = $this->reminderType === 'today' ? 'Today' : 'Tomorrow';
        return new Envelope(
            subject: "Field Marketing Follow-up – {$this->visit->business_name} – Due {$when}!"
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.fm-followup-reminder');
    }

    public function attachments(): array
    {
        return [];
    }
}

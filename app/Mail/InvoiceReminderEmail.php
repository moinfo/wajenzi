<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use App\Models\BillingDocument;
use PDF;

class InvoiceReminderEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $document;
    public $reminderType;
    public $customMessage;
    public $customSubject;
    public $daysBeforeDue;
    public $daysOverdue;

    /**
     * Create a new message instance.
     */
    public function __construct(BillingDocument $document, $reminderType, $customSubject = null, $customMessage = null, $daysBeforeDue = null, $daysOverdue = null)
    {
        $this->document = $document;
        $this->reminderType = $reminderType;
        $this->customSubject = $customSubject;
        $this->customMessage = $customMessage;
        $this->daysBeforeDue = $daysBeforeDue;
        $this->daysOverdue = $daysOverdue;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->customSubject;
        
        if (!$subject) {
            switch ($this->reminderType) {
                case 'before_due':
                    $subject = "Payment Reminder - Invoice {$this->document->document_number} (Due in {$this->daysBeforeDue} days)";
                    break;
                case 'overdue':
                    $subject = "Overdue Payment Notice - Invoice {$this->document->document_number} ({$this->daysOverdue} days overdue)";
                    break;
                case 'late_fee':
                    $subject = "Late Fee Applied - Invoice {$this->document->document_number}";
                    break;
                default:
                    $subject = "Payment Reminder - Invoice {$this->document->document_number}";
            }
        }
        
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
            view: 'billing.emails.reminder',
            with: [
                'document' => $this->document,
                'reminderType' => $this->reminderType,
                'customMessage' => $this->customMessage,
                'daysBeforeDue' => $this->daysBeforeDue,
                'daysOverdue' => $this->daysOverdue,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $this->document->load(['client', 'items']);
        
        $pdf = PDF::loadView('billing.invoices.pdf', [
            'invoice' => $this->document
        ]);
        
        $filename = 'invoice-' . $this->document->document_number . '.pdf';
        
        return [
            Attachment::fromData(fn () => $pdf->output(), $filename)
                ->withMime('application/pdf'),
        ];
    }
}

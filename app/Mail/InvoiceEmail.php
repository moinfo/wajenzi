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

class InvoiceEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $document;
    public $customMessage;
    public $customSubject;

    /**
     * Create a new message instance.
     */
    public function __construct(BillingDocument $document, $customSubject = null, $customMessage = null)
    {
        $this->document = $document;
        $this->customSubject = $customSubject;
        $this->customMessage = $customMessage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->customSubject ?? ucfirst($this->document->document_type) . ' ' . $this->document->document_number;
        
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
            view: 'billing.emails.document',
            with: [
                'document' => $this->document,
                'customMessage' => $this->customMessage,
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
        
        // Map document types to their expected variable names in PDF views
        $variableMapping = [
            'invoice' => 'invoice',
            'proforma' => 'proforma', 
            'quote' => 'quotation'
        ];
        
        $variableName = $variableMapping[$this->document->document_type] ?? 'document';
        
        $pdf = PDF::loadView('billing.' . $this->document->document_type . 's.pdf', [
            $variableName => $this->document
        ]);
        
        $filename = $this->document->document_type . '-' . $this->document->document_number . '.pdf';
        
        return [
            Attachment::fromData(fn () => $pdf->output(), $filename)
                ->withMime('application/pdf'),
        ];
    }
}

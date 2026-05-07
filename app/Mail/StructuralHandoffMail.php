<?php

namespace App\Mail;

use App\Models\ProjectStructuralDesign;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StructuralHandoffMail extends Mailable
{
    use Queueable, SerializesModels;

    public ProjectStructuralDesign $design;

    public function __construct(ProjectStructuralDesign $design)
    {
        $this->design = $design->load(['project', 'stages']);
    }

    public function envelope(): Envelope
    {
        $projectName = $this->design->project->project_name ?? 'Project';

        return new Envelope(
            subject: "Structural Design Handoff: {$projectName} — Action Required",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.structural-handoff',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

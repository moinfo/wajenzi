<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\BillingDocument;
use App\Mail\InvoiceEmail;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {email : The email address to send test to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email functionality with billing system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info('Testing email configuration...');
        $this->info('Mail Host: ' . config('mail.mailers.smtp.host'));
        $this->info('Mail Port: ' . config('mail.mailers.smtp.port'));
        $this->info('Mail From: ' . config('mail.from.address'));
        
        // Get a sample document for testing
        $document = BillingDocument::with(['client', 'items'])->first();
        
        if (!$document) {
            $this->error('No billing documents found. Create a document first.');
            return;
        }
        
        try {
            $this->info("Sending test email to: {$email}");
            $this->info("Using document: {$document->document_type} #{$document->document_number}");
            
            Mail::to($email)->send(new InvoiceEmail(
                $document, 
                'Test Email - ' . ucfirst($document->document_type) . ' ' . $document->document_number,
                'This is a test email from the billing system. Please ignore this message.'
            ));
            
            $this->info('Email sent successfully!');
            
        } catch (\Exception $e) {
            $this->error('Failed to send email: ' . $e->getMessage());
        }
    }
}

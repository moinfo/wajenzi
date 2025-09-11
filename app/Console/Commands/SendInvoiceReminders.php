<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BillingDocument;
use App\Models\BillingReminderSetting;
use App\Mail\InvoiceReminderEmail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendInvoiceReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:send-reminders {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send automatic payment reminders and apply late fees to overdue invoices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $settings = BillingReminderSetting::getSettings();
        
        if (!$settings->auto_reminders_enabled) {
            $this->info('Automatic reminders are disabled.');
            return;
        }

        $isDryRun = $this->option('dry-run');
        $today = Carbon::today();
        
        $this->info('Starting invoice reminder process...');
        
        // Send pre-due reminders
        $this->sendPreDueReminders($settings, $today, $isDryRun);
        
        // Apply late fees and send overdue reminders
        $this->processOverdueInvoices($settings, $today, $isDryRun);
        
        $this->info('Invoice reminder process completed.');
    }

    private function sendPreDueReminders($settings, $today, $isDryRun)
    {
        $reminderIntervals = $settings->reminder_intervals;
        
        foreach ($reminderIntervals as $days) {
            $targetDate = $today->copy()->addDays($days);
            
            $invoices = BillingDocument::with(['client', 'items'])
                ->where('document_type', 'invoice')
                ->where('status', '!=', 'void')
                ->where('status', '!=', 'paid')
                ->where('balance_amount', '>', 0)
                ->whereDate('due_date', $targetDate)
                ->whereDoesntHave('reminderLogs', function ($query) use ($days) {
                    $query->where('reminder_type', 'before_due')
                          ->where('days_before_due', $days)
                          ->where('status', 'sent');
                })
                ->get();

            foreach ($invoices as $invoice) {
                if (!$invoice->client->email) {
                    continue;
                }

                $this->info("Sending {$days}-day reminder for Invoice {$invoice->document_number} to {$invoice->client->email}");

                if (!$isDryRun) {
                    try {
                        Mail::to($invoice->client->email)->send(
                            new InvoiceReminderEmail(
                                $invoice,
                                'before_due',
                                null,
                                null,
                                $days,
                                null
                            )
                        );

                        // Log the reminder
                        $invoice->reminderLogs()->create([
                            'reminder_type' => 'before_due',
                            'days_before_due' => $days,
                            'days_overdue' => null,
                            'recipient_email' => $invoice->client->email,
                            'cc_emails' => null,
                            'subject' => "Payment Reminder - Invoice {$invoice->document_number} (Due in {$days} days)",
                            'message' => 'Automated reminder',
                            'status' => 'sent',
                            'sent_by' => null,
                            'sent_at' => now()
                        ]);

                        $invoice->update(['last_reminder_sent_at' => now()]);
                        
                    } catch (\Exception $e) {
                        $this->error("Failed to send reminder for Invoice {$invoice->document_number}: " . $e->getMessage());
                        
                        // Log the failure
                        $invoice->reminderLogs()->create([
                            'reminder_type' => 'before_due',
                            'days_before_due' => $days,
                            'days_overdue' => null,
                            'recipient_email' => $invoice->client->email,
                            'cc_emails' => null,
                            'subject' => "Payment Reminder - Invoice {$invoice->document_number} (Due in {$days} days)",
                            'message' => 'Automated reminder',
                            'status' => 'failed',
                            'error_message' => $e->getMessage(),
                            'sent_by' => null,
                            'sent_at' => now()
                        ]);
                    }
                }
            }
        }
    }

    private function processOverdueInvoices($settings, $today, $isDryRun)
    {
        $overdueInvoices = BillingDocument::with(['client', 'items'])
            ->where('document_type', 'invoice')
            ->where('status', '!=', 'void')
            ->where('status', '!=', 'paid')
            ->where('balance_amount', '>', 0)
            ->where('due_date', '<', $today)
            ->get();

        foreach ($overdueInvoices as $invoice) {
            if (!$invoice->client->email) {
                continue;
            }

            $daysOverdue = $today->diffInDays($invoice->due_date);
            
            // Apply late fee if enabled and not already applied
            if ($settings->late_fees_enabled && !$invoice->late_fee_applied_at) {
                $this->info("Applying late fee to Invoice {$invoice->document_number} ({$daysOverdue} days overdue)");
                
                if (!$isDryRun) {
                    $originalAmount = $invoice->total_amount - $invoice->late_fee_amount;
                    $lateFeeAmount = ($originalAmount * $settings->late_fee_percentage) / 100;
                    
                    $invoice->update([
                        'late_fee_amount' => $lateFeeAmount,
                        'late_fee_percentage' => $settings->late_fee_percentage,
                        'late_fee_applied_at' => now(),
                        'total_amount' => $originalAmount + $lateFeeAmount,
                        'balance_amount' => ($originalAmount + $lateFeeAmount) - $invoice->paid_amount,
                    ]);
                    
                    // Send late fee notification
                    try {
                        Mail::to($invoice->client->email)->send(
                            new InvoiceReminderEmail(
                                $invoice,
                                'late_fee',
                                null,
                                null,
                                null,
                                $daysOverdue
                            )
                        );

                        $invoice->reminderLogs()->create([
                            'reminder_type' => 'late_fee',
                            'days_before_due' => null,
                            'days_overdue' => $daysOverdue,
                            'recipient_email' => $invoice->client->email,
                            'cc_emails' => null,
                            'subject' => "Late Fee Applied - Invoice {$invoice->document_number}",
                            'message' => 'Automated late fee notification',
                            'status' => 'sent',
                            'sent_by' => null,
                            'sent_at' => now()
                        ]);
                        
                    } catch (\Exception $e) {
                        $this->error("Failed to send late fee notification for Invoice {$invoice->document_number}: " . $e->getMessage());
                    }
                }
                
                continue; // Skip overdue reminder since we sent late fee notification
            }
            
            // Send overdue reminders
            if ($settings->late_fee_reminders_enabled) {
                $daysSinceLastReminder = $invoice->last_reminder_sent_at 
                    ? $today->diffInDays($invoice->last_reminder_sent_at)
                    : $daysOverdue;
                
                if ($daysSinceLastReminder >= $settings->late_fee_reminder_interval) {
                    $this->info("Sending overdue reminder for Invoice {$invoice->document_number} ({$daysOverdue} days overdue)");
                    
                    if (!$isDryRun) {
                        try {
                            Mail::to($invoice->client->email)->send(
                                new InvoiceReminderEmail(
                                    $invoice,
                                    'overdue',
                                    null,
                                    null,
                                    null,
                                    $daysOverdue
                                )
                            );

                            $invoice->reminderLogs()->create([
                                'reminder_type' => 'overdue',
                                'days_before_due' => null,
                                'days_overdue' => $daysOverdue,
                                'recipient_email' => $invoice->client->email,
                                'cc_emails' => null,
                                'subject' => "Overdue Payment Notice - Invoice {$invoice->document_number} ({$daysOverdue} days overdue)",
                                'message' => 'Automated overdue reminder',
                                'status' => 'sent',
                                'sent_by' => null,
                                'sent_at' => now()
                            ]);

                            $invoice->update(['last_reminder_sent_at' => now()]);
                            
                        } catch (\Exception $e) {
                            $this->error("Failed to send overdue reminder for Invoice {$invoice->document_number}: " . $e->getMessage());
                        }
                    }
                }
            }
        }
    }
}

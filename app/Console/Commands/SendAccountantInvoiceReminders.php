<?php

namespace App\Console\Commands;

use App\Models\BillingDocument;
use App\Models\User;
use App\Notifications\InvoiceDueReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendAccountantInvoiceReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:remind-accountants {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email reminders to accountants for invoices due today or overdue (internal staff reminders)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Checking for invoice reminders to send to accountants...');

        // Get invoices due today or overdue that haven't been reminded today
        $invoices = BillingDocument::with(['client', 'project'])
            ->unpaidWithDueDate()
            ->where(function($query) {
                $query->whereDate('due_date', '<=', now()->toDateString());
            })
            ->where(function($query) {
                $query->whereNull('last_reminder_sent_at')
                      ->orWhereDate('last_reminder_sent_at', '<', now()->toDateString());
            })
            ->get();

        if ($invoices->isEmpty()) {
            $this->info('No invoices need accountant reminders today.');
            return 0;
        }

        $this->info("Found {$invoices->count()} invoices that need reminders.");

        // Get all accountants
        $accountants = User::role('Accountant')->get();

        if ($accountants->isEmpty()) {
            $this->warn('No users with Accountant role found.');
            return 0;
        }

        $this->info("Sending reminders to {$accountants->count()} accountant(s).");

        if ($isDryRun) {
            $this->info('DRY RUN - No emails will be sent.');
            $this->table(
                ['Invoice #', 'Client', 'Balance', 'Due Date', 'Days Overdue'],
                $invoices->map(function($inv) {
                    return [
                        $inv->document_number,
                        $inv->client->name ?? 'N/A',
                        number_format($inv->balance_amount, 2),
                        $inv->due_date->format('Y-m-d'),
                        $inv->due_date->isPast() ? $inv->due_date->diffInDays(now()) . ' days' : 'Due today',
                    ];
                })->toArray()
            );
            return 0;
        }

        $sentCount = 0;
        $failedCount = 0;

        foreach ($invoices as $invoice) {
            try {
                Notification::send($accountants, new InvoiceDueReminderNotification($invoice));

                // Update reminder tracking
                $invoice->last_reminder_sent_at = now();
                $invoice->reminder_count = ($invoice->reminder_count ?? 0) + 1;
                $invoice->save();

                $this->line("  <info>✓</info> Sent reminder for invoice {$invoice->document_number}");
                $sentCount++;
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to send reminder for invoice {$invoice->document_number}: {$e->getMessage()}");
                \Log::error('Invoice accountant reminder failed', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage()
                ]);
                $failedCount++;
            }
        }

        $this->newLine();
        $this->info("Completed: {$sentCount} reminders sent, {$failedCount} failed.");

        return 0;
    }
}

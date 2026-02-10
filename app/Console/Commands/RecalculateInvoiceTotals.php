<?php

namespace App\Console\Commands;

use App\Models\BillingDocument;
use Illuminate\Console\Command;

class RecalculateInvoiceTotals extends Command
{
    protected $signature = 'billing:recalculate-totals';
    protected $description = 'Fix double-counted tax in billing documents by recalculating item line_totals and document totals';

    public function handle()
    {
        $docs = BillingDocument::with('items')->get();

        if ($docs->isEmpty()) {
            $this->info('No documents found.');
            return 0;
        }

        $fixed = 0;

        foreach ($docs as $doc) {
            $changed = false;

            // Fix item line_totals: remove tax if it was baked in
            foreach ($doc->items as $item) {
                $preTax = $item->quantity * $item->unit_price;

                // Recalculate discount
                $discountAmount = 0;
                if ($item->discount_type === 'percentage' && $item->discount_value > 0) {
                    $discountAmount = ($preTax * $item->discount_value) / 100;
                } elseif ($item->discount_type === 'fixed' && $item->discount_value > 0) {
                    $discountAmount = $item->discount_value;
                }

                $taxableAmount = $preTax - $discountAmount;
                $taxAmount = $item->tax_percentage > 0
                    ? ($taxableAmount * $item->tax_percentage) / 100
                    : 0;

                if (abs($item->line_total - $taxableAmount) > 0.01 || abs($item->tax_amount - $taxAmount) > 0.01) {
                    $item->timestamps = false;
                    $item->line_total = $taxableAmount;
                    $item->tax_amount = $taxAmount;
                    $item->discount_amount = $discountAmount;
                    $item->saveQuietly();
                    $changed = true;
                }
            }

            // Recalculate document totals
            $doc->load('items');
            $subtotal = $doc->items->sum('line_total');
            $taxAmount = $doc->items->sum('tax_amount');

            $discountAmount = 0;
            if ($doc->discount_type === 'percentage' && $doc->discount_value > 0) {
                $discountAmount = ($subtotal * $doc->discount_value) / 100;
            } elseif ($doc->discount_type === 'fixed' && $doc->discount_value > 0) {
                $discountAmount = $doc->discount_value;
            }

            $total = $subtotal - $discountAmount + $taxAmount + ($doc->shipping_amount ?? 0);
            $balance = $total - $doc->paid_amount;

            if (abs($doc->total_amount - $total) > 0.01) {
                $this->line("  {$doc->document_number}: {$doc->total_amount} -> {$total}");
                $changed = true;
            }

            $doc->timestamps = false;
            $doc->update([
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $total,
                'balance_amount' => $balance,
            ]);

            if ($changed) $fixed++;
        }

        $this->info("Done. Recalculated {$docs->count()} documents, {$fixed} had changes.");
        return 0;
    }
}

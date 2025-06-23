<?php

namespace App\Traits;

trait TANESCOReceiptTrait
{
    /**
     * Check if this is a TANESCO receipt
     */
    public function isTanescoReceipt(): bool
    {
        return str_contains(strtolower($this->company_name), 'tanzania electric supply') ||
            str_contains(strtolower($this->company_name), 'tanesco');
    }

    /**
     * Get total adjustments amount
     */
    public function getTotalAdjustments(): float
    {
        return $this->adjustments()->sum('amount');
    }

    /**
     * Get total payments amount
     */
    public function getTotalPayments(): float
    {
        return $this->payments()->sum('amount');
    }

    /**
     * Get balance after adjustments and payments
     */
    public function getBalanceAmount(): float
    {
        $totalAmount = $this->receipt_total_incl_of_tax;
        $totalAdjustments = $this->getTotalAdjustments();
        $totalPayments = $this->getTotalPayments();

        return $totalAmount - $totalAdjustments - $totalPayments;
    }
}

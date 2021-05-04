<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VatAnalysis extends Model
{
    use HasFactory;

    public function getTaxPayable($end_date){
        $purchases_vat = Purchase::getTotalPurchasesWithVAT($end_date);
        $sales_vat = Sale::getTotalExemptFromStart($end_date);
        $payment_vat = VatPayment::getTotalPayments($end_date);
        return $payment_vat - ($sales_vat-$purchases_vat);

    }
}

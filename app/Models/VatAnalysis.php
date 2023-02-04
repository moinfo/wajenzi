<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VatAnalysis extends Model
{
    use HasFactory;

    public function getTaxPayable($end_date){
        $purchases_vat = Purchase::getTotalPurchasesWithVAT($end_date);
        $purchases_vat_auto = Purchase::getTotalAutoPurchasesVAT($end_date);
        $sales_vat = Sale::getTotalVatAmt('2020-01-01',$end_date);
        $payment_vat = VatPayment::getTotalPayments($end_date);
        return  ($sales_vat-($purchases_vat+$purchases_vat_auto)) - $payment_vat;

    }
    public function getTaxPayableInMonth($start_date,$end_date){
        $purchases_vat = Purchase::getTotalPurchasesWithVAT($end_date,null,null,$start_date);
        $purchases_vat_auto = Purchase::getTotalAutoPurchasesVAT($end_date,$start_date);
        $sales_vat = Sale::getTotalVatAmt($start_date,$end_date);
        $payment_vat = VatPayment::getTotalPayments($end_date,$start_date);
        return  ($sales_vat-($purchases_vat+$purchases_vat_auto)) - $payment_vat;

    }
    public function getVatPayment($start_date,$end_date){

        return DB::table('vat_payments')
            ->whereBetween('date', [$start_date,$end_date])
            ->Where('status','APPROVED')
            ->sum('vat_payments.amount');

    }
    public function getTaxPayablePerMonth($end_date){
        $purchases_vat = Purchase::getTotalPurchasesWithVAT($end_date);
        $sales_vat = Sale::getTotalVatAmt('2020-01-01',$end_date);
        $payment_vat = VatPayment::getTotalPayments($end_date);
        return  ($sales_vat-$purchases_vat) - $payment_vat;

    }
}

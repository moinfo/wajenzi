<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoPurchase extends Model
{
    use HasFactory;

    public static function getAutoPurchases($start_date, $end_date)
    {
        return Receipt::whereBetween('receipt_date', [$start_date, $end_date])->get();
    }

    public static function getAutoPurchasesVAT($start_date, $end_date)
    {
        return Receipt::where('receipt_total_tax','!=',0)->whereBetween('receipt_date', [$start_date, $end_date])->get();
    }

    public static function getAutoPurchasesExempt($start_date, $end_date)
    {
        return Receipt::where('receipt_total_tax','=',0)->whereBetween('receipt_date', [$start_date, $end_date])->get();
    }


}

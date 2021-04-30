<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    public static function getTotalSupplierBalance($end_date){
        $receiving = SupplierReceiving::getAllSupplierReceivingAmount($end_date);
        $transaction = TransactionMovement::getAllSupplierTransactionAmount($end_date);
        return $receiving-$transaction;
    }
}

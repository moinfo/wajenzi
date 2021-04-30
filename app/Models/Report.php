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

    public static function getTotalInventoryForSpecificDate($start_date,$end_date){
      return SystemInventory::getTotalInventoryForSpecificDate($start_date,$end_date);
    }
    public static function getTotalCashForSpecificDate($start_date,$end_date){
      return SystemCash::getTotalCashForSpecificDate($start_date,$end_date);
    }
    public static function getTotalCreditForSpecificDate($start_date,$end_date){
      return SystemCredit::getSystemCreditForSpecificDate($start_date,$end_date);
    }
    public static function getTotalCapitalForSpecificDate($start_date,$end_date){
      return SystemCapital::getTotalCapitalForSpecificDate($start_date,$end_date);
    }
}

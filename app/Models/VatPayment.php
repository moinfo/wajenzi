<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VatPayment extends Model
{
    use HasFactory;
    public $fillable = ['id', 'bank_id', 'amount', 'date', 'description', 'file', 'status'];

    public function getAll($start_date,$end_date){
        $vat_payments =  DB::table('vat_payments')
            ->join('banks', 'banks.id', '=', 'vat_payments.bank_id','LEFT')
            ->select('vat_payments.*','banks.name as bank_name')
            ->whereBetween('date', [$start_date,$end_date])
            ->orderBy('date','desc')
            ->get();
        return $vat_payments;
    }

    public static function getTotalPayments($end_date){
        $start_date = '2020-01-01';
        $vat_payments =  DB::table('vat_payments')
            ->whereBetween('date', [$start_date,$end_date])
            ->sum('vat_payments.amount');
        return $vat_payments;
    }
    public static function getTotalPaymentOfLastMonth($start_date,$end_date){
        $vat_payments =  DB::table('vat_payments')
            ->whereBetween('date', [$start_date,$end_date])
            ->sum('vat_payments.amount');
        return $vat_payments;
    }

}

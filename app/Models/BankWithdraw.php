<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BankWithdraw extends Model
{
    use HasFactory;
    public $fillable = ['id', 'bank_id', 'amount', 'date'];

    public static function getTotalBankWithdrawPerDay($date)
    {
        return BankWithdraw::select([DB::raw("SUM(amount) as total_amount")])->Where('status','APPROVED')->Where('date',$date)->groupBy('date')->get()->first()['total_amount'] ?? 0;
    }

    public static function getTotalBankWithdrawAmountFromBeginning($end_date)
    {
        $start_date = '2020-01-01';
        return BankWithdraw::select([DB::raw("SUM(amount) as total_amount")])->Where('status','APPROVED')->WhereBetween('date',[$start_date,$end_date])->get()->first()['total_amount'] ?? 0;
    }


    public function bank() {
        return $this->belongsTo(Bank::class);
    }

    public static function getTotalBankWithdrawForSpecificDate($start_date,$end_date){
        return  $inventory = \App\Models\BankWithdraw::Where('status','APPROVED')->WhereBetween('date',[$start_date,$end_date])->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first()['total_amount'];

    }
}

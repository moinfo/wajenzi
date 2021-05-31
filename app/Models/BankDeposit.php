<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BankDeposit extends Model
{
    use HasFactory;
    public $fillable = ['id', 'bank_id', 'amount', 'date'];

    public static function getTotalBankDepositPerDay($date)
    {
        return BankDeposit::select([DB::raw("SUM(amount) as total_amount")])->Where('status','APPROVED')->Where('date',$date)->groupBy('date')->get()->first()['total_amount'] ?? 0;
    }


    public static function getTotalBandDepositAmountFromBeginning($end_date)
    {
        $start_date = '2020-01-01';
        return BankDeposit::select([DB::raw("SUM(amount) as total_amount")])->Where('status','APPROVED')->WhereBetween('date',[$start_date,$end_date])->get()->first()['total_amount'] ?? 0;
    }

    public function bank() {
        return $this->belongsTo(Bank::class);
    }

    public static function getTotalBankDepositForSpecificDate($start_date,$end_date){
        return  $inventory = \App\Models\BankDeposit::Where('status','APPROVED')->WhereBetween('date',[$start_date,$end_date])->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first()['total_amount'];

    }
}

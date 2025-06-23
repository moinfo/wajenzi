<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AllowancePayment extends Model
{
    use HasFactory;
    public $fillable = ['id', 'date', 'amount'];

    public static function getTotalAllowancePerDay($date)
    {
        return AllowancePayment::select([DB::raw("SUM(amount) as total_amount")])->Where('date',$date)->groupBy('date')->get()->first()['total_amount'] ?? 0;
    }
    public static function getTotalAllowanceAmountFromBeginning($end_date)
    {
        $start_date = '2020-01-01';
        return AllowancePayment::select([DB::raw("SUM(amount) as total_amount")])->WhereBetween('date',[$start_date,$end_date])->get()->first()['total_amount'] ?? 0;
    }

    public static function isCurrentAllowancePaid($start_date,$end_date)
    {
        $records = AllowancePayment::select([DB::raw("*")])
            ->whereDate('date','>=',$start_date)
            ->whereDate('date','<=',$end_date)
            ->get();
        if(count($records)){
            return true;
        }else{
            return false;
        }

    }



}

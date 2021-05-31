<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PayrollRecord extends Model
{
    use HasFactory;

    public static function getTotalNetSalaryPerDay($date)
    {
        return PayrollRecord::select([DB::raw("SUM(net) as total_amount, DATE(created_at) as date")])->Where('status','APPROVED')->whereDate('created_at',$date)->groupBy('date')->get()->first()['total_amount'] ?? 0;
    }

    public static function getTotalNetSalaryAmountFromBeginning($end_date)
    {
        $start_date = '2020-01-01';
        return PayrollRecord::select([DB::raw("SUM(net) as total_amount, DATE(created_at) as date")])->Where('status','APPROVED')->WhereBetween(DB::raw('date(created_at)'),[$start_date,$end_date])->get()->first()['total_amount'] ?? 0;
    }

    public function getCurrentPayroll($start_date, $end_date)
    {
      return  $records = PayrollRecord::Where('status','APPROVED')->WhereBetween('created_at',[$start_date,$end_date])->select([DB::raw("*")])->get();
    }

}

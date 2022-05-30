<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Payroll extends Model
{
    use HasFactory;

    public static function isCurrentPayrollPaid($start_date,$end_date)
    {
        $records = PayrollRecord::select([DB::raw("*")])
            ->whereDate('created_at','>=',$start_date)
            ->whereDate('created_at','<=',$end_date)
            ->get();
        if(count($records)){
            return true;
        }else{
            return false;
        }

    }


}

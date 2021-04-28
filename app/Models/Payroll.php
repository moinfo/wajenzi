<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Payroll extends Model
{
    use HasFactory;

    protected static function isCurrentPayrollPaid($start_date,$end_date)
    {
        $records = PayrollRecord::WhereBetween('created_at',[$start_date,$end_date])->select([DB::raw("*")])->get();
        if(count($records)){
            return true;
        }else{
            return false;
        }

    }
}

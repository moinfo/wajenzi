<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Payroll extends Model
{
    use HasFactory;

    public static function isCurrentPayrollPaid($month,$year)
    {
        $records = PayrollRecord::select([DB::raw("*")])
            ->where('month',$month)
            ->where('year',$year)
            ->get();
        if(count($records)){
            return true;
        }else{
            return false;
        }

    }


}

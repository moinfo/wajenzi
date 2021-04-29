<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PayrollRecord extends Model
{
    use HasFactory;

    public function getCurrentPayroll($start_date,$end_date)
    {
      return  $records = PayrollRecord::WhereBetween('created_at',[$start_date,$end_date])->select([DB::raw("*")])->get();
    }

}

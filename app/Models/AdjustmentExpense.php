<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdjustmentExpense extends Model
{
    use HasFactory;
    public $fillable = [
        'date','amount'
    ];

    public static function getAdjustable($start_date, $end_date)
    {
        return   AdjustmentExpense::WhereBetween('date',[$start_date,$end_date])->get();
    }
}

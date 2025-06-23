<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WithholdingTax extends Model
{
    use HasFactory;
    public $fillable = ['date','amount','description','file'];

    public static function getTotalWithholdingTax($start_date,$end_date){
        return \App\Models\WithholdingTax::Where('date','>=',$start_date)->Where('date','<=',$end_date)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }
}

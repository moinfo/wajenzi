<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Stock extends Model
{
    use HasFactory;
    public $fillable = ['id', 'stock_type', 'amount', 'date', 'file'];


    public static function getTotalOpeningStock($start_date, $end_date){
        return Stock::whereBetween('date', [$start_date, $end_date])->Where('stock_type','OPENING')->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }
    public static function getTotalClosingStock($start_date, $end_date){
        return Stock::whereBetween('date', [$start_date, $end_date])->Where('stock_type','CLOSING')->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }
}

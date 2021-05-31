<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Gross extends Model
{
    use HasFactory;
    public $fillable = ['id', 'supervisor_id', 'amount', 'date', 'description', 'file'];
    public function supervisor(){
        return $this->belongsTo(Supervisor::class);
    }

    static function getTotalGrossProfitPerDay($date){
        return \App\Models\Gross::Where('status','APPROVED')->Where('date',$date)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first()['total_amount'] ?? 0;
    }
    static function getTotalGrossProfitBySupervisorForSpecificDate($start_date, $end_date){
        return \App\Models\Gross::Where('status','APPROVED')->whereBetween('date', [$start_date, $end_date])->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }
    public static function getTotalGrossProfit($start_date,$end_date){
        $revenue = Sale::getTotalRevenue($start_date,$end_date);
        $cost_of_sales = Sale::getCostOfSales($start_date,$end_date);
        return $revenue-$cost_of_sales;
    }
}

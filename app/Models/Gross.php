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
        return \App\Models\Gross::Where('date',$date)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first()['total_amount'] ?? 0;
    }
    static function getTotalGrossProfitBySupervisorForSpecificDate($start_date, $end_date){
        return \App\Models\Gross::whereBetween('date', [$start_date, $end_date])->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }
}

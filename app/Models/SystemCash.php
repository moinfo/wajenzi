<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SystemCash extends Model
{
    use HasFactory;
    public $fillable = ['id', 'system_id', 'amount', 'date'];
    public function system() {
        return $this->belongsTo(System::class);
    }


    public static function getTotalCashForSpecificDate($start_date,$end_date){
        return  $inventory = \App\Models\SystemCash::Where('status','APPROVED')->WhereBetween('date',[$start_date,$end_date])->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first()['total_amount'];

    }

    public static function getTotalCashForSystem($start_date,$end_date,$system_id){
        return  $inventory = \App\Models\SystemCash::Where('status','APPROVED')->Where('system_id',$system_id)->WhereBetween('date',[$start_date,$end_date])->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first()['total_amount'];
    }


}

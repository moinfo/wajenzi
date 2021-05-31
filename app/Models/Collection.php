<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Collection extends Model
{
    use HasFactory;
    public $fillable = ['id', 'supervisor_id','bank_id', 'amount', 'date', 'description', 'file'];
    public function supervisor(){
        return $this->belongsTo(Supervisor::class);
    }
    public function bank(){
        return $this->belongsTo(Bank::class);
    }
    static function getTotalCollectionPerDay($date){
        return \App\Models\Collection::Where('status','APPROVED')->Where('date',$date)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }

    static function getTotalCollectionToAllSupervisors($start_date, $end_date){
        return \App\Models\Collection::Where('status','APPROVED')->whereBetween('date', [$start_date, $end_date])->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }

    public static function getCollectionAmount($end_date)
    {
        $start_date = '2020-01-01';
        return Collection::select([DB::raw("SUM(collections.amount) as total_amount")])->join('supervisors','supervisors.id','=','collections.supervisor_id')->Where('collections.status','APPROVED')->WhereBetween('collections.date',[$start_date,$end_date])->get()->first()['total_amount'] ?? 0;
    }
}

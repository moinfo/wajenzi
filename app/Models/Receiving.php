<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Receiving extends Model
{
    use HasFactory;
    public $fillable = ['id', 'efd_id', 'description', 'date', 'amount'];



    public static function getAll($start_date,$end_date,$efd_id = null){
        $receiving = DB::table('receivings')
            ->join('efds', 'efds.id', '=', 'receivings.efd_id')
            ->select('receivings.*','efds.name as efd')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);

        if($efd_id != null){
            $receiving->where('efd_id','=',$efd_id);
        }
        return $receiving->get();
    }

    public static function getTotalReceivingPerDayPerSupervisor($start_date, $end_date, $efd_id)
    {
        $receiving = Receiving::join('efds', 'efds.id', '=', 'receivings.efd_id')
            ->select([DB::raw("SUM(amount) as amount")])
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);

        if($efd_id != null){
            $receiving->where('efd_id','=',$efd_id);
        }
        return $receiving->get()->first()['amount'];
    }
}

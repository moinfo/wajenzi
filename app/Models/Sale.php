<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    use HasFactory;
    public $fillable = ['id', 'efd_id', 'amount', 'date', 'net', 'tax', 'turn_over', 'file'];

    public function efd(){
        return $this->belongsTo(Efd::class);
    }

    public function getAll($start_date,$end_date,$efd_id = null){
        $sales = DB::table('sales')
            ->join('efds', 'efds.id', '=', 'sales.efd_id')
            ->select('sales.*','efds.name as efd')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);
        if($efd_id != null){
            $sales->where('efd_id','=',$efd_id);
        }
        return $sales = $sales->get();
    }

    public static function getTotalNet($start_date,$end_date,$efd_id = null){
        $sales = DB::table('sales')
            ->join('efds', 'efds.id', '=', 'sales.efd_id')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);
        if($efd_id != null){
            $sales->where('efd_id','=',$efd_id);
        }
        return $sales = $sales->sum('sales.net');
    }

    public static function getTotalTax($start_date,$end_date,$efd_id = null){
        $sales = DB::table('sales')
            ->join('efds', 'efds.id', '=', 'sales.efd_id')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);
        if($efd_id != null){
            $sales->where('efd_id','=',$efd_id);
        }
        return $sales = $sales->sum('sales.tax');
    }

    public static function getTotalExempt($start_date,$end_date,$efd_id = null){
        $sales = DB::table('sales')
            ->join('efds', 'efds.id', '=', 'sales.efd_id')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);
        if($efd_id != null){
            $sales->where('efd_id','=',$efd_id);
        }
        return $sales = $sales->sum('sales.turn_over');
    }
}

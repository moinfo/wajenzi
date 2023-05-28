<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SupplierTarget extends Model
{
    use HasFactory;
    public $fillable = [
      'supplier_id',
      'type',
      'amount',
      'date'
    ];

    public static function getAll($start_date,$end_date,$supplier_id)
    {
        $target = SupplierTarget::whereBetween('date',[$start_date,$end_date]);
        if($supplier_id){
            $target->where('supplier_id',$supplier_id);
        }
       return $target->get();
    }

    public static function getTargetDifference($start_date, $end_date, $supplier_id)
    {
        $target = SupplierTarget::select(DB::raw('supplier_targets.amount AS total_target'),'suppliers.name AS supplier_name','supplier_targets.supplier_id AS supplier_id','supplier_targets.date AS target_date')
            ->join('suppliers','suppliers.id','=','supplier_targets.supplier_id');
        if ($supplier_id){
            $target ->where('supplier_targets.supplier_id',$supplier_id);
        }
        return $target->whereBetween('supplier_targets.date',[$start_date,$end_date])->get();
    }

    public function supplier(){
        return $this->belongsTo(Supplier::class);
    }
}

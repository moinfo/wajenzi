<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierTargetPreparation extends Model
{
    use HasFactory;
    public $fillable = [
        'supplier_target_id',
        'efd_id',
        'amount',
        'description',
        'date'
    ];


    public function supplierTarget(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SupplierTarget::Class);
    }

    public static function getAll($start_date,$end_date,$supplier_id = null)
    {
        $target = SupplierTargetPreparation::whereBetween('date',[$start_date,$end_date]);
        if($supplier_id){
            $target->where('supplier_id',$supplier_id);
        }
        return $target->get();
    }

    public static function getAllTargets($start_date,$end_date,$supplier_id)
    {
        $target = SupplierTargetPreparation::whereBetween('date',[$start_date,$end_date]);
        if($supplier_id){
            $target->where('supplier_id',$supplier_id);
        }
        $target->where('type','TARGET');
        return $target->get();
    }

    public static function getAllCommissions($start_date,$end_date,$supplier_id)
    {
        $target = SupplierTargetPreparation::whereBetween('date',[$start_date,$end_date]);
        if($supplier_id){
            $target->where('supplier_id',$supplier_id);
        }
        $target->where('type','COMMISSION');
        return $target->get();
    }

    public function efd(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Efd::Class);
    }
}

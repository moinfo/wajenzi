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
      'description',
      'beneficiary_id',
      'date'
    ];

    public function beneficiary(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Beneficiary::Class);
    }

    public static function getAll($start_date,$end_date,$supplier_id)
    {
        $target = SupplierTarget::whereBetween('date',[$start_date,$end_date]);
        if($supplier_id){
            $target->where('supplier_id',$supplier_id);
        }
       return $target->get();
    }
    public static function getAllTargets($start_date,$end_date,$supplier_id)
    {
        $target = SupplierTarget::whereBetween('date',[$start_date,$end_date]);
        if($supplier_id){
            $target->where('supplier_id',$supplier_id);
        }
        $target->where('type','TARGET');
        return $target->get();
    }
    public static function getAllCommissions($start_date,$end_date,$supplier_id)
    {
        $target = SupplierTarget::whereBetween('date',[$start_date,$end_date]);
        if($supplier_id){
            $target->where('supplier_id',$supplier_id);
        }
        $target->where('type','COMMISSION');
        return $target->get();
    }

    public static function getTargetDifference($start_date, $end_date, $supplier_id)
    {
        $target = SupplierTarget::select(DB::raw('supplier_targets.amount AS total_target'),'supplier_targets.id','suppliers.name AS supplier_name','beneficiaries.name AS beneficiary_name','supplier_targets.supplier_id AS supplier_id','supplier_targets.date AS target_date')
            ->join('suppliers','suppliers.id','=','supplier_targets.supplier_id')
            ->join('beneficiaries','beneficiaries.id','=','supplier_targets.beneficiary_id');
        if ($supplier_id){
            $target ->where('supplier_targets.supplier_id',$supplier_id);
        }
        $target ->where('supplier_targets.type','TARGET');
        return $target->whereBetween('supplier_targets.date',[$start_date,$end_date])->get();
    }

    public static function getTotalSupplierCommissionWithDeposit($supplier_id,$start_date, $end_date)
    {
        $target = SupplierTarget::select([DB::raw('SUM(supplier_targets.amount) AS total_target')])
            ->join('suppliers','suppliers.id','=','supplier_targets.supplier_id');
        if ($supplier_id != 0){
            $target ->where('supplier_targets.supplier_id',$supplier_id);
        }
        $target ->where('supplier_targets.type','COMMISSION');
        $target->whereBetween('supplier_targets.date',[$start_date,$end_date]);
        return $target->get()->first()['total_target'];
    }

    public static function getTotalSupplierWithDeposit($supplier_id, $start_date, $end_date)
    {
        $target = SupplierTarget::selectRaw('SUM(supplier_targets.amount) AS total_target')
            ->join('suppliers', 'suppliers.id', '=', 'supplier_targets.supplier_id');

        if ($supplier_id != 0) {
            $target->where('supplier_targets.supplier_id', $supplier_id);
        }

        $target->whereBetween('supplier_targets.date', [$start_date, $end_date]);

        return $target->first()->total_target ?? 0; // Return 0 if no result is found
    }


    public function supplier(){
        return $this->belongsTo(Supplier::class);
    }

    // Add to SupplierTarget model
    public static function getTodayTargets($date)
    {
        return DB::table('supplier_targets as st')
            ->select([
                'st.id',
                'st.beneficiary_id',
                'b.name as beneficiary_name',
                'st.amount',
                DB::raw('(st.amount - COALESCE((
                SELECT SUM(amount)
                FROM supplier_target_preparations
                WHERE supplier_target_id = st.id
            ), 0)) as remaining_balance')
            ])
            ->join('beneficiaries as b', 'b.id', '=', 'st.beneficiary_id')
            ->whereDate('st.date', $date)
            ->where('st.type', 'TARGET')
            ->groupBy('st.id', 'st.beneficiary_id', 'b.name', 'st.amount') // Group by to prevent duplicates
            ->having(DB::raw('(st.amount - COALESCE((
            SELECT SUM(amount)
            FROM supplier_target_preparations
            WHERE supplier_target_id = st.id
        ), 0))'), '>', 0)
            ->get();
    }
}

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
        $target = SupplierTarget::select(DB::raw('supplier_targets.amount AS total_target'),'suppliers.name AS supplier_name','beneficiaries.name AS beneficiary_name','supplier_targets.supplier_id AS supplier_id','supplier_targets.date AS target_date')
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
        return self::select([
            'supplier_targets.*',
            'beneficiaries.name as beneficiary_name',
            'beneficiary_accounts.bank_id',
            'beneficiary_accounts.account',
            DB::raw('(supplier_targets.amount - COALESCE((
            SELECT SUM(amount)
            FROM supplier_target_preparations
            WHERE supplier_target_id = supplier_targets.id
        ), 0)) as remaining_balance')
        ])
            ->join('beneficiaries', 'beneficiaries.id', '=', 'supplier_targets.beneficiary_id')
            ->join('beneficiary_accounts', 'beneficiary_accounts.beneficiary_id', '=', 'beneficiaries.id')
            ->whereDate('supplier_targets.date', $date)
            ->where('supplier_targets.type', 'TARGET')
            ->having('remaining_balance', '>', 0)
            ->get();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BankReconciliation extends Model
{
    use HasFactory;
    public $fillable = ['id', 'supplier_id', 'efd_id', 'description', 'date', 'debit', 'credit'];

    public static function getTotalDepositPerDayPerSupplier($start_date, $end_date, $efd_id, $supplier_id)
    {
        $receiving = BankReconciliation::join('efds', 'efds.id', '=', 'bank_reconciliations.efd_id')
            ->select([DB::raw("SUM(debit) as amount")])
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);

        if($efd_id != null){
            $receiving->where('efd_id','=',$efd_id);
        }
        if($supplier_id != null){
            $receiving->where('supplier_id','=',$supplier_id);
        }
        return $receiving->get()->first()['amount'];
    }


    public static function getSupplierAllTimeDebit($supplier_id,$end_date)
    {
        $start_date = '2010-01-01';
        return BankReconciliation::select(DB::raw('SUM(debit) as debit'))->where('supplier_id',$supplier_id)->where('date','>=',$start_date)->where('date','<=',$end_date)->get()->first()['debit'] ?? 0;
    }

    public static function getSupplierAllTimeCredit($supplier_id,$end_date)
    {
        $start_date = '2010-01-01';
        return BankReconciliation::select(DB::raw('SUM(credit) as credit'))->where('supplier_id',$supplier_id)->where('date','>=',$start_date)->where('date','<=',$end_date)->get()->first()['credit'] ?? 0;
    }
    public static function getSupplierCurrentBalance($supplier_id,$end_date)
    {
        return self::getSupplierAllTimeCredit($supplier_id,$end_date) - self::getSupplierAllTimeDebit($supplier_id,$end_date);
    }

    public static function getSupplierOpeningBalance($supplier_id, $end_date)
    {
        $yesterday = date('Y-m-d', strtotime('-1 day', strtotime($end_date)));
        return self::getSupplierAllTimeCredit($supplier_id,$yesterday) - self::getSupplierAllTimeDebit($supplier_id,$yesterday);


    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function efd()
    {
        return $this->belongsTo(Efd::class, 'efd_id', 'id');
    }

    public function getAmount()
    {
        return $this->debit ?? $this->credit;
    }

    public static function getAll($start_date,$end_date,$efd_id = null,$supplier_id = null){
        $bank_reconciliation = DB::table('bank_reconciliations')
            ->join('efds', 'efds.id', '=', 'bank_reconciliations.efd_id')
            ->join('suppliers', 'suppliers.id', '=', 'bank_reconciliations.supplier_id')
            ->select('bank_reconciliations.*','efds.name as efd','suppliers.name as supplier')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);

        if($efd_id != null){
            $bank_reconciliation->where('efd_id','=',$efd_id);
        }
        if($supplier_id != null){
            $bank_reconciliation->where('supplier_id','=',$supplier_id);
        }
        return $bank_reconciliation->get();
    }

    public static function getTotalDepositPerDayPerSupervisor($start_date, $end_date, $efd_id)
    {
        $receiving = BankReconciliation::join('efds', 'efds.id', '=', 'bank_reconciliations.efd_id')
            ->select([DB::raw("SUM(debit) as amount")])
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);

        if($efd_id != null){
            $receiving->where('efd_id','=',$efd_id);
        }
        return $receiving->get()->first()['amount'];
    }

    public static function getTotalDepositPerSupplier($start_date, $end_date, $supplier_id)
    {
        $receiving = BankReconciliation::join('suppliers', 'suppliers.id', '=', 'bank_reconciliations.supplier_id')
            ->select([DB::raw("SUM(debit) as amount")])
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);

        if($supplier_id != null){
            $receiving->where('supplier_id','=',$supplier_id);
        }
        return $receiving->get()->first()['amount'];
    }

    public static function getTotalDepositPerDay($start_date, $end_date, $efd_id)
    {
        $receiving = BankReconciliation::join('efds', 'efds.id', '=', 'bank_reconciliations.efd_id')
            ->select([DB::raw("debit as amount")])
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);

        if($efd_id != null){
            $receiving->where('efd_id','=',$efd_id);
        }
        return $receiving->get();

    }

}

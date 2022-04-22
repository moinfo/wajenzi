<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BankReconciliation extends Model
{
    use HasFactory;
    public $fillable = ['id', 'supplier_id', 'efd_id', 'description', 'date', 'debit', 'credit', 'payment_type', 'reference'];

    public static function getTotalDepositPerDayPerSupplier($start_date, $end_date, $efd_id, $supplier_id)
    {
        $receiving = BankReconciliation::join('efds', 'efds.id', '=', 'bank_reconciliations.efd_id')
            ->select([DB::raw("SUM(debit) as amount")])
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->where('payment_type','=','SALES');

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
        return  self::getSupplierAllTimeDebit($supplier_id,$end_date) - self::getSupplierAllTimeCredit($supplier_id,$end_date);
    }

    public static function getSupplierOpeningBalance($supplier_id, $end_date)
    {
        $yesterday = date('Y-m-d', strtotime('-1 day', strtotime($end_date)));
        return self::getSupplierAllTimeCredit($supplier_id,$yesterday) - self::getSupplierAllTimeDebit($supplier_id,$yesterday);


    }

    public static function getSimCardRegisteredTransactions($start_date, $end_date, $sim_card_registration_id, $account_id)
    {
        if($account_id == 1) {
            $transactions = DB::select("SELECT * FROM ((SELECT j.transaction_date,j.sim_card_registration_id,j.account_id,j.transaction_type_id, j.debit,null as credit FROM journal_entries j JOIN ledgers l ON (l.id = j.ledger_id) WHERE j.account_id IN(1,3,4)  and j.sim_card_registration_id = '$sim_card_registration_id' AND l.status = 'APPROVED' AND j.debit != 0) UNION ALL (SELECT j.transaction_date,j.sim_card_registration_id,j.account_id,j.transaction_type_id, null as debit, j.credit FROM journal_entries j JOIN ledgers l ON (l.id = j.ledger_id) WHERE j.account_id IN(1,3,4) and j.sim_card_registration_id = '$sim_card_registration_id' AND l.status = 'APPROVED' AND j.credit != 0)) b WHERE  DATE(transaction_date) BETWEEN '$start_date' AND '$end_date' order by transaction_date asc");
        }else{
            $transactions = DB::select("SELECT * FROM ((SELECT j.transaction_date,j.sim_card_registration_id,j.account_id,j.transaction_type_id, j.debit,null as credit FROM journal_entries j JOIN ledgers l ON (l.id = j.ledger_id) WHERE j.account_id = '$account_id' and j.sim_card_registration_id = '$sim_card_registration_id' AND l.status = 'APPROVED' AND j.debit != 0) UNION ALL (SELECT j.transaction_date,j.sim_card_registration_id,j.account_id,j.transaction_type_id, null as debit, j.credit FROM journal_entries j JOIN ledgers l ON (l.id = j.ledger_id) WHERE j.account_id = '$account_id' and j.sim_card_registration_id = '$sim_card_registration_id' AND l.status = 'APPROVED' AND j.credit != 0)) b WHERE  DATE(transaction_date) BETWEEN '$start_date' AND '$end_date' order by transaction_date asc");
        }

        return $transactions;

    }
    public static function getSupplierTransactions($start_date, $end_date, $supplier_id)
    {
        return DB::select("SELECT * FROM ((SELECT  description,efd_id,supplier_id,date,null as credit, debit FROM `bank_reconciliations` WHERE supplier_id = '$supplier_id' AND debit != 0) UNION ALL (SELECT  description,efd_id, supplier_id, date,credit, null as debit FROM `bank_reconciliations` WHERE supplier_id = '$supplier_id' AND credit != 0)) b WHERE supplier_id = '$supplier_id' AND `date` BETWEEN '$start_date' AND '$end_date' order by `date` asc");

    }

    public static function getTotalDepositPerDayPerSystem($start_date, $end_date, $system_id)
    {
        $receiving = BankReconciliation::join('efds', 'efds.id', '=', 'bank_reconciliations.efd_id')
            ->join('systems','systems.id','=','efds.system_id')
            ->select([DB::raw("SUM(debit) as amount")])
            ->where('payment_type','=','SALES')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);

        if($system_id != null){
            $receiving->where('efds.system_id','=',$system_id);
        }
        return $receiving->get()->first()['amount'];
    }

    public static function getTotalDepositPerDayPerSystemExcluded($start_date, $end_date, $system_id)
    {
        $receiving = BankReconciliation::join('efds', 'efds.id', '=', 'bank_reconciliations.efd_id')
            ->join('systems','systems.id','=','efds.system_id')
            ->select([DB::raw("SUM(debit) as amount")])
            ->where('payment_type','=','SALES')
            ->where('bank_reconciliations.supplier_id',42)
            ->orWhere('bank_reconciliations.supplier_id',88)
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);

        if($system_id != null){
            $receiving->where('efds.system_id','=',$system_id);
        }
        return $receiving->get()->first()['amount'];
    }



//    public static function getTotalDepositPerDayPerSystem($start_date, $end_date, $system_id)
//    {
//        BankReconciliation::where('date','>=',$start_date)->where('date','<=',$end_date)->
//                            where('payment_type','SALES')->select('suppliers.name','bank_reconciliations.supplier_id')
//            ->join('suppliers','suppliers.id','=','bank_reconciliations.supplier_id')->groupBy('supplier_id')->get();
//    }

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

    public static function getAll($start_date,$end_date,$efd_id = null,$supplier_id = null,$payment_type = null){
        $bank_reconciliation = DB::table('bank_reconciliations')
            ->join('efds', 'efds.id', '=', 'bank_reconciliations.efd_id')
            ->join('suppliers', 'suppliers.id', '=', 'bank_reconciliations.supplier_id')
            ->select('bank_reconciliations.*','efds.name as efd','suppliers.name as supplier')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);

        if($efd_id != null){
            $bank_reconciliation->where('efd_id','=',$efd_id);
        }
        if($payment_type != null){
            $bank_reconciliation->where('payment_type','=', "$payment_type");
        }
        if($supplier_id != null){
            $bank_reconciliation->where('supplier_id','=',$supplier_id);
        }
        return $bank_reconciliation->get();
    }
    public static function getOnlyTransfered($start_date,$end_date,$efd_id = null,$supplier_id = null){
        $bank_reconciliation = DB::table('bank_reconciliations')
            ->join('efds', 'efds.id', '=', 'bank_reconciliations.efd_id')
            ->join('suppliers', 'suppliers.id', '=', 'bank_reconciliations.supplier_id')
            ->select('bank_reconciliations.*','efds.name as efd','suppliers.name as supplier')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->where('reference', 'LIKE', "%TRANSFER%");

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
            ->where('payment_type','=','SALES')
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
            ->where('payment_type','=','SALES')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);

        if($supplier_id != null){
            $receiving->where('supplier_id','=',$supplier_id);
        }
        return $receiving->get()->first()['amount'];
    }

    public static function getTotalDepositPerSupplierBank($start_date, $end_date)
    {
        $receiving = BankReconciliation::join('suppliers', 'suppliers.id', '=', 'bank_reconciliations.supplier_id')
            ->select([DB::raw("SUM(bank_reconciliations.debit) as amount")])
            ->where('payment_type','=','SALES')
            ->where('bank_reconciliations.debit','>=','0')
            ->where('suppliers.supplier_type','=',"DIRECT")
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);

//        if($supplier_id != null){
//            $receiving->where('supplier_id','=',$supplier_id);
//        }
        return $receiving->get()->first()['amount'];
    }

    public static function getTotalDepositPerDay($start_date, $end_date, $efd_id)
    {
        $receiving = BankReconciliation::join('efds', 'efds.id', '=', 'bank_reconciliations.efd_id')
            ->select([DB::raw("debit as amount")])
            ->where('payment_type','=','SALES')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);

        if($efd_id != null){
            $receiving->where('efd_id','=',$efd_id);
        }
        return $receiving->get();

    }

}

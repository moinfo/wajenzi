<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BankReconciliation extends Model
{
    use HasFactory;
    public $fillable = ['id', 'to_id','from_to_id','supplier_id', 'efd_id', 'description', 'date', 'debit', 'status', 'credit', 'payment_type', 'reference'];

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
    public static function getTotalDepositPerDayPerSupplierInWhitestar($start_date, $end_date, $efd_id)
    {
        $receiving = BankReconciliation::join('efds', 'efds.id', '=', 'bank_reconciliations.efd_id')
            ->join('suppliers', 'suppliers.id', '=', 'bank_reconciliations.supplier_id')
            ->select([DB::raw("SUM(bank_reconciliations.debit) as amount")])
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->where('supplier_depend_on_system','=','WHITESTAR')
            ->where('payment_type','=','SALES');

        if($efd_id != null){
            $receiving->where('efd_id','=',$efd_id);
        }
        return $receiving->get()->first()['amount'];
    }
    public static function getTotalDepositWhitestar($start_date, $end_date, $efd_id)
    {
        $receiving = BankReconciliation::join('efds', 'efds.id', '=', 'bank_reconciliations.efd_id')
            ->join('suppliers', 'suppliers.id', '=', 'bank_reconciliations.supplier_id')
            ->select([DB::raw("SUM(bank_reconciliations.debit) as amount")])
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->where('payment_type','=','SALES');

        if($efd_id != null){
            $receiving->where('efd_id','=',$efd_id);
        }
        return $receiving->get()->first()['amount'];
    }
    public static function getTotalDepositWhitestarAuto($start_date, $end_date, $efd_id)
    {
        $receiving = BankReconciliation::join('efds', 'efds.id', '=', 'bank_reconciliations.efd_id')
            ->join('suppliers', 'suppliers.id', '=', 'bank_reconciliations.supplier_id')
            ->select([DB::raw("SUM(bank_reconciliations.debit) as amount")])
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->where('reference','LIKE','%Auto')
            ->where('payment_type','=','SALES');

        if($efd_id != null){
            $receiving->where('efd_id','=',$efd_id);
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
        return BankReconciliation::select(DB::raw('SUM(credit) as credit'))->where('supplier_id',$supplier_id)->where('status','APPROVED')->where('date','>=',$start_date)->where('date','<=',$end_date)->get()->first()['credit'] ?? 0;
    }

    public static function getSupplierAllTimeFinancialCharges($supplier_id,$end_date)
    {
        $start_date = '2010-01-01';
        return FinancialCharge::select(DB::raw('SUM(amount) as credit'))->where('supplier_id',$supplier_id)->where('date','>=',$start_date)->where('date','<=',$end_date)->get()->first()['credit'] ?? 0;
    }
    public static function getSupplierCurrentBalance($supplier_id,$end_date)
    {
        return  self::getSupplierAllTimeDebit($supplier_id,$end_date) - self::getSupplierAllTimeCredit($supplier_id,$end_date) - self::getSupplierAllTimeFinancialCharges($supplier_id,$end_date);
    }

    public static function getSupplierOpeningBalance($supplier_id, $end_date)
    {
        $yesterday = date('Y-m-d', strtotime('-1 day', strtotime($end_date)));
        return (self::getSupplierAllTimeCredit($supplier_id,$yesterday) + self::getSupplierAllTimeFinancialCharges($supplier_id,$yesterday)) - self::getSupplierAllTimeDebit($supplier_id,$yesterday);


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
        return DB::select("SELECT * FROM
              (
    (SELECT  description,efd_id,supplier_id,date,null as credit, debit,null as transfer_in,null as transfer_out,null as amount FROM `bank_reconciliations` WHERE supplier_id = '$supplier_id' AND `date` BETWEEN '$start_date' AND '$end_date' AND debit != 0 AND reference NOT LIKE 'TRANSFER%')
        UNION ALL
    (SELECT  description,efd_id,supplier_id,date,null as credit, null as debit,debit as transfer_in,null as transfer_out,null as amount FROM `bank_reconciliations` WHERE supplier_id = '$supplier_id' AND `date` BETWEEN '$start_date' AND '$end_date' AND debit > 0 AND reference LIKE 'TRANSFER%')
    UNION ALL
    (SELECT  description,null as efd_id,null as supplier_id,date,null as credit, null as debit,null as transfer_in,null as transfer_out,amount FROM `financial_charges` WHERE supplier_id = '$supplier_id' AND `date` BETWEEN '$start_date' AND '$end_date')
    UNION ALL
    (SELECT  description,efd_id, supplier_id, date,credit, null as debit,null as transfer_in,null as transfer_out,null as amount FROM `bank_reconciliations` WHERE supplier_id = '$supplier_id' AND credit != 0 AND `date` BETWEEN '$start_date' AND '$end_date' AND status = 'APPROVED')
                  UNION ALL
    (SELECT  description,efd_id,supplier_id,date,null as credit, null as debit,null as transfer_in,debit as transfer_out,null as amount FROM `bank_reconciliations` WHERE supplier_id = '$supplier_id' AND `date` BETWEEN '$start_date' AND '$end_date' AND debit < 0 AND reference LIKE 'TRANSFER%')
    ) b  order by `date` asc");

    }

    public static function getTotalDepositPerDayPerSystem($start_date, $end_date, $system_id)
    {
        $receiving = BankReconciliation::join('efds', 'efds.id', '=', 'bank_reconciliations.efd_id')
            ->join('systems','systems.id','=','efds.system_id')
            ->select([DB::raw("SUM(debit) as amount")])
            ->where('payment_type','=','SALES')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);
//        $receiving->where('bank_reconciliations.supplier_id','!=',50);

        if($system_id != null){
            $receiving->where('efds.system_id','=',$system_id);
        }
        return $receiving->get()->first()['amount'];
    }

    public static function getTotalDepositPerDayPerSystemOnly($start_date, $end_date, $system_id)
    {
        $start_date = date('Y-m-d', strtotime('-1 day', strtotime($start_date)));
        $end_date = date('Y-m-d', strtotime('-1 day', strtotime($end_date)));
        $receiving = BankReconciliation::join('efds', 'efds.id', '=', 'bank_reconciliations.efd_id')
            ->join('systems','systems.id','=','efds.system_id')
            ->select([DB::raw("SUM(debit) as amount")])
            ->where('payment_type','=','SALES')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);
        $receiving->where('bank_reconciliations.supplier_id','=',50);

        if($system_id != null){
            $receiving->where('efds.system_id','=',$system_id);
        }
        return $receiving->get()->first()['amount'];
    }

    public static function getTotalDepositPerDayPerSystemExcluded($start_date, $end_date, $system_id)
    {
        $receiving = BankReconciliation::join('efds', 'efds.id', '=', 'bank_reconciliations.efd_id')
            ->join('systems','systems.id','=','efds.system_id')
            ->join('suppliers','suppliers.id','=','bank_reconciliations.supplier_id')
            ->select([DB::raw("SUM(bank_reconciliations.debit) as amount")])
            ->where('payment_type','=','SALES')
            ->where('suppliers.is_transferred','YES')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);
//        $receiving->where('bank_reconciliations.supplier_id','!=',50);
        if($system_id != null){
            $receiving->where('efds.system_id','=',$system_id);
        }
        return $receiving->get()->first()['amount'];
    }

    public static function getTotalDepositPerDayPerSystemExcludedOnly($start_date, $end_date, $system_id)
    {
        $start_date = date('Y-m-d', strtotime('-1 day', strtotime($start_date)));
        $end_date = date('Y-m-d', strtotime('-1 day', strtotime($end_date)));
        $receiving = BankReconciliation::join('efds', 'efds.id', '=', 'bank_reconciliations.efd_id')
            ->join('systems','systems.id','=','efds.system_id')
            ->join('suppliers','suppliers.id','=','bank_reconciliations.supplier_id')
            ->select([DB::raw("SUM(bank_reconciliations.debit) as amount")])
            ->where('payment_type','=','SALES')
            ->where('suppliers.is_transferred','YES')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);
        $receiving->where('bank_reconciliations.supplier_id','=',50);

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
    public static function getDebitDepositMainStore($start_date, $end_date, $system_id)
    {
        return \App\Models\BankReconciliation::select(\Illuminate\Support\Facades\DB::raw("SUM(debit) as total_amount"))->join('efds','efds.id','=','bank_reconciliations.efd_id')->join('systems','systems.id','=','efds.system_id')->
            where('bank_reconciliations.date',[$start_date,$end_date])->where('efds.system_id',$system_id)->get()->first()->total_amount ?? 0;
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
    public static function getOnlyTransferedBySupplier($start_date,$end_date,$efd_id = null,$supplier_id = null){
        $bank_reconciliation = DB::table('bank_reconciliations')
            ->join('efds', 'efds.id', '=', 'bank_reconciliations.efd_id')
            ->join('suppliers', 'suppliers.id', '=', 'bank_reconciliations.supplier_id')
            ->select(DB::raw('SUM(bank_reconciliations.debit) as debit_amount,suppliers.name as supplier,bank_reconciliations.date as date,bank_reconciliations.payment_type as payment_type,bank_reconciliations.reference as reference'))
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->where('bank_reconciliations.debit','<',0)
            ->where('reference', 'LIKE', "%TRANSFER%");

        if($efd_id != null){
            $bank_reconciliation->where('efd_id','=',$efd_id);
        }
        if($supplier_id != null){
            $bank_reconciliation->where('supplier_id','=',$supplier_id);
        }
        return $bank_reconciliation->groupBy('from_to_id')->get();
    }
    public static function getTotalDebitOnlyTransferedBySupplier($start_date,$end_date,$supplier_id = null){
        $bank_reconciliation = DB::table('bank_reconciliations')
            ->select(DB::raw('SUM(bank_reconciliations.debit) as debit'))
            ->join('efds', 'efds.id', '=', 'bank_reconciliations.efd_id')
            ->join('suppliers', 'suppliers.id', '=', 'bank_reconciliations.supplier_id')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->where('bank_reconciliations.debit','<',0)
            ->where('bank_reconciliations.from_to_id','!=',0)
            ->where('reference', 'LIKE', "%TRANSFER%");
        $bank_reconciliation->where('bank_reconciliations.supplier_id','=',$supplier_id);


        return $bank_reconciliation->get()->first()->debit;
    }

    public static function getOnlyTransferedTo($start_date,$reference){
        $bank_reconciliation = DB::table('bank_reconciliations')
            ->join('efds', 'efds.id', '=', 'bank_reconciliations.efd_id')
            ->join('suppliers', 'suppliers.id', '=', 'bank_reconciliations.supplier_id')
            ->select('bank_reconciliations.*','efds.name as efd','suppliers.name as supplier')
            ->where('date','>=',$start_date)
//            ->where('bank_reconciliations.debit','<',0)
            ->where('reference', '=', "$reference 1");

        return $bank_reconciliation->get()->first();
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
